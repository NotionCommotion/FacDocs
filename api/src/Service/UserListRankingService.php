<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Entity\ListRanking\RankedListAttribute;
use App\Entity\ListRanking\RankedListInterface;
use App\Entity\ListRanking\UserListRanking;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use App\Service\UserRetreiverService;

/**
 * General
 *   Purpose of this and related classes is to track individual user's preference of various RankedListInterface lists so that for future requests, they will be presented first.
 *   Algorithm which ranks more current requests higher.
 * App\Entity\ListRanking\RankedListAttribute:
 *   Placed on class to indicate that it has ranked properties and on properties to indicate which ones (and maybe future on methods).
 * App\Entity\ListRanking\AbstractRankedList:
 *   All individual lists (i.e. DocumentStage, ProjectStage, etc) extend.
 * App\Entity\ListRanking\UserListRanking:
 *   Used to track on a per-user basis.
 * App\EventSubscriber\HasRankedListSubscriber:
 *   This class is responsible for tracking changes when creating/updating an entity.
 *   Ideally would listen to subproperties (see https://stackoverflow.com/questions/69993950/listeners-and-or-subscribes-which-listen-for-sub-properties)
 * App\DataProvider\HasRankedListDataProvider
 *   This class is responsible for tracking changes when searching for an entity.
 *   Probably should change to use a filter?  How are filters decorated or extended?
 *   Error when using search filter on non-Doctrine identifier (see https://github.com/api-platform/core/issues/3575).
 * TODO
 *   Create a CRON job to update rankings based on how long ago they occured (otherwise they are only updated when applied).
 *   Figure out error regarding search filter on non-Doctrine identifier.
 *   Sort returned based on ranking (https://api-platform.com/docs/core/filters/#order-filter-sorting, https://api-platform.com/docs/core/default-order/).
 */

/**
 * A list of options is presented to the user for them to choose one, and I wish to present the list in order of most common past selections and bias for more recent choices.
 * My bias factory formuala is A = P (1 - r)t (however, I could change if something else is more suitable).
 * For instance, the following shows how many times the user selected Option A along with the biasing factor which ranges from 10 if selected today to around 1 for thirty days ago.
 * To calculate the user's prefernce for Option 1, I could integrate the weight*clicksPerDay over time to get TotalWeight and use that to compare to other options.
 * However, performing this math each time is not fiesable, and instead I wish save just TotalWeight and the date that it was calculated, and will update the value periodically.
 */
final class UserListRankingService
{
    /**
     * A = P (1 - r)^t
     * Where
     * A = Final Ranking
     * P = Initial Ranking
     * r = decay factor
     * t = current time - last update time.
     */
    private const DECAY_FACTOR = 0.06 / 24 / 60 / 60;
    private const ADDED_RANKING_PER_USE = 1;

    public function __construct(private UserRetreiverService $userRetreiverService, private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Called by CRON job.
     * Use recursive native SQL query to update all outside of Doctrine.
     */
    public function updateAllRankings(): bool
    {
        return false;
    }

    public function updatePersistUserRankings(Request $request, object $object): bool
    {
        $method = $request->getMethod();

        if (!\in_array($method, [Request::METHOD_POST, Request::METHOD_PUT, Request::METHOD_PATCH], true)) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($object);

        if (!$this->hasRankedListAttributes($reflectionClass)) {
            return false;
        }

        // Only used with METHOD_PATCH to ensure that properties are included in the request.
        $parameters = Request::METHOD_PATCH === Request::METHOD_PATCH ? json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) : null;

        $rankedLists = [];
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            foreach ($reflectionProperty->getAttributes() as $attrReflect) {
                if (RankedListAttribute::class === $attrReflect->getName()) {
                    if (null === $parameters || isset($parameters[$reflectionProperty->getName()])) {
                        // exit(get_class($attrReflect->newInstance()));
                        $rankedLists[] = $attrReflect->newInstance()->getValue($object, $reflectionProperty);
                    }
                    break;
                }
            }
        }

        return $rankedLists !== [] && $this->updateUserRankings(...$rankedLists);
    }

    public function updateSearchUserRankings(Request $request, ReflectionClass $reflectionClass): bool
    {
        if (!$this->hasRankedListAttributes($reflectionClass)) {
            return false;
        }

        $params = $request->query->all();

        $classes = [];
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            foreach ($reflectionProperty->getAttributes() as $attrReflect) {
                if (RankedListAttribute::class === $attrReflect->getName()) {
                    $name = $reflectionProperty->getName();
                    // $attrReflect->newInstance()
                    if (isset($params[$name]) && ($id = explode('/', ltrim((string) $params[$name], '/'))[1] ?? null)) {
                        $classes[ltrim($reflectionProperty->getType(), '?')][] = $id;
                    }
                    break;
                }
            }
        }
        $objects = [];
        foreach ($classes as $class => $ids) {
            $objects = [...$this->entityManager->getRepository($class)->findBy(['identifier' => $ids]), ...$objects];
        }

        return $objects !== [] && $this->updateUserRankings(...$objects);
    }

    private function updateUserRankings(RankedListInterface ...$rankedList): bool
    {
        $user = $this->userRetreiverService->getUser();
        $exitingUserRankedLists = [];
        foreach ($this->entityManager->getRepository($user::class)->getRankedLists($user, ...$rankedList) as $rs) {
            $exitingUserRankedLists[$rs->getRankedList()->getId()] = $rs;
        }
        foreach ($rankedList as $singleRankedList) {
            // echo(get_class($rs).PHP_EOL);
            // print_r(array_keys($exitingUserRankedLists));
            // exit('id: '.$rs->getId().PHP_EOL);
            $ulr = isset($exitingUserRankedLists[$singleRankedList->getId()])
            ? $exitingUserRankedLists[$singleRankedList->getId()]->setRanking(self::ADDED_RANKING_PER_USE + $exitingUserRankedLists[$singleRankedList->getId()]->getRanking() * (1 - self::DECAY_FACTOR) ** $exitingUserRankedLists[$singleRankedList->getId()]->getHistorySeconds())
            : new UserListRanking($user, $singleRankedList);
            // printf('UserListRanking: %s(%s) User %s(%s) RankedList:%s(%s)'.PHP_EOL, get_class($ulr), $ulr->getId(), get_class($ulr->getUser()), $ulr->getUser()->getId(), get_class($ulr->getRankedList()), $ulr->getRankedList()->getId());
            $this->entityManager->persist($ulr);
        }
        // exit;
        return true;
    }

    private function hasRankedListAttributes(ReflectionClass $reflectionClass): bool
    {
        foreach ($reflectionClass->getAttributes() as $attribute) {
            if (RankedListAttribute::class === $attribute->getName()) {
                // Future: Consider adding arguements to class attribute
                return true;
            }
        }

        return false;
    }
}

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

namespace App\Repository\User;

use App\Entity\Acl\PermissionEnum;
use App\Entity\ListRanking\RankedListInterface;
use App\Entity\ListRanking\UserListRanking;
use App\Entity\User\AbstractUser;
use App\Entity\User\UserInterface;
use App\Repository\AbstractRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Uid\Ulid;
use App\Entity\User\BasicUserInterface;

/**
 * @method UserInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserInterface[]    findAll()
 * @method UserInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends AbstractRepository implements PasswordUpgraderInterface, UserRepositoryInterface
{
    public function __construct(ManagerRegistry $managerRegistry, ?string $class = null)
    {
        parent::__construct($managerRegistry, $class ?? AbstractUser::class);
    }

    public function getUser(Ulid $organizationId, string $email): ?UserInterface
    {
        //echo($this->showDoctrineQuery($this->createQueryBuilder('u')->join('u.organization', 'o')->andWhere('u.email = :email')->andWhere('o.id = :organizationId')->setParameter('email', $email)->setParameter('organizationId', $organizationId, 'ulid')->getQuery()).PHP_EOL);
        return $this->createQueryBuilder('u')
        ->join('u.organization', 'o')
        ->andWhere('u.email = :email')
        ->andWhere('o.id = :organizationId')
        ->setParameter('email', $email)
        ->setParameter('organizationId', $organizationId, 'ulid')
        ->getQuery()
        ->getOneOrNullResult();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(UserInterface $user, bool $flush = true): void
    {
        $this->_em->persist($user);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(UserInterface $user, bool $flush = true): void
    {
        $this->_em->remove($user);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function getRankedLists(UserInterface $user, RankedListInterface ...$rankedList): array
    {
        $entityManager = $this->getEntityManager();
        // exit($em->getRepository($user::class)->debug($exiting));
        $dql = sprintf('SELECT ulr FROM %s ulr WHERE ulr.user=?0 AND ulr.rankedList IN (%s)', UserListRanking::class, $this->getWhereInQuery(\count($rankedList), 1));
        $query = $entityManager->createQuery($dql);
        $query->setParameters(array_merge([$user], $rankedList));

        return $query->getResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $passwordAuthenticatedUser, string $newHashedPassword): void
    {
        if (!$passwordAuthenticatedUser instanceof UserInterface) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $passwordAuthenticatedUser::class));
        }

        $passwordAuthenticatedUser->setPassword($newHashedPassword);
        $this->_em->persist($passwordAuthenticatedUser);
        $this->_em->flush();
    }
}

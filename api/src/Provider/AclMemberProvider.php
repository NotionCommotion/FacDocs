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

namespace App\Provider;

use App\Entity\Acl\ResourceAclMember;
use App\Entity\User\UserInterface;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;

class AclMemberProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }
    
    public function provide(Operation $operation, array $uriVariables = [], array $context = []):object|array|null
    {
        if($operation->getMethod()!=='POST') {
            return $this->entityManager->createQueryBuilder()
            ->select('m')
            ->from($operation->getClass(), 'm')
            ->innerJoin('m.user', 'u')
            ->innerJoin('m.acl', 'a')
            ->andWhere('a.id = :aclId')
            ->andWhere('u.id = :userId')
            ->setParameter('aclId', $uriVariables['id'], 'ulid')
            ->setParameter('userId', $uriVariables['userId'], 'ulid')
            ->getQuery()
            ->getOneOrNullResult();
        }
        $errors = [];
        if(!$aclResource = $this->entityManager->getRepository($operation->getUriVariables()['id']->getFromClass())->find($uriVariables['id'])) {
            $errors[] = 'Resource does not exist';
        }
        if(!$user = $this->entityManager->getRepository(UserInterface::class)->find($uriVariables['userId'])) {
            $errors[] = 'User does not exist';
        }
        if($errors) {
            throw new \Exception(implode(', ', $errors));
        }
        return new ($operation->getClass())($aclResource, $user);
    }
}

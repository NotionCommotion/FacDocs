<?php

namespace App\Doctrine\IdGenerator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Ulid;

// Replaces Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator since it can't use present IDs in costructor.
final class UlidGenerator extends AbstractIdGenerator
{
    private ?UlidFactory $factory;

    public function __construct(UlidFactory $factory = null)
    {
        $this->factory = $factory;
    }

    /**
     * doctrine/orm < 2.11 BC layer.
     */
    public function generate(EntityManager $em, $entity): Ulid
    {
        return $entity->getId()??$this->generateId($em, $entity);
    }

    public function generateId(EntityManagerInterface $em, $entity): Ulid
    {
        if ($id = $entity->getId()) {
            return $id;
        }
        if ($this->factory) {
            return $this->factory->create();
        }

        return new Ulid();
    }
}

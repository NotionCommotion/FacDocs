<?php
declare(strict_types=1);

namespace App\DataFixtures\Purger;

use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory;
use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\ORM\EntityManagerInterface;

use Doctrine\Bundle\FixturesBundle\Purger\ORMPurgerFactory;
use Symfony\Component\Filesystem\Filesystem;

class DoctrinePurgerFactory implements PurgerFactory
{
    public function __construct(private ORMPurgerFactory $purgeFactory, private Filesystem $filesystem, private string $mediaUploadDirectory, private string $archiveStoragePath)
    {
    }
    
    public function createForEntityManager(?string $emName, EntityManagerInterface $em, array $excluded = [], bool $purgeWithTruncate = false) : PurgerInterface
    {                             
        return new DoctrinePurger($emName, $em, $excluded, $purgeWithTruncate, $this->purgeFactory, $this->filesystem, $this->mediaUploadDirectory, $this->archiveStoragePath);
    }
}
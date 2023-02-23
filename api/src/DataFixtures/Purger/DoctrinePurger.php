<?php
declare(strict_types=1);

namespace App\DataFixtures\Purger;

use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurgerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Bundle\FixturesBundle\Purger\ORMPurgerFactory;
use Symfony\Component\Filesystem\Filesystem;

class DoctrinePurger implements ORMPurgerInterface
{
    public function __construct(private ?string $emName, private EntityManagerInterface $entityManager, private array $excluded, private bool $purgeWithTruncate, private ORMPurgerFactory $purgeFactory, private Filesystem $filesystem, private string $mediaUploadDirectory, private string $archiveStoragePath)
    {
        
    }
    public function setEntityManager(EntityManagerInterface $entityManager):void
    {
        $this->entityManager = $entityManager;
    }

    public function purge() : void
    {
        $this->purgeWithTruncate = true;
        $purger = $this->purgeFactory->createForEntityManager($this->emName, $this->entityManager, $this->excluded, $this->purgeWithTruncate);
        /*
        print_r(get_class_methods($this->purgeFactory));
        print_r(get_class_methods($this->entityManager));
        print_r(get_class_methods($purger));
        */
        $purger->purge();
        
        $this->filesystem->remove(array_merge($this->getFiles($this->mediaUploadDirectory), $this->getFiles($this->archiveStoragePath)));
        
        /*
        exit('purge');
        // Delete Projects before Assets.
        //$this->entityManager->createQuery(sprintf('DELETE FROM %s', Project::class))->execute();
        $purger = $this->inner->createForEntityManager('bla', $this->entityManager);
        echo(get_class($this->inner).PHP_EOL);
        print_r(get_class_methods($this->inner));
        echo(get_class($purger).PHP_EOL);
        print_r(get_class_methods($purger));
        //$this->entityManager->purge();
        */
    }

    private function getFiles(string $path):array
    {
        return array_map(function(string $name)use($path) {return sprintf('%s/%s', $path, $name);}, array_diff(is_dir($path)?scandir($path):[], ['.','..']));
    }
}
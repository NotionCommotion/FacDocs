<?php

declare(strict_types=1);

namespace App\Service;
use Symfony\Contracts\Cache\CacheInterface;
use Doctrine\ORM\EntityManagerInterface;

/*
Currently only used with Gedmo\Mapping\Annotation\Blameable, and at this time identifies the following as having the attribute:
App\Entity\Project\Project, App\Entity\Specification\CustomSpecification, App\Entity\Asset\Asset, App\Entity\HelpDesk\Topic, App\Entity\HelpDesk\Post, App\Entity\Document\SupportedMediaType, App\Entity\Document\Document, App\Entity\Document\Media, App\Entity\DocumentGroup\DocumentGroup, App\Entity\User\SystemUser, App\Entity\User\AbstractUser, App\Entity\User\TenantUser, App\Entity\User\VendorUser, App\Entity\Archive\Template, App\Entity\Archive\Archive, App\Entity\Error\Error
*/

final class UsesAttributeService
{
    public function __construct(private EntityManagerInterface $entityManager, private CacheInterface $cache)
    {
    }

    public function usesAttribute(string $class, string $attribute): bool
    {
        $array = $this->cache->get('attribute_'.str_replace('\\', '_', $attribute), function() use($attribute) {
            return array_flip($this->getClassesThatUseAttribute($attribute));
        });
        return isset($array[$class]);
    }

    private function getClassesThatUseAttribute(string $attribute):array
    {
        $classes=[];
        foreach($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            foreach($metadata->getReflectionProperties() as $p) {
                foreach($p->getAttributes() as $a) {
                    if($a->getName()=== $attribute) {
                        $classes[] = $metadata->getName();
                        continue;
                    }
                }
            }
        }
        return $classes;
    }
}

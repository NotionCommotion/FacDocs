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

namespace App\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Organization\Tenant;
use App\Entity\Specification\CsiSpecification;
use App\Entity\Acl\AclPermission;
use App\Entity\Acl\AclPermissionEnum;
use App\Entity\Archive\Template;
use App\Entity\Asset\Asset;
use App\Entity\Document\MediaType;
use App\Entity\Document\SupportedMediaType;

final class PersistTenantListener
{
    public function __construct(
        private array $resourceAclPermission,
        private array $documentAclPermission,
        private array $defaultTenantMediaTypes,
        private array $defaultTenantAssets
    ) {
    }

    public function prePersist(Tenant $tenant, LifecycleEventArgs $event): void
    {
        // Ensure permission values are set and provide some sample records.
        $entityManager = $event->getObjectManager();

        $resourceAclPermissionPrototype = $tenant->getResourceAclPermissionSetPrototype();
        $documentAclPermissionPrototype = $tenant->getDocumentAclPermissionSetPrototype();

        if (!$tenant->getPrimarySpecification()) {
            $tenant->setPrimarySpecification($entityManager->getRepository(CsiSpecification::class)->findOneBy(['division'=>'00', 'section'=>'00', 'scope'=>'00']));
        }

        $this
        ->setResourceAclPermission($resourceAclPermissionPrototype->getTenantUserPermission(),      $this->resourceAclPermission['tenant']['user'])
        ->setResourceAclPermission($resourceAclPermissionPrototype->getTenantMemberPermission(),    $this->resourceAclPermission['tenant']['member'])
        ->setResourceAclPermission($resourceAclPermissionPrototype->getVendorUserPermission(),      $this->resourceAclPermission['vendor']['user'])
        ->setResourceAclPermission($resourceAclPermissionPrototype->getVendorMemberPermission(),    $this->resourceAclPermission['vendor']['member'])

        ->setDocumentAclPermission($documentAclPermissionPrototype->getTenantUserPermission(),      $this->documentAclPermission['tenant']['user'])
        ->setDocumentAclPermission($documentAclPermissionPrototype->getTenantMemberPermission(),    $this->documentAclPermission['tenant']['member'])
        ->setDocumentAclPermission($documentAclPermissionPrototype->getVendorUserPermission(),      $this->documentAclPermission['vendor']['user'])
        ->setDocumentAclPermission($documentAclPermissionPrototype->getVendorMemberPermission(),    $this->documentAclPermission['vendor']['member'])

        ->addSampleMediaTypes($tenant, $entityManager)
        ->addSampleTemplate($tenant, $entityManager)
        ->addSampleAssets($tenant, $entityManager)
        ;
    }

    private function setResourceAclPermission(AclPermission $permission, array $defaultValues):self
    {
        $permission
        ->setRead(AclPermissionEnum::fromName($defaultValues['read']))
        ->setUpdate(AclPermissionEnum::fromName($defaultValues['update']));
        return $this;
    }
    private function setDocumentAclPermission(AclPermission $permission, array $defaultValues):self
    {
        $permission
        ->setRead(AclPermissionEnum::fromName($defaultValues['read']))
        ->setUpdate(AclPermissionEnum::fromName($defaultValues['update']))
        ->setCreate(AclPermissionEnum::fromName($defaultValues['create']))
        ->setDelete(AclPermissionEnum::fromName($defaultValues['delete']));
        return $this;
    }

    private function addSampleMediaTypes(Tenant $tenant, EntityManagerInterface $entityManager): self
    {
        foreach ($this->getDefaultMediaTypes($entityManager) as $mediaType) {
            $supportedMediaType = new SupportedMediaType();
            $supportedMediaType->setMediaType($mediaType);
            $tenant->addSupportedMediaType($supportedMediaType);
        }
        return $this;
    }

    private function addSampleTemplate(Tenant $tenant, EntityManagerInterface $entityManager): self
    {
        $template = new Template();
        $template
        ->setName('Sample Template')
        ->setDescription('Sample Description')
        ->setHtml($this->getDefaultTemplateHtml());
        $tenant->addTemplate($template);
        return $this;
    }

    private function addSampleAssets(Tenant $tenant, EntityManagerInterface $entityManager): self
    {
        $rootAsset = $tenant->getRootAsset();
        foreach ($this->defaultTenantAssets as $defaultTenantAsset) {
            $asset = new Asset();
            $asset
            ->setName($defaultTenantAsset)
            ->setDescription(sprintf('Description for your %s.', $defaultTenantAsset))
            ->addParent($rootAsset);
            $tenant->addAsset($asset);
        }
        return $this;
    }

    private function getDefaultTemplateHtml(): string
    {
        return <<<EOL
<h1>{{ tenant.name }}</h1>
<h3>Documents for {{ project.name }} (Project ID: {{ project.id }})</h3>
<h5>Start Date: {{ date.start }}</h5>
EOL;
    }

    private function getDefaultMediaTypes(EntityManagerInterface $entityManager): array
    {
        return $entityManager->getRepository(MediaType::class)->findBy(['id'=>$this->defaultTenantMediaTypes]);
    }
}

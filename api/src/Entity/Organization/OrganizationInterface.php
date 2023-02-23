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

namespace App\Entity\Organization;

use App\Entity\Acl\HasDocumentAclInterface;
use App\Entity\Acl\AclPermission;
use App\Entity\Location\Location;
use App\Entity\Specification\AbstractSpecification;
use App\Entity\MultiTenenacy\HasUlidInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Ulid;

interface OrganizationInterface extends HasUlidInterface
{
    public function getId(): ?Ulid;

    public function getName(): ?string;

    public function setName(string $name): self;

    public function getLocation(): ?Location;

    // Per resource type (i.e. project, asset)
    //public function getAclUserPermission(HasDocumentAclInterface $resource): AclPermission;

    //public function getAclMemberPermission(HasDocumentAclInterface $resource): AclPermission;

    // public function getResourceMembers(HasDocumentAclInterface $resource): Collection;
    public function getPrimarySpecification(): ?AbstractSpecification;
}

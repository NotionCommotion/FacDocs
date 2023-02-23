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

use App\Repository\Organization\SystemOrganizationRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User\UserInterface;
use App\Entity\User\SystemUser;
use Symfony\Component\Uid\NilUlid;

// Not a ApiResource
#[ORM\Entity(repositoryClass: SystemOrganizationRepository::class)]
class SystemOrganization extends AbstractOrganization implements OrganizationInterface, TenantInterface, VendorInterface
{

    public function __construct()
    {
        parent::__construct();
		$this->id = new NilUlid;
    }

    public function addUser(UserInterface $user): self
    {
        if (!$user instanceof SystemUser) {
            throw new \Exception(sprintf('User must be a SystemUser to belong to a SystemOrganization. %s given.', get_class($user)));
        }
        return parent::addUser($user);
    }

    public function getRootUser(): SystemUser
    {
        $rootId = new NilUlid;
        return $this->getUsers()->filter(function(SystemUser $element) use($rootId) {
            return $element->isRoot();
        })->first();
    }

    public function isRoot(): ?bool
    {
        return $this->id?$this->id->equals(new NilUlid):null;
    }

    public function getType(): OrganizationType
	{
        return OrganizationType::System;
	}
}

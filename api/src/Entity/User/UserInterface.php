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

namespace App\Entity\User;

use App\Model\Config\ConfigInterface;
use App\Entity\HelpDesk\Topic;
use App\Entity\ListRanking\UserListRanking;
use App\Entity\Organization\OrganizationInterface;
use App\Entity\Specification\AbstractSpecification;
use App\Entity\Acl\HasRolesInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

interface UserInterface extends BasicUserInterface, HasRolesInterface, PasswordAuthenticatedUserInterface, SymfonyUserInterface, HashUserPasswordInterface
{
    public function getConfig(): ConfigInterface;

    public function getPlainPassword(): ?string;

    public function setPlainPassword(string $plainPassword): self;

    public function getEmail(): ?string;

    public function setEmail(string $email): self;

    public function getUsername(): ?string;

    public function setUsername(string $username): self;

    public function getUserIdentifier(): string;

    public function getRoles(): array;

    public function setRoles(array $roles): self;

    public function getPassword(): string;

    public function setPassword(string $password): self;

    public function getSalt(): void;

    public function eraseCredentials(): void;

    public function getFirstName(): ?string;

    public function setFirstName(?string $firstName): self;

    public function getLastName(): ?string;

    public function setLastName(?string $lastName): self;

    public function getFullname(): string;

    public function getOrganization(): OrganizationInterface;

    public function setOrganization(OrganizationInterface $organization): self;

    public function getPrimarySpecification(): ?AbstractSpecification;

    public function setPrimarySpecification(?AbstractSpecification $primarySpecification): self;

    public function getUserListRankings(): Collection;

    public function addUserListRanking(UserListRanking $userListRanking): self;

    public function removeUserListRanking(UserListRanking $userListRanking): self;

    public function getHelpDeskTopics(): Collection;

    public function addHelpDeskTopic(Topic $helpDeskTopic): self;

    public function removeHelpDeskTopic(Topic $helpDeskTopic): self;

    public function isSystemUser(): bool;

    public function isTenantUser(): bool;

    public function isVendorUser(): bool;
}

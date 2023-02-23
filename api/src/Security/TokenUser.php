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

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Component\Uid\Ulid;
use App\Entity\Organization\OrganizationType;
use App\Entity\User\UserInterface;
use App\Entity\User\BasicUserInterface;

/*
This databaseless user is stored in Symfony\Component\Security\Core\Security instead of a doctrine user.
TODO - true to limit the times UserRetreiverService is needed to get the Doctrine user to improve performance.
*/
final class TokenUser implements JWTUserInterface, BasicUserInterface
{
    public function __construct(private Ulid $id, private OrganizationType $type, private array $roles, private Ulid $organizationId, private ?Ulid $tenantId)
    {
    }

    public static function createFromPayload($id, array $payload):self
    {
        return new self(Ulid::fromString($id), OrganizationType::fromName($payload['type']), $payload['roles'], Ulid::fromString($payload['organizationId']), $payload['tenantId']?Ulid::fromString($payload['tenantId']):null);
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getOrganizationId(): Ulid
    {
        return $this->organizationId;
    }

    public function getTenantId(): ?Ulid
    {
        return $this->tenantId;
    }

    /*
    // A system user impersonates a tenant or vendor user.  Currently only sed with tenant users?
    public function impersonate(\App\Entity\Organization\OrganizationInterface $organization): self
    {
        if(!$this->isSystemUser()) {
            throw new \Exception(sprintf('Only system users may impersonate and not a "%s user".', $this->type));
        }
        if($organization->getType()->isSystem()) {
            throw new \Exception('System users may not be impersonated.');
        }
        $this->organizationId = $organization->getId();
        $this->tenantId = $organization->getType()->isTenant()?$organization->getId():$organization->getTenant()->getId();
        return $this;
    }
    */

    public function getType(): OrganizationType
    {
        return $this->type;
    }

    public function getRoles(): array
    {
        return $this->roles?$this->roles:['ROLE_USER'];
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
        /*
        debug_print_backtrace();
        $x = new \App\Service\UserRetreiverService\UserRetreiverService;
        exit;
        $this->userRetreiverService->getUser()->eraseCredentials();
        */
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getClass(): string
    {
        return $this->type->getUserClass();
    }

    public function isSystemUser(): bool
    {
        return $this->type->isSystem();
    }

    public function isTenantUser(): bool
    {
        return $this->type->isTenant();
    }

    public function isVendorUser(): bool
    {
        return $this->type->isVendor();
    }

    public function isSame(BasicUserInterface $user): bool
    {
        return $this->getId()->equals($user->getId());
    }
    public function isCoworker(BasicUserInterface $user): bool
    {
        return $this->getOrganizationId()->equals($user->getOrganizationId()) && !$this->isSame($user);
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'type'=>$this->type->name, 'roles'=>$this->roles, 'organizationId'=>$this->organizationId->toBase32(), 'organizationId'=>$this->organizationId?$this->organizationId->toBase32():'NULL', 'class'=>get_class($this)];
    }
}
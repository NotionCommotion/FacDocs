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

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Model\Config\SystemConfig;
use App\Entity\Organization\OrganizationInterface;
use App\Entity\Organization\SystemOrganization;
use App\Entity\Specification\AbstractSpecification;
use App\Repository\User\SystemUserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\NilUlid;
use App\Entity\Organization\TenantInterface;
use App\Entity\Organization\Tenant;

#[ApiResource(operations: [new Get(), new GetCollection()], normalizationContext: ['groups' => ['user:read', 'system_user:read']])]
#[ORM\Entity(repositoryClass: SystemUserRepository::class)]
class SystemUser extends AbstractUser implements SystemUserInterface
{
    final public const ROLES = ['ROLE_SYSTEM_USER', 'ROLE_SYSTEM_ADMIN', 'ROLE_SYSTEM_SUPER'];

    private ?OrganizationInterface $imposteredOrganization=null;

    public function setOrganization(OrganizationInterface $organization): self
    {
        if (!$organization instanceof SystemOrganization) {
            throw new \Exception(sprintf('SystemUsers may only belong to a SystemOrganization and not a %s.', get_class($organization)));
        }

        return parent::setOrganization($organization);
    }

    // Override parent to imposter a tenant or vendor user.
    public function getOrganization(): OrganizationInterface
    {
        return $this->imposteredOrganization??$this->organization;
    }

    public function getTenant(): ?TenantInterface
    {
        // Future.  Change from using method_exist to using an interface.
        return $this->imposteredOrganization?(method_exists($this->imposteredOrganization, 'getTenant')?$this->imposteredOrganization->getTenant():$this->imposteredOrganization):null;
    }
    public function setTenant(TenantInterface $tenant): self
    {
        // Just used when generating fixture data
        $this->imposteredOrganization = $tenant;

        return $this;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return parent::debug($follow, $verbose, $hide);
    }

    public function impersonate(?OrganizationInterface $organization): self
    {
        $this->imposteredOrganization = $organization;
        return $this;
    }

    public function getRealOrganization(): OrganizationInterface
    {
        return parent::getOrganization();
    }

    public function getLogon(?string $password=null, $asString=false): array|string
    {
        $logon = ['id'=>($this->imposteredOrganization??$this->getRealOrganization())->getId()->toRfc4122(), 'email'=>$this->email, 'password'=>$password??$this->plainPassword??'Unknown'];
        return $asString?json_encode($logon):$logon;
    }

    public function getConfig(): SystemConfig
    {
        return new SystemConfig($this);
    }

    public function isRoot(): ?bool
    {
        return $this->id?$this->id->equals(new NilUlid):null;
    }
}

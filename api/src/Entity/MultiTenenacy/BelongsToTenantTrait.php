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

namespace App\Entity\MultiTenenacy;

use Exception;
use App\Entity\Organization\TenantInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
//use Gedmo\Mapping\Annotation as Gedmo;

trait BelongsToTenantTrait
{
    #[ORM\ManyToOne(targetEntity: TenantInterface::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Ignore]
    protected ?TenantInterface $tenant=null;

    public function getTenant(): ?TenantInterface
    {
        return $this->tenant;
    }

    // Tenant collection will not be updated to include new object.  Fix???
    public function setTenant(TenantInterface $tenant): self
    {
        if ($this->tenant && $this->tenant!==$tenant) {
            throw new Exception('Tenant cannot be changed');
        }
        $this->tenant = $tenant;

        return $this;
    }

    /**
    #[Gedmo\Blameable(on: 'create')]
    public function setTenantByUser($user): self
    {
        exit(get_class($user));
        return $this;
    }
    **/
}

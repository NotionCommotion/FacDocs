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

namespace App\Entity\Version;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User\TenantUserInterface;

trait VersionCollectionTrait
{
    #[ORM\OneToMany(targetEntity: TenantUserInterface::class, mappedBy: 'tenant')]
    private Collection $versions;
    // private $versions;    Located in concrete class since cannot override annotations

    /**
     * @return Collection|SolutionVersion[]
     */
    public function getVersions(): Collection
    {
        return $this->versions;
    }

    public function addVersion(SolutionVersion $solutionVersion): self
    {
        if (!$this->versions->contains($solutionVersion)) {
            $this->versions[] = $solutionVersion;
            $solutionVersion->setParent($this);
        }

        return $this;
    }

    public function removeVersion(SolutionVersion $solutionVersion): self
    {
        if (!$this->versions->removeElement($solutionVersion)) {
            return $this;
        }

        if ($solutionVersion->getParent() !== $this) {
            return $this;
        }

        $solutionVersion->setParent(null);

        return $this;
    }
}

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
/*
Need to figure out what to use for identifiers.
CSI specifications should be something like "230900.2.31".
Custom specifications should be something like "230900.2.31-4" or maybe just "4".
Until I figure this out, make public ID for custom specifications start after all CSI specifications?
*/

namespace App\Entity\Specification;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\Specification\AbstractSpecificationRepository;
use App\Entity\MultiTenenacy\HasUlidInterface;
use App\Entity\MultiTenenacy\HasUlidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [new Get, new GetCollection,],
    normalizationContext: [
        'groups' => ['specification:read', 'identifier:read'],
        'enable_max_depth' => true
    ],
    paginationItemsPerPage: 20,
    shortName: 'Specifications'
)]
#[ORM\Entity(repositoryClass: AbstractSpecificationRepository::class)]
#[ORM\Table(name: 'specification')]
#[ORM\InheritanceType(value: 'JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: 'string')]
// Future: Consider adding "root specification" which all others are under.
#[ORM\DiscriminatorMap(value: ['csi_specification' => CsiSpecification::class, 'custom_specification' => CustomSpecification::class])]
abstract class AbstractSpecification implements HasUlidInterface
{
    use HasUlidTrait;

    // $parent defined in extended class and $children in this call will be overriden by CsiSpecification.
    #[ORM\OneToMany(targetEntity: CustomSpecification::class, mappedBy: 'parent')]
    #[Groups(['specification:read'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    protected Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child) && $child->getParent() === $this) {
            $child->setParent(null);
        }

        return $this;
    }
}

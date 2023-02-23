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

namespace App\Entity\Specification;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\Specification\CsiSpecificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Nette\Utils\Strings;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [new Get, new GetCollection,],
    normalizationContext: [
        'groups' => ['specification:read','identifier:read'],
        'enable_max_depth' => true
    ],
    paginationItemsPerPage: 20
)]

#[ORM\Entity(repositoryClass: CsiSpecificationRepository::class, readOnly: true)]
#[ORM\Table]
#[ORM\Index(name: 'idx_division', columns: ['division'])]
#[ORM\Index(name: 'idx_section', columns: ['section'])]
#[ORM\Index(name: 'idx_scope', columns: ['scope'])]
#[ORM\Index(name: 'idx_scope', columns: ['subscope'])]
#[ORM\UniqueConstraint(name: 'idx_unique_spec', columns: ['division', 'section', 'scope', 'subscope'])]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'spec' => 'partial'])]
class CsiSpecification extends AbstractSpecification implements SpecificationInterface
{
    #[ORM\Column(type: 'string', length: 2)]
    private ?string $division = null;
    #[ORM\Column(type: 'string', length: 2)]
    private ?string $section = null;
    #[ORM\Column(type: 'string', length: 2)]
    private ?string $scope = null;
    #[ORM\Column(type: 'string', length: 2, nullable: true)]
    private ?string $subscope = null;
    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['specification:read'])]
    private ?string $spec = null;

    // Methods defined in parent.
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    //#[ORM\JoinColumn(nullable: true)]
    #[Groups(['specification:read'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    protected self $parent;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    #[Groups(['specification:read'])]
    #[ApiProperty(readableLink: false, writableLink: false)]
    protected Collection $children;

    //No unique since CSI duplicates titles (i.e. Printers)
    #[ORM\Column(type: 'string', length: 180)]
    #[Groups(['specification:read'])]
    private string $title;

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'spec'=>$this->spec, 'class'=>get_class($this)];
    }

    public function getSpec(): ?string
    {
        return $this->spec;
    }

    public function setSpec(string $spec): self
    {
        $this->spec = $spec;

        return $this;
    }

    public function getParent(): self
    {
        return $this->parent;
    }

    public function setParent(self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getDivision(): ?string
    {
        return $this->division;
    }

    public function setDivision(string $division): self
    {
        $this->division = $division;

        return $this;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(string $section): self
    {
        $this->section = $section;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getSubscope(): ?string
    {
        return $this->subscope;
    }

    public function setSubscope(?string $subscope): self
    {
        $this->subscope = $subscope;

        return $this;
    }

    // create(), setFormatedSpec(), getFormatedSpec() and other methods are used to initally populate database
    public static function create(string $title, string $spec): self
    {
        return (new self())->setTitle($title)->setSpecFromString($spec);
    }

    /**
     * @return array<string, mixed>|mixed[]
     */
    public function specToArray(bool $includeKeys = true): array
    {
        return $includeKeys ? ['division' => $this->division, 'section' => $this->section, 'scope' => $this->scope, 'subscope' => $this->subscope] : [$this->division, $this->section, $this->scope, $this->subscope];
    }

    public function setSpecFromString(string $spec): self
    {
        $spec = explode('.', str_replace(' ', '', $spec));
        $this->subscope = $spec[1] ?? null;
        $spec = str_pad($spec[0], 6, '0', \STR_PAD_LEFT);
        $this->division = Strings::substring($spec, 0, 2);
        $this->section = Strings::substring($spec, 2, 2);
        $this->scope = Strings::substring($spec, 4, 2);
        $this->spec = $this->getFormatedSpec();

        return $this;
    }

    public function getFormatedSpec(): string
    {
        return sprintf('%s %s %s%s', $this->division, $this->section, $this->scope, $this->subscope ? '.'.$this->subscope : '');
    }

    // returns one of: division, section, scope, or subscope (23 09 23.21 division section scope.subscope)
    public function getType(): SpecificationType
    {
        if ($this->isSubscope()) {
            return SpecificationType::SubScope;
        }
        if ('00' === $this->scope) {
            return '00' === $this->section ? SpecificationType::Division : SpecificationType::Section;
        }

        return SpecificationType::Scope;
    }

    // returns index relative to its parent.  i.e. 230000 => 23, 230900 => 09, 230923 => 23, 230923.19 => 19
    public function getIndex(): string
    {
        return $this->getType()->getIndex($this);
    }

    public function isChild(self $parent, bool $includeAncestors = false): bool
    {
        return $this->_isChild($this, $parent, $includeAncestors);
    }

    public function isParent(self $child, bool $includeAncestors = false): bool
    {
        return $this->_isChild($child, $this, $includeAncestors);
    }

    private function _isChild(self $child, self $parent, bool $includeAncestors): bool
    {
        return match ($parent->getType()->name) {
            'Root' => false,
            'Division' => $child->getDivision() === $parent->getDivision() && ($child->isSection() || $includeAncestors && !$child->isDivision()),
            'Section' => $child->getDivision() === $parent->getDivision() && $child->getSection() === $parent->getSection() && ($child->isScope() || $includeAncestors && !$child->isDivision() && !$child->isSection()),
            'Scope' => $child->getDivision() === $parent->getDivision() && $child->getSection() === $parent->getSection() && $child->getScope() === $parent->getScope() && $child->isSubscope(),
            'SubScope' => false,
            default => throw new Exception('Invalid type: '.$parent->getType()->name),
        };
    }

    public function isDivision(): bool
    {
        return '00' === $this->section && '00' === $this->scope && null === $this->subscope;
    }

    public function isSection(): bool
    {
        return '00' !== $this->section && '00' === $this->scope && null === $this->subscope;
    }

    public function isScope(): bool
    {
        return '00' !== $this->section && '00' !== $this->scope && null === $this->subscope;
    }

    public function isSubscope(): bool
    {
        return null !== $this->subscope;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}

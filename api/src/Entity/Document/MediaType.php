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

namespace App\Entity\Document;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\Document\MediaTypeRepository;
use App\Provider\MediaTypeProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Ignore;
//use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    openapiContext: ['description' => 'All dots in subtype are replaced with a hyphen (-)'],
    operations: [
        /*
        // Provider works but for unknown reason cannot create an identifier.
        new Get(
            uriTemplate: '/media_types/{type}/{subtype}',
            uriVariables: [
                //'type'      => new Link(fromClass: self::class, identifiers: ['type', 'subtype']),
                'type'      => new Link(fromClass: self::class, fromProperty: 'type'),
                'subtype'   => new Link(fromClass: self::class, fromProperty: 'subtype')
            ],
            provider: MediaTypeProvider::class,
        ),
        */
        new Get,
        new GetCollection()
    ]
)]

#[ORM\Entity(repositoryClass: MediaTypeRepository::class, readOnly: true)]
#[ORM\Index(name: 'idx_media_type_type', columns: ['type'])]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'type' => 'exact'])]
class MediaType implements \Stringable
{
    // I tried to use both the primary type(i.e. image) and the subtype (i.e. jpeg) as a composite PK but couldn't get it working.

    // FK constraint added indendent of Doctrine.  Maybe index is redundent?
    #[ORM\Column(type: 'string', length: 16)]
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'enum' => ['application', 'image', 'video', 'text', 'audio', 'font', 'model'],
            'example' => 'application'
        ]
    )]
    private ?string $type = null;
    #[ORM\Column(type: 'string', length: 255)]
    #[ApiProperty(
        identifier: true,
        openapiContext: [
            'type' => 'string',
            'example' => 'pdf',
            'description' => 'All dots are replaced with a hyphen (-)'
        ]
    )]
    private $subtype;
    
    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;
    
    #[ORM\Column(type: 'string', length: 255)]
    //#[Ignore]
    private ?string $reference = null;
    
    #[ORM\Column(type: 'json')]
    private array $supportedExtensions = [];
    
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $compressible;

    public function __construct(
        #[ORM\Id]
        #[ORM\GeneratedValue(strategy: 'NONE')]
        #[ORM\Column(type: 'string', length: 255)]
        #[ApiProperty(identifier: false)]
        //#[Ignore]
        private string $id
    ) {
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return array_merge(get_object_vars($this), ['class'=>get_class($this)]);
    }

    public function getId(): ?string
    {
        // return $this->identifier;
        // API-Platform returns "Unable to generate an IRI for \"App\\Entity\\Media\\MediaType\"." error if identifier has a foward slash
        // See https://stackoverflow.com/questions/70550281/rest-api-calls-with-forward-slash-in-identifier?noredirect=1#comment124713308_70550281
        // No longer needed after adding 'requirements' => ['identifier' => '.+'] (but item get requests result in not-found error)
        // return str_replace('/', ':', $this->identifier);
        // return urlencode($this->identifier);
        return $this->id;
        // str_replace('/', ':', $this->identifier);
    }

    public function supportsExtension(string $extension): bool
    {
        return \in_array(strtolower($extension), $this->supportedExtensions, true);
    }

    public function getDefaultExtension(): ?string
    {
        $parts = explode('/', $this->id);
        $ext = end($parts);

        return $this->supportsExtension($ext) ? $ext : $this->supportedExtensions[0] ?? null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    public function setSubtype(string $subtype): self
    {
        $this->subtype = $subtype;

        return $this;
    }

    #[Ignore]
    public function getTemplate(): ?string
    {
        $parts = explode('/', $this->getId());

        return 2 === \count($parts) ? $parts[1] : null;
    }

    #[Ignore]
    public function getMediaName(): string
    {
        $parts = explode('/', $this->getId());

        return $parts[\count($parts) - 1];
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getSupportedExtensions(): ?array
    {
        return $this->supportedExtensions;
    }

    public function setSupportedExtensions(array $supportedExtensions): self
    {
        $this->supportedExtensions = $supportedExtensions;

        return $this;
    }

    public function getCompressible(): ?bool
    {
        return $this->compressible;
    }

    public function setCompressible(?bool $compressible): self
    {
        $this->compressible = $compressible;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}

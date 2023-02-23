<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
Change to use composition!!!!
Ranked lists
Uses integer ID
/departments/1    Department    {"id": "1", "name": "Facilities"}
/job_titles/1    JobTitle    {"id": "1", "name": "Architect"}

Uses string ID
/media_types/type=application;subtype=1d-interleaved-parityfec    MediaType    {"id": "application/1d-interleaved-parityfec", "name": "application/1d-interleaved-parityfec"}

Uses string ID
/document_stages/active        DocumentStage    {"id": "active", "name": "Active"}
/document_types/submittal    DocumentType    {"id": "submittal", "name": "Submittal"}
/project_stages/planning    ProjectStage    {"id": "planning", "name": "Active"}
*/
declare(strict_types=1);

namespace App\Entity\ListRanking;

use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

trait HasStringIdentifierRankedListTrait
{
    use HasIntegerIdentifierRankedListTrait;

    // Override parent to make non-identifing
    #[ORM\Id]
    #[ApiProperty(identifier: false)]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Ignore]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[SerializedName('id')]
    #[ApiProperty(identifier: true)]
    // #[Groups(['ranked_list:read'])]
    protected ?string $identifier = null;

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }
}

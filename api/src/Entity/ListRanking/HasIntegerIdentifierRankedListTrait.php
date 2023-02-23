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
use App\Entity\Trait\IdentifyingIdTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait HasIntegerIdentifierRankedListTrait
{
    use IdentifyingIdTrait;

    #[ORM\Column(type: 'string', length: 180)]
    // #[Groups(['ranked_list:read'])]
    protected ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRankedList(): ?RankedListInterface
    {
        return $this->rankedList;
    }

    /*
    public function setRankedList(RankedListInterface $rankedList): self
    {
        $this->rankedList = $rankedList;

        return $this;
    }
    */

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'name'=>$this->name, 'class'=>get_class($this)];
    }
}

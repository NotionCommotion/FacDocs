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
Ranked lists
Uses integer ID
/departments/1    Department    {"id": "1", "name": "Facilities"}
/job_titles/1    JobTitle    {"id": "1", "name": "Architect"}

Uses string ID
/document_stages/active        DocumentStage    {"id": "active", "name": "Active"}
/document_types/submittal    DocumentType    {"id": "submittal", "name": "Submittal"}
/project_stages/planning    ProjectStage    {"id": "planning", "name": "Active"}

### NOT COMPLETE??? ###
Uses string ID
/media_types/type=application;subtype=1d-interleaved-parityfec    MediaType    {"id": "application/1d-interleaved-parityfec", "name": "application/1d-interleaved-parityfec"}
*/
declare(strict_types=1);

namespace App\Entity\ListRanking;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Annotation\ApiResource;
use App\Entity\Trait\IdentifyingIdTrait;
use App\Repository\ListRanking\AbstractRankedListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: AbstractRankedListRepository::class, readOnly: true)]
#[ORM\Table(name: 'ranked_list')]
#[ORM\InheritanceType(value: 'JOINED')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: 'string')]
abstract class AbstractRankedList implements RankedListInterface
{
    use IdentifyingIdTrait;

    #[ORM\Column(type: 'float', nullable: true)]
    #[Ignore]
    protected ?float $defaultRanking = null;

    /**
     * @var UserListRanking[]|Collection|ArrayCollection
     */
    #[ORM\OneToMany(targetEntity: UserListRanking::class, mappedBy: 'rankedList')]
    #[Ignore]
    protected Collection $userListRankings;

    public function __construct()
    {
        $this->userListRankings = new ArrayCollection();
    }

    public function getDefaultRanking(): ?float
    {
        return $this->defaultRanking;
    }

    public function setDefaultRanking(?float $defaultRanking): self
    {
        $this->defaultRanking = $defaultRanking;

        return $this;
    }

    /**
     * @return Collection|UserListRanking[]
     */
    public function getUserListRankings(): Collection
    {
        return $this->userListRankings;
    }

    public function addUserListRanking(UserListRanking $userListRanking): self
    {
        if (!$this->userListRankings->contains($userListRanking)) {
            $this->userListRankings[] = $userListRanking;
            $userListRanking->setRankedList($this);
        }

        return $this;
    }

    public function removeUserListRanking(UserListRanking $userListRanking): self
    {
        if (!$this->userListRankings->removeElement($userListRanking)) {
            return $this;
        }

        if ($userListRanking->getRankedList() !== $this) {
            return $this;
        }

        $userListRanking->setRankedList(null);

        return $this;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'class'=>get_class($this)];
    }
}

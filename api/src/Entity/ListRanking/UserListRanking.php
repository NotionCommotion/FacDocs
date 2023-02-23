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

namespace App\Entity\ListRanking;

use App\Entity\Trait\IdTrait;
use App\Entity\User\UserInterface;
use App\Repository\ListRanking\UserListRankingRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserListRankingRepository::class)]
#[ORM\Table(indexes: ['(columns={"ranking"})'])]
class UserListRanking
{
    use IdTrait;

    #[ORM\Column(type: 'float')]
    private ?float $ranking = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: UserInterface::class, inversedBy: 'userListRankings')]
        #[ORM\JoinColumn(nullable: false)]
        private ?UserInterface $user = null,

        #[ORM\ManyToOne(targetEntity: AbstractRankedList::class, inversedBy: 'userListRankings')]
        #[ORM\JoinColumn(nullable: false)]
        private ?AbstractRankedList $rankedList = null,

        #[ORM\Column(type: 'datetime')]
        #[ORM\JoinColumn(nullable: false)]
        private DateTime $rankingAt = new DateTime()
    ) {
        $this->ranking = $rankedList?$rankedList->getDefaultRanking():null;
    }

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'class'=>get_class($this)];
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRankedList(): ?AbstractRankedList
    {
        return $this->rankedList;
    }

    public function setRankedList(?AbstractRankedList $rankedList): self
    {
        $this->rankedList = $rankedList;

        return $this;
    }

    public function getRanking(): float
    {
        return $this->ranking;
    }

    public function setRanking(float $ranking, bool $setRankingAt = true): self
    {
        $this->ranking = $ranking;

        if ($setRankingAt) {
            $this->rankingAt = new DateTime();
        }

        return $this;
    }

    public function getRankingAt(): ?DateTime
    {
        return $this->rankingAt;
    }

    public function setRankingAt(DateTime $rankingAt): self
    {
        $this->rankingAt = $rankingAt;

        return $this;
    }

    public function getHistorySeconds(): int
    {
        return time() - $this->rankingAt->getTimestamp();
    }
}

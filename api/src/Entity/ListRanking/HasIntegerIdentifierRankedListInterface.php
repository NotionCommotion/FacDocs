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

use Doctrine\Common\Collections\Collection;

// See App\Service\UserListRankingService
interface HasIntegerIdentifierRankedListInterface
{
    public function getName(): ?string;

    public function setName(string $name): self;

    public function getRankedList(): ?RankedListInterface;

    //public function setRankedList(RankedListInterface $rankedList): self;
}

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
interface RankedListInterface
{
    public function getDefaultRanking(): ?float;

    public function setDefaultRanking(?float $defaultRanking): self;

    public function getUserListRankings(): Collection;

    public function addUserListRanking(UserListRanking $userListRanking): self;

    public function removeUserListRanking(UserListRanking $userListRanking): self;
}

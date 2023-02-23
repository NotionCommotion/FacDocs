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

namespace App\Entity\User;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\ListRanking\HasIntegerIdentifierRankedListTrait;
use App\Entity\ListRanking\HasIntegerIdentifierRankedListInterface;
use Doctrine\ORM\Mapping as ORM;

#[ApiResource(operations: [new Get(), new GetCollection()])]
#[ORM\Entity(readOnly: true)]
class Department implements HasIntegerIdentifierRankedListInterface
{
    use HasIntegerIdentifierRankedListTrait;

    public function __construct(
        #[ORM\OneToOne(cascade: ['persist', 'remove'])]
        #[ORM\JoinColumn(nullable: false)]
        private DepartmentRankedList $rankedList
    ){}
}

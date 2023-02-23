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

use App\Entity\ListRanking\AbstractRankedList;
use App\Repository\Document\DocumentStageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentStageRepository::class, readOnly: true)]
class DocumentStageRankedList extends AbstractRankedList
{
}

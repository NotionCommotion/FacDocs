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

namespace App\Security\Voter;

use App\Security\Service\DocumentAclService;
use App\Entity\Document\Media;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MediaVoter extends Voter
{
    public function __construct(private DocumentAclService $aclService)
    {
    }

    // Supports reading a single media only.  Post has no security other than being logged on.  For collections, must use document.
    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof Media && $attribute==='MEDIA_READ';
    }

    protected function voteOnAttribute(string $attribute, $media, TokenInterface $token): bool
    {
        //echo(__METHOD__.PHP_EOL);return true;
        return $this->aclService->userHasAccessToMedia($media);
    }
}

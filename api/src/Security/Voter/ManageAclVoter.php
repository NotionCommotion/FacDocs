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

use App\Entity\Acl\AclEntityInterface;

use App\Entity\Acl\AclInterface;
use App\Entity\Acl\DocumentAclInterface;
use App\Security\Service\ResourceAclService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageAclVoter extends Voter
{
    public function __construct(private ResourceAclService $aclService)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof AclInterface;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $this->aclService->canManageAcl($subject instanceof DocumentAclInterface?$subject->getResource()->getResourceAcl():$subject, $attribute);
    }
}
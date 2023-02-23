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

use App\Entity\Acl\AclMemberInterface;
use App\Security\Service\ResourceAclService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ManageAclMemberVoter extends Voter
{
    public function __construct(private ResourceAclService $aclService)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof AclMemberInterface;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $this->aclService->canManageAcl($subject->getAcl(), $attribute);
    }
}
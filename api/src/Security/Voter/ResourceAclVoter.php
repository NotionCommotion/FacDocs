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

use App\Security\Service\ResourceAclService;
use App\Entity\Acl\HasResourceAclInterface;
use App\Entity\Acl\HasContainerAclInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

// Get collection handled by doctrine extension and performed by SQL.
class ResourceAclVoter extends Voter
{
    public function __construct(private ResourceAclService $aclService)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof HasResourceAclInterface;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $this->aclService->canPerformCrud($subject, $this->getAction($attribute));
    }

    private function getAction(string $attribute): string
    {
        // $attribute will be ACL_RESOURCE_* where options are READ or UPDATE
        return strtolower(substr($attribute, 13));
    }
}

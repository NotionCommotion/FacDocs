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
use App\Entity\Document\DocumentInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

// Get collection handled by doctrine extension and performed by SQL.
class DocumentAclVoter extends Voter
{
    public function __construct(private DocumentAclService $aclService)
    {
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof DocumentInterface;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        return $this->aclService->canPerformCrud($subject, $this->getAction($attribute));
    }

    private function getAction(string $attribute): string
    {
        // $attribute will be ACL_DOCUMENT_* where options are CREATE, READ, UPDATE, or DELETE.
        // ACL_DOCUMENT_CREATE, ACL_DOCUMENT_READ, ACL_DOCUMENT_UPDATE, or ACL_DOCUMENT_DELETE
        return strtolower(substr($attribute, 13));
    }

    protected function debugCaller(int $back=2):string
    {
        $db = debug_backtrace()[$back];
        return sprintf('%s::%s (%s)', $db['class'], $db['function'], $db['line']);
    }
}

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

namespace App\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use App\Entity\Document\Document;
use App\Entity\Acl\DocumentAclMember;
use App\Service\UserRetreiverService;


final class PersistDocumentListener
{
    public function __construct(private UserRetreiverService $userRetreiverService)
    {
    }

    public function prePersist(Document $document, LifecycleEventArgs $event): void
    {
        $this->setSpecifications($document, $event);
    }

    private function setSpecifications(Document $document, LifecycleEventArgs $event): void
    {
        if($document->getSpecification()) {
            return;
        }
        $documentAcl = $document->getProject()->getDocumentAcl();
        $user = $this->userRetreiverService->getUser();
        $specification = ($member = $event->getObjectManager()->getRepository(DocumentAclMember::class)->findOneBy(['acl' => $documentAcl, 'user' => $user]))
        ?$member->getSpecification()
        :null;
        $document->setSpecification(
            $specification
            ??$user->getPrimarySpecification()
            ??$user->getOrganization()->getPrimarySpecification())
        ;
    }
}

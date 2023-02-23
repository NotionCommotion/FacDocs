<?php

declare(strict_types=1);

namespace App\Service;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User\UserInterface;
use App\Entity\User\BasicUserInterface;
use App\Entity\User\AbstractUser;
use App\Entity\User\SystemUser;
use App\Entity\Organization\AbstractOrganization;
use Symfony\Component\Uid\NilUlid;
use App\Security\TokenUser;

/*
Symfony\Component\Security\Core\Security stores TokenUser (a database-less user) and not the Doctrine user.
This service will provide the Doctrine user given that TokenUser.
Ideally TokenUser would have a method to convert to the Doctrine user, but it might require a listener on load to set EntityManager which seems excessive.
*/
final class UserRetreiverService implements UserRetreiverServiceInterface
{
    private ?UserInterface $user=null;
    private bool $isChecked=false;

    public function __construct(private EntityManagerInterface $entityManager, private Security $security)
    {
    }

    public function getTokenUser(): ?BasicUserInterface
    {
        // When creating documents, TenantUser is returned?
        return $this->security->getUser();
    }

    public function getUser(): ?UserInterface
    {
        if(!$this->isChecked) {
            $this->isChecked = true;
            if(!$user = $this->security->getUser()) {
                return null;
            }
            if(!$this->user = $this->entityManager->getRepository(AbstractUser::class)->find($user->getId())) {
                throw new \Exception('User does not exist.');
            }
            if($this->user instanceof SystemUser) {
                $organizationId = $user->getOrganizationId();
                if($organizationId != new NilUlid) {
                    $organization = $this->entityManager->getRepository(AbstractOrganization::class)->find($organizationId);
                    $this->user->impersonate($organization);
                }
            }
        }
        return $this->user;
    }
}

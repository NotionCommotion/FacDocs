<?php

declare(strict_types=1);

namespace App\Test\Model\Api;

use App\Entity\User\UserInterface;
use App\Entity\User\TenantUser;
use App\Entity\User\VendorUser;
use App\Entity\Document\Document;
use App\Entity\Acl\DocumentAclInterface;
use Symfony\Component\Uid\Ulid;

class DocumentUserCreator implements \IteratorAggregate
{
    private const NORMAL_USER = 'ROLE_USER';
    private const PRIVILEGED_USER = 'ROLE_MANAGE_DOCUMENT';

    private \SplObjectStorage $users;

    private UserInterface $subjectUser;
    private UserInterface $cowokerUser;
    private UserInterface $nonCowokerUser;

    private EntityResponse $resourceResponse;

    public function __construct(private ApiUser $adminApiUser, private string $resourceUri, string $subjectUserClass, private string $defaultPassword)
    {
        $this->userData = new \SplObjectStorage();

        $apiTestCase = $adminApiUser->getApiTestCase();
        $nonCoworkerOrganization = $apiTestCase->createVendorResponse($adminApiUser, [], 'Admin user creates non-coworker organization.');
        switch($subjectUserClass) {
            case TenantUser::class:
                $this->subjectUser = $apiTestCase->createTenantUser($adminApiUser, ['roles'=>[self::NORMAL_USER]], sprintf('Admin user creates subject tenant user with role %s.', self::NORMAL_USER));
                $this->coworkerUser = $apiTestCase->createTenantUser($adminApiUser, ['roles'=>[self::PRIVILEGED_USER]], sprintf('Admin user creates coworker tenant user with role %s.', self::PRIVILEGED_USER));
                $this->nonCoworkerUser = $apiTestCase->createVendorUser($nonCoworkerOrganization, $adminApiUser, ['roles'=>[self::PRIVILEGED_USER]], sprintf('Admin user creates non-coworker tenant user with role %s.', self::PRIVILEGED_USER));
                break;
            case VendorUser::class:
                $subjectOrganizationResponse = $apiTestCase->createVendorResponse($adminApiUser);
                $this->subjectUser = $apiTestCase->createVendorUser($subjectOrganizationResponse, $adminApiUser, ['roles'=>[self::NORMAL_USER]], sprintf('Admin user creates subject tenant user with role %s.', self::NORMAL_USER));
                $this->coworkerUser = $apiTestCase->createVendorUser($subjectOrganizationResponse, $adminApiUser, ['roles'=>[self::PRIVILEGED_USER]], sprintf('Admin user creates coworker tenant user with role %s.', self::PRIVILEGED_USER));
                $this->nonCoworkerUser = $apiTestCase->createVendorUser($nonCoworkerOrganization, $adminApiUser, ['roles'=>[self::PRIVILEGED_USER]], sprintf('Admin user creates non-coworker tenant user with role %s.', self::PRIVILEGED_USER));
                break;
            default: throw new \Exception('Invalid user type: '.$subjectUserClass);
        }

        $this->userData[$this->subjectUser]        = new DocumentUserData($adminApiUser->createApiUser($this->subjectUser, $this->defaultPassword), 'subjectUser');
        $this->userData[$this->coworkerUser]       = new DocumentUserData($adminApiUser->createApiUser($this->coworkerUser, $this->defaultPassword), 'coworkerUser');
        $this->userData[$this->nonCoworkerUser]    = new DocumentUserData($adminApiUser->createApiUser($this->nonCoworkerUser, $this->defaultPassword), 'nonCoworkerUser');

        // Pre-populate responses and documents
        $this->getDocumentResponse($this->subjectUser);
        $this->getDocumentResponse($this->coworkerUser);
        $this->getDocumentResponse($this->nonCoworkerUser);
    }

    public function getIterator():\generator
    {
        foreach($this->getUsers() as $documentOwner) {
            yield $this->getSubjectApiUser() => $documentOwner;
        }
    }

    public function deleteUserDocument(UserInterface $user):self
    {
        $this->userData[$user]->deleteDocument();
        return $this;
    }

    public function getDocumentResponse(UserInterface $user):EntityResponse
    {
        if(!$this->userData[$user]->getDocumentResponse()) {
            $this->updateDocumentUserData($user);
        }
        return $this->userData[$user]->getDocumentResponse();
    }

    public function getDocument(UserInterface $user):Document
    {
        if(!$this->userData[$user]->getDocument()) {
            $this->updateDocumentUserData($user);
        }
        return $this->userData[$user]->getDocument();
    }

    public function getMediaUri(UserInterface $user):string
    {
        return $this->getDocumentResponse($user)->toArray()['media'];
    }

    public function getDocumentUri(UserInterface $user):string
    {
        return $this->getDocumentResponse($user)->toArray()['@id'];
    }

    public function getDocumentId(UserInterface $user):Ulid
    {
        return $this->getDocument($user)->getId();
    }

    public function getSubjectApiUser():ApiUser
    {
        return $this->userData[$this->subjectUser]->getApiUser();
    }

    public function getResourceUri():string
    {
        return $this->resourceUri;
    }

    private function updateDocumentUserData(UserInterface $user):void
    {
        $userData = $this->userData[$user];
        $apiUser = $userData->getApiUser();
        if(!in_array(self::PRIVILEGED_USER, $user->getRoles())) {
            $roles = $user->getRoles();
            $this->adminApiUser->put($user::class, $user->getId(), ['roles' => [self::PRIVILEGED_USER]])->assert()->log(sprintf('Set %s user\'s permission temporarally to allow creating a document.', $userData->getType()));
            $apiUser->authenticate();
            $userData->setDocumentResponse($apiUser->uploadInitialDocument($this->resourceUri)->assert(201)->log(sprintf('Create test document for %s user.', $userData->getType())));
            $this->adminApiUser->put($user::class, $user->getId(), ['roles' => $roles])->assert()->log(sprintf('Set %s user\'s permission back to the orginal', $userData->getType()));
            $userData->getApiUser()->authenticate();
        }
        else {
            $userData->setDocumentResponse($apiUser->uploadInitialDocument($this->resourceUri)->assert(201)->log(sprintf('Create test document for %s user.', $userData->getType())));
        }
    }

    private function getUsers():array
    {
        return [$this->subjectUser, $this->coworkerUser, $this->nonCoworkerUser];
    }

    private function getShortName(object|string $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }

    // Used for testing only.
    public function getAdminApiUser():ApiUser
    {
        return $this->adminApiUser;
    }
    /*
    public function getSubjectUser():UserInterface
    {
        return $this->subjectUser;
    }
    public function getCoworkerUser():UserInterface
    {
        return $this->coworkerUser;
    }
    public function getNonCoworkerUser():UserInterface
    {
        return $this->nonCoworkerUser;
    }
    */
}
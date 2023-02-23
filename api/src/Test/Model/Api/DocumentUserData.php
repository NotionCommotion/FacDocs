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

namespace App\Test\Model\Api;

use App\Entity\User\UserInterface;
use App\Entity\Document\Document;

class DocumentUserData
{
    private ?EntityResponse $documentResponse = null;
    private ?Document $document = null;

    public function __construct(private ApiUser $apiUser, private string $type)
    {
    }

    public function getDocumentResponse():?EntityResponse
    {
        return $this->documentResponse;
    }

    public function getDocument():?Document
    {
        return $this->document;
    }

    public function setDocumentResponse(EntityResponse $documentResponse):self
    {
        $this->documentResponse = $documentResponse;
        $this->document = $documentResponse->toEntity();
        return $this;
    }

    public function deleteDocument():self
    {
        $this->documentResponses = null;
        $this->document = null;
        return $this;
    }

    public function getApiUser():ApiUser
    {
        return $this->apiUser;
    }

    public function getUser():UserInterface
    {
        return $this->apiUser->getUser();
    }

    public function getType():string
    {
        return $this->type;
    }
}

<?php

declare(strict_types=1);

namespace App\Test\Model\Api;

use App\Entity\User\UserInterface;

class SimpleAuthorizationStatus implements AuthorizationStatusInterface
{
    private array $userRoles;
    private bool $isCollection=false;

    public function __construct(private UserInterface $user, private int $anticipatedStatusCode, private bool $isAuthorized, private string $resourceClass, private mixed $resourceId)
    {
        $this->userRoles = $user->getRoles();
    }

    public function getUser():UserInterface
    {
        return $this->user;
    }
    public function getUserRoles():array
    {
        return $this->userRoles;
    }

    public function getResourceClass():string
    {
        return $this->resourceClass;
    }
    public function getResourceId():mixed
    {
        return $this->resourceId;
    }

    public function isAuthorized(): bool
    {
        return $this->isAuthorized;
    }
    public function getAnticipatedStatusCode(): int
    {
        return $this->anticipatedStatusCode;
    }

    public function setAnticipatedStatusCode(int $anticipatedStatusCode): self
    {
        $this->anticipatedStatusCode = $anticipatedStatusCode;
        return $this;
    }

    public function isCollection(): bool
    {
        return $this->isCollection;
    }

    public function setIsCollection(bool $isCollection): self
    {
        $this->isCollection = $isCollection;
        return $this;
    }

    public function getMessage(): string
    {
        return sprintf('%s with role %s',$this->getShortName($this->getUser()),implode(', ', $this->getUser()->getRoles()),);
    }

    public function getData():array
    {
        return [
            'message' => $this->getMessage(),
            'resource'=> [
                'id' => $this->resourceId,
                'type'=>$this->getShortName($this->resourceClass),
            ],
            'isAuthorized' => $this->isAuthorized,
            'anticipatedStatusCode' => $this->anticipatedStatusCode,
            'user' => [
                'id' => (string) $this->user->getId(),
                'organizationId'=>$this->user->getOrganization()->getId()->toBase32(),
                'organizationType'=>$this->user->getOrganization()->getType()->name,
                'type' => $this->getShortName($this->user::class),
                'roles' => $this->userRoles,
            ],
            'acl' => null
        ];
    }

    public function getDebugMessage():string
    {
        return implode(PHP_EOL, $this->_getDebugMessage());
    }
    protected function _getDebugMessage():array
    {
        return [
            sprintf('%s %s %s', $this->getShortName($this->getUser()::class), 'do something', $this->getShortName($this->resourceClass)),
            sprintf('User Roles: %s', implode(', ', $this->getUserRoles())),
        ];
    }

    public function debug(): array
    {
        return $this->toArray(false);
    }
    public function toArray(): array
    {
        return [
            'isAuthorized'=>$this->isAuthorized,
            'user'=>array_merge($this->user->debug(), ['roles'=>$this->userRoles]),
            'anticipatedStatusCode'=>$this->anticipatedStatusCode,
        ];
    }

    protected function _echo(string $msg): void
    {
        syslog(LOG_INFO, $msg);
        echo($msg.PHP_EOL);
    }

    protected function getShortName(object|string $class): string
    {
        return (new \ReflectionClass($class))->getShortName();
    }
}

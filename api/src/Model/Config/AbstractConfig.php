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

namespace App\Model\Config;

use App\Entity\User\UserInterface;
use libphonenumber\PhoneNumber;

abstract class AbstractConfig
{
    public function __construct(private UserInterface $user)
    {
    }

    public function getEmail(): string
    {
        return $this->user->getEmail();
    }

    public function setEmail(string $email): self
    {
        $this->user->setEmail($email);

        return $this;
    }

    public function getUsername(): string
    {
        return $this->user->getUsername();
    }

    public function setUsername(string $username): self
    {
        $this->user->setUsername($username);

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->user->getFirstName();
    }

    public function setFirstName(string $firstName): self
    {
        $this->user->setFirstName($firstName);

        return $this;
    }

    public function getLastName(): string
    {
        return $this->user->getLastName();
    }

    public function setLastName(string $lastName): self
    {
        $this->user->setLastName($lastName);

        return $this;
    }

    public function getMobilePhoneNumber(): ?PhoneNumber
    {
        return $this->user->getMobilePhoneNumber();
    }

    public function setMobilePhoneNumber(?PhoneNumber $mobilePhoneNumber): self
    {
        $this->user->setMobilePhoneNumber($mobilePhoneNumber);

        return $this;
    }

    public function getDirectPhoneNumber(): ?PhoneNumber
    {
        return $this->user->getDirectPhoneNumber();
    }

    public function setDirectPhoneNumber(?PhoneNumber $directPhoneNumber): self
    {
        $this->user->setDirectPhoneNumber($directPhoneNumber);

        return $this;
    }

    public function getRoles()// :array
    {
        // return $this->user->getRoles();
        return json_encode($this->user->getRoles(), JSON_THROW_ON_ERROR);
    }

    protected function getUser(): UserInterface
    {
        return $this->user;
    }
}

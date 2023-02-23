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

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use App\Entity\User\UserInterface;

final class AuthenticationSuccessListener
{
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $authenticationSuccessEvent): void
    {
        //$authenticationSuccessEvent methods: __construct, getData, setData, getUser, getResponse, isPropagationStopped, stopPropagation
        
        $user = $authenticationSuccessEvent->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $data = $authenticationSuccessEvent->getData();
        $data['data'] = [
            'id' => $user->getId()->toRfc4122(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ];
        $authenticationSuccessEvent->setData($data);
    }
}

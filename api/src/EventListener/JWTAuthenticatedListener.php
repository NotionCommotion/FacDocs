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

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;

final class JWTAuthenticatedListener
{
    /**
     * @param JWTAuthenticatedEvent $event
     *
     * @return void
     */
    public function onJWTAuthenticated(JWTAuthenticatedEvent $event)
    {
        syslog(LOG_ERR, 'JWTAuthenticatedListener not used');
        exit('JWTAuthenticatedListener not used');
        $token->setAttribute('uuid', $payload['uuid']);
    }
}

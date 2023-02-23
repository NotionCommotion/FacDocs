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

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;

final class JWTCreatedListener
{

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(private RequestStack $requestStack)
    {
    }

    /**
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        //$event methods: __construct, getHeader, setHeader, getData (public data), setData, getUser (The actual entity user.  How does it get it), isPropagationStopped,stopPropagation
        $this->setData($event);
        $this->setExpirationDate($event);
    }

    private function setData(JWTCreatedEvent $event)
    {
        // Get rid of Request?
        $request = $this->requestStack->getCurrentRequest();

        $user = $event->getUser();

        if(($request->headers->get('id') ?? $request->toArray()['id']) !== $user->getOrganization()->toRfc4122()) {
            throw new \Exception(sprintf('User organizationId in header is %s but in entity is %s', $request->headers->get('id') ?? $request->toArray()['id'], $user->getOrganization()->toRfc4122()));
        }

        $payload = $event->getData();

        $payload['organizationId'] = $user->getOrganization()->toRfc4122();
        $payload['tenantId'] = ($tenant=$user->getTenant())?$tenant->toRfc4122():null;
        $payload['type'] = $user->getType()->name;
        //$payload['ip'] = $request->getClientIp();
        
        $event->setData($payload);
        /*
        $header        = $event->getHeader();
        $header['cty'] = 'JWT';

        $event->setHeader($header);
        */
    }

    private function setExpirationDate(JWTCreatedEvent $event)
    {
        $expiration = new \DateTime('+1 day');
        $expiration->setTime(2, 0, 0);

        $payload        = $event->getData();
        $payload['exp'] = $expiration->getTimestamp();

        $event->setData($payload);
    }
}

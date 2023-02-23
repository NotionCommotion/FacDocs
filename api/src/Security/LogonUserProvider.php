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

namespace App\Security;

use App\Entity\User\UserInterface as AllUserInterface;
use App\Repository\User\UserRepository;
use App\Repository\Organization\TenantRepository;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\NilUlid;

final class LogonUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private UserRepository $userRepository, private TenantRepository $tenantRepository, private RequestStack $requestStack)
    {
    }

    /**
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier(string $usernameOrEmail): UserInterface
    {
        // Should I return App\Entity\User\UserInterface or use App\Entity\User\TokenUser?
        $request = $this->requestStack->getCurrentRequest();
        // Do not use this? Why isn't that stored inside the token?
        $id = $request->headers->get('id') ?? $request->toArray()['id'];
        // Don't use Ulid's constructor as it will only work if string is provided as base32, howebver, both fromBase32() and fromRfc4122() work for either.
        if ($user = $this->userRepository->getUser(Ulid::fromRfc4122($id), $usernameOrEmail)) {
            // Either a system, tenant, or vendor user which is using their own organization ID
            if(!$user->getIsActive()) {
                throw new UserNotFoundException();
            }
            return $user;
        }
        $systemUlid = new NilUlid();
        if (($user = $this->userRepository->getUser($systemUlid, $usernameOrEmail)) !== null && $user->getIsActive()) {
            // A system user imposterating as a tenant user.
            if(!$user->getIsActive()) {
                throw new UserNotFoundException();
            }
            if (!$tenant = $this->tenantRepository->find(Ulid::fromRfc4122($id))) {
                throw new CustomUserMessageAuthenticationException('System user found but not tenant.');
            }
            return $user->impersonate($tenant);
        }

        throw new UserNotFoundException();
    }

    /**
     * Refreshes the user after being reloaded from the session (not applicable to an stateless API).
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        syslog(LOG_ERR, 'LogonUserProvider::refreshUser() - Fix????');
        return $user;
        throw new Exception('UserProvider::() is not applicable');
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        syslog(LOG_ERR, 'LogonUserProvider::supportsClass() - Fix????');
        return is_subclass_of($class, AllUserInterface::class);
    }

    /**
     * Upgrades the encoded password of a user, typically for using a better hash algorithm.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $passwordAuthenticatedUser, string $newEncodedPassword): void
    {
        syslog(LOG_ERR, 'LogonUserProvider::upgradePassword() - Fix????');
        $this->userRepository->upgradePassword($passwordAuthenticatedUser, $newEncodedPassword);
    }
}

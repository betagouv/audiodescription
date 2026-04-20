<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/** @implements UserProviderInterface<ApiUser> */
class ApiUserProvider implements UserProviderInterface
{
    public function refreshUser(UserInterface $user): ApiUser
    {
        assert($user instanceof ApiUser);
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return $class === ApiUser::class;
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return new ApiUser();
    }
}

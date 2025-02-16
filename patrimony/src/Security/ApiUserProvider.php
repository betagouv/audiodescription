<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ApiUserProvider implements UserProviderInterface
{
  public function refreshUser(UserInterface $user): UserInterface
  {
    return $user;
  }

  public function supportsClass(string $class): bool
  {
    return $class === ApiUser::class;
  }

  public function loadUserByIdentifier(string $identifier): UserInterface
  {
    return new ApiUser();
  }
}
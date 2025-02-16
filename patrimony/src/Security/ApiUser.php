<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class ApiUser implements UserInterface
{

  public function getRoles(): array
  {
    return ['ROLE_API'];
  }

  public function eraseCredentials(): void
  {

  }

  public function getUserIdentifier(): string
  {
    return 'API';
  }
}
<?php
namespace App\Security;

use SensitiveParameter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class ApiTokenHandler implements AccessTokenHandlerInterface
{
  private string $apiToken;

  public function __construct(private ParameterBagInterface $parameterBag)
  {
    $this->apiToken = $this->parameterBag->get("patrimony_api_key");
  }

  public function getUserBadgeFrom(#[SensitiveParameter] string $accessToken): UserBadge
  {
    if ($accessToken !== $this->apiToken) {
      throw new BadCredentialsException('Invalid credentials.');
    }

    return new UserBadge('API');
  }
}
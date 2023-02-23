<?php

namespace App\Entity\User;

use Doctrine\ORM\Mapping as ORM;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken as BaseRefreshToken;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_token')]
class RefreshToken extends BaseRefreshToken
{
}

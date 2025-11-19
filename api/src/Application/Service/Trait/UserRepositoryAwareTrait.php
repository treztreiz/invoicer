<?php

declare(strict_types=1);

namespace App\Application\Service\Trait;

use App\Domain\Contracts\UserRepositoryInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait UserRepositoryAwareTrait
{
    protected ?UserRepositoryInterface $userRepository = null;

    #[Required]
    public function setUserRepository(UserRepositoryInterface $userRepository): void
    {
        $this->userRepository = $userRepository;
    }
}

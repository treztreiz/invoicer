<?php

declare(strict_types=1);

namespace App\Application\Dto\User\Input;

use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression(
    'this.newPassword === this.confirmPassword',
    message: 'New password confirmation does not match.'
)]
final readonly class PasswordInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Current password is required.')]
        private(set) string $currentPassword,

        #[Assert\NotBlank]
        #[Assert\Length(min: 12, max: 72)]
        #[Assert\NotCompromisedPassword]
        private(set) string $newPassword,

        #[Assert\NotBlank]
        private(set) string $confirmPassword,
    ) {
    }
}

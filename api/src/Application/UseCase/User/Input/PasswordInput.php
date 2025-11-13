<?php

declare(strict_types=1);

namespace App\Application\UseCase\User\Input;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\Expression(
    'this.newPassword === this.confirmPassword',
    message: 'New password confirmation does not match.'
)]
final class PasswordInput
{
    /**
     * Filled internally to identify the authenticated user.
     * Not exposed via serialization groups.
     */
    public string $userId = '';

    public function __construct(
        #[Groups(['user:password:write'])]
        #[Assert\NotBlank(message: 'Current password is required.')]
        #[ApiProperty(openapiContext: ['example' => 'CurrentPassw0rd!'])]
        private(set) readonly string $currentPassword,

        #[Groups(['user:password:write'])]
        #[Assert\NotBlank]
        #[Assert\Length(min: 12, max: 72)]
        #[Assert\NotCompromisedPassword]
        #[ApiProperty(openapiContext: ['example' => 'NewSecurePass123!'])]
        private(set) readonly string $newPassword,

        #[Groups(['user:password:write'])]
        #[Assert\NotBlank]
        #[ApiProperty(openapiContext: ['example' => 'NewSecurePass123!'])]
        private(set) readonly string $confirmPassword,
    ) {
    }
}

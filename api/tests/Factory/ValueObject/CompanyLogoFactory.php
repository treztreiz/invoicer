<?php

declare(strict_types=1);

namespace App\Tests\Factory\ValueObject;

use App\Domain\ValueObject\CompanyLogo;
use Zenstruck\Foundry\ObjectFactory;

/**
 * @extends ObjectFactory<CompanyLogo>
 */
final class CompanyLogoFactory extends ObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return CompanyLogo::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [];
    }
}

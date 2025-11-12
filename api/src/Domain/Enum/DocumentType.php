<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum DocumentType: string
{
    case QUOTE = 'QUOTE';
    case INVOICE = 'INVOICE';

    public function getPrefix(): string
    {
        return match ($this) {
            self::INVOICE => 'INV',
            self::QUOTE => 'Q',
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum QuoteStatus: string
{
    case DRAFT = 'DRAFT';
    case SENT = 'SENT';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
}

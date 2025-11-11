<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document;

use App\Domain\Entity\Document\Quote;

/** @extends DocumentFactory<Quote> */
class QuoteFactory extends DocumentFactory
{
    public static function class(): string
    {
        return Quote::class;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Factory\Document\Quote;

use App\Domain\Entity\Document\Quote\Quote;
use App\Domain\Enum\QuoteStatus;
use App\Tests\Factory\Common\BuildableFactoryTrait;
use App\Tests\Factory\Document\DocumentFactory;

/** @extends DocumentFactory<Quote> */
class QuoteFactory extends DocumentFactory
{
    use BuildableFactoryTrait;

    public static function class(): string
    {
        return Quote::class;
    }

    public function draft(): self
    {
        return $this->with(['status' => QuoteStatus::DRAFT]);
    }

    public function sent(): self
    {
        return $this->with(['status' => QuoteStatus::SENT]);
    }

    public function accepted(): self
    {
        return $this->with(['status' => QuoteStatus::ACCEPTED]);
    }

    public function rejected(): self
    {
        return $this->with(['status' => QuoteStatus::REJECTED]);
    }
}

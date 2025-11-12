<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Stub;

use App\Application\Service\EntityFetcher;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;

class EntityFetcherStub extends TestCase
{
    public static function create(
        ?UserRepositoryInterface $userRepository = null,
        ?CustomerRepositoryInterface $customerRepository = null,
        ?QuoteRepositoryInterface $quoteRepository = null,
        ?InvoiceRepositoryInterface $invoiceRepository = null,
    ): EntityFetcher {
        $fetcher = new EntityFetcher();
        $fetcher->setUserRepository($userRepository ?: static::createStub(UserRepositoryInterface::class));
        $fetcher->setCustomerRepository($customerRepository ?: static::createStub(CustomerRepositoryInterface::class));
        $fetcher->setQuoteRepository($quoteRepository ?: static::createStub(QuoteRepositoryInterface::class));
        $fetcher->setInvoiceRepository($invoiceRepository ?: static::createStub(InvoiceRepositoryInterface::class));

        return $fetcher;
    }
}

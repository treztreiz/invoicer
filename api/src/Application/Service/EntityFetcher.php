<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\Exception\ResourceNotFoundException;
use App\Domain\Contracts\CustomerRepositoryInterface;
use App\Domain\Contracts\InvoiceRepositoryInterface;
use App\Domain\Contracts\QuoteRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\Document\Invoice;
use App\Domain\Entity\Document\Quote;
use App\Domain\Entity\User\User;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\Attribute\Required;

final class EntityFetcher
{
    private ?UserRepositoryInterface $userRepository = null;

    private ?CustomerRepositoryInterface $customerRepository = null;

    private ?InvoiceRepositoryInterface $invoiceRepository = null;

    private ?QuoteRepositoryInterface $quoteRepository = null;

    #[Required]
    public function setUserRepository(UserRepositoryInterface $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    #[Required]
    public function setCustomerRepository(CustomerRepositoryInterface $customerRepository): void
    {
        $this->customerRepository = $customerRepository;
    }

    #[Required]
    public function setInvoiceRepository(InvoiceRepositoryInterface $invoiceRepository): void
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    #[Required]
    public function setQuoteRepository(QuoteRepositoryInterface $quoteRepository): void
    {
        $this->quoteRepository = $quoteRepository;
    }

    public function user(string $id): User
    {
        $repository = $this->userRepository ?? throw new \LogicException('User repository is not configured.');

        return $this->findOneById($repository, User::class, $id);
    }

    public function customer(string $id): Customer
    {
        $repository = $this->customerRepository ?? throw new \LogicException('Customer repository is not configured.');

        return $this->findOneById($repository, Customer::class, $id);
    }

    public function invoice(string $id): Invoice
    {
        $repository = $this->invoiceRepository ?? throw new \LogicException('Invoice repository is not configured.');

        return $this->findOneById($repository, Invoice::class, $id);
    }

    public function quote(string $id): Quote
    {
        $repository = $this->quoteRepository ?? throw new \LogicException('Quote repository is not configured.');

        return $this->findOneById($repository, Quote::class, $id);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function findOneById(
        object $repository,
        string $class,
        string $id,
    ): object {
        if (!method_exists($repository, $method = 'findOneById')) {
            throw new \InvalidArgumentException(sprintf('Method '.$method.' does not exist in "%s".', get_class($repository)));
        }

        return $repository->findOneById(Uuid::fromString($id)) ?? throw new ResourceNotFoundException($class, $id);
    }
}

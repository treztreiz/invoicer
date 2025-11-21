<?php

declare(strict_types=1);

namespace App\Application\Service\Trait;

use App\Domain\Contracts\Repository\CustomerRepositoryInterface;
use Symfony\Contracts\Service\Attribute\Required;

trait CustomerRepositoryAwareTrait
{
    protected ?CustomerRepositoryInterface $customerRepository = null;

    #[Required]
    public function setCustomerRepository(CustomerRepositoryInterface $customerRepository): void
    {
        $this->customerRepository = $customerRepository;
    }
}

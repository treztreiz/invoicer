<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Customer\Handler;

use App\Application\Exception\ResourceNotFoundException;
use App\Application\UseCase\Customer\Handler\UpdateCustomerHandler;
use App\Application\UseCase\Customer\Input\CustomerAddressInput;
use App\Application\UseCase\Customer\Input\CustomerInput;
use App\Application\UseCase\Customer\Input\Mapper\UpdateCustomerMapper;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Domain\Entity\Customer\Customer;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Unit\Application\UseCase\Stub\CustomerRepositoryStub;
use App\Tests\Unit\Application\UseCase\Stub\EntityFetcherStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class UpdateCustomerHandlerTest extends TestCase
{
    use Factories;

    private CustomerInput $input;

    protected function setUp(): void
    {
        $this->input = new CustomerInput(
            firstName: 'Updated',
            lastName: 'Customer',
            email: 'updated@example.com',
            address: new CustomerAddressInput(
                '42 avenue',
                '10100',
                'Berlin',
                'DE',
            ),
            phone: '+33999999999',
        );

        $this->input->customerId = Uuid::v7()->toRfc4122();
    }

    public function test_handle_updates_customer(): void
    {
        $customer = CustomerFactory::build()->create();

        $output = $this->createHandler($customer)->handle($this->input);

        static::assertSame('Updated', $output->firstName);
        static::assertSame('Berlin', $output->address->city);
    }

    public function test_handle_throws_when_customer_missing(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler()->handle($this->input);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(?Customer $customer = null): UpdateCustomerHandler
    {
        $repository = new CustomerRepositoryStub($customer);

        return new UpdateCustomerHandler(
            customerRepository: $repository,
            entityFetcher: EntityFetcherStub::create(
                customerRepository: $repository,
            ),
            mapper: new UpdateCustomerMapper(),
            outputMapper: new CustomerOutputMapper(),
        );
    }
}

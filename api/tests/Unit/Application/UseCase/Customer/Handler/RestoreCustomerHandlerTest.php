<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\UseCase\Customer\Handler;

use App\Application\Exception\ResourceNotFoundException;
use App\Application\UseCase\Customer\Handler\RestoreCustomerHandler;
use App\Application\UseCase\Customer\Output\Mapper\CustomerOutputMapper;
use App\Application\UseCase\Customer\Task\CustomerStatusTask;
use App\Domain\Entity\Customer\Customer;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Unit\Application\Stub\CustomerRepositoryStub;
use App\Tests\Unit\Application\Stub\EntityFetcherStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Test\Factories;

/**
 * @testType sociable-unit
 */
final class RestoreCustomerHandlerTest extends TestCase
{
    use Factories;

    private CustomerStatusTask $task;

    protected function setUp(): void
    {
        $this->task = new CustomerStatusTask(Uuid::v7()->toRfc4122());
    }

    public function test_handle_restores_customer(): void
    {
        $customer = CustomerFactory::build()->archived()->create();

        $output = $this->createHandler($customer)->handle($this->task);

        static::assertFalse($output->isArchived);
    }

    public function test_handle_throws_when_customer_missing(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->createHandler()->handle($this->task);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createHandler(?Customer $customer = null): RestoreCustomerHandler
    {
        $repository = new CustomerRepositoryStub($customer);

        return new RestoreCustomerHandler(
            customerRepository: $repository,
            entityFetcher: EntityFetcherStub::create(
                customerRepository: $repository,
            ),
            outputMapper: new CustomerOutputMapper()
        );
    }
}

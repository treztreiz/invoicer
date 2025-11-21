<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace App\Tests\Functional\Api\Customer;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Tests\Factory\Customer\CustomerFactory;
use App\Tests\Factory\ValueObject\NameFactory;
use App\Tests\Functional\Api\Common\ApiClientHelperTrait;
use Symfony\Component\Uid\Uuid;

/**
 * @testType functional
 */
final class CustomerApiTest extends ApiTestCase
{
    use ApiClientHelperTrait;

    protected static ?bool $alwaysBootKernel = true;

    public function test_create_customer_persists_entity(): void
    {
        $client = $this->createAuthenticatedClient();

        $response = $this->apiRequest($client, 'POST', '/api/customers', [
            'json' => $this->createCustomerPayload('Charlie', 'Xavier', 'Stub Corp', 'charlie.xavier@example.com'),
        ]);

        self::assertResponseStatusCodeSame(201);
        $data = $response->toArray(false);

        static::assertArrayHasKey('customerId', $data);
        static::assertSame('Charlie', $data['firstName']);
        static::assertSame('Xavier', $data['lastName']);
        static::assertSame('Stub Corp', $data['legalName']);

        CustomerFactory::assert()->exists([
            'id' => Uuid::fromString($data['customerId']),
            'contact.email' => 'charlie.xavier@example.com',
        ]);
    }

    public function test_list_customers_returns_active_customers(): void
    {
        $client = $this->createAuthenticatedClient();
        CustomerFactory::createSequence([
            ['name' => NameFactory::new(['lastName' => 'Yellow'])],
            ['name' => NameFactory::new(['lastName' => 'Zephyr'])],
        ]);

        $response = $this->apiRequest($client, 'GET', '/api/customers');

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertArrayHasKey('member', $data);
        static::assertCount(2, $data['member']);
        static::assertSame('Yellow', $data['member'][0]['lastName']);
        static::assertSame('Zephyr', $data['member'][1]['lastName']);
    }

    public function test_get_customer_returns_single_customer(): void
    {
        $client = $this->createAuthenticatedClient();
        $customer = CustomerFactory::createOne([
            'name' => NameFactory::new([
                'firstName' => 'Diane',
                'lastName' => 'Watson',
            ]),
        ]);

        $response = $this->apiRequest($client, 'GET', sprintf('/api/customers/%s', $customer->id->toRfc4122()));

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('Diane', $data['firstName']);
        static::assertSame('Watson', $data['lastName']);
    }

    public function test_update_customer_persists_changes(): void
    {
        $client = $this->createAuthenticatedClient();
        $customer = CustomerFactory::createOne();

        $response = $this->apiRequest($client, 'PUT', sprintf('/api/customers/%s', $customer->id->toRfc4122()), [
            'json' => $this->createCustomerPayload('Evelyn', 'Vector', null, 'evelyn.vector@example.com'),
        ]);

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertSame('Evelyn', $data['firstName']);
        static::assertSame('evelyn.vector@example.com', $data['email']);
        static::assertNull($data['legalName']);
        static::assertSame('22 rue des Lilas', $data['address']['streetLine1']);

        static::assertSame('Evelyn', $customer->name->firstName);
        static::assertSame('evelyn.vector@example.com', $customer->contact->email);
        static::assertNull($customer->legalName);
        static::assertSame('22 rue des Lilas', $customer->address->streetLine1);
    }

    public function test_archive_customer_marks_it_archived(): void
    {
        $client = $this->createAuthenticatedClient();
        $customer = CustomerFactory::createOne(['isArchived' => false]);

        static::assertFalse($customer->isArchived);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/customers/%s/archive', $customer->id->toRfc4122()));

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertTrue($data['archived']);
        static::assertTrue($customer->isArchived);
    }

    public function test_restore_customer_reactivates_it(): void
    {
        $client = $this->createAuthenticatedClient();
        $customer = CustomerFactory::createOne(['isArchived' => true]);

        static::assertTrue($customer->isArchived);

        $response = $this->apiRequest($client, 'POST', sprintf('/api/customers/%s/restore', $customer->id->toRfc4122()));

        self::assertResponseIsSuccessful();
        $data = $response->toArray(false);

        static::assertFalse($data['archived']);
        static::assertFalse($customer->isArchived);
    }

    // /////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function createCustomerPayload(string $firstName, string $lastName, ?string $legalName, string $email): array
    {
        return [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'legalName' => $legalName,
            'email' => $email,
            'phone' => '+33000000000',
            'address' => [
                'streetLine1' => '22 rue des Lilas',
                'streetLine2' => 'Appartement 4',
                'postalCode' => '31000',
                'city' => 'Toulouse',
                'region' => 'Occitanie',
                'countryCode' => 'FR',
            ],
        ];
    }
}

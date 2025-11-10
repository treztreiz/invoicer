<?php

declare(strict_types=1);

namespace App\Application\UseCase\Invoice\Input\Mapper;

use App\Application\UseCase\Invoice\Input\InvoiceInput;
use App\Application\UseCase\Invoice\Input\InvoiceLineInput;
use App\Domain\DTO\DocumentLinePayload;
use App\Domain\DTO\InvoicePayload;
use App\Domain\Entity\Customer\Customer;
use App\Domain\Entity\User\User;
use App\Domain\Enum\RateUnit;
use App\Domain\ValueObject\AmountBreakdown;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Quantity;
use App\Domain\ValueObject\VatRate;

final readonly class CreateInvoiceMapper
{
    public function map(InvoiceInput $input, Customer $customer, User $user): InvoicePayload
    {
        $vatRate = new VatRate($this->decimal($input->vatRate, 2));

        $linePayloads = [];
        $totalNet = '0.00';
        $totalTax = '0.00';

        foreach ($input->lines as $index => $lineInput) {
            $lineDto = $lineInput instanceof InvoiceLineInput
                ? $lineInput
                : $this->hydrateLineInput($lineInput);

            $quantity = new Quantity($this->decimal($lineDto->quantity, 3));
            $rate = new Money($this->decimal($lineDto->rate));

            $net = $this->multiply($quantity->value, $rate->value);
            $tax = $this->percentage($net, $vatRate->value);
            $gross = $this->add($net, $tax);

            $linePayloads[] = new DocumentLinePayload(
                description: $lineDto->description,
                quantity: $quantity,
                rateUnit: RateUnit::from($lineDto->rateUnit),
                rate: $rate,
                amount: new AmountBreakdown(
                    net: new Money($net),
                    tax: new Money($tax),
                    gross: new Money($gross),
                ),
                position: $index,
            );

            $totalNet = $this->add($totalNet, $net);
            $totalTax = $this->add($totalTax, $tax);
        }

        $total = new AmountBreakdown(
            net: new Money($totalNet),
            tax: new Money($totalTax),
            gross: new Money($this->add($totalNet, $totalTax)),
        );

        return new InvoicePayload(
            title: $input->title,
            subtitle: $input->subtitle,
            currency: $input->currency,
            vatRate: $vatRate,
            total: $total,
            lines: $linePayloads,
            customerSnapshot: $this->customerSnapshot($customer),
            companySnapshot: $this->companySnapshot($user),
            dueDate: new \DateTimeImmutable($input->dueDate),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function customerSnapshot(Customer $customer): array
    {
        return [
            'id' => $customer->id?->toRfc4122(),
            'name' => [
                'first' => $customer->name->firstName,
                'last' => $customer->name->lastName,
            ],
            'contact' => [
                'email' => $customer->contact->email,
                'phone' => $customer->contact->phone,
            ],
            'address' => [
                'streetLine1' => $customer->address->streetLine1,
                'streetLine2' => $customer->address->streetLine2,
                'postalCode' => $customer->address->postalCode,
                'city' => $customer->address->city,
                'region' => $customer->address->region,
                'countryCode' => $customer->address->countryCode,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function companySnapshot(User $user): array
    {
        $company = $user->company;

        return [
            'legalName' => $company->legalName,
            'contact' => [
                'email' => $company->contact->email,
                'phone' => $company->contact->phone,
            ],
            'address' => [
                'streetLine1' => $company->address->streetLine1,
                'streetLine2' => $company->address->streetLine2,
                'postalCode' => $company->address->postalCode,
                'city' => $company->address->city,
                'region' => $company->address->region,
                'countryCode' => $company->address->countryCode,
            ],
            'defaultCurrency' => $company->defaultCurrency,
            'defaultHourlyRate' => $company->defaultHourlyRate->value,
            'defaultDailyRate' => $company->defaultDailyRate->value,
            'defaultVatRate' => $company->defaultVatRate->value,
            'legalMention' => $company->legalMention,
        ];
    }

    private function decimal(float $value, int $scale = 2): string
    {
        return number_format($value, $scale, '.', '');
    }

    private function multiply(string $left, string $right, int $scale = 2): string
    {
        return \bcmul($left, $right, $scale);
    }

    private function add(string $left, string $right, int $scale = 2): string
    {
        return \bcadd($left, $right, $scale);
    }

    private function percentage(string $amount, string $rate, int $scale = 2): string
    {
        if ('0.00' === $amount || '0.00' === $rate) {
            return number_format(0, $scale, '.', '');
        }

        $multiplied = \bcmul($amount, $rate, $scale + 4);

        return \bcdiv($multiplied, '100', $scale);
    }

    /**
     * @param array<string, mixed>|InvoiceLineInput $line
     */
    private function hydrateLineInput(array|InvoiceLineInput $line): InvoiceLineInput
    {
        if ($line instanceof InvoiceLineInput) {
            return $line;
        }

        return new InvoiceLineInput(
            description: (string) ($line['description'] ?? ''),
            quantity: (float) ($line['quantity'] ?? 0),
            rateUnit: (string) ($line['rateUnit'] ?? RateUnit::HOURLY->value),
            rate: (float) ($line['rate'] ?? 0),
        );
    }
}

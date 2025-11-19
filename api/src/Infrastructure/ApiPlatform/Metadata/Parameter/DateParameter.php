<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Metadata\Parameter;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\Validator\Constraints as Assert;

class DateParameter
{
    private function __construct()
    {
    }

    public static function create(string $key, string $description): QueryParameter
    {
        $example = new \DateTimeImmutable()->format('Y-m-d');

        return new QueryParameter(
            key: $key,
            openApi: new OpenApiParameter(
                name: $key,
                in: 'query',
                description: $description,
                schema: [
                    'type' => 'string',
                    'format' => 'date',
                ],
                example: $example,
            ),
            filter: DateFilter::class,
            constraints: [new Assert\Type(\DateTimeImmutable::class, message: 'This value is not a valid date. Expected format: "Y-m-d".')],
            nativeType: new ObjectType(\DateTimeImmutable::class),
            castToNativeType: true,
            castFn: static fn (string $date) => \DateTimeImmutable::createFromFormat('Y-m-d', $date) ?? $date,
        );
    }
}

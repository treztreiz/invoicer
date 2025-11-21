# API Platform Configuration

We model our API surface via PHP resource config files under
`api/config/resources/*.php`. Each file returns an `ApiResource`
definition describing the operations, state options, and query
parameters for a single DTO. We no longer use attributes or YAML.

Example (`api/config/resources/quote.php`):

```php
<?php

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Application\Dto\Quote\Input\QuoteInput;
use App\Application\Dto\Quote\Output\QuoteOutput;
use App\Infrastructure\ApiPlatform\State\Quote\CreateQuoteStateProcessor;

return new ApiResource(
    class: QuoteOutput::class,
    input: QuoteInput::class,
    routePrefix: '/quotes',
    operations: [
        new GetCollection(stateOptions: new Options(Quote::class)),
        new Get(uriTemplate: '/{quoteId}', stateOptions: new Options(Quote::class)),
        new Post(read: false, processor: CreateQuoteStateProcessor::class),
    ],
);
```

## State providers and processors

- **Read operations** leverage the default Doctrine providers. We no
  longer create custom providers; Doctrine returns entities, and the
  object mapper (documented in `application-mapping.md`) converts them
  to DTOs.
- **Write operations** use small processors in
  `App\Infrastructure\ApiPlatform\State\...` that:
  1. Map the DTO to a domain payload via the ObjectMapper.
  2. Invoke the relevant use case (e.g., `CreateQuoteUseCase`).
  3. Return the resulting aggregate; the Doctrine provider + object
     mapper handles serialization.

Example skeleton:

```php
final class CreateQuoteStateProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateQuoteUseCase $useCase,
        private ObjectMapperInterface $mapper,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Quote
    {
        $payload = $this->mapper->map($data, QuotePayload::class);

        return $this->useCase->handle($payload);
    }
}
```

## Configuration tips

- Keep resource PHP files small and focused on the DTO they represent.
- Use `stateOptions: new Options(Entity::class)` for read operations so
  Doctrine is the source of truth.
- Only create processors for write operations; read paths need no custom
  wiring.
- Place shared query parameter helpers (date filters, UUID filters) under
  `src/Infrastructure/ApiPlatform/Metadata/Parameter`.

For application-layer mapping details (DTOs, transformers, ObjectMapper),
see `doc/application-mapping.md`.

# Application Layer Mapping Approach

This document explains how the application layer handles input/output
mapping, how DTOs are wired to domain payloads, and how the object
mapper fits in.

> API Platform configuration (resources, providers/processors) lives in
> `doc/api-platform.md`.

## Flow overview

```
HTTP request → Serializer → DTOs → ObjectMapper → Domain payloads/aggregates
```

1. API Platform + the Symfony Serializer deserialize requests into DTOs
   defined on each operation (see `doc/api-platform.md`).
2. DTOs use `#[Map]` attributes and small transformers to normalize
   primitive fields into value objects or nested DTOs.
3. The Symfony ObjectMapper performs structural DTO → payload mapping.
4. Aggregates recompute derived data (totals, snapshots, installment
   amounts) and enforce invariants.

## DTOs and transformers

- DTOs stay declarative: they only expose the fields coming from or
  going to the HTTP layer.
- `#[Map]` annotations pull values from the entity (for outputs) or
  target payload field names (for inputs).
- Shared transformers live under
  `App\Application\Service\Transformer` (e.g., `InputTransformer::vatRate`).
- DTO-specific transformers sit next to the DTO (e.g.,
  `QuoteOutputTransitionsTransformer`).

Example (output DTO):

```php
#[Map(source: Quote::class)]
final class QuoteOutput
{
    #[Map(source: 'id', transform: [OutputTransformer::class, 'uuid'])]
    public string $quoteId;

    #[Map(transform: DocumentLineOutputTransformer::class)]
    public array $lines;
}
```

## Domain responsibilities

Aggregates remain the source of truth for derived data:
- `Document` recomputes line totals and snapshots via payloads.
- `InstallmentPlan` allocates amounts from percentages.
- `Recurrence` validates end strategy vs. dates/occurrence counts.

DTOs never compute these values; they pass raw input to the domain
payload, and the aggregate recomputes them to maintain invariants.

## Testing

- Functional tests exercise the entire stack (DTOs, mapper, processors).
- Domain unit tests cover the new computation logic (document totals,
  installment allocation, recurrence guards).
- For unit tests that need deterministic IDs or inverse relations, we
  use `tests/TestHelper` to set private fields via reflection—mirroring
  what Doctrine does in production.

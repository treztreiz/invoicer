# API Platform Infrastructure Conventions

This project keeps the application layer (use cases, DTOs) free from HTTP‑specific
concerns while still taking advantage of API Platform’s rich metadata system.
The glue lives in `api/src/Infrastructure/ApiPlatform/Metadata`.

This document explains how the pieces fit together and how to extend them safely.

---

## Overview

```
┌──────────────┐          ┌───────────────────────────────┐
│ UseCase DTOs │  (Output/Input)                          │
│  App/...     │────────▶│ ResourceRegistry              │
└──────────────┘          │ - declares ApiResource nodes  │
                          │ - opt-in auto config          │
                          └──────────────┬────────────────┘
                                         │
                          ┌──────────────▼────────────────┐
                          │ ResourceMetadataCollection... │
                          │ - merges registry + native    │
                          │ - derives handlers via naming │
                          └──────────────┬────────────────┘
                                         │
                          ┌──────────────▼────────────────┐
                          │ API Platform metadata chain   │
                          └───────────────────────────────┘
```

- DTOs live under `App\Application\UseCase\<Case>\Input|Output`.
- `ResourceRegistry` registers `ApiResource` instances tied to those DTOs.
- The metadata decorator merges registry entries with the base API Platform
  metadata (attributes/YAML) and fills in defaults (controller, provider,
  processor, input/output classes, serializer groups, formats).
- Anything defined outside the registry is left untouched unless it explicitly
  opts in.

---

## ResourceRegistry

*Path:* `api/src/Infrastructure/ApiPlatform/Metadata/ResourceRegistry.php`

- Accepts an array of `ApiResource` definitions keyed by the DTO FQCN.
- Each entry can be a single `ApiResource` or a list: this allows multiple
  routes for the same DTO (e.g. `/me` and `/me/preferences`).
- When the registry registers a resource it tags it with
  `extraProperties['api.autoconfigure'] = true`. External metadata (attributes,
  YAML) do not get this tag.

Example:

```php
new ResourceRegistry([
    MeOutput::class => [
        new ApiResource(
            uriTemplate: '/me',
            operations: [new Get(name: 'api_me_get'), new Put(name: 'api_me_update')]
        ),
        new ApiResource(
            uriTemplate: '/me/preferences',
            operations: [new Put(name: 'api_me_preferences_update')]
        ),
    ],
]);
```

---

## Resource Metadata Decorator

*Path:* `api/src/Infrastructure/ApiPlatform/Metadata/ResourceMetadataCollectionFactory.php`

Responsibilities:

1. **Seed metadata** – merge the decorated factory output with any registry
   entries (if a DTO is only defined in the registry, it still goes through the
   regular API Platform pipeline).
2. **Opt-in automation** – only resources tagged with `api.autoconfigure` are
   modified; everything else behaves exactly like vanilla API Platform.
3. **Derive naming tokens** – paths like
   `App\Application\UseCase\Me\Output\MeOutput` encode both the use case name
   (`Me`) and the base name (`Me`). These are stored as
   `extraProperties['token.use_case']` / `['token.base_name']`.
4. **Apply defaults**:
    - controller (Symfony main controller)
    - formats (input/output)
    - URI template / variables (inherited from the resource)
    - provider/processor/input/output classes derived from the naming tokens
    - serializer groups (`{basename}:read` on GET, `{basename}:write` on write
      operations)
    - we never override custom values: defining provider/processor/input/output
      or contexts explicitly wins over the convention.

This pipeline mirrors API Platform’s attribute behaviour: adding another
`ApiResource` for the same DTO produces another entry in the collection with its
own operations, controller, etc.

---

## Naming Convention

The default resolver kicks in only when the DTO follows the predictable pattern:

```
App\Application\UseCase\<UseCase>\Output\<BaseName>Output
App\Application\UseCase\<UseCase>\Input\<BaseName>Input
App\Infrastructure\ApiPlatform\State\<UseCase>\<BaseName>StateProvider
App\Infrastructure\ApiPlatform\State\<UseCase>\<BaseName>StateProcessor
```

- If the DTO lives elsewhere, the decorator leaves it alone and expects you to
  wire provider/processor/input/output via API Platform’s stock options.
- To override the template without changing folder structure, set the handler
  explicitly on the `ApiResource` or on individual operations.

---

## Overriding Behaviour

You always retain full control via API Platform’s built-in options:

- `provider` / `processor` on the resource or operation override the inferred
  class names.
- `input` / `output` can be any callable, DTO, or `['class' => ...]` array.
- To disable automation for a registry-defined resource, set
  `extraProperties: ['api.autoconfigure' => false]`.

When the automation cannot determine naming tokens (non-standard DTO path),
`generateClassFromTokens()` returns `null` and the operation keeps whatever you
defined manually.

---

## Multiple Resources per DTO

The registry accepts a list of `ApiResource`s for one DTO:

```php
MeOutput::class => [
    new ApiResource(uriTemplate: '/me', ...),
    new ApiResource(uriTemplate: '/me/preferences', ...),
],
```

API Platform appends them in order. Each resource gets its own set of
operations; conflicting routes/operation names follow the “last one wins”
behaviour, just like stacking multiple `#[ApiResource]` attributes.

---

## Testing Strategy

- `api/tests/Integration/Infrastructure/ApiPlatform/Metadata/ResourceMetadataCollectionFactoryTest.php`
  – verifies registry-backed resources are discovered, duplicated resources are
  handled, and inferred providers/processors/inputs are wired correctly.
- `api/tests/Functional/Api/MeApiTest.php` – end-to-end smoke test for `/api/me`
  (GET + PUT) to ensure handlers, serialization groups, and persistence align.

Together they give confidence across the two layers (infra wiring + behaviour).

---

## When to Update this Doc

Add or adjust sections when:

- New naming templates are introduced.
- Registry structure changes (e.g. supporting per-operation overrides directly).
- The metadata decorator gains new responsibilities (merging, validation, etc.).

Keep DTOs free of HTTP annotations and instead extend the registry/decorator
mechanism described above.

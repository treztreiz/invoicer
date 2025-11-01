## Doctrine Check-Aware Architecture

The `CheckAware` feature extends Doctrine so that database check constraints can be
generated, enforced, and diffed consistently across the entire stack (schema tool,
migrations, runtime assertions).

This document describes the moving pieces, how they are wired together, and how
to extend or customise the behaviour when new check specs or dialects are added.

---

### High-Level Flow

1. **Attribute & Spec**
   - `Attribute\SoftXorCheck` annotates Doctrine entities with the check metadata
     (properties that participate, optional constraint name, etc.).
   - Each attribute maps to a spec (`Spec\SoftXorCheckSpec`) implementing
     `CheckSpecInterface` so the rest of the pipeline can treat checks generically.

2. **Doctrine Driver Middleware**
   - `Middleware\CheckAwareDriverMiddleware` wraps Doctrine’s DBAL driver. It
     swaps the detected platform with our `PostgreSQLCheckAwarePlatform`
     whenever the underlying driver exposes PostgreSQL.
   - Additional check-aware platforms can be registered simply by implementing
     `CheckAwarePlatformInterface`; the middleware receives them via
     `#[AutowireIterator]`.

3. **Platform**
   - `Platform\PostgreSQLCheckAwarePlatform` extends Doctrine’s platform and
     pulls in `CheckAwarePlatformTrait`.
   - The trait injects shared services via `#[Required]` setters:
     `CheckAwareSchemaManagerFactory`, `CheckOptionManager`, and the platform’s
     SQL generator (`PostgreSQLCheckGenerator`).
   - Table creation / alter SQL is post-processed to append or update check
     constraints declared in metadata.

4. **Schema Manager**
   - `Schema\PostgreSQLCheckAwareSchemaManager` delegates to Doctrine’s default
     manager but enriches it with:
     - `CheckIntrospector` – reads check constraints from the database and
       annotates the in-memory `Schema`.
     - `CheckComparator` – compares desired vs existing constraints and yields
       a `CheckAwareTableDiff`.
     - `CheckOptionManager` – stores the desired check specs on a table (metadata)
       and exposes helpers (`desired()`, `existingByName()`, `diffDropped()`, etc.).
   - Instantiation is centralised in `CheckAwareSchemaManagerFactory`, allowing
     other dialects or consumers to decorate/replace the manager without touching
     the platform.

5. **Listener**
   - `EventListener\SoftXorCheckListener` inspects Doctrine metadata during schema
     generation, ensures the owning-side associations are valid, and registers
     the desired specs via `CheckOptionManager`.

6. **Migrations Fix**
   - Doctrine Migrations 3.x generates an unwanted `CREATE SCHEMA public` during
     down migrations on PostgreSQL. `Migrations\PostgreSqlSchemaFixSqlGenerator`
     extends Doctrine’s SQL generator and removes that statement.
   - `DependencyFactoryConfigurator` replaces Doctrine’s default `SqlGenerator`
     with our fix through the dependency factory configurator hook.

7. **Tests**
   - Dedicated unit tests cover `CheckOptionManager`, `CheckComparator`, etc., by
     manually bootstrapping the platform with the required services.
   - Integration tests validate:
     - Schema round-trip (introspect → diff → no drift) using a stub entity.
     - Migration diff (fresh diff contains a single soft XOR constraint, second
       diff is empty once applied).

---

### Extending the Feature

#### Adding a new check spec
1. Create a new attribute under `Attribute/`.
2. Implement a matching spec class under `Spec/`.
3. Update `PostgreSQLCheckGenerator::buildExpressionSQL()` (or create a new
   generator/dialect-specific platform if required).
4. Create a dedicated event listener/subscriber (e.g. `EventListener\EnumCheckListener`)
   to translate metadata into specs. `SoftXorCheckListener` is specific to the
   soft XOR attribute; additional checks should mirror it with their own listener.

#### Supporting another dialect
1. Implement `CheckAwarePlatformInterface` for the new platform.
2. Provide a schema manager subclass (extending Doctrine’s base for the dialect).
3. Register the platform as a service – the driver middleware will pick it up.
4. Ensure the schema manager factory knows how to instantiate your manager.

#### Customising comparator / schema behaviour
Everything is wired through services:
- Decorate `CheckAwareSchemaManagerFactory` to wrap or replace the schema manager.
- Decorate `CheckComparator` or `CheckOptionManager` if additional logic is needed.

---

### Wiring Summary

- `services.yaml` autowires `Infrastructure/Doctrine/**`, so all components are
  available for decoration.
- `Doctrine\DBAL` platform is overridden via driver middleware.
- Doctrine Migrations uses the configurator to swap the SQL generator.

---

### Files & Responsibilities

```
CheckAware/
  Attribute/SoftXorCheck.php
  Contracts/
    CheckAwarePlatformInterface.php
    CheckAwareSchemaManagerInterface.php
    CheckGeneratorInterface.php
    CheckSpecInterface.php
  Enum/CheckOption.php
  EventListener/SoftXorCheckListener.php
  Middleware/
    CheckAwareDriverMiddleware.php
    CheckAwareMiddleware.php
  Platform/
    PostgreSQLCheckAwarePlatform.php
    PostgreSQLCheckGenerator.php
    Trait/CheckAwarePlatformTrait.php
  Schema/
    PostgreSQLCheckAwareSchemaManager.php
    Service/
      CheckAwareSchemaManagerFactory.php
      CheckComparator.php
      CheckIntrospector.php
      CheckOptionManager.php
    Trait/CheckAwareSchemaManagerTrait.php
    ValueObject/CheckAwareTableDiff.php
  Spec/
    SoftXorCheckSpec.php
    DroppedCheckSpec.php
Migrations/
  DependencyFactoryConfigurator.php
  PostgreSqlSchemaFixSqlGenerator.php
```

---

### References

- Doctrine Migrations issue regarding `CREATE SCHEMA public`:
  https://github.com/doctrine/migrations/issues/1415
- Doctrine DBAL schema manager factories:
  https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-manager.html

---

Feel free to extend this document as new check specs or dialects are added.

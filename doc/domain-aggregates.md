# Domain Aggregates

This document summarizes the responsibilities and major invariants of the
main aggregates. For DTO/object-mapper details see
`doc/application-mapping.md`, and for API Platform wiring see
`doc/api-platform.md`.

## Document (base class)

Represents either a quote or an invoice. Core responsibilities:

- Store title/subtitle, currency, VAT rate, snapshots of the customer and
  company data.
- Manage `DocumentLine` children (`fromPayload()`/`applyDocumentPayload()`)
  by recomputing line totals and the document total via
  `computePayload()`.
- Enforce snapshot shape via PHPStan types and internal guards.

### DocumentLine

- Created from a `ComputedLinePayload` (derived net/tax/gross amounts +
  original `DocumentLinePayload`).
- Guards description, position, quantity/rate invariants.

## Quote

Extends `Document` and adds lifecycle transitions:

- `send()`, `markAccepted()`, `markRejected()` mutate status and
  timestamps.
- `linkConvertedInvoice()` allowed only when status is `ACCEPTED`.
- Invariants throw `DocumentTransitionException` or
  `DocumentRuleViolationException` when transitions are invalid.

## Invoice

Extends `Document` with invoice-specific behavior:

- Transitions: `issue()`, `markOverdue()`, `markPaid()`, `void()`, each
  with state guards.
- Scheduling exclusivity: cannot attach both a recurrence and an
  installment plan; generated invoices (`markGeneratedFrom…`) can no
  longer attach new schedules.
- `attachRecurrence()` / `attachInstallmentPlan()` ensure mutual
  exclusivity; detaching validates that installments are still mutable.

## InstallmentPlan & Installment

- `InstallmentPlan::fromPayload()` allocates amounts from percentages and
  builds `Installment` children.
- `applyPayload()` updates/removes/adds installments while preserving
  those with a generated invoice (Guard via `Installment::assertMutable()`).
- Residual cents are applied to the last installment to keep totals in
  sync with the invoice.
- `Installment` exposes `update()`, `moveTo()`, and `markGenerated()`.

## Recurrence

- Encapsulates recurrence frequency, interval, anchor date, and end
  strategy.
- Constructor enforces that `UNTIL_DATE` requires an `endDate`,
  `UNTIL_COUNT` requires `occurrenceCount`, and `NEVER` requires
  neither.
- Used by `Invoice` to generate future invoices via scheduled runs.

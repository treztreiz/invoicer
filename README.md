# Freelance Invoicing/Quoting App (MVP)

A production-capable, contractor-friendly **single-user** invoicing & quoting app that showcases a clean **Hexagonal
Architecture** with a modern React front end and a Symfony API back end.

---

## TL;DR

* **Backend:** Symfony 7, API Platform 4, Doctrine 3 (PostgreSQL, UUIDv7), Messenger jobs, headless Chromium for PDFs.
* **Frontend:** React 19, Vite, TypeScript, Tailwind, TanStack Query, React Hook Form + Zod.
* **Ops:** Docker for dev/prod parity, optional Swarm, CI/CD via GitHub Actions.
* **Scope:** Single user; quotes & invoices (with **either** recurrence **or** installments), PDF generation, CSV
  revenue export, sequential numbering.

---

## Features (MVP)

* Manage **Customers**, **Quotes**, and **Invoices**
* Convert **Quote → Invoice** (immutable snapshots of lines & totals)
* **Mutually exclusive** billing extensions per invoice: **Recurring schedule** *or* **Installment plan**
* Reference numbering, e.g. `INV-YYYY-####`
* Generate **PDFs** for quotes/invoices (streamed on demand)
* **Revenue CSV** export and lightweight dashboard widgets
* Clean status flows (Quotes: Draft→Sent→Accepted/Rejected; Invoices: Draft→Issued→Overdue→Paid/Voided)

> **Out of scope for MVP:** multi-tenant, payment gateways, credit notes, complex tax regimes.

---

## Architecture Overview

* **Pattern:** Hexagonal (Ports & Adapters)
* **Domain model highlights:**

    * `Document` (STI) with concrete `Quote` & `Invoice` → `DocumentLine`
    * `User`, `Customer`
    * `InvoiceRecurrence` (for scheduled invoices)
    * `InstallmentPlan` → `Installment` (percent-based splits of the **grand total**; rounding residual on the last
      installment)
    * `NumberSequence` for references
* **Invariants:** An invoice can have **either** a recurrence **or** installments (exclusive). DB constraints enforce
  this.
* **Jobs:** Idempotent workers for recurrence generation & overdue flagging

---

## Directory Structure

```
repo/
├─ api/                 # Symfony 7 + API Platform
│  ├─ src/
│  ├─ config/
│  ├─ migrations/
│  └─ ...
├─ web/                 # React 19 + Vite + TS
│  ├─ src/
│  ├─ public/
│  └─ ...
├─ ops/                 # dockerfiles, nginx, chromium, etc.
├─ .github/workflows/   # CI/CD pipelines
└─ docker-compose.yml
```

---

## Getting Started

### Prerequisites

* Docker & Docker Compose
* Make (optional)

### Quick Start

```bash
# 1) Clone
git clone <your-repo-url> invoicer && cd invoicer

# 2) Environment
cp api/.env.example api/.env
cp web/.env.example web/.env
cp .env.example .env

# 3) Build & Run (first boot will install deps & run migrations)
docker compose up -d --build

# 4) Access
# API:       http://localhost:8080
# API Docs:  http://localhost:8080/docs
# Frontend:  http://localhost:5173
```

---

## Configuration

### Top-level `.env`

```
# Reverse proxy / CORS
BASE_URL=http://localhost
FRONTEND_URL=http://localhost:5173
API_URL=http://localhost:8080
```

### `api/.env`

```
APP_ENV=dev
APP_SECRET=change-me
DATABASE_URL=postgresql://invoicer:invoicer@db:5432/invoicer?serverVersion=16&charset=utf8
CORS_ALLOW_ORIGIN=^https?://localhost(:[0-9]+)?$
PDF_CHROMIUM_BIN=/usr/bin/chromium
```

### `web/.env`

```
VITE_API_BASE_URL=http://localhost:8080
VITE_APP_NAME=Invoicer
```

---

## Database

* **Engine:** PostgreSQL 16
* **IDs:** UUIDv7 for all aggregates
* **Migrations:** Doctrine Migrations (`api/migrations`)

> On first boot, migrations run automatically via entrypoint; re-run with:
>
> ```bash
> docker compose exec api php bin/console doctrine:migrations:migrate
> ```

---

## API Surface (Initial)

* `GET /me` – current user profile (includes company info & logo upload URL)
* `CRUD /customers`
* `CRUD /documents` with filters & actions:

    * `POST /documents/{id}/issue`
    * `POST /documents/{id}/convert-to-invoice` (quote→invoice)
    * `POST /documents/{id}/void`
    * `POST /documents/{id}/duplicate`
* `CRUD /installment-plans/*`
* `CRUD /recurrences`
* `GET /reports/revenue.csv`
* `GET /pdf/{id}` (streamed PDF)

> API is exposed via **API Platform** with OpenAPI docs at `/docs`.

---

## Frontend

* React 19 + Vite + TS
* State/data: TanStack Query
* Forms/validation: React Hook Form + Zod
* Styling: Tailwind CSS

Dev commands:

```bash
npm run dev         # start vite dev server
npm run build       # production build
npm run preview     # preview production build locally
```

---

## Background Jobs

* **Recurrence generator:** creates next invoice instances on schedule (idempotent)
* **Overdue marker:** flips invoice status to Overdue when past due date

Run workers (example):

```bash
docker compose exec api php bin/console messenger:consume async -vv
```

---

## Testing

* **API:** PHPUnit + API Platform testing utilities
* **Frontend:** Vitest + Testing Library
* **Smoke:** Minimal end-to-end smoke via Docker on CI

```bash
# API tests
docker compose exec api php bin/phpunit

# Web tests
cd web && npm test
```

---

## Deployment

* Container images via CI → container registry
* Reverse proxy (e.g., Nginx) terminating TLS → API & Web containers
* PostgreSQL managed service or stateful container + scheduled backups
* Optional: Docker Swarm stack for simple orchestrated deploy

---

## Contributing

* Conventional Commits
* PRs with unit tests and API/contract updates
* Keep domain invariants enforced at DB and domain levels

---

## License

TBD

---

## Maintainer

Treztreiz [mathias@wuaro.com](mailto:mathias@wuaro.com)

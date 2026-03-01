# Warehouse Management System (POC)

A proof-of-concept warehousing system with a Symfony/MongoDB backend API and React TypeScript frontend.

## Quick Start

```bash
docker compose up -d --build
```

- **Frontend:** http://localhost:3000
- **Backend API:** http://localhost:8080/api
- **OpenAPI Docs:** http://localhost:8080/api/doc

## Seed Data

Populate the database with default M&S food products, warehouses (Milton Keynes, Bradford, Thatcham, Daventry, Chesterfield, Faversham), locations, and initial stock:

```bash
docker exec warehouse_backend php bin/console app:seed
```

To drop all existing data and re-seed from scratch:

```bash
docker exec warehouse_backend php bin/console app:seed --fresh
```

## Running Tests

```bash
cd backend
./vendor/bin/phpunit
```

## Tech Stack

- **Backend:** PHP 8.4 / Symfony 8 / MongoDB ODM
- **Frontend:** React 19 / TypeScript / Axios
- **Database:** MongoDB 7
- **Infra:** Docker Compose
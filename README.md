# Postal Codes API (Slim 4)

A small REST API for managing and searching postal codes, built with Slim Framework 4 and PHP 8.3. It exposes endpoints to list, create, and delete postal codes, and includes a CLI importer to load data from a ZIP archive. Authentication is enforced with an `X-API-KEY` header.

## Features

- List postal codes with filtering by `post_code` or free-text `address`
- Get a single postal code
- Create one or multiple postal code records in a single request
- Delete a single postal code or multiple at once
- API authentication via `X-API-KEY`
- CLI importer to bulk-load postal codes from a ZIP file
- Dockerized development environment (PHP built-in server + MySQL 8 + Swagger)

## Tech stack

- PHP 8.3, Slim 4, PHP-DI, Slim PSR-7, PSR-12, Swagger
- PDO (MySQL 8), dotenv
- PHPUnit, PHPStan, PHP_CodeSniffer
- ZipArchive, XMLReader

---

## Quick start (Docker)

Prerequisites: Docker and Docker Compose.

1) Copy and configure environment

```bash
cp .env.example .env
# then edit .env and set: DB_ROOT_PASS, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, API_KEY
# For docker-compose you can set:
#   DB_HOST=slim_mysql
#   DB_PORT=3306
```

2) Start services

```bash
docker-compose up -d
```

This starts:
- App at http://localhost:8080
- MySQL on 127.0.0.1:3306 (container name `slim_mysql`)
- Swagger documentation at http://localhost:8081

3) Install dependencies (run once)

```bash
docker exec -it slim_app composer install
```

4) Run database migrations

```bash
# Uses Makefile convenience targets (executes inside app container)
make migrate
```

You can now call the API at `http://localhost:8080`. Remember to include the `X-API-KEY` header with the value from your `.env`.


---

## Configuration

Environment variables are loaded from `.env` (see `.env.example`). Key variables:

- `DB_ROOT_PASS` root password for MySQL (Docker use)
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` database connection
- `API_KEY` API key required by the middleware
- `APP_ENV` optional environment name

Database connection is created in `config/database.php` using PDO and dotenv.

---

## Database migrations

Convenience Make targets are provided when using Docker:

```bash
make migrate         # apply latest migrations
make migrate-down    # roll back
make migrate-status  # show status
```

These call `php bin/migrate.php` inside the running app container.

---

## Authentication

All `/api/post-codes` endpoints are protected by the `AuthMiddleware` and require an `X-API-KEY` header matching `API_KEY` from `.env`.

On failure a 403 response is returned:

```json
{
  "error": "Forbidden",
  "message": "Invalid or missing API key"
}
```

Include the header in requests, for example:

```bash
-H "X-API-KEY: $API_KEY"
```

---

## API

Base URL: `http://localhost:8080`

All responses are JSON. Provide `Content-Type: application/json` for requests with a body.

### List postal codes

GET `/api/post-codes`

Query params (optional):
- `post_code` string, 5 digits, exact match
- `address` string, free text matched against region/district/settlement/post_office
- `page` integer, defaults to 1

Example:

```bash
curl -s \
  -H "X-API-KEY: $API_KEY" \
  "http://localhost:8080/api/post-codes?address=Kyiv&page=1"
```

### Get postal code
GET `/api/post-codes/{post_code}`

```bash
curl -s \
  -H "X-API-KEY: $API_KEY" \
  http://localhost:8080/api/post-codes/01001
```

### Create postal codes (single)

POST `/api/post-codes`

Body:

```json
{
  "region": "Kyivska",
  "district": "Kyiv",
  "settlement": "Kyiv",
  "post_office": "Main",
  "post_code": "01001"
}
```

Example:

```bash
curl -s -X POST \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: $API_KEY" \
  -d '{
        "region": "Kyivska",
        "district": "Kyiv",
        "settlement": "Kyiv",
        "post_office": "Main",
        "post_code": "01001"
      }' \
  http://localhost:8080/api/post-codes
```

### Create postal codes (bulk)

POST `/api/post-codes`

Body is an array of the same objects as above:

```json
[
  {
    "region": "Kyivska",
    "district": "Kyiv",
    "settlement": "Kyiv",
    "post_office": "Main",
    "post_code": "01001"
  },
  {
    "region": "Lvivska",
    "district": "Lviv",
    "settlement": "Lviv",
    "post_office": "Center",
    "post_code": "79000"
  }
]
```

Responses:
- 201 Created — if at least one record was created
- 409 Conflict — if nothing new was created
- 422 Unprocessable Entity — on validation errors

### Delete a single postal code

DELETE `/api/post-codes/{post_code}`

```bash
curl -s -X DELETE \
  -H "X-API-KEY: $API_KEY" \
  http://localhost:8080/api/post-codes/01001
```

### Delete multiple postal codes

DELETE `/api/post-codes`

Body:

```json
{ "post_codes": ["01001", "79000"] }
```

```bash
curl -s -X DELETE \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: $API_KEY" \
  -d '{"post_codes":["01001","79000"]}' \
  http://localhost:8080/api/post-codes
```

---

## Importer (CLI)

Use the CLI to import postal codes from a ZIP archive:

```bash
make import
```

Requirements:
- Database connection configured in `.env` (used by `config/database.php`)
- The ZIP format must be compatible with `ImportPostCodeService`

On success it prints: `Import completed successfully.` and exits with code 0.

---

## Quality

Code style:

```bash
./vendor/bin/phpcs -q --standard=phpcs.xml src tests
```

---

## Troubleshooting

- Ensure `.env` is present and `API_KEY` is set; otherwise API calls return 403.
- When using Docker, make sure containers are running: `docker ps` should show `slim_app` and `slim_mysql`.
- If migrations fail, verify DB credentials in `.env` and that the database exists.
- Logs are stored in `logs/` (mounted volume in Docker). Check for runtime errors there.

---

## License

MIT

# Student Records API — PHP 8+ and PDO

A runnable learning project based on the **PHP CRUD API using PDO** IT 110 guide.

## Included

- `database/schema.sql` — creates the `student_api` database and `students` table.
- `config/database.php` — PDO connection configuration.
- `api/students.php` — GET, POST, PUT, and DELETE endpoint.
- `students_25.json` — small illustrative sample dataset.
- `scripts/import_students.php` — one-time command-line JSON importer.
- `.env.example` — environment variable reference (no dotenv package required).

## Requirements

- PHP 8.0+ (PHP 8.1+ recommended for the importer)
- PHP extensions: `pdo_mysql` and `mbstring`
- MySQL or MariaDB
- Postman, curl, or another REST client

Check PHP and extensions:

```bash
php -v
php -m
```

Confirm that both `pdo_mysql` and `mbstring` appear in the module list.

## 1. Create the database

Import `database/schema.sql` using phpMyAdmin or the MySQL command line:

```bash
mysql -u root -p < database/schema.sql
```

For a local XAMPP installation with an empty root password, omit `-p` if appropriate for your setup.

## 2. Configure the connection

The defaults in `config/database.php` are:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `student_api`
- Username: `root`
- Password: empty

Change these values or set the `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables. Do not commit real credentials.

## 3. Start the PHP development server

Run this command from the `student-api` project directory:

```bash
php -S 127.0.0.1:8000
```

The endpoint is:

```text
http://127.0.0.1:8000/api/students.php
```

## 4. Import the sample records (optional)

The included JSON contains **three illustrative records**, not the original 25-record class dataset referenced in the guide. Replace `students_25.json` with the supplied class JSON if you have it.

Run the importer once, from the project root:

```bash
php scripts/import_students.php
```

The import runs in a database transaction. Running it again inserts duplicates; use an empty table or clear the existing test records first. Do not expose the importer as a public endpoint.

## 5. API routes

This implementation follows the guide's query-parameter routing:

| Operation | Method | URL |
|---|---|---|
| Read all | GET | `/api/students.php` |
| Read one | GET | `/api/students.php?id=1` |
| Create | POST | `/api/students.php` |
| Replace/update | PUT | `/api/students.php?id=1` |
| Delete | DELETE | `/api/students.php?id=1` |

### Student JSON fields

```json
{
  "first_name": "Alyssa",
  "middle_initial": "M",
  "last_name": "Reyes",
  "program": "BSIT",
  "year_level": 1
}
```

Allowed programs: `BSIT`, `BSIS`, `BSCS`. Allowed year levels: integers `1` through `4`. `middle_initial` may be an empty string or `null`.

### Create — POST

```bash
curl -i -X POST http://127.0.0.1:8000/api/students.php \
  -H "Content-Type: application/json" \
  -d '{"first_name":"Alyssa","middle_initial":"M","last_name":"Reyes","program":"BSIT","year_level":1}'
```

### Read all — GET

```bash
curl -i http://127.0.0.1:8000/api/students.php
```

### Read one — GET

```bash
curl -i "http://127.0.0.1:8000/api/students.php?id=1"
```

### Update — PUT

PUT is a full replacement of the editable fields. Send all five fields:

```bash
curl -i -X PUT "http://127.0.0.1:8000/api/students.php?id=1" \
  -H "Content-Type: application/json" \
  -d '{"first_name":"Alyssa","middle_initial":"M","last_name":"Reyes","program":"BSCS","year_level":2}'
```

### Delete — DELETE

```bash
curl -i -X DELETE "http://127.0.0.1:8000/api/students.php?id=1"
```

## 6. Expected status codes

| Status | Meaning |
|---|---|
| 200 | Request succeeded |
| 201 | Student created |
| 204 | OPTIONS preflight handled |
| 400 | Malformed JSON or invalid/missing ID |
| 404 | Student not found |
| 405 | Unsupported HTTP method |
| 422 | JSON is valid but field validation failed |
| 500 | Database/server error |

## 7. Notes before deployment

This is a teaching implementation, not a production-ready public service. Before deployment:

- Add authentication and authorization, especially for POST, PUT, and DELETE.
- Use a least-privilege database account and protected environment variables.
- Configure CORS for specific trusted origins only, if cross-origin browser access is needed.
- Use HTTPS, rate limiting, request-size limits, and audit logging.
- Keep configuration and one-time scripts outside the public document root where possible.
- Review server logs; do not expose raw database errors to API clients.

## 8. Troubleshooting

- **could not find driver** — enable/install `pdo_mysql` for the PHP runtime serving the project.
- **Access denied** — check database username, password, host, port, and grants.
- **Unknown database** — execute `database/schema.sql`.
- **404** — check the project directory and endpoint URL.
- **Empty JSON body** — send `Content-Type: application/json` and valid raw JSON.
- **500** — inspect the PHP/server error log; technical details are intentionally not returned to clients.
- **mb_strlen undefined** — enable the PHP `mbstring` extension.

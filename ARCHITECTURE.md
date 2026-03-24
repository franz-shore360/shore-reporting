# Shore Reporting – Layered Architecture

This document describes the Laravel 11 layered architecture and how to run the project.

## Architecture: Controller → Service → Repository → Model

| Layer        | Responsibility |
|-------------|----------------|
| **Controller** | HTTP request/response, calls Services, no business logic, no direct Eloquent |
| **Service**    | Business logic, coordinates repositories |
| **Repository** | All database access via Eloquent, no business logic |
| **Model**      | Eloquent model (database representation) |

## Folder Structure

```
app/
├── Http/
│   ├── Controllers/          # Handle HTTP, delegate to Services
│   │   ├── Controller.php
│   │   └── UserController.php
│   └── Requests/             # FormRequest validation
│       ├── StoreUserRequest.php
│       └── UpdateUserRequest.php
├── Models/
│   └── User.php
├── Repositories/
│   ├── Interfaces/           # Repository contracts
│   │   └── UserRepositoryInterface.php
│   └── UserRepository.php
└── Services/
    └── UserService.php

config/
├── database.php              # MySQL driver configured via .env
└── ...

database/
└── migrations/
    ├── 2014_10_12_000000_create_users_table.php
    └── ...

routes/
├── api.php                   # API routes (e.g. User resource)
└── ...
```

## Database Configuration (MySQL/MariaDB)

Use the `mysql` driver. Example `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shorereporting
DB_USERNAME=root
DB_PASSWORD=
```

Create the database before running migrations:

```sql
CREATE DATABASE shorereporting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Dependency Injection

Repositories are bound to their interfaces in `AppServiceProvider`:

```php
$this->app->bind(
    UserRepositoryInterface::class,
    UserRepository::class
);
```

Controllers and Services receive dependencies via constructor injection.

## Example: User API Routes

| Method   | URI              | Action  |
|----------|------------------|---------|
| GET      | /api/users       | index   |
| POST     | /api/users       | store   |
| GET      | /api/users/{user}| show    |
| PUT/PATCH| /api/users/{user}| update  |
| DELETE   | /api/users/{user}| destroy |

## Steps to Run Migrations

1. **Copy environment file** (if not already done):

   ```bash
   cp .env.example .env
   ```

2. **Set database variables in `.env`** as above (`DB_DATABASE=shorereporting`, etc.).

3. **Generate application key** (if not set):

   ```bash
   php artisan key:generate
   ```

4. **Run migrations**:

   ```bash
   php artisan migrate
   ```

5. **Optional: rollback**:

   ```bash
   php artisan migrate:rollback
   ```

## Coding Standards

- PSR-12
- Dependency injection for Controllers, Services, and Repositories
- Eloquent used only in Repositories
- Controllers stay thin (validation via FormRequest, logic in Service)

# Laravel 12 Task API

A RESTful API built with Laravel 12 for learning modern Laravel development patterns.

## Tech Stack

- **Laravel 12** - PHP web framework
- **Laravel Sanctum 4** - API authentication with tokens
- **Scramble 0.13** - Automatic OpenAPI documentation
- **Pest 4** - Modern PHP testing framework

## Quick Start

```bash
# Install dependencies
composer install
```

## Development Commands

### Daily Development

```bash
# Start the API server (http://127.0.0.1:8000)
php artisan serve

# Alternative: Use composer script
composer run dev
```

### Testing & Quality

```bash
# Run all tests
composer run test

# Run PHPStan type checking
composer run phpstan

# Run both PHPStan + tests
composer run check

# Format code with Laravel Pint
vendor/bin/pint
```

## API Documentation

- **API Base**: http://127.0.0.1:8000/api/v1
- **API Documentation**: http://127.0.0.1:8000/docs/api

<img width="1300" height="873" alt="image" src="https://github.com/user-attachments/assets/953a13a5-2ce4-4e07-ae40-57015bb6cd36" />

### API Versioning

All endpoints use the `/api/v1/` prefix:
- `/api/v1/register`
- `/api/v1/login`
- `/api/v1/tasks`

### Authentication

1. Register or login at `/api/v1/register` or `/api/v1/login` to get a token
2. In the docs UI, enter your token in the Bearer token input field
3. Format: `YOUR_TOKEN_HERE` (no "Bearer" prefix needed in the input)

## Rate Limiting

- **Register**: 10 attempts/minute per IP
- **Login**: 10 attempts/minute per IP
- **General API**: 60 requests/minute per user/IP

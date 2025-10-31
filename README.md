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

# Start development server
composer run dev

# Run tests
php artisan test
```

## API Documentation

**Interactive Docs**: [http://localhost:8000/docs/api](http://localhost:8000/docs/api)

Scramble automatically generates OpenAPI documentation from your code - no manual maintenance needed!

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

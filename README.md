<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Development Setup

### API Documentation

This API is documented using **[Scramble](https://scramble.dedoc.co/)** - an automatic OpenAPI documentation generator for Laravel that requires zero configuration.

#### Accessing the Documentation

Once the development server is running, you can access:

- **Interactive Documentation UI**: [http://localhost:8000/docs/api](http://localhost:8000/docs/api)
  - Beautiful Stoplight Elements interface
  - Try out endpoints directly in the browser
  - See request/response examples
  - View validation rules and error responses

- **OpenAPI Specification**: [http://localhost:8000/docs/api.json](http://localhost:8000/docs/api.json)
  - Download the raw OpenAPI 3.1.0 JSON spec
  - Import into Postman, Insomnia, or other API clients
  - Use for code generation or testing tools

#### Authentication in Documentation

Protected endpoints require a Bearer token:

1. First, use the `/api/register` or `/api/login` endpoint to get a token
2. Copy the token from the response
3. Click the "Authorize" button in the documentation UI
4. Enter your token in the format: `Bearer YOUR_TOKEN_HERE`
5. All subsequent requests will include your authentication

#### How It Works

Scramble automatically generates documentation by analyzing your code:

- **Routes**: Reads your `routes/api.php` file
- **Validation**: Extracts rules from FormRequest classes
- **Responses**: Infers structure from API Resources
- **Types**: Uses PHP type hints for accuracy
- **Descriptions**: Reads PHPDoc comments from controllers

**No manual maintenance required** - the docs update automatically when your code changes!

#### Rate Limiting

The API implements rate limiting to prevent abuse:

- **Register endpoint**: 3 attempts per minute per IP address
- **Login endpoint**: 5 attempts per minute per IP address
- **429 Too Many Attempts** response when limits are exceeded

### Pest Testing with Full IDE Support

This project uses [Pest 4](https://pestphp.com/) for testing with the [Laravel plugin](https://pestphp.com/docs/plugins#laravel) for enhanced testing capabilities.

#### ✅ Recommended: Use Namespaced Functions (Production-Ready)

The **official Pest way** - clean, works with all IDEs, and production-ready:

```php
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson};
use Laravel\Sanctum\Sanctum;

test('authenticated users can create tasks', function () {
    Sanctum::actingAs(User::factory()->create());
    
    postJson('/api/tasks', ['title' => 'Test Task'])
        ->assertStatus(201)
        ->assertJsonStructure(['id', 'title']);
});

test('guests cannot access protected routes', function () {
    getJson('/api/user')->assertStatus(401);
});
```

**Available namespaced functions:**
```php
use function Pest\Laravel\{
    // HTTP requests
    get, post, put, patch, delete,
    getJson, postJson, putJson, patchJson, deleteJson,
    
    // Authentication
    actingAs, assertAuthenticated, assertGuest,
    
    // Headers
    withHeader, withHeaders, withoutHeader,
    
    // Database
    assertDatabaseHas, assertDatabaseMissing,
    
    // ... ALL Laravel test helpers!
};
```

**Why use namespaced functions?**
- ✅ Official Pest best practice
- ✅ Works with ALL IDEs (PhpStorm, Intelephense, Phpactor)
- ✅ No type hints needed
- ✅ Cleaner, more functional code style
- ✅ Production-ready (used by Laravel community)

#### Alternative: Traditional $this-> Style

You can still use the traditional approach if preferred:

```php
test('my test', function () {
    $this->postJson('/api/endpoint')->assertStatus(200);
});
```

**For Intelephense users only:** If using `$this->` and you need IDE support, you can optionally add type hints during development:

```php
test('my test', function () {
    /** @var \Tests\TestCase $this */
    $this->postJson('/api/endpoint')->assertStatus(200);
});
```

However, **namespaced functions are strongly recommended** as they eliminate the need for type hints entirely.

#### IDE Helper Command (Optional)

Generate PHPDoc annotations for the TestCase class (useful for reference):

```bash
php artisan ide-helper:pest
```

This generates 442+ method annotations for IDE autocomplete when using the `$this->` style.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

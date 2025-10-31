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

### Hexlet tests and linter status:
[![Actions Status](https://github.com/Segodnya/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Segodnya/php-project-9/actions)
[![PHP CI](https://github.com/Segodnya/php-project-9/actions/workflows/php-ci.yml/badge.svg)](https://github.com/Segodnya/php-project-9/actions/workflows/php-ci.yml)

[![Quality gate](https://sonarcloud.io/api/project_badges/quality_gate?project=Segodnya_php-project-9)](https://sonarcloud.io/summary/new_code?id=Segodnya_php-project-9)

# Page Analyzer

Page Analyzer is a web application that allows you to analyze specified pages for SEO suitability.

https://php-project-9-zfib.onrender.com/

## Features

- Add URLs to the system for analysis
- Check URLs for SEO parameters (status code, H1, title, description)
- View the history of checks for each URL
- Modern MVC architecture with Slim framework
- Automatic database migrations on startup

## Requirements

* PHP 8.0 or higher
* Composer
* SQLite (for local development) or PostgreSQL (for production)

## Installation

Clone the repository and install dependencies:

```bash
git clone https://github.com/Segodnya/php-project-9.git
cd php-project-9
make install
```

## Usage

### Local Development

For local development, the application uses SQLite by default:

```bash
# Set up the database
make setup

# Start the application
make start
```

Then open http://localhost:8080 in your browser.

### Production Deployment

For production, configure the database connection using the `DATABASE_URL` environment variable in the `.env` file:

```
DATABASE_URL=postgresql://username:password@host:port/database
```

The application will automatically connect to the PostgreSQL database specified in the DATABASE_URL environment variable and run the necessary migrations on startup.

#### Database Migrations

To manually run database migrations, use the provided script:

```bash
# Run database migrations
make migrate

# Simulate migrations without executing them (dry-run)
make migrate-dry-run
```

### Docker Deployment

You can also run the application using Docker:

```bash
# Build the Docker image
make docker-build

# Run with Docker (optionally providing a DATABASE_URL)
make docker-run
# or
docker run -p 8080:8080 -e DATABASE_URL=postgresql://username:password@host:port/database page-analyzer
```

## Render.com Deployment

This application is ready to be deployed on Render.com.

1. Create a new Web Service pointing to your repository
2. Set the environment variable `DATABASE_URL` if you want to use PostgreSQL
3. The Dockerfile will be automatically detected and used for deployment

## Project Structure

- `public/index.php` - Main application entry point
- `src/` - Application source code
  - `Controllers/` - Route controllers
  - `Services/` - Business logic services
  - `Database/` - Database interaction
  - `routes.php` - Route definitions
  - `dependencies.php` - DI Container configuration
  - `middleware.php` - Application middleware
- `views/` - Twig templates
- `database.sql` - SQL schema for the database

## Development

```bash
# Check code style
make lint

# Fix code style issues
make lint-fix

# Run static analysis
make phpstan

# Run all code quality checks
make check

# Check database connection
make db-check

# Reset database to initial state
make reset-db
```

## Error Handling

The application implements a robust error handling approach:

1. **Generic User Messages**: When errors occur, users only see generic error messages to avoid exposing sensitive information.

2. **Comprehensive Logging**: Detailed error information including exception stack traces is logged to:
   - In development: The standard PHP error log
   - In production: A dedicated app.log file in the `/logs` directory

3. **Using the Logger Service**: The application provides a centralized `LoggerService` for consistent logging throughout the codebase:

```php
// Example of logging an error with exception details
$this->logger->error('Error message', $exception);

// Example of logging an informational message
$this->logger->info('Information message');

// Example of logging a warning
$this->logger->warning('Warning message');
```

This approach ensures that developers have access to detailed error information for debugging while maintaining security by not exposing sensitive details to end users.

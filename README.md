### Hexlet tests and linter status:
[![Actions Status](https://github.com/Segodnya/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Segodnya/php-project-9/actions)
[![PHP CI](https://github.com/Segodnya/php-project-9/actions/workflows/php-ci.yml/badge.svg)](https://github.com/Segodnya/php-project-9/actions/workflows/php-ci.yml)

# Page Analyzer

Page Analyzer is a web application that analyzes specified pages for SEO suitability.

Page Analyzer is available at [https://php-project-9-zfib.onrender.com/](https://php-project-9-zfib.onrender.com/).

## Requirements

* PHP 8.1+ or Docker
* Composer (for local development)
* PostgreSQL (for local development)

## Installation

### Local Installation (requires PHP 8.1+ and PostgreSQL)

```bash
git clone https://github.com/Segodnya/php-project-9.git
cd php-project-9
make install

# Set up the database
cp .env.example .env
# Edit .env to match your PostgreSQL credentials
make setup-db
```

### Using Docker (recommended)

```bash
git clone https://github.com/Segodnya/php-project-9.git
cd php-project-9
make docker-build
```

## Usage

### Running Locally (requires PHP 8.1+ and PostgreSQL)

```bash
# Make sure your PostgreSQL server is running
make start
```

Then open http://localhost:8000 in your browser.

### Running with Docker (recommended)

```bash
make docker-start
```

This will start both the PHP application and a PostgreSQL database container.
Then open http://localhost:8000 in your browser.

To access the application container shell:

```bash
make docker-exec
```

To access the PostgreSQL database:

```bash
make docker-psql
```

### Database Configuration

The application uses PostgreSQL for data storage. The connection is configured using the `DATABASE_URL` environment variable:

```
DATABASE_URL=postgresql://username:password@host:port/database
```

When running with Docker, this is automatically configured. For local development, copy `.env.example` to `.env` and update the values to match your PostgreSQL instance.

To manually initialize or update the database schema:

```bash
# Local
make load-db

# Docker
make docker-psql
\i database.sql
```

### Docker Development Commands

If you need to rebuild the Docker image (e.g., after adding new dependencies):

```bash
make docker-rebuild
```

To run Composer commands inside the Docker container:

```bash
make docker-install  # Run composer install in the container
```

## Troubleshooting

### Database Connectivity Issues

- Check that your PostgreSQL server is running
- Verify the `DATABASE_URL` environment variable is correctly set
- For Docker, ensure both the `app` and `postgres` services are running with `docker-compose ps`

### Issues with vendor directory

If you encounter errors related to missing vendor files or autoloading issues, try:

```bash
make docker-rebuild
make docker-start
```

This will rebuild the Docker image and properly install all dependencies.

## Development

Lint check:

```bash
make lint
```

Fix linting issues:

```bash
make lint-fix
```

## Refactoring Plan

Based on analysis of the current codebase, here is a step-by-step plan to refactor the application for improved maintainability and extensibility:

### 1. Implement MVC Architecture

- **Create directory structure**:
  - `/src/Controllers` - Handle HTTP requests and responses
  - `/src/Models` - Data and business logic
  - `/src/Services` - Business logic services
  - `/src/Repositories` - Database operations
  - `/src/Validation` - Input validation rules
  - `/src/Middleware` - Request/response middleware
  - `/src/Exceptions` - Custom exception classes

- **Move and refactor code**:
  - Move all route handlers from `public/index.php` to dedicated controller classes
  - Create a proper router configuration file

### 2. Implement Dependency Injection

- **Create a Service Container**:
  - Use PSR-11 compatible container 
  - Configure services in a dedicated configuration file
  - Replace direct class instantiation with container resolution

### 3. Improve Database Layer

- **Implement Repository Pattern**:
  - Create a base Repository interface
  - Extract common database methods to abstract classes
  - Implement specific repositories for each entity

- **Implement Entity Objects**:
  - Create proper entity classes for Url and UrlCheck
  - Implement getter/setter methods
  - Add type hints and validation

### 4. Refactor Validator and Analyzer

- **Create dedicated Service classes**:
  - Move URL validation logic to UrlValidator service
  - Refactor Analyzer to be more modular and testable
  - Implement proper exception handling and error messaging

### 5. Implement Proper HTTP Request/Response Handling

- **Use PSR-7 Request/Response objects consistently**:
  - Create response builders for common response types
  - Standardize error responses

### 6. Refactor Templates

- **Implement a View Service**:
  - Create a dedicated service for rendering views
  - Organize templates by feature
  - Extract common UI components to partials
  - Implement view helpers for common UI patterns

### 7. Implement Error Handling

- **Create error handlers**:
  - Custom exception classes
  - Dedicated error pages
  - Proper logging

### 8. Add Unit and Integration Tests

- **Create test structure**:
  - Unit tests for each component
  - Integration tests for key features
  - Database tests with in-memory SQLite

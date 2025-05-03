### Hexlet tests and linter status:
[![Actions Status](https://github.com/Segodnya/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Segodnya/php-project-9/actions)

# Page Analyzer

Page Analyzer is a web application that analyzes specified pages for SEO suitability.

Page Analyzer is available at [https://php-project-9-zfib.onrender.com/](https://php-project-9-zfib.onrender.com/).

## Requirements

* PHP 8.1+ or Docker
* Composer (for local development)
* PostgreSQL (for local development)

## Installation

### Local Installation (requires PHP 8.4.6 and PostgreSQL)

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

### Running Locally (requires PHP 8.4.6 and PostgreSQL)

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

## Testing

The application includes a comprehensive test suite. To run the tests:

```bash
# Run all tests
make test

# Run only unit tests
make test-unit

# Run tests with code coverage report
make test-coverage
```

For Docker environments:

```bash
# Run tests in Docker
make docker-test
```

For more details about the testing framework, see [tests/README.md](tests/README.md).

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

Based on analysis of the current codebase, here is a step-by-step plan to refactor the application for improved maintainability and extensibility, reduce code duplication and improve code readability.
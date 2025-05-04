### Hexlet tests and linter status:
[![Actions Status](https://github.com/Segodnya/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Segodnya/php-project-9/actions)

# Page Analyzer

Page Analyzer is a web application that allows you to analyze specified pages for SEO suitability.

https://php-project-9-zfib.onrender.com/

## Features

- Add URLs to the system for analysis
- Check URLs for SEO parameters (status code, H1, title, description)
- View the history of checks for each URL
- Simple, single-file PHP application

## Requirements

* PHP 8.2 or higher
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

For production, configure the database connection using the `DATABASE_URL` environment variable:

```
DATABASE_URL=postgresql://username:password@host:port/database
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

- `public/index.php` - Main application file containing all the code
- `public/views/` - HTML views
- `database.sql` - SQL schema for the database

## Development

```bash
# Check code style
make lint

# Fix code style issues
make lint-fix

# Check database connection
make db-check
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

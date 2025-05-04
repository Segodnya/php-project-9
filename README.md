### Hexlet tests and linter status:
[![Actions Status](https://github.com/Segodnya/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Segodnya/php-project-9/actions)

# Page Analyzer

Page Analyzer is a web application that analyzes specified pages for SEO suitability.

Page Analyzer is available at [https://php-project-9-zfib.onrender.com/](https://php-project-9-zfib.onrender.com/).

## Requirements

* PHP 8.3.20 or higher
* Composer

## Installation

```bash
git clone https://github.com/Segodnya/php-project-9.git
cd php-project-9
make install
```

## Usage

### Running Locally

The application uses SQLite by default in development, with no additional configuration required.

```bash
# Start the application
make start
```

Then open http://localhost:8080 in your browser.

### Database Configuration

For local development, the application automatically uses SQLite with a `database.sqlite` file created in the project root directory. No configuration is needed.

For production environments, configure the database connection using the `DATABASE_URL` environment variable:

```
DATABASE_URL=postgresql://username:password@host:port/database
```

Example:
```
DATABASE_URL=postgresql://janedoe:mypassword@localhost:5432/mydb
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

1. **Implement Data Transfer Objects (DTOs)**
   - Create DTOs for clear data passing between layers
   - Replace direct array usage with typed objects
   - Improve type safety and code readability

2. **Enhance Error Handling**
   - Implement centralized error handling middleware
   - Create specific exception classes for different error types
   - Add proper logging throughout the application

3. **Extract Business Logic from Controllers**
   - Move business logic from controllers to dedicated service classes
   - Controllers should only be responsible for HTTP request/response handling
   - Implement the Command pattern for complex operations

4. **Implement Repository Pattern Consistently**
   - Standardize interface for all repositories
   - Add caching layer for frequently accessed data
   - Introduce unit of work pattern for transaction management

5. **Apply SOLID Principles**
   - Single Responsibility: Split large classes into smaller, focused ones
   - Open/Closed: Use interfaces and abstractions for extensibility
   - Dependency Inversion: Inject dependencies rather than creating them

6. **Improve Service Layer**
   - Create domain-specific services with clear responsibilities
   - Reduce direct dependency on external libraries
   - Implement adapter pattern for external services

7. **Enhance Testing Strategy**
   - Increase unit test coverage
   - Implement integration tests for critical paths
   - Add contract tests for external dependencies

8. **Optimize Database Interactions**
   - Review and optimize SQL queries
   - Implement database migrations
   - Add indexes for frequently queried columns

9. **Standardize Configuration**
   - Centralize configuration management
   - Implement environment-specific configuration
   - Use dependency injection for configuration

10. **Documentation Improvements**
    - Add comprehensive PHPDoc comments
    - Create API documentation
    - Improve code organization with consistent naming conventions

The refactoring should be done incrementally, with thorough testing after each step to ensure functionality is maintained.
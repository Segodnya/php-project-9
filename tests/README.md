# Testing Framework for Page Analyzer

This directory contains the testing infrastructure for the Page Analyzer application.

## Testing Structure

- `tests/` - Base directory for all tests
  - `Unit/` - Unit tests for individual components
    - `Models/` - Tests for model classes
    - `Repositories/` - Tests for repository classes
    - `Services/` - Tests for service classes
    - `Validation/` - Tests for validation classes
  - `TestCase.php` - Base TestCase class all tests extend from
  - `bootstrap.php` - Test bootstrap file

## Running Tests

You can run tests using the provided Makefile commands:

```bash
# Run all tests
make test

# Run only unit tests
make test-unit

# Run tests with code coverage report
make test-coverage

# Run a specific test or test group
make test-filter filter=UrlValidatorTest
```

For Docker environments:

```bash
# Run all tests in Docker
make docker-test

# Run only unit tests in Docker
make docker-test-unit

# Run tests with code coverage in Docker
make docker-test-coverage
```

## Test Environment

Tests use an in-memory SQLite database by default instead of PostgreSQL. This makes them fast and isolated from the actual application database.

The `.env.testing` file contains environment variables specific to the testing environment.

## Writing New Tests

1. Create a new test class in the appropriate directory (`Unit/`, etc.)
2. Extend the `Tests\TestCase` base class
3. Implement test methods following PHPUnit conventions:
   - Method names should start with `test`
   - Use assertion methods to verify expected outcomes
   - Use data providers for testing multiple cases

### Example:

```php
<?php

namespace Tests\Unit\YourNamespace;

use Tests\TestCase;
use App\YourNamespace\YourClass;

class YourClassTest extends TestCase
{
    public function testSomeMethod(): void
    {
        $object = new YourClass();
        $result = $object->someMethod();
        
        $this->assertEquals('expected', $result);
    }
}
```

## Mocking

For testing components that have external dependencies, use Mockery to create mock objects:

```php
$mockService = Mockery::mock(Service::class);
$mockService->shouldReceive('method')
    ->once()
    ->with('argument')
    ->andReturn('result');
```

## SQLite Testing Database

The test database is created automatically in memory using SQLite. The schema from `database.sql` is converted to be SQLite-compatible in the base `TestCase` class. 
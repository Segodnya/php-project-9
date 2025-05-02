### Hexlet tests and linter status:
[![Actions Status](https://github.com/Segodnya/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/Segodnya/php-project-9/actions)
[![PHP CI](https://github.com/Segodnya/php-project-9/actions/workflows/php-ci.yml/badge.svg)](https://github.com/Segodnya/php-project-9/actions/workflows/php-ci.yml)

# Page Analyzer

Page Analyzer is a web application that analyzes specified pages for SEO suitability.

## Requirements

* PHP 8.0+ or Docker
* Composer (for local development)

## Installation

### Local Installation (requires PHP 8.0+)

```bash
git clone https://github.com/Segodnya/php-project-9.git
cd php-project-9
make install
```

### Using Docker (recommended)

```bash
git clone https://github.com/Segodnya/php-project-9.git
cd php-project-9
make docker-build
```

## Usage

### Running Locally (requires PHP 8.0+)

```bash
make start
```

Then open http://localhost:8000 in your browser.

### Running with Docker (recommended)

```bash
make docker-start
```

Then open http://localhost:8000 in your browser.

To access the container shell:

```bash
make docker-bash
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
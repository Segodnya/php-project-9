PORT ?= 8080

install:
	composer install

validate:
	composer validate

# Initialize the database schema
setup:
	test -f database.sqlite || (touch database.sqlite && cat database.sql | sed 's/SERIAL PRIMARY KEY/INTEGER PRIMARY KEY AUTOINCREMENT/g' | sed 's/NOW()/CURRENT_TIMESTAMP/g' | sqlite3 database.sqlite)

# Run migrations on the PostgreSQL database (from DATABASE_URL)
migrate:
	php run-migrations.php

# Reset the database by removing the existing one and recreating it
reset-db:
	@echo "Resetting database..."
	@if [ -f database.sqlite ]; then rm database.sqlite; fi
	@make setup
	@echo "Database reset complete"

# Dry run of migrations - show what would happen without executing
migrate-dry-run:
	@echo "Running migration dry-run (simulation) mode..."
	@php -r '$$code = file_get_contents("run-migrations.php"); $$code = str_replace("$$pdo->exec($$statement);", "// Dry-run mode: $$statement; would be executed here", $$code); file_put_contents("run-migrations-dry.php", $$code);'
	@php run-migrations-dry.php
	@rm run-migrations-dry.php

autoload:
	composer dump-autoload

start: reset-db
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

# Run only PHPCS linting
lint:
	php -d vendor/bin/phpcs --standard=phpcs.xml

lint-fix:
	php -d vendor/bin/phpcbf --standard=phpcs.xml

phpstan:
	php -d memory_limit=256M vendor/bin/phpstan analyse -c phpstan.neon

# Run all code quality checks
check: lint phpstan

# Create and connect to the test database
db-check:
	test -f database.sqlite || make setup
	echo "SELECT COUNT(*) FROM urls;" | sqlite3 database.sqlite

docker-build:
	docker build -t page-analyzer .

docker-run: reset-db
	docker run -p $(PORT):8080 -e DATABASE_URL=$(DATABASE_URL) page-analyzer

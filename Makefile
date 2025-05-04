PORT ?= 8080

install:
	composer install

validate:
	composer validate

setup:
	# Initialize the database schema
	test -f database.sqlite || (touch database.sqlite && cat database.sql | sed 's/SERIAL PRIMARY KEY/INTEGER PRIMARY KEY AUTOINCREMENT/g' | sed 's/NOW()/CURRENT_TIMESTAMP/g' | sqlite3 database.sqlite)

start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	php -d error_reporting=E_ALL^E_DEPRECATED vendor/bin/phpcs --standard=phpcs.xml

lint-fix:
	php -d error_reporting=E_ALL^E_DEPRECATED vendor/bin/phpcbf --standard=phpcs.xml

test:
	APP_ENV=testing php vendor/bin/phpunit

test-coverage:
	APP_ENV=testing XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-html coverage

db-check:
	# Create and connect to the test database
	test -f database.sqlite || make setup
	echo "SELECT COUNT(*) FROM urls;" | sqlite3 database.sqlite

docker-build:
	docker build -t page-analyzer .

docker-run:
	docker run -p $(PORT):8080 -e DATABASE_URL=$(DATABASE_URL) page-analyzer

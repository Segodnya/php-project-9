PORT ?= 8000
DB_URL ?= postgresql://postgres:mypassword@localhost:5432/mydb

install:
	composer install

start:
	PHP_CLI_SERVER_WORKERS=5 php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" -S 0.0.0.0:$(PORT) -t public

lint:
	php -d error_reporting=E_ALL^E_DEPRECATED vendor/bin/phpcs --standard=PSR12 public src

lint-fix:
	php -d error_reporting=E_ALL^E_DEPRECATED vendor/bin/phpcbf --standard=PSR12 public src

setup-db:
	createdb mydb || true
	psql -a -d $(DB_URL) -f database.sql

load-db:
	psql -a -d $(DB_URL) -f database.sql

test:
	APP_ENV=testing php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit || true

test-unit:
	APP_ENV=testing php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit --testsuite=Unit || true

test-coverage:
	APP_ENV=testing XDEBUG_MODE=coverage php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit --coverage-html coverage || true

test-filter:
	APP_ENV=testing php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit --filter=$(filter) || true

docker-build:
	docker-compose build

docker-start:
	docker-compose up

docker-start-d:
	docker-compose up -d

docker-stop:
	docker-compose down

docker-bash:
	docker-compose run --rm app bash

docker-install:
	docker-compose run --rm app composer install --ignore-platform-reqs

docker-update:
	docker-compose run --rm app composer update --ignore-platform-reqs

docker-rebuild:
	docker-compose build --no-cache

docker-exec:
	docker-compose exec app bash

docker-psql:
	docker-compose exec postgres psql -U postgres -d mydb

docker-test:
	docker-compose exec app php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit || true

docker-test-unit:
	docker-compose exec app php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit --testsuite=Unit || true

docker-test-coverage:
	docker-compose exec app php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" -d xdebug.mode=coverage vendor/bin/phpunit --coverage-html coverage || true

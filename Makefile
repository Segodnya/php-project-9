PORT ?= 8000
DB_URL ?= postgresql://janedoe:mypassword@localhost:5432/mydb

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
	docker-compose run --rm app composer install

docker-rebuild:
	docker-compose build --no-cache

docker-exec:
	docker-compose exec app bash

docker-psql:
	docker-compose exec postgres psql -U postgres -d mydb

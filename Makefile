PORT ?= 8000

install:
	composer install

start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public src

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 public src

docker-build:
	docker-compose build

docker-start:
	docker-compose up

docker-bash:
	docker-compose run --rm app bash

docker-install:
	docker-compose run --rm app composer install

docker-rebuild:
	docker-compose build --no-cache 
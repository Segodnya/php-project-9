PORT ?= 8080

install:
	composer install

start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	php -d error_reporting=E_ALL^E_DEPRECATED vendor/bin/phpcs --standard=PSR12 public src

lint-fix:
	php -d error_reporting=E_ALL^E_DEPRECATED vendor/bin/phpcbf --standard=PSR12 public src

db-check:
	php public/db-test.php

test:
	APP_ENV=testing php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit || true

test-unit:
	APP_ENV=testing php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit --testsuite=Unit || true

test-coverage:
	APP_ENV=testing XDEBUG_MODE=coverage php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit --coverage-html coverage || true

test-filter:
	APP_ENV=testing php -d error_reporting="E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" vendor/bin/phpunit --filter=$(filter) || true

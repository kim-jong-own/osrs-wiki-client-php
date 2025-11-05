.PHONY: help

# List all available Makefile commands.
help:
	@echo "Available commands:"
	@echo "   make help                  : List all available Makefile commands"
	@echo "   make setup-php             : Start the dev environment with PHP 8.4"
	@echo "   make shell                 : Get an interactive shell on the PHP container"
	@echo "   make static-analysis       : Run Static Analysis (PHPStan)"
	@echo "   make coding-standards      : Run Coding Standards (PHP-CS-Fixer)"
	@echo "   make start-containers      : Start the dev environment"
	@echo "   make stop-containers       : Stop the dev environment"
	@echo "   make kill-containers       : Stop and remove all containers"
	@echo "   make composer-install      : Install composer dependencies"

# Typing 'make setup' will start the dev environment with PHP 8.4
setup: stop-containers start-containers composer-install

# Get a shell on the PHP container
shell:
	docker compose exec -it osrs-wiki-client /bin/bash

# Run Static Analysis (PHPStan)
static-analysis:
	docker compose exec osrs-wiki-client ./vendor/bin/phpstan analyse --memory-limit=1G

coding-standards:
	docker compose exec osrs-wiki-client php ./bin/php-cs-fixer-v3.phar fix --config=./.php-cs-fixer.dist.php

# Start the dev environment
start-containers:
	docker compose up -d --build

# Stop the dev environment
stop-containers:
	docker compose down

# Stop and remove all containers
kill-containers:
	docker compose kill
	docker compose rm --force

# Install composer dependencies
composer-install:
	docker compose exec osrs-wiki-client composer install --no-interaction

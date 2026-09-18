.PHONY: up down build logs test pint serve shell install scan

SERVICE = app
DOCKER_RUN = docker compose run --rm -T $(SERVICE)

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

logs:
	docker compose logs -f

install:
	$(DOCKER_RUN) composer install

test:
	$(DOCKER_RUN) ./vendor/bin/phpunit

pint:
	$(DOCKER_RUN) ./vendor/bin/pint

serve:
	docker compose exec $(SERVICE) php artisan serve --host=0.0.0.0 --port=8000

shell:
	docker compose exec $(SERVICE) bash

scan:
	$(DOCKER_RUN) php artisan checkout:scan $(ITEMS)
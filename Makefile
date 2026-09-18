.PHONY: up down build migrate test lint

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

migrate:
	docker compose run --rm app php scripts/migrate.php

test:
	docker compose run --rm app composer test

lint:
	docker compose run --rm app composer lint

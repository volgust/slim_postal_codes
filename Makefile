.PHONY: migrate migrate-down migrate-status

APP_CONTAINER=$(shell docker ps --filter "ancestor=postal_codes-slim" --format "{{.Names}}" | head -n 1)

migrate:
	docker exec -it $(APP_CONTAINER) php bin/migrate.php up

migrate-down:
	docker exec -it $(APP_CONTAINER) php bin/migrate.php down

migrate-status:
	docker exec -it $(APP_CONTAINER) php bin/migrate.php status
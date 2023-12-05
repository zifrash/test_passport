.DEFAULT_GOAL:=help

.PHONY: first-build
first-build: build-without-cache start composer-self-update composer-packages-install migrations-migrate ## Команда первого запуска, запустит наши образы (php, nginx, postgres), и произведет первичную настройку. Так же запустит composer install.

.PHONY: hard-rebuild
hard-rebuild: composer-packages-remove destroy first-build ## Удалит папку vendor (composer), а так же все докер образы. И заново все соберет (first-build).

.PHONY: build-without-cache
build-without-cache: ## Скачает, докер образы без кеша.
	docker compose build --no-cache

.PHONY: build
build: ## Скачает, докер образы.
	docker compose build

.PHONY: destroy
destroy: ## Удалит докер образы.
	docker compose down --rmi all --volumes --remove-orphans

.PHONY: start
start: ## Запустит работу докер образов.
	docker compose up -d

.PHONY: stop
stop: ## Остановит работу докер образов.
	docker compose down

.PHONY: restart
restart: stop start ## Перезапустит докер образы.

.PHONY: rebuild
rebuild: destroy build start ## Переустановит докер образы (destroy build).

.SILENT: composer
composer: ## Передаст arg в php контейнер artisan
	docker compose exec php composer $(arg)

.PHONY: composer-packages-install
composer-packages-install: ## Установит пакеты composer (composer.json).
	docker compose exec php composer install

.PHONY: composer-packages-remove
composer-packages-remove: ## Удалит пакеты composer (папку vendor).
	rm -rf vendor

.SILENT: composer-self-update
composer-self-update: ## Обновить сам composer.
	docker compose exec -u root php chmod 777 /usr/bin/
	docker compose exec php composer self-update
	docker compose exec -u root php chmod 755 /usr/bin/

.PHONY: console
console: ## Передаст arg в php контейнер bin/console
	docker compose exec php php bin/console $(arg)

.SILENT: add-passport-data
add-passport-data: ## Добавляет данные паспортов базу данных
	docker compose exec php php bin/console app:add-passport-data

.SILENT: migrations-migrate
migrations-migrate: ## Запускает миграции
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

.PHONY: help
help:
	@awk 'BEGIN {FS = ":.*##";} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

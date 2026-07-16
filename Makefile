.PHONY: help up down build restart logs shell ps migrate fresh seed test

# Default target
.DEFAULT_GOAL := help

help: ## Tampilkan bantuan ini
	@echo "Lacakeen - Docker Commands"
	@echo "========================"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Jalankan semua container (app, nginx, postgres, redis)
	docker compose up -d
	@echo "✅ Lacakeen running at http://localhost:8080"

up-full: ## Jalankan semua container termasuk queue worker & scheduler
	docker compose --profile full up -d
	@echo "✅ Lacakeen (full) running at http://localhost:8080"

down: ## Hentikan semua container
	docker compose down

build: ## Build ulang image
	docker compose build --no-cache

restart: ## Restart semua container
	docker compose restart

logs: ## Lihat log semua container
	docker compose logs -f

app-logs: ## Lihat log app saja
	docker compose logs -f app

shell: ## Masuk ke shell container app
	docker compose exec app sh

ps: ## Lihat status container
	docker compose ps

migrate: ## Jalankan migration
	docker compose exec app php artisan migrate

fresh: ## Fresh migration dengan seed
	docker compose exec app php artisan migrate:fresh --seed

seed: ## Jalankan db seeder
	docker compose exec app php artisan db:seed

tinker: ## Buka Laravel tinker
	docker compose exec app php artisan tinker

artisan: ## Jalankan artisan command (contoh: make artisan cmd="route:list")
	docker compose exec app php artisan $(cmd)

npm: ## Jalankan npm command (contoh: make npm cmd="install axios")
	docker compose exec app npm $(cmd)

composer: ## Jalankan composer command (contoh: make composer cmd="require laravel/breeze")
	docker compose exec app composer $(cmd)

test: ## Jalankan Pest tests
	docker compose exec app php artisan test

cache-clear: ## Clear semua cache
	docker compose exec app php artisan optimize:clear

prune: ## Hapus volumes & data (hati-hati!)
	docker compose down -v


FRONTEND_DIR = frontend
BACKEND_DIR = backend


all: front back

install:
	cd $(FRONTEND_DIR) && npm install
	cd $(BACKEND_DIR) && composer install

front:
	cd $(FRONTEND_DIR) && npm start

back:
	cd $(BACKEND_DIR) && symfony serve

migrate:
	cd $(BACKEND_DIR) && php bin/console doctrine:migrations:migrate

migration:
	cd $(BACKEND_DIR) && php bin/console make:migration

cache-clear:
	cd $(BACKEND_DIR) && php bin/console cache:clear


schema-validate:
	cd $(BACKEND_DIR) && php bin/console doctrine:schema:validate


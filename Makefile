example:
	composer install
	rm -f example/app/pager.db3
	php example/app/console doctrine:database:create
	php example/app/console doctrine:schema:update --force
	php example/app/console doctrine:fixtures:load --append
	php example/app/console cache:clear
	php example/app/console server:run

test:
	echo "todo"

.PHONY: example test

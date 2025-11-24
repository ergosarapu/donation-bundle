help:                                                                          		## show help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_\-\.]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.PHONY: install
install: composer.json																## install composer dependencies
	composer install

.PHONY: phpunit
phpunit: phpunit-unit phpunit-acceptance phpunit-integration phpunit-functional  	## run all phpunit tests

.PHONY: phpunit-unit
phpunit-unit:																		## run phpunit unit tests
	vendor/bin/phpunit --testdox --testsuite=Unit										

.PHONY: phpunit-acceptance
phpunit-acceptance:																	## run phpunit acceptance tests
	vendor/bin/phpunit --testdox  --testsuite=Acceptance

.PHONY: phpunit-integration
phpunit-integration:																## run phpunit integration tests
	vendor/bin/phpunit --testdox --testsuite=Integration --exclude-filter SubscriptionManagerTest

.PHONY: phpunit-functional
phpunit-functional:																	## run phpunit functional tests
	vendor/bin/phpunit  --testdox --testsuite=Functional --exclude-filter IndexControllerTest

.PHONY: migrate
migrate:																			## run database migrations
	mkdir migrations -p; \
	vendor/bin/doctrine-migrations migrations:diff --no-interaction; \
	vendor/bin/doctrine-migrations migrations:migrate --no-interaction

.PHONY: phpstan
phpstan:																			## run phpstan static analysis
	php phpstan-create-cache.php; \
	vendor/bin/phpstan analyse -v

.PHONY: cs
cs:																					## php-cs-fixer fix
	vendor/bin/php-cs-fixer fix -v

.PHONY: cs-check
cs-check:																					## php-cs-fixer check
	vendor/bin/php-cs-fixer check -v
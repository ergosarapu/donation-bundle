#!/bin/sh
mkdir migrations

# Unit tests
./vendor/bin/phpunit --testdox --testsuite Unit

# Functional and Integration tests
export DATABASE_URL=pdo-mysql://db:db@db/db
./vendor/bin/doctrine-migrations migrations:diff --no-interaction
./vendor/bin/doctrine-migrations migrations:migrate --no-interaction
./vendor/bin/phpunit --testdox --testsuite Functional
./vendor/bin/phpunit --testdox --testsuite Integration

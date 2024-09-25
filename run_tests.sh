#!/bin/sh
mkdir migrations

# Unit tests
./vendor/bin/simple-phpunit --testsuite Unit

# Functional and Integration tests
export DATABASE_URL=pdo-mysql://db:db@db/db
./vendor/bin/doctrine-migrations migrations:diff --no-interaction
./vendor/bin/doctrine-migrations migrations:migrate --no-interaction
./vendor/bin/simple-phpunit --testsuite Functional
./vendor/bin/simple-phpunit --testsuite Integration

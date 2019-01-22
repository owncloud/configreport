SHELL := /bin/bash

COMPOSER_BIN := $(shell command -v composer 2> /dev/null)
ifndef COMPOSER_BIN
    $(error composer is not available on your system, please install composer)
endif

# app definitions
app_name=$(notdir $(CURDIR))
project_directory=$(CURDIR)/../$(app_name)

occ=$(CURDIR)/../../occ

# bin file definitions
PHPUNIT=php -d zend.enable_gc=0  ../../lib/composer/bin/phpunit
PHPUNITDBG=phpdbg -qrr -d memory_limit=4096M -d zend.enable_gc=0 "../../lib/composer/bin/phpunit"
PHP_CS_FIXER=php -d zend.enable_gc=0 vendor-bin/owncloud-codestyle/vendor/bin/php-cs-fixer
PHP_CODESNIFFER=vendor-bin/php_codesniffer/vendor/bin/phpcs

.DEFAULT_GOAL := help

help:
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'

##
## Tests
##--------------------------------------

.PHONY: test-php-unit
test-php-unit:             ## Run php unit tests
test-php-unit:
	$(PHPUNIT) --configuration ./phpunit.xml --testsuite unit

.PHONY: test-php-unit-dbg
test-php-unit-dbg:         ## Run php unit tests using phpdbg
test-php-unit-dbg:
	$(PHPUNITDBG) --configuration ./phpunit.xml --testsuite unit

.PHONY: test-php-codecheck
test-php-codecheck:        ## Run occ app code checks
test-php-codecheck:
	$(occ) app:check-code $(app_name) -c private -c strong-comparison
	$(occ) app:check-code $(app_name) -c deprecation

.PHONY: test-php-style
test-php-style:            ## Run php-cs-fixer and check owncloud code-style
test-php-style: vendor-bin/owncloud-codestyle/vendor vendor-bin/php_codesniffer/vendor
	$(PHP_CS_FIXER) fix -v --diff --diff-format udiff --allow-risky yes --dry-run
	$(PHP_CODESNIFFER) --runtime-set ignore_warnings_on_exit --standard=phpcs.xml tests/acceptance

.PHONY: test-php-style-fix
test-php-style-fix:        ## Run php-cs-fixer and fix code style issues
test-php-style-fix: vendor-bin/owncloud-codestyle/vendor
	$(PHP_CS_FIXER) fix -v --diff --diff-format udiff --allow-risky yes

.PHONY: test-acceptance-api
test-acceptance-api:       ## Run API acceptance tests
test-acceptance-api: vendor/bin/phpunit
	../../tests/acceptance/run.sh --remote --type api

##
## Dependency management
##--------------------------------------

.PHONY: install-php-deps
install-php-deps:          ## Install PHP dependencies
install-php-deps: vendor composer.json composer.lock

vendor: composer.lock
	$(COMPOSER_BIN) install --no-dev

composer.lock:
	$(COMPOSER_BIN) install

vendor/bamarni/composer-bin-plugin: composer.lock
	$(COMPOSER_BIN) install

vendor-bin/owncloud-codestyle/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/owncloud-codestyle/composer.lock
	$(COMPOSER_BIN) bin owncloud-codestyle install --no-progress

vendor-bin/owncloud-codestyle/composer.lock: vendor-bin/owncloud-codestyle/composer.json
	@echo owncloud-codestyle composer.lock is not up to date.

vendor-bin/php_codesniffer/vendor: vendor/bamarni/composer-bin-plugin vendor-bin/php_codesniffer/composer.lock
	composer bin php_codesniffer install --no-progress

vendor-bin/php_codesniffer/composer.lock: vendor-bin/php_codesniffer/composer.json
	@echo php_codesniffer composer.lock is not up to date.

vendor/bin/phpunit: composer.lock
	$(COMPOSER_BIN) install

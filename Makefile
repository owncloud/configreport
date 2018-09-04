SHELL := /bin/bash


# app definitions
app_name=$(notdir $(CURDIR))
project_directory=$(CURDIR)/../$(app_name)

occ=$(CURDIR)/../../occ


# bin file definitions
PHPUNIT=php -d zend.enable_gc=0  ../../lib/composer/bin/phpunit
PHPUNITDBG=phpdbg -qrr -d memory_limit=4096M -d zend.enable_gc=0 "../../lib/composer/bin/phpunit"
PHPLINT=php -d zend.enable_gc=0 ../../lib/composer/bin/parallel-lint

.DEFAULT_GOAL := help

help:
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'



##
## Tests
##--------------------------------------

.PHONY: test-php-unit
test-php-unit:             ## Run php unit tests
test-php-unit:
	$(PHPUNIT) --configuration ./tests/unit/phpunit.xml

.PHONY: test-php-unit-dbg
test-php-unit-dbg:         ## Run php unit tests using phpdbg
test-php-unit-dbg:
	$(PHPUNITDBG) --configuration ./tests/unit/phpunit.xml

.PHONY: test-php-codecheck
test-php-codecheck:        ## Run occ app code checks
test-php-codecheck:
	$(occ) app:check-code $(app_name) -c private -c strong-comparison
	$(occ) app:check-code $(app_name) -c deprecation

.PHONY: test-php-lint
test-php-lint:             ## Run parallel-lint
test-php-lint:
	$(PHPLINT) . --exclude 3rdparty --exclude build .



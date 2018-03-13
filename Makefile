app_name=$(notdir $(CURDIR))
project_directory=$(CURDIR)/../$(app_name)

occ=$(CURDIR)/../../occ


.PHONY: test-php-codecheck
test-php-codecheck:
	$(occ) app:check-code $(app_name) -c private -c strong-comparison
	$(occ) app:check-code $(app_name) -c deprecation

.PHONY: test-php-lint
test-php-lint:
	../../lib/composer/bin/parallel-lint . --exclude 3rdparty --exclude build .


.PHONY: test-php-unit
test-php-unit:
	./tests/run-unit.sh
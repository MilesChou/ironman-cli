#!/usr/bin/make -f

INSTALL_PATH := /usr/local/bin/app

.PHONY: all clean clean-all check test coverage bump

# ---------------------------------------------------------------------

all: clean test

clean:
	rm -rf ./build
	rm -f app.phar

clean-all: clean
	rm -rf ./vendor
	rm -rf ./composer.lock

check:
	php vendor/bin/phpcs

test: clean check
	phpdbg -qrr vendor/bin/phpunit

coverage: test
	@if [ "`uname`" = "Darwin" ]; then open build/coverage/index.html; fi

bump:
	@./scripts/bump-version ${VERSION}

app.phar: bump
	@echo ">>> Building phar ..."
	@composer install --no-dev --optimize-autoloader --quiet
	@php -d phar.readonly=off ./scripts/build
	@chmod +x app.phar.phar
	@echo ">>> Build phar finished."
	@composer install --dev --quiet

install:
	mv schemarkdown.phar ${INSTALL_PATH}

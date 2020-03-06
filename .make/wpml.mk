# Tutorial: http://www.cs.colby.edu/maxwell/courses/tutorials/maketutor/
# Docs: https://www.gnu.org/software/make/

empty:=
space:= $(empty) $(empty)
indent:=$(space)$(space)

# Help
.PHONY: help

help:
	$(info ================================================================================)
	$(info )
	$(info :: Setup)
	$(info )
	$(info  $(indent) Run `make setup` to configure the Git Hooks and install the dependencies`)
	$(info  $(indent) Run `make self-update` to update the Makefile and related scripts)
	$(info )
	$(info :: Dependencies)
	$(info )
	$(info  $(indent) Run `make install` to install the dependencies)
	$(info  $(indent) - Run `make composer-install` to only install Composer dependencies)
	$(info  $(indent) - Run `make npm-install` to only install NPM/Node dependencies)
	$(info  $(indent) Run `make install-prod` to install the NPM/Node dependencies in production mode)
	$(info  $(indent) - Run `make npm-install-prod` to only install NPM/Node dependencies in production mode)
	$(info )
	$(info :: Deployment)
	$(info )
	$(info  $(indent) Run `make dev` to bundle WebPack modules in development mode)
	$(info  $(indent) Run `make prod` to bundle WebPack modules in production mode)
	$(info )
	$(info :: Tests)
	$(info )
	$(info  $(indent) - Run `make tests` to run all tests in all forms and languages)
	$(info  $(indent) - Run `make tests group=groupname` to run tests for a specific group Jest will ignore this argument)
	$(info  $(indent) - Run `make jest` to run only Jest tests)
	$(info  $(indent) - Run `make integration-php` to run only the integration tests in PHP [1].)
	$(info  $(indent) - Run `make unit-php` to run only the unit tests in PHP [1].)
	$(info )
	$(info  $(indent) [1] It also accepts the `group` argument)
	$(info )
	$(info ================================================================================)
	$(info )
	$(info :: Extras)
	$(info  $(indent) Run `make adjust-phpunit [PHP="x.x"]` to adjust PhpUnit so it matches the PHP version. If PHP is omitted, update will be done for current PHP version)

# Setup
.PHONY: setup githooks self-update

setup:: githooks
setup:: install

githooks:
ifdef CI
	$(info Skipping Git Hooks in CI)
else ifdef OS
	cp .githooks/* .git/hooks/
	$(info Looks like you are on Windows... files copied.)

else
	@find .git/hooks -type l -exec rm {} \;
	@find .githooks -type f -exec ln -sf ../../{} .git/hooks/ \;
	$(info Git Hooks installed)
endif

# Install
.PHONY: install

install: composer-install
install: npm-install

install-prod: npm-install-prod

# Git Hooks
.PHONY: precommit

precommit:: validate-composer
precommit:: validate-npm
precommit:: dupes
precommit:: compatibility

# precommit
.PHONY: dupes compatibility validate-composer validate-npm

dupes: composer-install
	./.make/check-duplicates.sh

compatibility: composer-install
	./.make/check-compatibility.sh

validate-composer: composer-install
	./.make/check-composer.sh

validate-npm: npm-install
	./.make/check-npm.sh

# Dependency managers

## Composer
.PHONY: composer-install adjust-phpunit

composer.json composer.lock vendor/autoload.php:
	$(info Installing Composer dependencies)
	@composer install
	@touch $@

composer-install: | composer.json composer.lock vendor/autoload.php

adjust-phpunit:
	./.make/adjust-phpunit.sh ${PHP}

## NPM
.PHONY: npm-install npm-install-prod

package.json package-lock.json node_modules/:
	$(info Installing NPM/Node dependencies)
	@npm install
	@touch $@

npm-install npm-install-prod: | package.json package-lock.json node_modules/

# Build
.PHONY: dev prod

dev prod: npm-install
ifeq  ($(wildcard webpack.config.js), webpack.config.js)
	$(info Bundling modules with WebPack)
	@npm run build:$@
else
	$(info WebPack is not configured: skipping)
endif

# Tests
.PHONY: tests jest integration-php unit-php

phpunit_args= --no-coverage --stop-on-failure --fail-on-risky --fail-on-warning --verbose

ifdef group
$(info PHP tests will wun for $(group) group)
phpunit_args:= $(phpunit_args) --group=$(group)
endif

tests: jest integration-php unit-php

jest: npm-install
	npm run test

integration-php: composer-install
ifeq ($(and $(wildcard composer.json), $(wildcard tests/phpunit/phpunit.xml)),tests/phpunit/phpunit.xml)
	$(info Running PhpUnit from tests/phpunit/phpunit.xml $(group))
	vendor/bin/phpunit $(phpunit_args) --configuration=tests/phpunit/phpunit.xml
else
	$(info Tests in tests/phpunit/phpunit.xml not configured: skipping)
endif

unit-php: composer-install
ifeq ($(and $(wildcard composer.json), $(wildcard phpunit.xml)),phpunit.xml)
	$(info Running PhpUnit from phpunit.xml $(group))
	vendor/bin/phpunit $(phpunit_args)
else
	$(info Tests in phpunit.xml not configured: skipping)
endif

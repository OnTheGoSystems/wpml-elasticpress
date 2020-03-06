#!/bin/bash
# This file updates phpunit library depending on current php version, or using 1st argument.
# Usage:
#   adjust-phpunit.sh - to adjust phpunit library for current php version (or do nothing if already adjusted)
#   adjust-phpunit.sh x.x - to force adjust phpunit library for specific php version, where x.x = 5|7|5.6|7.0|7.1|7.2|7.3|7.4

if [[ $1 == '' ]]; then
  PHP_VERSION=$(php -v | tac | tail -n 1 | cut -d " " -f 2 | cut -c 1-3)

  if grep -qE 'version.+5\.7' 'vendor/phpunit/phpunit/src/Runner/Version.php'; then
    CURRENT_PHP_UNIT='5.7'
  fi

  if grep -qE 'version.+7\.5' 'vendor/phpunit/phpunit/src/Runner/Version.php'; then
    CURRENT_PHP_UNIT='7.5'
  fi

  echo "CURRENT_PHP_UNIT: $CURRENT_PHP_UNIT"
else
  PHP_VERSION=$1
fi

echo "PHP_VERSION: $PHP_VERSION"

if [[ $PHP_VERSION == '5' || $PHP_VERSION == '5.6' || $PHP_VERSION == '7.0' || $PHP_VERSION == '7.1' ]]; then
  PHP_UNIT='5.7'
fi

if [[ $PHP_VERSION == '7' || $PHP_VERSION == '7.2' || $PHP_VERSION == '7.3' || $PHP_VERSION == '7.4' ]]; then
  PHP_UNIT='7.5'
fi

if [[ $PHP_UNIT == '' ]]; then
  echo "Wrong PHP version: $PHP_VERSION"
  exit 1
fi

if [[ $CURRENT_PHP_UNIT == "$PHP_UNIT" ]]; then
  # Do nothing if current version of phpunit is the same as required. Important on CI.
  # Anytime force update available specifying first argument like 'adjust-phpunit.sh 7'
  exit 0
fi

echo "Building with phpunit-$PHP_UNIT"

if [[ $PHP_UNIT == '5.7' ]]; then
  composer config repositories.patchwork '{"type": "vcs", "url": "https://github.com/OnTheGoSystems/patchwork.git", "no-api": true}'
  composer config repositories.collect '{"type": "vcs", "url": "https://github.com/OnTheGoSystems/collect.git", "no-api": true}'
  composer config github-protocols https
  composer config platform.php 5.6

  composer remove --dev antecedent/patchwork otgs/unit-tests-framework sebastian/phpcpd phpunit/phpunit 10up/wp_mock lucatume/function-mocker symfony/dom-crawler symfony/css-selector phpunit/php-timer
  composer require --dev antecedent/patchwork:^2.0 otgs/unit-tests-framework:~1.2.0 sebastian/phpcpd:^3.0 phpunit/phpunit:^5 10up/wp_mock:~0.2.0 lucatume/function-mocker:1.3.4 symfony/dom-crawler:^3.1 symfony/css-selector:^3.1 phpunit/php-timer:^1.0.9
fi

if [[ $PHP_UNIT == '7.5' ]]; then
  composer config repositories.patchwork '{"type": "vcs", "url": "https://github.com/antecedent/patchwork.git", "no-api": true}'
  composer config repositories.collect '{"type": "vcs", "url": "https://github.com/OnTheGoSystems/collect.git", "no-api": true}'
  composer config github-protocols https
  composer config platform.php 7.2.5

  composer remove --dev antecedent/patchwork otgs/unit-tests-framework sebastian/phpcpd phpunit/phpunit 10up/wp_mock lucatume/function-mocker symfony/dom-crawler symfony/css-selector phpunit/php-timer
  composer require --dev antecedent/patchwork:^2.1 otgs/unit-tests-framework:^2.0 sebastian/phpcpd:^4.1 phpunit/phpunit:^7.5 10up/wp_mock:^0.4 lucatume/function-mocker:^1.3 symfony/dom-crawler:^5.0 symfony/css-selector:^5.0 phpunit/php-timer:^2.1
fi

RESULT=$?

# Restore main composer files to the current branch version.
git checkout -- composer.json composer.lock

exit $RESULT

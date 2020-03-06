#!/usr/bin/env bash

if [ $# -lt 3 ]; then
  echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
  exit 1
fi

CWD=$(pwd)

SVN_VERBOSITY_ARGS='--quiet'
WGET_VERBOSITY_ARGS='--quiet'
TAR_VERBOSITY_ARGS=''
UNZIP_VERBOSITY_ARGS='-q'
MYSQL_VERBOSITY_ARGS='-q'
GIT_CLONE_VERBOSITY_ARGS='--quiet'

if [[ ${OTGS_CI_DEBUG_MODE:=0} == 1 ]]; then
  SVN_VERBOSITY_ARGS=''
  WGET_VERBOSITY_ARGS='--verbose'
  TAR_VERBOSITY_ARGS='-v'
  UNZIP_VERBOSITY_ARGS=''
  MYSQL_VERBOSITY_ARGS='-vvv'
  GIT_CLONE_VERBOSITY_ARGS='--verbose'
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5}

if [[ ${WP_VERSION} == "latest" || ${WP_VERSION} == "" ]]; then
  WP_VERSION="master"
  WP_TEST_VERSION="master"
fi

SCRIPTPATH=$(pwd -P)

WP_TESTS_DIR=${SCRIPTPATH}/wordpress-tests-lib
WP_CORE_DIR=${SCRIPTPATH}/wordpress/

set -ex

install_wp() {
  cd ${CWD}

  if [[ ! -d ${WP_CORE_DIR} || ${CI:0} == 1 ]]; then
    rm -rf ${WP_CORE_DIR}
    git clone ${GIT_CLONE_VERBOSITY_ARGS} git://github.com/WordPress/WordPress.git ${WP_CORE_DIR}
  fi

  if [[ ! -d ${WP_CORE_DIR} ]]; then
    echo "${WP_CORE_DIR} does not exists!"
    exit 1
  fi

  cd ${WP_CORE_DIR}

  git checkout ${WP_VERSION}

  if [[ ${WP_TEST_VERSION} == "master" ]]; then
    WP_TEST_VERSION=$(
      cd ${WP_CORE_DIR}
      wp core version --allow-root
    )
  fi

  cd ${CWD}
  #  wget -nv -O ${WP_CORE_DIR}/wp-content/db.php https://raw.github.com/markoheijnen/wp-mysqli/master/db.php
}

install_test_suite() {
  cd ${CWD}

  # portable in-place argument for both GNU sed and Mac OSX sed
  if [[ $(uname -s) == 'Darwin' ]]; then
    local ioption='-i .bak'
  else
    local ioption='-i'
  fi

  # set up testing suite
  if [[ ! -d ${WP_TESTS_DIR} || ${CI:0} == 1 ]]; then
    rm -Rf ${WP_TESTS_DIR}
    mkdir -p ${WP_TESTS_DIR}
  fi

  if [[ ! -d wordpress-develop || ${CI:0} == 1 ]]; then
    rm -rf wordpress-develop
    git clone ${GIT_CLONE_VERBOSITY_ARGS} git://github.com/WordPress/wordpress-develop.git
  fi

  if [[ ! -d wordpress-develop ]]; then
    echo "wordpress-develop does not exists!"
    exit 1
  fi

  cd wordpress-develop

  git checkout ${WP_VERSION}

  cp -rf tests/phpunit/includes ${WP_TESTS_DIR}/includes

  if [[ ! -d ${WP_TESTS_DIR}/includes ]]; then
    echo "includes does not exists!"
    exit 1
  fi

  cp -rf tests/phpunit/data ${WP_TESTS_DIR}/data

  if [[ ! -d ${WP_TESTS_DIR}/data ]]; then
    echo "data does not exists!"
    exit 1
  fi

  cp wp-tests-config-sample.php ${WP_TESTS_DIR}/wp-tests-config.php

  if [[ ! -f ${WP_TESTS_DIR}/wp-tests-config.php ]]; then
    echo "wp-tests-config.php does not exists!"
    exit 1
  fi

  cd ${WP_TESTS_DIR}

  if [[ ${CI:0} == 1 ]]; then
    rm -rf wordpress-develop
  fi

  sed $ioption "s:dirname( __FILE__ ) . '/src/':'${WP_CORE_DIR}':" wp-tests-config.php
  sed $ioption "s:__DIR__ . '/src/':'${WP_CORE_DIR}':" wp-tests-config.php
  sed $ioption "s/youremptytestdbnamehere/${DB_NAME}/" wp-tests-config.php
  sed $ioption "s/yourusernamehere/${DB_USER}/" wp-tests-config.php
  sed $ioption "s/yourpasswordhere/${DB_PASS}/" wp-tests-config.php
  sed $ioption "s|localhost|${DB_HOST}|" wp-tests-config.php
  echo "\$table_prefix = 'wptests' . ( getenv('TEST_TOKEN') !== false ? getenv('TEST_TOKEN') : '' ) . '_';" >>wp-tests-config.php

  cd ${CWD}
}

install_db() {
  cd ${CWD}

  # parse DB_HOST for port or socket references
  local PARTS=(${DB_HOST//\:/ })
  local DB_HOSTNAME=${PARTS[0]}
  local DB_SOCK_OR_PORT=${PARTS[1]}
  local EXTRA=""

  if ! [ -z ${DB_HOSTNAME} ]; then
    if [[ "${DB_SOCK_OR_PORT}" =~ ^[0-9]+$ ]]; then
      EXTRA=" --host=${DB_HOSTNAME} --port=${DB_SOCK_OR_PORT} --protocol=tcp"
    elif ! [ -z ${DB_SOCK_OR_PORT} ]; then
      EXTRA=" --socket=${DB_SOCK_OR_PORT}"
    elif ! [ -z ${DB_HOSTNAME} ]; then
      EXTRA=" --host=${DB_HOSTNAME} --protocol=tcp"
    fi
  fi

  # create database
  MYSQL=$(which mysql)

  ${MYSQL} ${MYSQL_VERBOSITY_ARGS} --user="${DB_USER}" --password="${DB_PASS}"${EXTRA} -e "DROP DATABASE IF EXISTS ${DB_NAME};"
  ${MYSQL} ${MYSQL_VERBOSITY_ARGS} --user="${DB_USER}" --password="${DB_PASS}"${EXTRA} -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
  ${MYSQL} ${MYSQL_VERBOSITY_ARGS} --user="${DB_USER}" --password="${DB_PASS}"${EXTRA} -e "GRANT USAGE ON *.* TO ${DB_USER}@${DB_HOSTNAME} IDENTIFIED BY '${DB_PASS}';"
  ${MYSQL} ${MYSQL_VERBOSITY_ARGS} --user="${DB_USER}" --password="${DB_PASS}"${EXTRA} -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO ${DB_USER}@${DB_HOSTNAME};"
  ${MYSQL} ${MYSQL_VERBOSITY_ARGS} --user="${DB_USER}" --password="${DB_PASS}"${EXTRA} -e "FLUSH PRIVILEGES;"
  ${MYSQL} ${MYSQL_VERBOSITY_ARGS} --user="${DB_USER}" --password="${DB_PASS}"${EXTRA} -e "USE ${DB_NAME};"

  #	mysqladmin create ${DB_NAME} --user="${DB_USER}" --password="${DB_PASS}"${EXTRA}

  cd ${CWD}
}

install_wp
install_test_suite
install_db

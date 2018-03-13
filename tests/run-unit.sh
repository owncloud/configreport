#!/usr/bin/env bash
set -xeo pipefail

if [[ "$(pwd)" == "$(cd "$(dirname "$0")"; pwd -P)" ]]; then
  echo "Can only be executed from project root!"
  exit 1
fi

declare -x COVERAGE
[[ -z "${COVERAGE}" ]] && COVERAGE="false"

readonly BASE_DIR="$(pwd)"

main () {

  # go to server root dir
  core_path="$(dirname "$(dirname "${BASE_DIR}")")"
  cd "${core_path}"

  #php occ app:enable notifications_mail

  # run unit tests
  if [[ "${COVERAGE}" == "true" ]]; then
    phpdbg -d memory_limit=4096M -rr ./lib/composer/bin/phpunit --configuration "${BASE_DIR}/tests/unit/phpunit.xml"
  else
    ./lib/composer/bin/phpunit --configuration "${BASE_DIR}/tests/unit/phpunit.xml"
  fi

}

main
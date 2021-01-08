#!/bin/bash
#
# Run fast tests.
#
set -e

echo '=> Linting code.'
./scripts/shell-lint.sh
./scripts/yaml-lint.sh
./scripts/php-lint.sh

echo '=> Unit tests.'
./scripts/php-unit.sh

echo '=> Check for deprecated code'
./scripts/check-deprecated.sh

echo '=> Static analysis'
./scripts/php-static-analysis.sh

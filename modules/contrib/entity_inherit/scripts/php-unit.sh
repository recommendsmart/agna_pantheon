#!/bin/bash
#
# Run unit tests.
#
set -e

docker run -v "$(pwd)":/app dcycle/phpunit:1 \
  --group entity_inherit

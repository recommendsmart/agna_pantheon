#!/bin/bash
#
# Check for deprecated code.
#
set -e

docker run --rm -v "$(pwd)":/var/www/html/modules/entity_inherit dcycle/drupal-check:1 entity_inherit/src

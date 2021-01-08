#!/bin/bash
#
# Deploy a development or testing environment.
#
set -e

echo ''
echo '-----'
echo 'About to create the entity_inherit_default network if it does not exist,'
echo 'because we need it to have a predictable name when we try to connect'
echo 'other containers to it (for example browser testers).'
echo 'See https://github.com/docker/compose/issues/3736.'
docker network ls | grep entity_inherit_default || docker network create entity_inherit_default

echo ''
echo '-----'
echo 'About to start persistent (-d) containers based on the images defined'
echo 'in ./Dockerfile-* files. We are also telling docker-compose to'
echo 'rebuild the images if they are out of date.'
if [ "$1" == 8 ]; then
  DRUPALVERSION=8
else
  DRUPALVERSION=9
fi

docker-compose -f docker-compose.base.yml -f docker-compose.drupal"$DRUPALVERSION".yml up -d --build

echo ''
echo '-----'
echo 'Running the deploy scripts on the container.'
docker-compose exec -T drupal /bin/bash -c 'cd ./modules/custom/entity_inherit/scripts/lib/docker-resources && ./deploy.sh'

echo ''
echo '-----'
echo ''
echo 'If all went well you can now access your site at:'
echo ''
./scripts/uli.sh
echo ''

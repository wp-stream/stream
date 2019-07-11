#!/bin/bash
#
# A helper to foward all incoming commands to a docker container.
#

# Enable verbose output.
set -x

DOCKER_COMPOSE_FILE=${DOCKER_COMPOSE_FILE:-"docker-compose.yml"}

# Run the command passed to this script (captured by $@) inside the WP environment.
# Set --user to 1000 to allow file writes and -T to disable the TTL thing.
docker-compose --file $DOCKER_COMPOSE_FILE exec --user 1000 -T wordpress "${@}"

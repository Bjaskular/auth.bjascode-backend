#!/bin/bash

echo ""
set -a
source ../.env

echo $DOCKER_PROJECT_NAME

BOLD="$(tput bold)"
RED="$(tput setaf 1)"
GREEN="$(tput setaf 2)"
YELLOW="$(tput setaf 3)"
BLUE="$(tput setaf 4)"
RESET="$(tput sgr 0)"

cd docker
docker compose pull
docker compose -p ${DOCKER_PROJECT_NAME} up -d
cd ../
echo ""
echo -e "Config project: "

echo -e "${BOLD}${RED}---------${RESET}"
echo -e "${BOLD}Wait 10 sec for containers and network${RESET}"
sleep 10

echo -e "${BOLD}${RED}---------${RESET}"
echo -e "Composer install"
docker exec -it ${DOCKER_PROJECT_NAME}_php composer install

echo -e "${BOLD}${RED}---------${RESET}"
echo -e "docker exec -it ${DOCKER_PROJECT_NAME}_php php artisan migrate"
docker exec -it ${DOCKER_PROJECT_NAME}_php php artisan migrate

echo -e "${BOLD}${RED}---------${RESET}"
echo -e "docker network connect ${DOCKER_NETWORK} ${DOCKER_PROJECT_NAME}_php"
docker network connect ${DOCKER_NETWORK} ${DOCKER_PROJECT_NAME}_php

echo "${BOLD}${RED}--------------------------------------------------------------------------------${RESET}"
echo "${YELLOW}The DB server is available at: ${BOLD}${GREEN}${DOCKER_IP}:${DOCKER_PORT_DB}${RESET}"
echo "${YELLOW}The application is available at: ${BOLD}${GREEN}${DOCKER_IP}:${DOCKER_PORT_HTTP}${RESET}"
echo "${BOLD}${RED}--------------------------------------------------------------------------------${RESET}"
echo ""

read -n 1 -s -r -p "Press enter to continue..."

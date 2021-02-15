#!/usr/bin/env bash

VALET_HOME="./.valet-home"
WHITE='\033[1;37m'
NC='\033[0m'

echo -e "${WHITE}
Butler (Laravel Valet for Docker)
=================================

This will install Butler (Laravel Valet for Docker)

Although it is a replacement for the original Laravel Valet, it is not a drop-in replacement. 
It means you need to disable current running Laravel Valet either by uninstall it or by running valet stop.

If you are not running Laravel Valet, please make sure port 80, 443, 53 inside your MacOS is not in use.
${NC}"



while true; do
    read -p "Do you wish to install continue this installation? [Y/n]" yn
    case $yn in
        [Yy]* ) break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done

# Ensure that Docker is running...
if ! docker info > /dev/null 2>&1; then
    echo -e "${WHITE}Docker is not running.${NC}" >&2

    exit 1
fi


mkdir -p www
mkdir -p $VALET_HOME/{CA,Certificates,Drivers,Extensions,Log,Nginx,Sites,dnsmasq.d,dnsmasq-internal.d}
touch $VALET_HOME/Log/nginx-error.log
cp ./valet/cli/stubs/SampleValetDriver.php $VALET_HOME/Drivers/SampleValetDriver.php

# copy config file if not exists
if [ ! -f $VALET_HOME/config.json ]; then
    cp ./stubs/config.json $VALET_HOME/config.json
fi

if [ ! -f .env ]; then
    cp ./stubs/.env .env
    sed -i '' "s|REPLACEME|$PWD/www|g" ./.env
fi

if [ ! -f docker-compose.yaml ]; then
    cp ./stubs/docker-compose.yaml ./docker-compose.yaml
fi


sed "s|REPLACEME|$PWD|g" ./bin/butler > ./butler
chmod +x ./butler
mv ./butler /usr/local/bin/butler
echo "Waiting for Butler services to start..."
butler start
docker exec -i -w /valet/master butler_php_1 composer install -vvv
butler install

# echo "Register as sudoers..."

# echo -e "Cmnd_Alias BUTLER = /usr/local/bin/butler *
# %admin ALL=(root) NOPASSWD:SETENV: BUTLER" | sudo tee /etc/sudoers.d/butler > /dev/null


echo -e "${WHITE}


Please make sure to set 127.0.0.1 inside your DNS setting for custom domain to work


${NC}"
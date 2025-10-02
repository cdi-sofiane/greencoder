#!/bin/bash
now="$(date +%s)"

mkdir /var/www/green-encoder-sf/release/"$now"

cp -RT . /var/www/green-encoder-sf/release/"$now"

rm -rf /var/www/green-encoder-sf/current

ln -s /var/www/green-encoder-sf/release/"$now" /var/www/green-encoder-sf/current

cd /var/www/green-encoder-sf/release && ls -t | tail -n +6 | xargs rm -rf

cd /var/www/green-encoder-sf/release/"$now"

cp prod.env .env

cp src/prod.env src/.env

docker-compose down

docker-compose -f docker-compose.prod.yml up -d --build
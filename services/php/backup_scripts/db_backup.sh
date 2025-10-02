#!/bin/bash

## default backup_script for mysql inside an application container 
project_name=${APP_NAME}
env=${APP_ENV}
date_string=$1
backup_dir="/backup/database"

mysqldump -u $MYSQL_USER -p$MYSQL_PASSWORD -h $MYSQL_HOST $MYSQL_DATABASE | gzip > ${backup_dir}/${project_name}-${env}_${date_string}.sql.gz
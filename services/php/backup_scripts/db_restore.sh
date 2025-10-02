#!/bin/bash

## this script is made to restore a mysql backup of a database, with a date as parameter

date_string=$1
project_name=${APP_NAME}
env=${APP_ENV}
backup_dir=/backup/database


gunzip < ${backup_dir}/${project_name}-${env}_${date_string}.sql.gz | mysql -u ${MYSQL_USER} -p${MYSQL_PASSWORD} -h ${MYSQL_HOST} ${MYSQL_DATABASE}

#!/usr/bin/env bash

CONTAINER=sgi_mysql
DATABASE=c0310458_sgi
TIMESTAMP=$(date +'%Y%m%d%H%M')
DUMPS_FOLDER=../dumps

docker exec $CONTAINER /usr/bin/mysqldump -u root --password=changeme $DATABASE > $DUMPS_FOLDER/$DATABASE.$TIMESTAMP.sql

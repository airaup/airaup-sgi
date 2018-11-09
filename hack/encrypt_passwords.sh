#!/usr/bin/env bash

CONTAINER=sgi_php
FILE=encrypt_passwords.php
DEST_PATH=/tmp/

docker cp $FILE $CONTAINER:$DEST_PATH
docker exec -it $CONTAINER php -f $DEST_PATH/$FILE

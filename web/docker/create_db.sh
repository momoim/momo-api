#!/bin/bash

create_db ()
{
    mysql -h"${MYSQL_PORT_3306_TCP_ADDR}" -P"${MYSQL_PORT_3306_TCP_PORT}" -uroot -p"${MYSQL_ENV_MYSQL_ROOT_PASSWORD}" < /tmp/momo_contact.sql > /dev/null 2>&1
    mysql -h"${MYSQL_PORT_3306_TCP_ADDR}" -P"${MYSQL_PORT_3306_TCP_PORT}" -uroot -p"${MYSQL_ENV_MYSQL_ROOT_PASSWORD}" < /tmp/momo_v3.sql > /dev/null 2>&1
    echo "create database done"
}

create_db
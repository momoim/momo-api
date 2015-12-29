#!/bin/sh

#docker rm -f momo-mysql
# 需要持久化
#docker run -d -p --name momo-mysql -v "$(pwd)/mysql":/var/lib/mysql -e MYSQL_ROOT_PASSWORD=123456 mysql

docker run -d -p 3306:3306 --name momo-mysql -e MYSQL_ROOT_PASSWORD=123456 mysql

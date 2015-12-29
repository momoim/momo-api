#!/bin/sh

#docker rm -f momo-mongo
# 需要持久化
#docker run --name momo-mongo -v ${pwd}/mongo:/data/db -d mongo

docker run --name momo-mongo -p 27017:27017 -d mongo


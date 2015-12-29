#!/bin/sh

#docker rm -f momo-rabbitmq
# 需要持久化
#docker run -d --name momo-rabbitmq -p 5672:5672 -p 15672:15672 -v "$(pwd)/rabbitmq":/data dockerfile/rabbitmq

docker run -d --name momo-rabbitmq  -p 5672:5672 -p 15672:15672 dockerfile/rabbitmq

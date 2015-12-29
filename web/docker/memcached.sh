#!/bin/sh
docker rm -f momo-memcached
docker run -d --name momo-memcached memcached

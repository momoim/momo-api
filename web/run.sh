#!/bin/bash

env="dev" # prod
curr_dir=$(pwd)
docker_dir="$(pwd)/docker"
log_dir="${docker_dir}/logs"
app_log_dir="$(pwd)/application/logs"

mysql="mo-mysql"
mongo="mo-mongo"
rabbitmq="mo-rabbitmq"
memcached="mo-memcached"
redis="mo-redis"
php="mo-php"

mkdir -p ${log_dir}
mkdir -p ${app_log_dir}
chmod -R 0777 ${app_log_dir}

is_running() {
    return $(docker ps -a |grep $1 | wc -l)
}

start_mysql() {
    name=${mysql}
    image="mysql"
    is_running=$(docker ps -a |grep ${name} | wc -l)
    if [ ${is_running} == 0 ]; then
        echo "start ${name}..."
        if [ ${env} == "prod" ]; then
        # 需要持久化
            docker run -d --restart=always --name ${name} -v "${docker_dir}/mysql":/var/lib/mysql -e MYSQL_ROOT_PASSWORD=123456 ${image}
        else
            docker run -d --restart=always -p 3306:3306 --name ${name} -e MYSQL_ROOT_PASSWORD=123456 ${image}
        fi
    else
        echo "${name} running..."
    fi
}

start_mongo() {
    name=${mongo}
    image="mongo"
    is_running=$(docker ps -a |grep ${name} | wc -l)
    if [ ${is_running} == 0 ]; then
        echo "start ${name}..."
        if [ ${env} == "prod" ]; then
            # 需要持久化
            docker run --name ${name} -v "${docker_dir}/mongo":/data/db -d ${image}
        else
            docker run --name ${name} -p 27017:27017 -d ${image}
        fi
    else
        echo "${name} running..."
    fi
}

start_rabbitmq() {
    name=${rabbitmq}
    image="dockerfile/rabbitmq"
    is_running=$(docker ps -a |grep ${name} | wc -l)
    if [ ${is_running} == 0 ]; then
        echo "start ${name}..."
        if [ ${env} == "prod" ]; then
            # 需要持久化
            docker run -d --name ${name} -p 5672:5672 -p 15672:15672 -v "${docker_dir}/rabbitmq":/data ${image}
        else
            docker run -d --name ${name} -p 5672:5672 -p 15672:15672 ${image}
        fi
    else
        echo "${name} running..."
    fi
}

init_mysql() {
    # 初始化数据库
    image="mysql"
    docker run -it --link ${mysql}:mysql --rm -v "${docker_dir}":/tmp ${image} sh /tmp/create_db.sh
}

start_memcached() {
    name=${memcached}
    image="58.22.120.52:5000/memcached"
    is_running=$(docker ps -a |grep ${name} | wc -l)
    if [ ${is_running} == 0 ]; then
        echo "start ${name}..."
        docker run -d  --restart=always --name ${name} ${image}
    else
        echo "${name} running..."
    fi
}

start_momo() {
    name=${php}
    image="58.22.120.52:5000/php:latest"
    is_running=$(docker ps -a |grep ${name} | wc -l)
    if [ ${is_running} == 0 ]; then
        echo "start ${name}..."
        docker run -d --restart=always -p 80:80 --name ${name} -v "${log_dir}/supervisor":/var/log/supervisor -v "${log_dir}/php5":/var/log/php5 -v "${log_dir}/nginx":/var/log/nginx -v "${curr_dir}":/var/www -v "${docker_dir}/default-site.conf":/etc/nginx/sites-available/default --link ${memcached}:memcached --link ${mysql}:mysql --link ${rabbitmq}:rabbitmq --link ${mongo}:mongo ${image}
    else
        echo "restart ${name}..."
        docker restart ${name}
    fi
}

start() {
    start_mysql
    start_mongo
    start_rabbitmq
    start_memcached
    start_momo
}

restart() {
    docker restart ${php}
}

stop() {
    docker stop ${mysql}
    docker stop ${mongo}
    docker stop ${rabbitmq}
    docker stop ${memcached}
    docker stop ${php}
}

rm() {
    docker rm ${mysql}
    docker rm ${mongo}
    docker rm ${rabbitmq}
    docker rm ${memcached}
    docker rm ${php}
}

case "$1" in
    "")
        start
        ;;
    start)
        start
        ;;
    restart)
        restart
        ;;
    stop)
        stop
        ;;
    rm)
        rm
        ;;
    init)
        init_mysql
        ;;
    *)
        echo $"Usage: $0 {start|init|restart|stop|rm}"
        exit 2
esac

exit $?


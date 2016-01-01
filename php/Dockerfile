FROM index.alauda.cn/library/php:5.4-fpm

MAINTAINER lost <dragon829@gmail.com>

RUN echo "deb http://mirrors.163.com/debian/ jessie main non-free contrib\n"\
"deb http://mirrors.163.com/debian/ jessie-updates main non-free contrib\n"\
"deb http://mirrors.163.com/debian/ jessie-backports main non-free contrib\n"\
"deb-src http://mirrors.163.com/debian/ jessie main non-free contrib\n"\
"deb-src http://mirrors.163.com/debian/ jessie-updates main non-free contrib\n"\
"deb-src http://mirrors.163.com/debian/ jessie-backports main non-free contrib\n"\
"deb http://mirrors.163.com/debian-security/ jessie/updates main non-free contrib\n"\
"deb-src http://mirrors.163.com/debian-security/ jessie/updates main non-free contrib" > /etc/apt/sources.list

# Add pecl
COPY docker-php-pecl-install /usr/local/bin/

# Install other needed extensions
RUN apt-get update && apt-get install -y libfreetype6 libjpeg62-turbo libmcrypt4 libpng12-0 sendmail --no-install-recommends && rm -rf /var/lib/apt/lists/*
RUN buildDeps=" \
		libfreetype6-dev \
		libjpeg-dev \
		libldap2-dev \
		libmcrypt-dev \
		libpng12-dev \
		zlib1g-dev \
	"; \
	set -x \
	&& apt-get update && apt-get install -y $buildDeps --no-install-recommends && rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-configure gd --enable-gd-native-ttf --with-jpeg-dir=/usr/lib/x86_64-linux-gnu --with-png-dir=/usr/lib/x86_64-linux-gnu --with-freetype-dir=/usr/lib/x86_64-linux-gnu \
	&& docker-php-ext-install gd \
	&& docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
	&& docker-php-ext-install ldap \
	&& docker-php-ext-install mbstring \
	&& docker-php-ext-install mcrypt \
	&& docker-php-ext-install mysql \
	&& docker-php-ext-install mysqli \
	&& docker-php-ext-install pdo_mysql \
	&& docker-php-ext-install zip \
	&& docker-php-ext-install exif \
	&& apt-get purge -y --auto-remove $buildDeps \
	&& cd /usr/src/php \
	&& make clean

RUN apt-get update && apt-get install -y libz-dev libmemcached-dev libssl-dev gearman-job-server libgearman-dev --no-install-recommends && rm -rf /var/lib/apt/lists/*
COPY ext /tmp/ext
RUN docker-php-pecl-install /tmp/ext/gearman-1.1.2.tgz /tmp/ext/memcache-2.2.7.tgz /tmp/ext/mongo-1.6.12.tgz /tmp/ext/redis-2.2.7.tgz \
    && docker-php-ext-enable gearman memcache mongo redis

# Setup timezone to Asia/Shanghai
RUN cat /usr/src/php/php.ini-production | sed 's/^;\(date.timezone.*\)/\1 \"Asia\/Shanghai\"/' > /usr/local/etc/php/php.ini

# Disable cgi.fix_pathinfo in php.ini
RUN sed -i 's/;\(cgi\.fix_pathinfo=\)1/\10/' /usr/local/etc/php/php.ini

# Install Composer
ADD composer.phar /usr/local/bin/composer
RUN composer config -g repositories.packagist composer http://packagist.phpcomposer.com

# php 5.6
#RUN sed -i "s/;always_populate_raw_post_data = -1/always_populate_raw_post_data = -1/" /usr/local/etc/php/php.ini

CMD ["php-fpm"]
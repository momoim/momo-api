FROM momov3api_fpm:latest

MAINTAINER lost <dragon829@gmail.com>

RUN apt-get update && apt-get install -y openssh-server

RUN mkdir -p /var/run/sshd && sed -i "s/UsePrivilegeSeparation.*/UsePrivilegeSeparation no/g" /etc/ssh/sshd_config && sed -i "s/UsePAM.*/UsePAM no/g" /etc/ssh/sshd_config && sed -i "s/PermitRootLogin.*/PermitRootLogin yes/g" /etc/ssh/sshd_config
RUN echo 'root:password' | chpasswd

RUN docker-php-pecl-install xdebug

RUN echo "xdebug.default_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-pecl-xdebug.ini \
    && echo "xdebug.idekey = \"PHPStorm\"" >> /usr/local/etc/php/conf.d/docker-php-pecl-xdebug.ini \
    && echo "xdebug.remote_enable = 1" >> /usr/local/etc/php/conf.d/docker-php-pecl-xdebug.ini \
    && echo "xdebug.remote_autostart = 1" >> /usr/local/etc/php/conf.d/docker-php-pecl-xdebug.ini \
    && echo "xdebug.remote_port = 9000" >> /usr/local/etc/php/conf.d/docker-php-pecl-xdebug.ini \
    && echo "xdebug.remote_handler = dbgp" >> /usr/local/etc/php/conf.d/docker-php-pecl-xdebug.ini \
    && echo "xdebug.remote_host=YYYYYYYYYYYYY" >> /usr/local/etc/php/conf.d/docker-php-pecl-xdebug.ini \
    && echo "xdebug.remote_connect_back = on" >> /usr/local/etc/php/conf.d/docker-php-pecl-xdebug.ini

EXPOSE 22

CMD ["sh", "-c", "/usr/sbin/sshd && php-fpm"]
# swoft/alphp:cli:
# @description php 7.1 image base on the alpine 3.7
# ------------------------------------------------------------------------------------
# @link https://hub.docker.com/_/alpine/      alpine image
# @link https://hub.docker.com/_/php/         php image
# @link https://github.com/docker-library/php php dockerfiles
# ------------------------------------------------------------------------------------
# @build-example docker build . -f Dockerfile -t scil/swoole:app1
#

FROM swoft/alphp:cli
LABEL maintainer="scil" version="1.0"

##
# ---------- building ----------
##

RUN set -ex \
        && mkdir -p /var/laravel_app \
        && chown -R www-data:www-data /var/laravel_app \
        && echo -e "\033[42;37m Build Completed :).\033[0m\n"

EXPOSE 9501 80
VOLUME ["/var/www", "/data"]
WORKDIR /var/www

ENTRYPOINT ["php", "vendor/scil/laravel-fly/bin/fly", "start"]

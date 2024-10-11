#syntax=docker/dockerfile:1

# Versions
FROM dunglas/frankenphp:1-php8.3 AS frankenphp_upstream

ARG APT_FLAGS_COMMON="-qq -y"
ARG APT_FLAGS_PERSISTENT="${APT_FLAGS_COMMON} --no-install-recommends"
ARG WORK_DIR="/app"

ENV WORK_DIR="${WORK_DIR}"

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

WORKDIR "${WORK_DIR}"
VOLUME "${WORK_DIR}/var/"

# persistent / runtime deps
RUN apt-get update ${APT_FLAGS_COMMON} && apt-get install ${APT_FLAGS_PERSISTENT} \
    acl     \
    file    \
    gettext \
    git     \
    curl    \
    wget && \
    rm -Rf                                         \
          /var/log/*.log                           \
          /var/log/apt/*                           \
          /usr/share/doc/*                         \
          /usr/share/icons/*                       \
          /var/cache/apt                           \
          /tmp/*                                   \
          /var/lib/apt/lists/*                     \
          /var/cache/debconf/templates.dat-old     \
          /var/cache/debconf/config.dat-old        \
    ;

# Base FrankenPHP image
FROM frankenphp_upstream AS frankenphp_base

ARG APT_FLAGS_COMMON="-qq -y"
ARG APT_FLAGS_PERSISTENT="${APT_FLAGS_COMMON} --no-install-recommends"

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

WORKDIR ${WORK_DIR}

# persistent / runtime deps
RUN set -eux; \
    install-php-extensions \
        @composer          \
        apcu               \
        intl               \
        opcache            \
        ldap               \
        gd                 \
        zip                \
        mbstring           \
        json               \
        gettext            \
        http               \
        imap               \
        pdo_mysql          \
        mysqli             \
        oauth              \
        openssl            \
        soap               \
        xml                \
        curl               \
    ;                      \
    rm -rf /tmp/*

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_INI_SCAN_DIR=":${PHP_INI_DIR}/app.conf.d"

###> recipes ###
###< recipes ###

COPY ./frankenphp/conf.d/10-app.ini ${PHP_INI_DIR}/app.conf.d/
COPY --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY ./frankenphp/Caddyfile /etc/caddy/Caddyfile

ENTRYPOINT ["docker-entrypoint"]

HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile" ]

# Angular build
FROM node:bookworm AS build_angular

ARG TERM="xterm-256color"
SHELL ["/bin/bash", "-o", "pipefail", "-c"]

WORKDIR /src/app

ARG APT_FLAGS_COMMON="-qq -y"
ARG APT_FLAGS_PERSISTENT="${APT_FLAGS_COMMON} --no-install-recommends"

ADD . /src/app
RUN apt-get update ${APT_FLAGS_COMMON} && apt-get install ${APT_FLAGS_PERSISTENT} \
      bash \
      git && \
    yarn global add @angular/cli; \
    yarn install;                 \
    npm run build-dev;            \
    npm run build-dev:defaultExt  \
    ;

# Dev FrankenPHP image
FROM frankenphp_base AS frankenphp_dev

ARG TZ="Asia/Baku"
ARG APP_ENV="dev"
ARG XDEBUG_MODE="off"
ENV APP_ENV=${APP_ENV} \
    XDEBUG_MODE=${XDEBUG_MODE} \
    TZ=${TZ}

RUN mv "${PHP_INI_DIR}/php.ini-development" "${PHP_INI_DIR}/php.ini" && \
    set -eux;                                                           \
    install-php-extensions                                              \
        xdebug                                                          \
    ;                                                                   \
    rm -Rf                                                              \
      ./frankenphp/                                                     \
      /var/log/*.log                                                    \
      /var/log/apt/*                                                    \
      /usr/share/doc/*                                                  \
      /usr/share/icons/*                                                \
      /var/cache/apt                                                    \
      /tmp/*                                                            \
      /var/lib/apt/lists/*                                              \
      /var/cache/debconf/templates.dat-old                              \
      /var/cache/debconf/config.dat-old                                 \
    ;                                                                   \

ADD ./frankenphp/conf.d/20-app.dev.ini ${PHP_INI_DIR}/app.conf.d/

CMD [ "frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch" ]

# Prod FrankenPHP image
FROM frankenphp_base AS frankenphp_prod

ARG TZ="Asia/Baku"
ARG APP_ENV="prod"
ARG XDEBUG_MODE="off"

#ARG FRANKENPHP_CONFIG="import worker.Caddyfile"
ENV FRANKENPHP_CONFIG="worker ./public/index.php" \
    APP_ENV=${APP_ENV}                            \
    XDEBUG_MODE=${XDEBUG_MODE}                    \
    TZ=${TZ}                                      \
    FRANKENPHP_CONFIG=${FRANKENPHP_CONFIG}        \
    DEBIAN_FRONTEND=noninteractive                \
    TERM="xterm-256color"                         \
    WORK_DIR="${WORK_DIR}"


WORKDIR "${WORK_DIR}"

ADD ./frankenphp/conf.d/20-app.prod.ini ${PHP_INI_DIR}/app.conf.d/
ADD ./frankenphp/worker.Caddyfile /etc/caddy/
# prevent the reinstallation of vendors at every changes in the source code
COPY composer.* symfony.* ./

RUN mv "${PHP_INI_DIR}/php.ini-production" "${PHP_INI_DIR}/php.ini"; \
    set -eux; \
    composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

# copy sources
COPY --from=build_angular /src/app/public/dist ./public/dist/
COPY --from=build_angular /src/app/public/extensions ./public/extensions/
COPY --from=build_angular /src/app/dist ./dist
COPY . .

RUN set -eux; \
    mkdir -p ./var/cache ./var/log; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    composer dump-env prod; \
    composer run-script --no-dev post-install-cmd; \
    true && \
    chmod +x ./bin/console; \
    echo "PS1='\[\033[32m\][\u@\h\[\033[32m\]]\[\033[00m\] \[\033[36m\]\w\[\033[0m\] \[\033[33m\]\$\[\033[00m\] '" >> /root/.bashrc && \
    echo "alias ll='ls -lha --color=auto'" >> /root/.bashrc && \
    echo "alias ls='ls -ah --color=auto'"  >> /root/.bashrc && \
    true && \
    rm -Rf \
      ./frankenphp/ \
      /var/log/*.log \
      /var/log/apt/* \
      /usr/share/doc/* \
      /usr/share/icons/* \
      /var/cache/apt \
      /tmp/* \
      /var/lib/apt/lists/* \
      /var/cache/debconf/templates.dat-old \
      /var/cache/debconf/config.dat-old \
    ; \
    sync

USER ${USER}

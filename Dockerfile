FROM dunglas/frankenphp:1-php8.4

WORKDIR /app

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && install-php-extensions curl mbstring

COPY Caddyfile /etc/caddy/Caddyfile
COPY public ./public
COPY src ./src
COPY partials ./partials
COPY data ./data

ENV PORT=80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

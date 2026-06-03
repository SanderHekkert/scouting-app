FROM scouting-app-php AS php

FROM nginx:1.27-alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=php /var/www/html/public /var/www/html/public

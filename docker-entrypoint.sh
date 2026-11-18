#!/bin/sh
set -eu

port="${PORT:-10000}"
case "$port" in
    ''|*[!0-9]*)
        echo "PORT debe ser un numero." >&2
        exit 1
        ;;
esac

mkdir -p /var/www/html/uploads/perfiles /var/www/html/uploads/proyectos
chown www-data:www-data /var/www/html/uploads \
    /var/www/html/uploads/perfiles /var/www/html/uploads/proyectos

sed -i "s/^Listen .*/Listen ${port}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \\*:.*>/<VirtualHost *:${port}>/" \
    /etc/apache2/sites-available/000-default.conf

exec "$@"

#!/bin/sh

sed -i "s/xx_server_name_xx/${SERVER_NAME}/g" /etc/nginx/conf.d/host.conf
sed -i "s/xx_server_name_xx/${SERVER_NAME}/g" /etc/nginx/conf.d/host.ssl.inc
sed -i "s/xx_root_server_name_xx/${ROOT_SERVER_NAME}/g" /etc/nginx/conf.d/host.ssl.inc

sed -i "s/xx_log_server_xx/${LOG_SERVER}/g" /etc/rsyslog.conf

cd /app
composer update

exec supervisord -c /etc/supervisord.conf

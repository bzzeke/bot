server {
    listen                  443 ssl http2;
    server_name             xx_server_name_xx
    ssl                     on;
    ssl_certificate         /etc/ssl/le/live/xx_root_server_name_xx/fullchain.pem;
    ssl_certificate_key     /etc/ssl/le/live/xx_root_server_name_xx/privkey.pem;

    include /etc/nginx/conf.d/ssl-params.inc;

    root /app;
    index index.php;

    location / {
        try_files $uri $uri/ @rewriteapp;
    }
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        include fastcgi_params;
    }

    location @rewriteapp {
        rewrite ^(.*)$ /index.php?$args last;
    }
}

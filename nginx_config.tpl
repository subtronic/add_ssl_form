server {
    listen 80;
    listen [::]:80;

    listen 443 ssl;

    root /usr/local/var/www/%domain%;
    include /usr/local/etc/nginx/include/common;

    server_name %domain% www.%domain%;
    ssl_certificate     /usr/local/var/www/test/ssl/%domain%.pem;
    ssl_certificate_key /usr/local/var/www/test/ssl/%domain%.pem;

    location / {
    try_files $uri $uri/ /index.php?$args;
        location ~ \.php$ {
            include       /usr/local/etc/nginx/include/php;
            include /usr/local/etc/nginx/include/cors;
        }
    }


}
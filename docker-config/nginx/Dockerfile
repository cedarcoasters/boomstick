FROM nginx:alpine

# Copy custom Nginx config
COPY docker-config/nginx/nginx.conf /etc/nginx/nginx.conf

# Copy source code (shared with PHP-FPM)
COPY . /var/www/html/

# Symlink logs to stdout/stderr for App Platform
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
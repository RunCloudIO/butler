FROM openresty/openresty:alpine-fat

COPY entrypoint/docker-entrypoint.sh /
COPY entrypoint/10-envsubst-on-templates.sh /docker-entrypoint.d/10-envsubst-on-templates.sh
RUN ln -s /usr/local/openresty/bin/openresty /bin/openresty
ENTRYPOINT ["/docker-entrypoint.sh"]

CMD ["/usr/local/openresty/bin/openresty", "-g", "daemon off;"]


# Use SIGQUIT instead of default SIGTERM to cleanly drain requests
# See https://github.com/openresty/docker-openresty/blob/master/README.md#tips--pitfalls
STOPSIGNAL SIGQUIT
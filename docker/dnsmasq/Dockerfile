
FROM alpine:edge
LABEL maintainer="fikri@runcloud.io"

ENV BUILD_DEPS="gettext"  \
    RUNTIME_DEPS="libintl"

RUN set -x \    
    && mkdir -p /etc/default/ \
    && mkdir -p /etc/dnsmasq-templates \
    && echo -e "ENABLED=1\nIGNORE_RESOLVCONF=yes" > /etc/default/dnsmasq \
    && apk --no-cache add dnsmasq \
    && apk add --update $RUNTIME_DEPS \
    && apk add --virtual build_deps $BUILD_DEPS \
    && cp /usr/bin/envsubst /usr/local/bin/envsubst \
    && apk del build_deps

COPY ./dnsmasq.conf /etc/dnsmasq.conf
COPY ./docker-entrypoint.sh /docker-entrypoint.sh

# ENTRYPOINT ["dnsmasq", "-k"]
ENTRYPOINT ["/docker-entrypoint.sh"]

CMD [ "dnsmasq", "-k" ]
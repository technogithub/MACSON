# ==============================================================================
# FreeRADIUS Container Dockerfile (Ubuntu 22.04 LTS)
# ==============================================================================

FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# Install FreeRADIUS & MariaDB Client via apt
RUN apt-get update && apt-get install -y \
    freeradius \
    freeradius-mysql \
    freeradius-utils \
    mariadb-client \
    curl \
    net-tools \
    && rm -rf /var/lib/apt/lists/*

# Enable SQL module links
RUN ln -sf /etc/freeradius/3.0/mods-available/sql /etc/freeradius/3.0/mods-enabled/sql

EXPOSE 1812/udp 1813/udp

CMD ["freeradius", "-X"]

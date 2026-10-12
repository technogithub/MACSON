FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# Install Kea DHCPv4 Server dan utilities
RUN apt-get update && \
    apt-get install -y --no-install-recommends \
    kea-dhcp4-server \
    procps \
    tzdata \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set timezone
ENV TZ=Asia/Jakarta
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Buat direktori runtime dan lease
RUN mkdir -p /var/run/kea /var/lib/kea /etc/kea

# Expose DHCP ports (UDP 67 untuk DHCP server)
EXPOSE 67/udp

# Jalankan Kea DHCPv4 Server
CMD ["kea-dhcp4", "-c", "/etc/kea/kea-dhcp4.conf"]

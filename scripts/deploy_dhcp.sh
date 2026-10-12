#!/usr/bin/env bash
# ==============================================================================
# Script: deploy_dhcp.sh
# Deskripsi: Deploy & Start Kea DHCPv4 Server dengan ZERO-DOWNTIME
# ==============================================================================

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}        DEPLOYING KEA DHCP SERVER (ZERO-DOWNTIME)               ${NC}"
echo -e "${BLUE}================================================================${NC}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
cd "$PROJECT_ROOT"

# 1. Siapkan folder storage/dhcp di dalam project Laravel (sudah pasti writable oleh www-data)
echo -e "\n${YELLOW}[1/6] Menyiapkan folder storage DHCP di dalam container...${NC}"
STORAGE_DHCP="$PROJECT_ROOT/backend-laravel/storage/dhcp"
mkdir -p "$STORAGE_DHCP/leases"
chmod -R 777 "$STORAGE_DHCP"
if [ ! -f "$STORAGE_DHCP/kea-dhcp4.conf" ]; then
    touch "$STORAGE_DHCP/kea-dhcp4.conf"
fi
chmod 666 "$STORAGE_DHCP/kea-dhcp4.conf"

# Fix permission di DALAM container agar www-data bisa nulis ke storage/dhcp
if docker ps --format '{{.Names}}' | grep -q "^radius_laravel_app$"; then
    echo -e "Memperbaiki permission storage/dhcp di dalam container..."
    docker exec radius_laravel_app mkdir -p /var/www/html/storage/dhcp/leases || true
    docker exec radius_laravel_app chmod -R 777 /var/www/html/storage/dhcp || true
    docker exec radius_laravel_app chown -R www-data:www-data /var/www/html/storage/dhcp || true
fi

# 2. Jalankan migration database Laravel
echo -e "\n${YELLOW}[2/6] Menjalankan migration database (tabel dhcp_subnets & static_leases)...${NC}"
docker exec radius_laravel_app php artisan migrate --force

# 3. Sinkronisasi konfigurasi awal dan bersihkan cache Laravel
echo -e "\n${YELLOW}[3/6] Mengoptimalkan cache Laravel...${NC}"
docker exec radius_laravel_app php artisan optimize:clear

# 4. Build dan Nyalakan Container Kea DHCP (HANYA container DHCP, container FreeRADIUS & DB tetap hidup!)
echo -e "\n${YELLOW}[4/6] Membangun dan menyalakan container radius_dhcp...${NC}"
cd "$PROJECT_ROOT/docker"
docker compose up -d --build --no-recreate dhcp

# 5. Atur Firewall UFW (Izinkan UDP 67 untuk DHCP Server)
echo -e "\n${YELLOW}[5/6] Memeriksa firewall host (UDP 67)...${NC}"
if command -v ufw >/dev/null 2>&1 && ufw status | grep -q "Status: active"; then
    echo -e "Menambahkan rule UFW untuk DHCP Server (Port 67/udp)..."
    ufw allow 67/udp comment 'Kea_DHCP_Server' || true
fi

# 6. Verifikasi Status Layanan
echo -e "\n${YELLOW}[6/6] Verifikasi status container...${NC}"
docker compose ps

echo -e "\n${GREEN}================================================================${NC}"
echo -e "${GREEN} [SUCCESS] DHCP Server berhasil di-deploy tanpa downtime!        ${NC}"
echo -e "${GREEN} Container 'radius_dhcp' siap melayani request DHCP Relay L3.    ${NC}"
echo -e "${GREEN} Menu DHCP Server sudah aktif di Web Dashboard SANTAFE.          ${NC}"
echo -e "${GREEN}================================================================${NC}"

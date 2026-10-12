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

# 1. Siapkan folder direktori leases & permissions
echo -e "\n${YELLOW}[1/6] Menyiapkan folder runtime DHCP...${NC}"
mkdir -p "$PROJECT_ROOT/dhcp/leases"
chmod -R 777 "$PROJECT_ROOT/dhcp"
if [ ! -f "$PROJECT_ROOT/dhcp/leases/kea-leases4.csv" ]; then
    touch "$PROJECT_ROOT/dhcp/leases/kea-leases4.csv"
    chmod 666 "$PROJECT_ROOT/dhcp/leases/kea-leases4.csv"
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

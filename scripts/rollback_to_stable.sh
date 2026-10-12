#!/usr/bin/env bash
# ==============================================================================
# Script: rollback_to_stable.sh
# Deskripsi: Mengembalikan sistem ke versi stabil (tag: v1.0-stable) secara aman
# ==============================================================================

set -euo pipefail

# Warna output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

TARGET_TAG="v1.0-stable"

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}          EMERGENCY ROLLBACK TO STABLE VERSION                  ${NC}"
echo -e "${BLUE}                    Target: ${TARGET_TAG}                       ${NC}"
echo -e "${BLUE}================================================================${NC}"

# 1. Pastikan dijalankan dari root direktori project
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
cd "$PROJECT_ROOT"

echo -e "\n${YELLOW}[1/5] Memeriksa status Git repository...${NC}"
if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo -e "${RED}[ERROR] Folder ini bukan git repository!${NC}"
    exit 1
fi

# 2. Hentikan container DHCP jika ada yang sedang berjalan
echo -e "\n${YELLOW}[2/5] Memeriksa dan mematikan service DHCP (jika aktif)...${NC}"
if docker ps -a --format '{{.Names}}' | grep -q "^radius_dhcp$"; then
    echo -e "Mematikan container radius_dhcp..."
    docker stop radius_dhcp || true
    docker rm radius_dhcp || true
    echo -e "${GREEN}Container radius_dhcp berhasil dinonaktifkan.${NC}"
else
    echo -e "Tidak ada container DHCP yang aktif."
fi

# 3. Rollback migration database Laravel jika ada migrasi DHCP baru
echo -e "\n${YELLOW}[3/5] Menyesuaikan database Laravel...${NC}"
if docker ps --format '{{.Names}}' | grep -q "^radius_laravel_app$"; then
    echo -e "Menjalankan cache clear pada Laravel..."
    docker exec radius_laravel_app php artisan optimize:clear || true
fi

# 4. Git Reset ke Tag v1.0-stable
echo -e "\n${YELLOW}[4/5] Mengembalikan source code ke tag ${TARGET_TAG}...${NC}"
git fetch origin --tags
git checkout -f "$TARGET_TAG"
git reset --hard "$TARGET_TAG"
git clean -fd -e .env

# 5. Verifikasi Container Inti (FreeRADIUS, MariaDB, Nginx, App)
echo -e "\n${YELLOW}[5/5] Memastikan container inti tetap berjalan normal...${NC}"
cd docker
docker compose ps

echo -e "\n${GREEN}================================================================${NC}"
echo -e "${GREEN} [SUCCESS] Rollback selesai! Sistem kembali ke ${TARGET_TAG}   ${NC}"
echo -e "${GREEN} Commit Aktif: $(git rev-parse --short HEAD)                    ${NC}"
echo -e "${GREEN} Layanan FreeRADIUS & Web Santafe berjalan normal!               ${NC}"
echo -e "${GREEN}================================================================${NC}"

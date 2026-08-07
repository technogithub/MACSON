#!/usr/bin/env bash
# ==============================================================================
# Enterprise RADIUS & Multi-SSID MAC System - Automated Ubuntu 26.04 Clean Uninstaller
# Operating System: Ubuntu Server 26.04 LTS / 24.04 LTS / 22.04 LTS
# Supports: Interactive mode & Non-Interactive CLI flags (--auto, -y)
# ==============================================================================

set -euo pipefail

# Color Codes for Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Ensure script is executed as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR] This uninstaller must be run as root (use: sudo ./uninstall.sh)${NC}"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

NON_INTERACTIVE=false

# Parse CLI Arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --auto|-y|--force)
      NON_INTERACTIVE=true
      shift
      ;;
    --help|-h)
      echo "Usage: sudo ./uninstall.sh [OPTIONS]"
      echo "Options:"
      echo "  --auto, -y, --force  Purge containers, data, and firewall rules without confirmation prompt"
      exit 0
      ;;
    *)
      echo -e "${RED}Unknown option: $1${NC}"
      exit 1
      ;;
  esac
done

echo -e "${BLUE}=================================================================${NC}"
echo -e "${YELLOW}   ⚠️  MACSON Multi-SSID System - Automated Uninstaller${NC}"
echo -e "${BLUE}=================================================================${NC}"
echo ""

if [ "$NON_INTERACTIVE" = false ]; then
    echo -e "${RED}WARNING: This operation will completely remove:${NC}"
    echo " - All running OmniRadius Docker containers & volumes"
    echo " - All MariaDB database data, device records & audit logs"
    echo " - Configured RADIUS & Web UFW firewall rules"
    echo " - Generated SSL certificates"
    echo ""

    read -p "Type 'uninstall' to confirm complete purge: " CONFIRMATION

    if [ "${CONFIRMATION}" != "uninstall" ]; then
        echo -e "${YELLOW}[CANCELLED] Aborted by user. No changes were made.${NC}"
        exit 0
    fi
fi

echo -e "\n${GREEN}[1/4] Stopping and purging Docker container stack & volumes...${NC}"
if [ -f "${PROJECT_DIR}/docker/docker-compose.yml" ]; then
    cd "${PROJECT_DIR}/docker"
    docker compose down -v --remove-orphans || true
fi

echo -e "\n${GREEN}[2/4] Removing UFW Firewall rule entries...${NC}"
# Delete rules by port/protocol - UFW does not support deletion by comment
ufw delete allow 1812/udp   2>/dev/null || true
ufw delete allow 1813/udp   2>/dev/null || true
ufw delete allow 80/tcp     2>/dev/null || true
ufw delete allow 443/tcp    2>/dev/null || true
ufw delete allow 22/tcp     2>/dev/null || true
ufw delete allow proto udp to any port 1812 2>/dev/null || true
ufw delete allow proto udp to any port 1813 2>/dev/null || true
echo -e "${GREEN}   UFW rules cleaned (non-existent rules skipped safely).${NC}"


echo -e "\n${GREEN}[3/4] Cleaning generated SSL certificates...${NC}"
rm -rf "${PROJECT_DIR}/docker/ssl"

echo -e "\n${GREEN}[4/4] Purging residual temporary files...${NC}"
rm -rf "${PROJECT_DIR}/docker/mariadb_data" 2>/dev/null || true

echo ""
echo -e "${BLUE}=================================================================${NC}"
echo -e "${GREEN} 🗑️ OMNIRADIUS UNINSTALL COMPLETED SUCCESSFULLY!${NC}"
echo -e " System has been safely purged and returned to default state."
echo -e "${BLUE}=================================================================${NC}"

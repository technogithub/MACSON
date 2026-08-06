#!/usr/bin/env bash
# ==============================================================================
# MACSON - MAC Authentication Centralized Santos Operations Network
# Automated Ubuntu 26.04 / 24.04 / 22.04 Installer
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
    echo -e "${RED}[ERROR] This installer must be run as root (use: sudo ./install.sh)${NC}"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

# Default Configuration Parameters
NON_INTERACTIVE=false
NAS_SUBNET="192.168.1.0/24"
ADMIN_SUBNET="192.168.1.0/24"
RADIUS_SECRET="RadiusSecretKey2026!"

# Parse CLI Arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    --auto|-y)
      NON_INTERACTIVE=true
      shift
      ;;
    --nas-subnet)
      NAS_SUBNET="$2"
      shift 2
      ;;
    --admin-subnet)
      ADMIN_SUBNET="$2"
      shift 2
      ;;
    --secret)
      RADIUS_SECRET="$2"
      shift 2
      ;;
    --help|-h)
      echo "Usage: sudo ./install.sh [OPTIONS]"
      echo "Options:"
      echo "  --auto, -y           Run non-interactively with default or passed values"
      echo "  --nas-subnet CIDR    Allowed NAS Network Subnet (default: 192.168.1.0/24)"
      echo "  --admin-subnet CIDR  Allowed Admin Web UI & SSH Subnet (default: 192.168.1.0/24)"
      echo "  --secret STRING      RADIUS Shared Secret Key"
      exit 0
      ;;
    *)
      echo -e "${RED}Unknown option: $1${NC}"
      exit 1
      ;;
  esac
done

echo -e "${BLUE}=================================================================${NC}"
echo -e "${BLUE}   🛡️  MACSON System - Ubuntu 26.04 Automated Installer          ${NC}"
echo -e "${BLUE}   (MAC Authentication Centralized Santos Operations Network)     ${NC}"
echo -e "${BLUE}=================================================================${NC}"
echo ""

# Verify OS Compatibility
if [ -f /etc/os-release ]; then
    . /etc/os-release
    echo -e "${GREEN}[INFO] Detected OS: ${PRETTY_NAME}${NC}"
    if [[ "$ID" != "ubuntu" && "$ID_LIKE" != *"ubuntu"* ]]; then
        echo -e "${YELLOW}[WARNING] System is not Ubuntu. Continuing at your own risk...${NC}"
    fi
fi

# Interactive Prompts if not running in auto mode
if [ "$NON_INTERACTIVE" = false ]; then
    read -p "Enter NAS Network Subnet (e.g., 192.168.1.0/24) [$NAS_SUBNET]: " INPUT_NAS
    NAS_SUBNET=${INPUT_NAS:-$NAS_SUBNET}

    read -p "Enter Admin Allowed IP/Subnet [$ADMIN_SUBNET]: " INPUT_ADMIN
    ADMIN_SUBNET=${INPUT_ADMIN:-$ADMIN_SUBNET}

    read -p "Enter RADIUS Shared Secret Key [$RADIUS_SECRET]: " INPUT_SECRET
    RADIUS_SECRET=${INPUT_SECRET:-$RADIUS_SECRET}

    echo ""
    echo "-----------------------------------------------------------------"
    echo " Summary Configuration:"
    echo " - NAS Network Subnet     : ${NAS_SUBNET}"
    echo " - Admin Allowed Segment  : ${ADMIN_SUBNET}"
    echo " - RADIUS Shared Secret   : ${RADIUS_SECRET}"
    echo "-----------------------------------------------------------------"
    read -p "Proceed with installation? (y/N): " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        echo -e "${RED}[CANCELLED] Installation aborted by user.${NC}"
        exit 0
    fi
fi

# ------------------------------------------------------------------------------
# 1. Update APT Repository & Install Prerequisites
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}[1/6] Updating APT repositories & installing base packages...${NC}"
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y --no-install-recommends \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release \
    ufw \
    net-tools \
    git \
    openssl \
    netcat-openbsd

# ------------------------------------------------------------------------------
# 2. Check and Install Docker Engine & Compose Plugin
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}[2/6] Verifying & installing Docker Engine...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}Installing Docker Engine & Docker Compose via APT...${NC}"
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg --yes
    chmod a+r /etc/apt/keyrings/docker.gpg

    UBUNTU_CODENAME=$(lsb_release -cs 2>/dev/null || echo "noble")
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu ${UBUNTU_CODENAME} stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

    apt-get update -y
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi

systemctl enable --now docker

# ------------------------------------------------------------------------------
# 3. Configure Firewall (UFW)
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}[3/6] Configuring UFW Firewall Segment Restrictions...${NC}"
ufw --force reset
ufw default deny incoming
ufw default allow outgoing

# Allow SSH & Web UI (80/443) strictly from Admin Segment
ufw allow from "${ADMIN_SUBNET}" to any port 22 proto tcp comment "Admin SSH Access"
ufw allow from "${ADMIN_SUBNET}" to any port 80 proto tcp comment "Admin Web UI HTTP"
ufw allow from "${ADMIN_SUBNET}" to any port 443 proto tcp comment "Admin Web UI HTTPS"

# Allow FreeRADIUS Auth (1812/udp) & Accounting (1813/udp) strictly from NAS Subnet
ufw allow from "${NAS_SUBNET}" to any port 1812 proto udp comment "RADIUS Auth from NAS"
ufw allow from "${NAS_SUBNET}" to any port 1813 proto udp comment "RADIUS Acct from NAS"

ufw --force enable
echo -e "${GREEN}[OK] UFW Firewall Active & Enforced!${NC}"

# ------------------------------------------------------------------------------
# 4. Generate SSL Certificates & RADIUS Configs
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}[4/6] Generating SSL Certificates & FreeRADIUS Configuration...${NC}"
SSL_DIR="${PROJECT_DIR}/docker/ssl"
mkdir -p "${SSL_DIR}"

if [ ! -f "${SSL_DIR}/server.crt" ]; then
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "${SSL_DIR}/server.key" \
        -out "${SSL_DIR}/server.crt" \
        -subj "/C=ID/ST=Jakarta/L=Jakarta/O=OmniRadius/CN=radius.local"
fi

cat <<EOF > "${PROJECT_DIR}/freeradius/clients.conf"
# ==============================================================================
# Auto-generated FreeRADIUS Client Configuration
# ==============================================================================

client localhost {
    ipaddr      = 127.0.0.1
    proto       = *
    secret      = testing123
    shortname   = localhost
}

client nas_network {
    ipaddr      = ${NAS_SUBNET}
    secret      = ${RADIUS_SECRET}
    shortname   = nas_segment
    nas_type    = other
}
EOF

# ------------------------------------------------------------------------------
# 5. Build and Launch Docker Microservices Stack
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}[5/6] Building & Launching OmniRadius Docker Microservices Stack...${NC}"
cd "${PROJECT_DIR}/docker"
docker compose up -d --build

# ------------------------------------------------------------------------------
# 6. Service Health Check & Final Output
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}[6/6] Verifying System Health & Startup Status...${NC}"
sleep 5
bash "${SCRIPT_DIR}/health_check.sh" || true

SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "localhost")

echo ""
echo -e "${BLUE}=================================================================${NC}"
echo -e "${GREEN} 🚀 OMNIRADIUS AUTOMATED INSTALLATION COMPLETED SUCCESSFULLY!${NC}"
echo -e " - Admin Web Interface : ${YELLOW}https://${SERVER_IP}${NC}"
echo -e " - RADIUS Auth Server  : ${YELLOW}UDP 1812 (Allowed from ${NAS_SUBNET})${NC}"
echo -e " - RADIUS Shared Secret: ${YELLOW}${RADIUS_SECRET}${NC}"
echo -e " - Admin IP Restriction: ${YELLOW}Allowed from ${ADMIN_SUBNET}${NC}"
echo -e "${BLUE}=================================================================${NC}"

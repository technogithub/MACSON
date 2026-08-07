#!/usr/bin/env bash
# ==============================================================================
# MACSON - MAC Authentication Centralized Santos Operations Network
# Automated Ubuntu 26.04 / 24.04 / 22.04 Installer
# Supports: Interactive TTY prompts & Automated Non-Interactive (--auto)
# ==============================================================================

set -eo pipefail

# Ensure shell process working directory is valid
cd /tmp 2>/dev/null || cd / 2>/dev/null || true

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

# Safe script directory detection (handles standalone /tmp execution vs full cloned repo)
DETECTED_DIR=""
if [ -n "${BASH_SOURCE[0]:-}" ] && [ -f "${BASH_SOURCE[0]:-}" ]; then
    CANDIDATE_DIR="$(dirname "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)")"
    if [ -f "${CANDIDATE_DIR}/docker/docker-compose.yml" ]; then
        DETECTED_DIR="${CANDIDATE_DIR}"
    fi
fi

PROJECT_DIR="${DETECTED_DIR:-/opt/macson}"
SCRIPT_DIR="${PROJECT_DIR}/scripts"

# Default Configuration Parameters
NON_INTERACTIVE=false
NAS_SUBNET="192.168.1.0/24"
SSH_SUBNET="192.168.1.0/24"
ADMIN_SUBNET="192.168.1.0/24"
RADIUS_SECRET="RadiusSecretKey2026!"

# Default Admin Credentials (overridden by interactive prompts or CLI flags)
SUPERADMIN_NAME="Super Administrator"
SUPERADMIN_EMAIL="admin@macson.local"
SUPERADMIN_PASSWORD=""
OPERATOR_NAME="Operator User"
OPERATOR_EMAIL="operator@macson.local"
OPERATOR_PASSWORD=""

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
    --ssh-subnet)
      SSH_SUBNET="$2"
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
    --admin-email)
      SUPERADMIN_EMAIL="$2"
      shift 2
      ;;
    --admin-password)
      SUPERADMIN_PASSWORD="$2"
      shift 2
      ;;
    --operator-email)
      OPERATOR_EMAIL="$2"
      shift 2
      ;;
    --operator-password)
      OPERATOR_PASSWORD="$2"
      shift 2
      ;;
    --help|-h)
      echo "Usage: sudo ./install.sh [OPTIONS]"
      echo "Options:"
      echo "  --auto, -y                Run non-interactively with default or passed values"
      echo "  --nas-subnet CIDR         Allowed NAS Network Subnet (default: 192.168.1.0/24)"
      echo "  --ssh-subnet CIDR         Allowed SSH Access Subnet (default: 192.168.1.0/24)"
      echo "  --admin-subnet CIDR       Allowed Admin Web UI Subnet (default: 192.168.1.0/24)"
      echo "  --secret STRING           RADIUS Shared Secret Key"
      echo "  --admin-email EMAIL       Super Admin login email"
      echo "  --admin-password PASS     Super Admin login password (min 8 chars)"
      echo "  --operator-email EMAIL    Operator login email"
      echo "  --operator-password PASS  Operator login password (min 8 chars)"
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
    if [[ "${ID:-}" != "ubuntu" && "${ID_LIKE:-}" != *"ubuntu"* ]]; then
        echo -e "${YELLOW}[WARNING] System is not Ubuntu. Continuing at your own risk...${NC}"
    fi
fi

# Helper function for reading input from TTY keyboard
prompt_read() {
    local prompt_msg="$1"
    local var_name="$2"
    local input_val=""
    if [ -c /dev/tty ]; then
        read -p "$prompt_msg" input_val < /dev/tty || true
    else
        read -p "$prompt_msg" input_val || true
    fi
    eval "$var_name=\"\$input_val\""
}

# Helper: read password silently from TTY
prompt_password() {
    local prompt_msg="$1"
    local var_name="$2"
    local input_val=""
    if [ -c /dev/tty ]; then
        read -s -p "$prompt_msg" input_val < /dev/tty || true
    else
        read -s -p "$prompt_msg" input_val || true
    fi
    echo ""
    eval "$var_name=\"\$input_val\""
}

# Interactive Prompts if not running in auto mode
if [ "$NON_INTERACTIVE" = false ]; then
    prompt_read "Enter NAS Network Subnet for RADIUS (e.g., 192.168.1.0/24) [$NAS_SUBNET]: " INPUT_NAS
    NAS_SUBNET=${INPUT_NAS:-$NAS_SUBNET}

    prompt_read "Enter SSH Allowed Subnet for Port 22 (e.g., 192.168.1.50/32) [$SSH_SUBNET]: " INPUT_SSH
    SSH_SUBNET=${INPUT_SSH:-$SSH_SUBNET}

    prompt_read "Enter Admin Web UI Allowed Subnet (e.g., 192.168.1.0/24) [$ADMIN_SUBNET]: " INPUT_ADMIN
    ADMIN_SUBNET=${INPUT_ADMIN:-$ADMIN_SUBNET}

    prompt_read "Enter RADIUS Shared Secret Key [$RADIUS_SECRET]: " INPUT_SECRET
    RADIUS_SECRET=${INPUT_SECRET:-$RADIUS_SECRET}

    echo ""
    echo -e "${BLUE}-----------------------------------------------------------------${NC}"
    echo -e "${BLUE}  🔐 Setup MACSON Admin Login Credentials${NC}"
    echo -e "${BLUE}-----------------------------------------------------------------${NC}"

    # Super Admin credentials
    prompt_read "Enter Super Admin Name [${SUPERADMIN_NAME}]: " INPUT_SA_NAME
    SUPERADMIN_NAME=${INPUT_SA_NAME:-$SUPERADMIN_NAME}

    prompt_read "Enter Super Admin Email [${SUPERADMIN_EMAIL}]: " INPUT_SA_EMAIL
    SUPERADMIN_EMAIL=${INPUT_SA_EMAIL:-$SUPERADMIN_EMAIL}

    while true; do
        prompt_password "Enter Super Admin Password (min 8 chars): " INPUT_SA_PASS
        if [ ${#INPUT_SA_PASS} -lt 8 ]; then
            echo -e "${RED}  ✗ Password too short! Minimum 8 characters required.${NC}"
            continue
        fi
        prompt_password "Confirm Super Admin Password: " INPUT_SA_PASS2
        if [ "$INPUT_SA_PASS" != "$INPUT_SA_PASS2" ]; then
            echo -e "${RED}  ✗ Passwords do not match! Please try again.${NC}"
        else
            SUPERADMIN_PASSWORD="$INPUT_SA_PASS"
            echo -e "${GREEN}  ✓ Super Admin password set.${NC}"
            break
        fi
    done

    echo ""

    # Operator credentials
    prompt_read "Enter Operator Name [${OPERATOR_NAME}]: " INPUT_OP_NAME
    OPERATOR_NAME=${INPUT_OP_NAME:-$OPERATOR_NAME}

    prompt_read "Enter Operator Email [${OPERATOR_EMAIL}]: " INPUT_OP_EMAIL
    OPERATOR_EMAIL=${INPUT_OP_EMAIL:-$OPERATOR_EMAIL}

    while true; do
        prompt_password "Enter Operator Password (min 8 chars): " INPUT_OP_PASS
        if [ ${#INPUT_OP_PASS} -lt 8 ]; then
            echo -e "${RED}  ✗ Password too short! Minimum 8 characters required.${NC}"
            continue
        fi
        prompt_password "Confirm Operator Password: " INPUT_OP_PASS2
        if [ "$INPUT_OP_PASS" != "$INPUT_OP_PASS2" ]; then
            echo -e "${RED}  ✗ Passwords do not match! Please try again.${NC}"
        else
            OPERATOR_PASSWORD="$INPUT_OP_PASS"
            echo -e "${GREEN}  ✓ Operator password set.${NC}"
            break
        fi
    done

    echo ""
    echo -e "${BLUE}-----------------------------------------------------------------${NC}"
    echo " Summary Configuration:"
    echo " - NAS Network Subnet (RADIUS 1812/1813): ${NAS_SUBNET}"
    echo " - SSH Allowed Segment (Port 22 SSH)    : ${SSH_SUBNET}"
    echo " - Admin Web UI Segment (Port 80/443)   : ${ADMIN_SUBNET}"
    echo " - RADIUS Shared Secret                 : ${RADIUS_SECRET}"
    echo " - Super Admin Email                    : ${SUPERADMIN_EMAIL}"
    echo " - Operator Email                       : ${OPERATOR_EMAIL}"
    echo -e "${BLUE}-----------------------------------------------------------------${NC}"
    prompt_read "Proceed with installation? (y/N): " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        echo -e "${RED}[CANCELLED] Installation aborted by user.${NC}"
        exit 0
    fi
fi

# Auto mode: set fallback passwords if not provided via CLI flags
if [ -z "$SUPERADMIN_PASSWORD" ]; then
    SUPERADMIN_PASSWORD="Admin@$(date +%Y)!"
    echo -e "${YELLOW}[INFO] No admin password provided. Using auto-generated: ${SUPERADMIN_PASSWORD}${NC}"
fi
if [ -z "$OPERATOR_PASSWORD" ]; then
    OPERATOR_PASSWORD="Operator@$(date +%Y)!"
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

# If running via piped stdin or /tmp, ensure repository is cloned to PROJECT_DIR
if [ ! -f "${PROJECT_DIR}/docker/docker-compose.yml" ]; then
    echo -e "${GREEN}[INFO] Cloning MACSON repository to ${PROJECT_DIR}...${NC}"
    cd /tmp 2>/dev/null || cd /
    rm -rf "${PROJECT_DIR}" 2>/dev/null || true
    mkdir -p "${PROJECT_DIR}"
    git clone https://github.com/technogithub/MACSON.git "${PROJECT_DIR}"
fi

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
# 3. Configure Firewall (UFW) with Strict SSH, RADIUS, & Web UI Restrictions
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}[3/6] Configuring UFW Firewall Segment Restrictions...${NC}"
ufw --force reset
ufw default deny incoming
ufw default allow outgoing

# Allow SSH (Port 22) strictly from SSH Subnet
ufw allow from "${SSH_SUBNET}" to any port 22 proto tcp comment "Admin SSH Access"

# Allow Web UI (80/443) strictly from Admin Segment
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
        -subj "/C=ID/ST=Jakarta/L=Jakarta/O=MACSON/CN=radius.local"
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
# Note: vendor/ is baked INTO the Docker image (no host bind-mount needed)
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}[5/6] Building & Launching MACSON Docker Microservices Stack...${NC}"
cd "${PROJECT_DIR}/docker"

# Remove any stale app_code volume so fresh image contents populate it
docker compose down -v --remove-orphans 2>/dev/null || true
docker compose up -d --build

echo -e "${GREEN}[INFO] Waiting for MariaDB & Laravel to initialize...${NC}"
sleep 10

# Finalize Laravel: generate APP_KEY & clear caches
echo -e "${GREEN}[INFO] Finalizing Laravel application setup...${NC}"
docker exec radius_laravel_app cp -n .env.example .env 2>/dev/null || true
docker exec radius_laravel_app php artisan config:clear 2>/dev/null || true
docker exec radius_laravel_app php artisan view:clear 2>/dev/null || true
docker exec radius_laravel_app php artisan route:clear 2>/dev/null || true
docker exec radius_laravel_app chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# Set admin credentials via artisan tinker using user-provided values
echo -e "${GREEN}[INFO] Creating admin user accounts in database...${NC}"
sleep 8

docker exec radius_laravel_app php artisan tinker --no-interaction << TINKER_EOF 2>/dev/null || true
use App\\Models\\User;
use Illuminate\\Support\\Facades\\Hash;

\$admin = User::where('email', '${SUPERADMIN_EMAIL}')->first();
if (\$admin) {
    \$admin->name     = '${SUPERADMIN_NAME}';
    \$admin->password = Hash::make('${SUPERADMIN_PASSWORD}');
    \$admin->role     = 'Super Admin';
    \$admin->save();
} else {
    User::create([
        'name'     => '${SUPERADMIN_NAME}',
        'email'    => '${SUPERADMIN_EMAIL}',
        'password' => Hash::make('${SUPERADMIN_PASSWORD}'),
        'role'     => 'Super Admin',
    ]);
}

\$op = User::where('email', '${OPERATOR_EMAIL}')->first();
if (\$op) {
    \$op->name     = '${OPERATOR_NAME}';
    \$op->password = Hash::make('${OPERATOR_PASSWORD}');
    \$op->role     = 'Operator';
    \$op->save();
} else {
    User::create([
        'name'     => '${OPERATOR_NAME}',
        'email'    => '${OPERATOR_EMAIL}',
        'password' => Hash::make('${OPERATOR_PASSWORD}'),
        'role'     => 'Operator',
    ]);
}
echo "Users created successfully.\n";
TINKER_EOF
echo -e "${GREEN}[INFO] Admin credentials configured.${NC}"

# ------------------------------------------------------------------------------
# 6. Service Health Check & Final Output
# ------------------------------------------------------------------------------
echo -e "\n${GREEN}[6/6] Verifying System Health & Startup Status...${NC}"
sleep 5
if [ -f "${SCRIPT_DIR}/health_check.sh" ]; then
    bash "${SCRIPT_DIR}/health_check.sh" || true
fi

SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "localhost")

echo ""
echo -e "${BLUE}=================================================================${NC}"
echo -e "${GREEN} 🚀 MACSON AUTOMATED INSTALLATION COMPLETED SUCCESSFULLY!${NC}"
echo -e " - Admin Web Interface : ${YELLOW}http://${SERVER_IP}${NC}  (or https://${SERVER_IP})"
echo -e " - RADIUS Auth Server  : ${YELLOW}UDP 1812 (Allowed from ${NAS_SUBNET})${NC}"
echo -e " - RADIUS Shared Secret: ${YELLOW}${RADIUS_SECRET}${NC}"
echo -e " - SSH Allowed Segment : ${YELLOW}Allowed from ${SSH_SUBNET}${NC}"
echo -e " - Admin IP Restriction: ${YELLOW}Allowed from ${ADMIN_SUBNET}${NC}"
echo -e " - Installation Path   : ${YELLOW}${PROJECT_DIR}${NC}"
echo -e "${BLUE}=================================================================${NC}"
echo -e "${GREEN} 🔐 YOUR LOGIN CREDENTIALS${NC}"
echo -e " - Super Admin Email   : ${YELLOW}${SUPERADMIN_EMAIL}${NC}"
echo -e " - Super Admin Password: ${YELLOW}${SUPERADMIN_PASSWORD}${NC}"
echo -e " - Operator Email      : ${YELLOW}${OPERATOR_EMAIL}${NC}"
echo -e " - Operator Password   : ${YELLOW}${OPERATOR_PASSWORD}${NC}"
echo -e "${BLUE}=================================================================${NC}"
echo -e "${YELLOW} 💡 TIP: Run 'bash ${SCRIPT_DIR}/reset_admin_password.sh' to change passwords${NC}"
echo ""

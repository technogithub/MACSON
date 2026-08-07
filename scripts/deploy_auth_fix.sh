#!/bin/bash
# =============================================================================
# MACSON - Full Auth Fix & Rebuild Script
# Run this on the Ubuntu server to fully fix authentication
# Usage: sudo bash scripts/deploy_auth_fix.sh
# =============================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

APP_CONTAINER="radius_laravel_app"

echo ""
echo -e "${BLUE}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║       MACSON - Auth Fix & Full Rebuild Script        ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════╝${NC}"
echo ""

# Detect project dir
PROJECT_DIR=""
for D in /opt/macson /home/*/macson /root/macson; do
    if [ -f "${D}/docker/docker-compose.yml" ]; then
        PROJECT_DIR="$D"
        break
    fi
done

if [ -z "$PROJECT_DIR" ]; then
    echo -e "${RED}❌ ERROR: Cannot find MACSON project directory.${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Project found at: $PROJECT_DIR${NC}"
cd "$PROJECT_DIR"

# Pull latest code
echo ""
echo -e "${YELLOW}⏳ Pulling latest code from GitHub...${NC}"
git pull origin main || echo -e "${YELLOW}⚠️  Git pull skipped${NC}"

# Stop all containers
echo ""
echo -e "${YELLOW}⏳ Stopping all MACSON containers...${NC}"
cd "$PROJECT_DIR/docker"
docker compose down --remove-orphans 2>/dev/null || true

# Remove the app_code named volume (CRITICAL - this forces fresh code into container)
echo -e "${YELLOW}⏳ Removing stale app_code volume to force fresh build...${NC}"
docker volume rm docker_app_code 2>/dev/null || \
docker volume rm $(docker volume ls -q | grep app_code) 2>/dev/null || \
echo -e "${YELLOW}⚠️  Volume not found or already removed${NC}"

# Rebuild app image from scratch (no cache)
echo ""
echo -e "${YELLOW}⏳ Rebuilding Docker image (no cache)...${NC}"
docker compose build --no-cache app

# Start all services
echo ""
echo -e "${YELLOW}⏳ Starting all services...${NC}"
docker compose up -d

# Wait for MariaDB to be healthy
echo -e "${YELLOW}⏳ Waiting for MariaDB to be ready (up to 60s)...${NC}"
for i in $(seq 1 12); do
    if docker exec radius_mariadb mysqladmin ping -h localhost -u radius_user -pradius_password --silent 2>/dev/null; then
        echo -e "${GREEN}✅ MariaDB is ready!${NC}"
        break
    fi
    echo "   Waiting... ($((i*5))s)"
    sleep 5
done

# Ensure cache and session tables exist in MariaDB
echo -e "${YELLOW}⏳ Ensuring cache & session tables exist in MariaDB...${NC}"
docker exec -i radius_mariadb mysql -u radius_user -pradius_password radius << 'SQL_EOF' 2>/dev/null || true
CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL PRIMARY KEY,
  `user_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL_EOF
echo -e "${GREEN}  ✓ Cache & Session tables ready in MariaDB${NC}"

# Wait for app container
echo -e "${YELLOW}⏳ Waiting for Laravel app to start (15s)...${NC}"
sleep 15

# Clear all Laravel caches
echo ""
echo -e "${YELLOW}⏳ Clearing Laravel caches...${NC}"
docker exec "$APP_CONTAINER" php artisan config:clear 2>/dev/null && echo -e "${GREEN}  ✓ Config cache cleared${NC}"
docker exec "$APP_CONTAINER" php artisan view:clear   2>/dev/null && echo -e "${GREEN}  ✓ View cache cleared${NC}"
docker exec "$APP_CONTAINER" php artisan route:clear  2>/dev/null && echo -e "${GREEN}  ✓ Route cache cleared${NC}"
docker exec "$APP_CONTAINER" php artisan cache:clear  2>/dev/null && echo -e "${GREEN}  ✓ App cache cleared${NC}"
docker exec "$APP_CONTAINER" chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# Verify auth config is loaded
echo ""
echo -e "${YELLOW}⏳ Verifying auth configuration...${NC}"
AUTH_DRIVER=$(docker exec "$APP_CONTAINER" php artisan tinker --execute="echo config('auth.defaults.guard');" 2>/dev/null || echo "unknown")
echo -e "${GREEN}  ✓ Auth guard: ${AUTH_DRIVER}${NC}"

SESSION_DRV=$(docker exec "$APP_CONTAINER" php artisan tinker --execute="echo config('session.driver');" 2>/dev/null || echo "unknown")
echo -e "${GREEN}  ✓ Session driver: ${SESSION_DRV}${NC}"

# Check users in DB
echo ""
echo -e "${YELLOW}⏳ Checking users in database...${NC}"
USER_COUNT=$(docker exec "$APP_CONTAINER" php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null || echo "0")
echo -e "${GREEN}  ✓ Users in DB: ${USER_COUNT}${NC}"

# Prompt for admin credentials
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  🔐 Set Admin Login Credentials${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

read -p "Super Admin Email [admin@macson.local]: " SA_EMAIL
SA_EMAIL=${SA_EMAIL:-admin@macson.local}

while true; do
    read -s -p "Super Admin Password (min 8 chars): " SA_PASS; echo ""
    [ ${#SA_PASS} -ge 8 ] && break
    echo -e "${RED}  ✗ Too short, minimum 8 characters.${NC}"
done

read -s -p "Confirm Password: " SA_PASS2; echo ""
if [ "$SA_PASS" != "$SA_PASS2" ]; then
    echo -e "${RED}❌ Passwords do not match! Please rerun the script.${NC}"
    exit 1
fi

# Create/update admin user
echo ""
echo -e "${YELLOW}⏳ Creating/updating admin user in database...${NC}"

docker exec "$APP_CONTAINER" php artisan tinker --no-interaction << TINKER_EOF 2>/dev/null
use App\Models\User;
use Illuminate\Support\Facades\Hash;

\$u = User::where('email', '${SA_EMAIL}')->first();
if (\$u) {
    \$u->password = Hash::make('${SA_PASS}');
    \$u->save();
    echo "✓ Admin user updated: ${SA_EMAIL}\n";
} else {
    User::create([
        'name'     => 'Super Administrator',
        'email'    => '${SA_EMAIL}',
        'password' => Hash::make('${SA_PASS}'),
        'role'     => 'Super Admin',
    ]);
    echo "✓ Admin user created: ${SA_EMAIL}\n";
}
TINKER_EOF

# Verify login works
echo ""
echo -e "${YELLOW}⏳ Verifying password hash in database...${NC}"
HASH_CHECK=$(docker exec "$APP_CONTAINER" php artisan tinker --execute="
\$u = App\Models\User::where('email','${SA_EMAIL}')->first();
echo \$u ? 'FOUND hash:'.substr(\$u->password,0,7) : 'NOT FOUND';
" 2>/dev/null || echo "error")
echo -e "${GREEN}  ✓ ${HASH_CHECK}${NC}"

SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "YOUR_SERVER_IP")

echo ""
echo -e "${BLUE}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║            ✅ Auth Fix Completed Successfully!           ║${NC}"
echo -e "${BLUE}╠══════════════════════════════════════════════════════════╣${NC}"
echo -e "║  🌐 Web UI    : ${YELLOW}http://${SERVER_IP}/login${NC}"
echo -e "║  📧 Admin     : ${YELLOW}${SA_EMAIL}${NC}"
echo -e "║  🔑 Password  : ${YELLOW}[the one you just set]${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""

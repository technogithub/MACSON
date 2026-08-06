#!/bin/bash
# =============================================================================
# MACSON - Quick Deploy Auth Fix Script
# Run this on the Ubuntu server to apply authentication changes
# Usage: bash scripts/deploy_auth_fix.sh
# =============================================================================

set -e

COMPOSE_FILE="/opt/macson/docker/docker-compose.yml"
PROJECT_DIR="/opt/macson"
APP_CONTAINER="radius_laravel_app"

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║       MACSON - Auth Fix Deployment Script            ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# Detect project dir
if [ ! -f "$COMPOSE_FILE" ]; then
    # Try to find it
    COMPOSE_FILE="$(find /opt /home -name 'docker-compose.yml' 2>/dev/null | grep macson | head -1)"
    PROJECT_DIR="$(dirname "$(dirname "$COMPOSE_FILE")")"
    if [ -z "$COMPOSE_FILE" ]; then
        echo "❌ ERROR: Cannot find MACSON docker-compose.yml. Is MACSON installed?"
        exit 1
    fi
fi

echo "✅ Project found at: $PROJECT_DIR"
echo ""

# Pull latest from git
echo "⏳ Pulling latest changes from git..."
cd "$PROJECT_DIR"
git pull origin main 2>/dev/null || echo "⚠️  Git pull skipped (no remote or not a git repo)"
echo ""

# Rebuild and restart the app container
echo "⏳ Rebuilding and restarting the Laravel app container..."
cd "$PROJECT_DIR/docker"
docker compose build app
docker compose up -d app

echo "⏳ Waiting for app to start..."
sleep 10

# Clear caches
echo "⏳ Clearing Laravel caches..."
docker exec "$APP_CONTAINER" php artisan config:clear 2>/dev/null || true
docker exec "$APP_CONTAINER" php artisan view:clear 2>/dev/null || true
docker exec "$APP_CONTAINER" php artisan route:clear 2>/dev/null || true
docker exec "$APP_CONTAINER" php artisan cache:clear 2>/dev/null || true
docker exec "$APP_CONTAINER" chmod -R 777 storage bootstrap/cache 2>/dev/null || true

echo ""
echo "⏳ Setting up admin credentials in database..."
sleep 3

docker exec "$APP_CONTAINER" php artisan tinker --no-interaction <<'TINKER_EOF' 2>/dev/null || true
$admin = App\Models\User::where('email', 'admin@radius.local')->first();
if ($admin) {
    $admin->password = bcrypt('Admin@2026!');
    $admin->save();
    echo "✅ Admin password updated.\n";
} else {
    App\Models\User::create([
        'name'     => 'Super Administrator',
        'email'    => 'admin@radius.local',
        'password' => bcrypt('Admin@2026!'),
        'role'     => 'Super Admin',
    ]);
    echo "✅ Admin user created.\n";
}

$op = App\Models\User::where('email', 'operator@radius.local')->first();
if ($op) {
    $op->password = bcrypt('Operator@2026!');
    $op->save();
    echo "✅ Operator password updated.\n";
} else {
    App\Models\User::create([
        'name'     => 'Operator User',
        'email'    => 'operator@radius.local',
        'password' => bcrypt('Operator@2026!'),
        'role'     => 'Operator',
    ]);
    echo "✅ Operator user created.\n";
}
TINKER_EOF

SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "localhost")

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║              ✅ Auth Fix Deployed Successfully!          ║"
echo "╠══════════════════════════════════════════════════════════╣"
echo "║  Web Interface : http://${SERVER_IP}                     "
echo "║  Login URL     : http://${SERVER_IP}/login               "
echo "╠══════════════════════════════════════════════════════════╣"
echo "║  Admin Email   : admin@radius.local                      "
echo "║  Admin Password: Admin@2026!                             "
echo "║  Operator Email: operator@radius.local                   "
echo "║  Op Password   : Operator@2026!                          "
echo "╠══════════════════════════════════════════════════════════╣"
echo "║  ⚠️  Change passwords after first login!                 ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo "💡 TIP: Run 'bash scripts/reset_admin_password.sh' to set custom passwords"
echo ""

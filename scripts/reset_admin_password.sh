#!/bin/bash
# =============================================================================
# MACSON - Reset Admin Password Script
# Run this script on the Ubuntu server AFTER docker-compose is running
# Usage: bash scripts/reset_admin_password.sh
# =============================================================================

set -e

CONTAINER="radius_app"
ADMIN_EMAIL="admin@radius.local"
OPERATOR_EMAIL="operator@radius.local"

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║         MACSON - Admin Password Reset Utility        ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# Check if container is running
if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
    echo "❌ ERROR: Container '${CONTAINER}' is not running!"
    echo "   Please run: docker-compose -f docker/docker-compose.yml up -d"
    exit 1
fi

echo "✅ Container '${CONTAINER}' is running."
echo ""

# Prompt for new Admin password
while true; do
    read -s -p "🔐 Enter new Admin password (min 8 chars): " ADMIN_PASS
    echo ""
    if [ ${#ADMIN_PASS} -ge 8 ]; then break; fi
    echo "⚠️  Password too short, minimum 8 characters required."
done

read -s -p "🔐 Enter new Operator password (min 8 chars) [press Enter to use same]: " OP_PASS
echo ""
if [ -z "$OP_PASS" ]; then
    OP_PASS="$ADMIN_PASS"
fi

echo ""
echo "⏳ Resetting passwords inside container..."

# Use PHP inside the container to generate hash and update DB
docker exec -i "$CONTAINER" php artisan tinker --no-interaction <<EOF
use App\Models\User;
use Illuminate\Support\Facades\Hash;

\$admin = User::where('email', '${ADMIN_EMAIL}')->first();
if (\$admin) {
    \$admin->password = Hash::make('${ADMIN_PASS}');
    \$admin->save();
    echo "✅ Admin password updated for: ${ADMIN_EMAIL}\n";
} else {
    User::create([
        'name'     => 'Super Administrator',
        'email'    => '${ADMIN_EMAIL}',
        'password' => Hash::make('${ADMIN_PASS}'),
        'role'     => 'Super Admin',
    ]);
    echo "✅ Admin user created: ${ADMIN_EMAIL}\n";
}

\$op = User::where('email', '${OPERATOR_EMAIL}')->first();
if (\$op) {
    \$op->password = Hash::make('${OP_PASS}');
    \$op->save();
    echo "✅ Operator password updated for: ${OPERATOR_EMAIL}\n";
} else {
    User::create([
        'name'     => 'Operator User',
        'email'    => '${OPERATOR_EMAIL}',
        'password' => Hash::make('${OP_PASS}'),
        'role'     => 'Operator',
    ]);
    echo "✅ Operator user created: ${OPERATOR_EMAIL}\n";
}

echo "✅ Done!\n";
EOF

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║               ✅ Password Reset Complete!            ║"
echo "╠══════════════════════════════════════════════════════╣"
echo "║  Admin Email   : ${ADMIN_EMAIL}          ║"
echo "║  Operator Email: ${OPERATOR_EMAIL}      ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

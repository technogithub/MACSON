#!/bin/bash
# =============================================================================
# SANTAFE NAC - User Password Reset Utility (Super Admin & Operator)
# Run this script on the Ubuntu server AFTER docker-compose is running
# Usage: bash scripts/reset_admin_password.sh
# =============================================================================

set -e

# Detect container name dynamically (radius_laravel_app, radius_app, or fallback)
CONTAINER=""
for cname in "radius_laravel_app" "radius_app" "app"; do
    if docker ps --format '{{.Names}}' | grep -q "^${cname}$"; then
        CONTAINER="$cname"
        break
    fi
done

ADMIN_USER="admin"
OPERATOR_USER="operator"

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║      SANTAFE NAC - User Password Reset Utility       ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# Check if container was found and is running
if [ -z "$CONTAINER" ]; then
    echo "❌ ERROR: Laravel container ('radius_laravel_app') is not running!"
    echo "   Please run: docker compose -f docker/docker-compose.yml up -d"
    exit 1
fi

echo "✅ Container '${CONTAINER}' is running."
echo ""

# Prompt for user selection
echo "Select account to reset password:"
echo " 1) Super Admin (admin)"
echo " 2) Operator (operator)"
echo " 3) Reset BOTH accounts"
read -p "Select option [1-3]: " CHOICE

ADMIN_PASS=""
OP_PASS=""

if [[ "$CHOICE" == "1" || "$CHOICE" == "3" ]]; then
    while true; do
        read -s -p "🔐 Enter new Super Admin ('admin') password (min 6 chars): " ADMIN_PASS
        echo ""
        if [ ${#ADMIN_PASS} -ge 6 ]; then break; fi
        echo "⚠️  Password too short, minimum 6 characters required."
    done
fi

if [[ "$CHOICE" == "2" || "$CHOICE" == "3" ]]; then
    while true; do
        read -s -p "🔐 Enter new Operator ('operator') password (min 6 chars): " OP_PASS
        echo ""
        if [ ${#OP_PASS} -ge 6 ]; then break; fi
        echo "⚠️  Password too short, minimum 6 characters required."
    done
fi

echo ""
echo "⏳ Resetting passwords inside container..."

# Use PHP inside the container to generate valid Bcrypt hash and update DB
docker exec -i "$CONTAINER" php artisan tinker --no-interaction <<EOF
if (!empty('${ADMIN_PASS}')) {
    \$admin = \App\Models\User::where('username', '${ADMIN_USER}')->orWhere('email', 'admin@radius.local')->first();
    if (\$admin) {
        \$admin->password = \Illuminate\Support\Facades\Hash::make('${ADMIN_PASS}');
        \$admin->save();
        echo "✅ Super Admin ('${ADMIN_USER}') password updated successfully.\n";
    }
}

if (!empty('${OP_PASS}')) {
    \$op = \App\Models\User::where('username', '${OPERATOR_USER}')->orWhere('email', 'operator@radius.local')->first();
    if (\$op) {
        \$op->password = \Illuminate\Support\Facades\Hash::make('${OP_PASS}');
        \$op->save();
        echo "✅ Operator ('${OPERATOR_USER}') password updated successfully.\n";
    }
}

echo "✅ Done!\n";
EOF

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║               ✅ Password Reset Complete!            ║"
echo "╠══════════════════════════════════════════════════════╣"
echo "║  Super Admin Username : admin                        ║"
echo "║  Operator Username    : operator                     ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

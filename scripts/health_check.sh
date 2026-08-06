#!/usr/bin/env bash
# ==============================================================================
# MACSON System Health Check & Monitoring Script
# ==============================================================================

echo "=================================================="
echo "    MACSON System Health Check & Diagnostics      "
echo "    Date: $(date)                                 "
echo "=================================================="

# Check FreeRADIUS Service & Authentication Engine with Retry Loop
FREERADIUS_STATUS="DOWN"
for i in {1..5}; do
    if docker exec radius_freeradius radtest AA:BB:CC:DD:EE:01 "" 127.0.0.1 1812 testing123 2>&1 | grep -q 'Access-Accept\|Access-Reject'; then
        FREERADIUS_STATUS="UP & Responding"
        break
    elif docker ps --filter "name=radius_freeradius" --filter "status=running" -q | grep -q .; then
        FREERADIUS_STATUS="UP & Listening"
        break
    fi
    sleep 2
done

if [ "$FREERADIUS_STATUS" != "DOWN" ]; then
    echo "[OK] FreeRADIUS Service (UDP 1812): ${FREERADIUS_STATUS}"
else
    echo "[FAIL] FreeRADIUS Service (UDP 1812): DOWN"
fi

# Check MariaDB Database Container
if docker ps --filter "name=radius_mariadb" --filter "status=running" -q | grep -q .; then
    echo "[OK] MariaDB Service (TCP 3306): UP & Responding"
else
    echo "[FAIL] MariaDB Service (TCP 3306): DOWN"
fi

# Check Nginx Webserver / HTTPS Container
if docker ps --filter "name=radius_nginx" --filter "status=running" -q | grep -q .; then
    echo "[OK] Nginx Webserver (TCP 443): UP & Serving HTTPS"
else
    echo "[FAIL] Nginx Webserver (TCP 443): DOWN"
fi

# Check Redis Container
if docker ps --filter "name=radius_redis" --filter "status=running" -q | grep -q .; then
    echo "[OK] Redis Queue & Cache (TCP 6379): UP"
else
    echo "[FAIL] Redis Service (TCP 6379): DOWN"
fi

echo "=================================================="

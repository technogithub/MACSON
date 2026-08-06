#!/usr/bin/env bash
# ==============================================================================
# Enterprise RADIUS System Health Check & Monitoring Script
# ==============================================================================

echo "=================================================="
echo "    MACSON System Health Check & Diagnostics      "
echo "    Date: $(date)                                 "
echo "=================================================="

# Check FreeRADIUS Port 1812 UDP
if nc -z -v -u 127.0.0.1 1812 2>&1 | grep -q 'open\|succeeded'; then
    echo "[OK] FreeRADIUS Service (UDP 1812): UP & Listening"
else
    echo "[FAIL] FreeRADIUS Service (UDP 1812): DOWN"
fi

# Check MariaDB Database Port 3306
if nc -z 127.0.0.1 3306 2>&1 | grep -q 'open\|succeeded'; then
    echo "[OK] MariaDB Service (TCP 3306): UP & Responding"
else
    echo "[FAIL] MariaDB Service (TCP 3306): DOWN"
fi

# Check Nginx / HTTPS Port 443
if nc -z 127.0.0.1 443 2>&1 | grep -q 'open\|succeeded'; then
    echo "[OK] Nginx Webserver (TCP 443): UP & Serving HTTPS"
else
    echo "[FAIL] Nginx Webserver (TCP 443): DOWN"
fi

# Check Redis Port 6379
if nc -z 127.0.0.1 6379 2>&1 | grep -q 'open\|succeeded'; then
    echo "[OK] Redis Queue & Cache (TCP 6379): UP"
else
    echo "[FAIL] Redis Service (TCP 6379): DOWN"
fi

echo "=================================================="

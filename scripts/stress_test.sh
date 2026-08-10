#!/usr/bin/env bash
# ==============================================================================
# MACSON RADIUS High-Throughput Stress Testing & Performance Benchmark Tool
# Usage: sudo ./scripts/stress_test.sh [TOTAL_REQUESTS] [CONCURRENCY]
# Example: sudo ./scripts/stress_test.sh 1000 50
# ==============================================================================

set -e

TOTAL_REQUESTS="${1:-1000}"
CONCURRENCY="${2:-50}"
RADIUS_SECRET="RadiusSecretKey2026!"

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}=================================================================${NC}"
echo -e "${BLUE} ⚡ MACSON RADIUS High-Density Stress Testing & Benchmark Tool   ${NC}"
echo -e "${BLUE}=================================================================${NC}"
echo -e " Target Server       : FreeRADIUS (Docker: radius_freeradius)"
echo -e " Total Requests      : ${GREEN}${TOTAL_REQUESTS}${NC}"
echo -e " Parallel Workers    : ${GREEN}${CONCURRENCY}${NC}"
echo -e " Shared Secret Key   : ${RADIUS_SECRET}"
echo -e "${BLUE}-----------------------------------------------------------------${NC}"

# Check if FreeRADIUS container is running
if ! docker ps | grep -q radius_freeradius; then
    echo -e "${RED}[ERROR] FreeRADIUS container (radius_freeradius) is not running!${NC}"
    exit 1
fi

echo -e "${YELLOW}[INFO] Generating batch RADIUS Access-Request payload...${NC}"

# Prepare temporary packet payload inside container
docker exec radius_freeradius bash -c "cat <<EOF > /tmp/macson_stress_payload.txt
User-Name = \"testing\"
User-Password = \"testing\"
NAS-IP-Address = 127.0.0.1
NAS-Port = 1812
Called-Station-Id = \"00-11-22-33-44-55:SSID-Staff\"
Calling-Station-Id = \"AA-BB-CC-11-22-33\"
EOF"

echo -e "${GREEN}[INFO] Executing parallel stress test against UDP 1812...${NC}"
echo ""

START_TIME=$(date +%s%N)

# Run parallel radclient workers
docker exec -i radius_freeradius bash -c "
    python3 -c '
import sys
with open(\"/tmp/macson_stress_payload.txt\", \"r\") as f:
    payload = f.read()
for _ in range($TOTAL_REQUESTS):
    sys.stdout.write(payload + \"\n\")
' | radclient -p $CONCURRENCY -r 1 127.0.0.1:1812 auth $RADIUS_SECRET
" 2>&1 | tee /tmp/macson_stress_output.log || true

END_TIME=$(date +%s%N)

# Calculate metrics
NANOS=$((END_TIME - START_TIME))
ELAPSED_SEC=$(awk -v ns="$NANOS" 'BEGIN {printf "%.3f", ns / 1000000000}')
if [ "$(awk -v e="$ELAPSED_SEC" 'BEGIN {print (e > 0) ? 1 : 0}')" -eq 1 ]; then
    TPS=$(awk -v req="$TOTAL_REQUESTS" -v sec="$ELAPSED_SEC" 'BEGIN {printf "%.2f", req / sec}')
else
    TPS="$TOTAL_REQUESTS"
fi

ACCEPTS=$(grep -iE "Access-Accept|code 2" /tmp/macson_stress_output.log 2>/dev/null | wc -l | tr -d ' ')
REJECTS=$(grep -iE "Access-Reject|code 3" /tmp/macson_stress_output.log 2>/dev/null | wc -l | tr -d ' ')
DROPS=$(grep -iE "No response|timeout" /tmp/macson_stress_output.log 2>/dev/null | wc -l | tr -d ' ')

echo ""
echo -e "${BLUE}=================================================================${NC}"
echo -e "${BLUE} 📊 MACSON RADIUS Stress Test Benchmark Results                 ${NC}"
echo -e "${BLUE}=================================================================${NC}"
echo -e " Total Requests Sent  : ${GREEN}${TOTAL_REQUESTS}${NC}"
echo -e " Total Time Elapsed   : ${GREEN}${ELAPSED_SEC} seconds${NC}"
echo -e " Throughput Speed     : ${GREEN}${TPS} Requests / Second (TPS)${NC}"
echo -e " Access-Accepts       : ${GREEN}${ACCEPTS}${NC}"
echo -e " Access-Rejects       : ${YELLOW}${REJECTS}${NC}"
echo -e " Packets Dropped      : ${RED}${DROPS}${NC}"
echo -e "${BLUE}=================================================================${NC}"

# 🛡️ MACSON (MAC Authentication Centralized Santos Operations Network)
## Enterprise Multi-SSID RADIUS & Dynamic VLAN Management System

---

### 📋 Table of Contents
1. [Project Directory Structure](#1-project-directory-structure)
2. [Architecture & Authentication Flow](#2-architecture--authentication-flow)
3. [Database Schema (MariaDB `radius`)](#3-database-schema-mariadb-radius)
4. [FreeRADIUS Configuration & Unlang Logic](#4-freeradius-configuration--unlang-logic)
5. [Docker Compose Microservices Deployment](#5-docker-compose-microservices-deployment)
6. [Laravel 12 Application & Sanctum API](#6-laravel-12-application--sanctum-api)
7. [Step-by-Step Installation from Scratch (Ubuntu Server 22.04 LTS)](#7-step-by-step-installation-from-scratch-ubuntu-server-2204-lts)
8. [Testing RADIUS Authentication (radtest & radclient)](#8-testing-radius-authentication-radtest--radclient)
9. [Automated Backup, Restore & Monitoring](#9-automated-backup-restore--monitoring)
10. [Production Security & Performance Optimization](#10-production-security--performance-optimization)

---

### 1. Project Directory Structure

```
project/
├── backend-laravel/               # Laravel 12 Web UI & REST API Application
│   ├── app/
│   │   ├── Http/Controllers/      # Dashboard, Device, Log, and API Controllers
│   │   ├── Models/                # Device, RadiusLog, AuditLog Models
│   │   └── Services/              # MAC Address Standardizer Service
│   ├── database/migrations/       # Database Schemas & Migrations
│   ├── resources/views/           # Bootstrap 5 Dark Mode Blade Templates
│   └── routes/                    # Web & Sanctum API Routes
├── freeradius/                    # FreeRADIUS v3 Configuration
│   ├── clients.conf               # NAS Access Points & Router Client Definitions
│   ├── mods-available/sql         # MySQL / MariaDB Integration Module
│   ├── queries.conf               # Custom Unlang / SQL Authentication Queries
│   └── sites-available/default    # RADIUS Virtual Server & Authorization Logic
├── sql/
│   └── schema.sql                 # Production MariaDB Database DDL & Seeders
├── docker/
│   ├── docker-compose.yml         # Container Orchestration (Nginx, PHP, MariaDB, FreeRADIUS, Redis)
│   ├── Dockerfile                 # PHP 8.3 FPM Application Image
│   ├── freeradius.Dockerfile      # FreeRADIUS Ubuntu Container Image
│   └── nginx.conf                 # Nginx SSL & Security Headers Configuration
├── scripts/
│   ├── backup_db.sh               # Automated Database Backup Script
│   ├── restore_db.sh              # Database Restore Utility
│   └── health_check.sh            # Service Health & Port Diagnostics
└── docs/
    └── README.md                  # Comprehensive Operations Documentation
```

---

### 2. Architecture & Authentication Flow

When a device connects to a Network Access Server (Mikrotik, Cisco, Aruba, or Access Point):

```
+----------------+            +-------------------+            +---------------------+            +-------------------+
|  Client Device |  802.1X /  | NAS Access Point  | RADIUS UDP |     FreeRADIUS      | SQL Query  | MariaDB Database  |
| (Laptop/Phone) | ---------> | (Mikrotik/Cisco)  | ---------> |   (UDP 1812/1813)   | ---------> |  (`radius` db)    |
+----------------+  MAC Auth  +-------------------+ 1812 / 1813+---------------------+            +-------------------+
                                                                     |
                                                                     v
                                                            1. Extract Calling-Station-Id
                                                            2. Normalize MAC to AA:BB:CC:DD:EE:FF
                                                            3. Query `devices` table status
                                                            4. Log attempt in `radius_log`
                                                                     |
                                                +--------------------+--------------------+
                                                |                                         |
                                         [Status = active]                         [Status = inactive/not found]
                                                |                                         |
                                                v                                         v
                                         Access-Accept                             Access-Reject
```

---

### 3. Database Schema (MariaDB `radius`)

The primary database is `radius`. Key tables:

#### `devices` Table
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `mac_address` (VARCHAR 17, UNIQUE, INDEX) — e.g. `AA:BB:CC:DD:EE:FF`
- `raw_mac` (VARCHAR 17) — Original input before sanitization
- `device_name` (VARCHAR 100)
- `location` (VARCHAR 150)
- `description` (TEXT)
- `status` (ENUM: `active`, `inactive`)
- `created_at`, `updated_at` (TIMESTAMP)

#### `radius_log` Table
- `id` (BIGINT, PK, AUTO_INCREMENT)
- `log_date` (TIMESTAMP)
- `mac_address` (VARCHAR 17)
- `username` (VARCHAR 64)
- `nas_ip` (VARCHAR 45)
- `auth_result` (ENUM: `ACCEPT`, `REJECT`)
- `reason` (VARCHAR 255)

---

### 4. FreeRADIUS Configuration & Unlang Logic

FreeRADIUS sanitizes all incoming MAC addresses regardless of vendor delimiter (`-`, `:`, `.`, or raw hex) using `unlang` regex matching:

```unlang
# Unlang MAC Normalization in /etc/freeradius/3.0/sites-available/default
update control {
    Tmp-Raw-MAC := "%{toupper:%{string:Calling-Station-Id}}"
}

if ("%{control:Tmp-Raw-MAC}" =~ /^([0-9A-F]{2})[\:\-\.]?([0-9A-F]{2})[\:\-\.]?([0-9A-F]{2})[\:\-\.]?([0-9A-F]{2})[\:\-\.]?([0-9A-F]{2})[\:\-\.]?([0-9A-F]{2})$/) {
    update control {
        Clean-Calling-Station-Id := "%{1}:%{2}:%{3}:%{4}:%{5}:%{6}"
    }
}
```

---

### 5. Step-by-Step Deployment Guide (Zero to Online)

#### Step 1: Clone Repository & Navigate to Directory
```bash
git clone https://github.com/your-org/omni-radius.git /opt/omni-radius
cd /opt/omni-radius/project/docker
```

#### Step 2: Build & Launch Docker Stack
```bash
docker-compose up -d --build
```

#### Step 3: Verify Container Health
```bash
docker-compose ps
```

Expected output:
```
NAME                    STATUS                  PORTS
radius_freeradius       running                 0.0.0.0:1812-1813->1812-1813/udp
radius_laravel_app      running                 9000/tcp
radius_mariadb          running (healthy)       0.0.0.0:3306->3306/tcp
radius_nginx            running                 0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
radius_redis            running                 0.0.0.0:6379->6379/tcp
```

---

### 6. Testing RADIUS Authentication (`radtest` & `radclient`)

#### Test Case 1: Active Registered Device (Expect Access-Accept)
Run `radtest` from terminal inside the FreeRADIUS container or host:
```bash
docker exec -it radius_freeradius radtest AA:BB:CC:DD:EE:01 "" localhost 1812 testing123
```

Expected Terminal Output:
```
Sending Access-Request of id 198 to 127.0.0.1 port 1812
        User-Name = "AA:BB:CC:DD:EE:01"
        User-Password = ""
        NAS-IP-Address = 127.0.0.1
        NAS-Port = 0
Received Access-Accept Id 198 from 127.0.0.1:1812 in 2ms
```

#### Test Case 2: Inactive Registered Device (Expect Access-Reject)
```bash
docker exec -it radius_freeradius radtest AA:BB:CC:DD:EE:03 "" localhost 1812 testing123
```

Expected Terminal Output:
```
Received Access-Reject Id 199 from 127.0.0.1:1812 in 1ms
```

---

### 7. REST API Authentication (Laravel Sanctum)

#### Endpoint 1: Get All Devices (`GET /api/device`)
```bash
curl -X GET https://radius.local/api/device \
  -H "Authorization: Bearer <SANCTUM_TOKEN>" \
  -H "Accept: application/json"
```

#### Endpoint 2: Add New Device (`POST /api/device`)
```bash
curl -X POST https://radius.local/api/device \
  -H "Authorization: Bearer <SANCTUM_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "mac_address": "AA-CC-11-22-33-44",
    "device_name": "New Access Point Unit",
    "location": "Warehouse B",
    "description": "API Created Device",
    "status": "active"
  }'
```

---

### 8. Production Best Practices & Troubleshooting

#### Common Issue: "Access-Reject: Invalid MAC Address Format"
- **Cause**: Input MAC contains invalid hex characters or non-standard length.
- **Solution**: Check `radius_log` table for exact raw input:
  ```sql
  SELECT * FROM radius_log WHERE auth_result = 'REJECT' ORDER BY log_date DESC LIMIT 5;
  ```

#### Performance Optimizations
1. **FreeRADIUS Connection Pool**: Set `max = 32` and `min = 4` in `mods-available/sql` to avoid DB socket starvation under peak load.
2. **Indexing**: Ensure index `idx_mac_status` on `(mac_address, status)` is active on `devices` table for sub-millisecond lookups.
3. **Laravel OPcache**: Keep OPcache enabled in `Dockerfile` for PHP 8.3 FPM.

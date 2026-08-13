# 🛡️ SANTAFE NAC (Santos Advanced Network Traffic & Access Filtering Engine)

> **Enterprise Multi-SSID RADIUS, Dynamic VLAN & UniFi Hotspot Voucher Management System for Ubiquiti UniFi & Enterprise Access Points**

![Release](https://img.shields.io/github/v/release/technogithub/MACSON?color=green&label=Release)
![Ubuntu](https://img.shields.io/badge/Ubuntu-26.04%20%7C%2024.04%20LTS-orange?logo=ubuntu)
![UniFi](https://img.shields.io/badge/UniFi-Network%20Integration-055BF6?logo=ubiquiti)
![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-v3.0-blue?logo=freeradius)
![Laravel](https://img.shields.io/badge/Laravel-12.x-red?logo=laravel)
![Docker](https://img.shields.io/badge/Docker-Microservices-2496ED?logo=docker)
![phpMyAdmin](https://img.shields.io/badge/phpMyAdmin-Port%208080-6C78AF?logo=phpmyadmin)
![License](https://img.shields.io/badge/License-MIT-brightgreen)

---

## 📋 Table of Contents
1. [Overview & Key Features](#-overview--key-features)
2. [Ubiquiti UniFi RADIUS & Multi-SSID Architecture](#-ubiquiti-unifi-radius--multi-ssid-architecture)
3. [Step-by-Step Ubuntu Installation Modes](#-step-by-step-ubuntu-installation-modes)
   - [Mode 1: Interactive Installation (6 Simple Prompts)](#mode-1-interactive-installation-6-simple-prompts)
   - [Mode 2: One-Liner Automatic Silent Mode](#mode-2-one-liner-automatic-silent-mode)
   - [Mode 3: Parameterized One-Liner Custom Mode](#mode-3-parameterized-one-liner-custom-mode)
   - [Mode 4: Local Git Clone & Custom Execution](#mode-4-local-git-clone--custom-execution)
4. [phpMyAdmin Database Management (Port 8080)](#-phpmyadmin-database-management-port-8080)
5. [Automated Log Pruning & Scaling](#-automated-log-pruning--scaling)
6. [Ubiquiti UniFi Controller Setup Guide](#-ubiquiti-unifi-controller-setup-guide)
7. [Project Directory Structure](#-project-directory-structure)
8. [Database Schema (MariaDB `radius`)](#-database-schema-mariadb-radius)
9. [FreeRADIUS Unlang & Dynamic VLAN Tagging](#-freeradius-unlang--dynamic-vlan-tagging)
10. [Testing RADIUS Authentication (`radtest`)](#-testing-radius-authentication-radtest)
11. [Restoring Stable Release (`v1.0.0-stable`)](#-restoring-stable-release-v100-stable)
12. [Automated Uninstallation](#-automated-uninstallation)
13. [Author & License](#-author--license)

---

## ✨ Overview & Key Features

**SANTAFE NAC** is a production-ready, enterprise-grade Network Access Control (NAC) system engineered for high-density **Ubiquiti UniFi Access Points**, UniFi Dream Machines (UDM), and enterprise Wireless Access Points. It handles central MAC address authentication, Target SSID filtering, dynamic IEEE 802.1Q VLAN assignment, UniFi Hotspot Voucher Generation, strict Role-Based Access Control (RBAC), firewall isolation, and comprehensive access logging.

* 🎫 **UniFi Hotspot Voucher Module (SJA SEMARANG HOTSPOT)**: Batch generate guest Wi-Fi vouchers with custom durations (Days, Hours, Minutes), speed limits, data quotas, printable thermal/A4 slips, and real-time UniFi UDM Controller API integration.
* 🔄 **Offline Resilience & Auto-Sync Worker**: Generates and revokes vouchers locally even when UniFi Controller is offline, then automatically syncs pending items when UniFi comes back online.
* 🔐 **Role-Based Access Control (RBAC)**: Supports **Super Admin** (`admin`) for full system configuration and **Operator** (`operator`) dedicated strictly to Guest Voucher management.
* 👤 **Username / Email Authentication**: Login flexibly using concise usernames (`admin`, `operator`) or full email addresses.
* 📶 **Target SSID MAC Authorization**: Restrict specific device MAC addresses to one specific UniFi SSID (e.g. `SSID-Staff`) or grant access to `ALL` SSIDs via interactive dropdowns.
* 🏷️ **UniFi Dynamic VLAN Assignment**: Automatically returns RADIUS `Tunnel-Private-Group-Id` to place connected devices into designated UniFi VLANs (e.g., VLAN 10, 20, 30).
* 🔒 **Strict SSH & Network Firewall Restrictions**: Enforces UFW Firewall rules separating **Port 22 SSH** (`$SSH_SUBNET`), **Web Admin UI & phpMyAdmin** (`$ADMIN_SUBNET`), and **RADIUS 1812/1813 UDP** (`$NAS_SUBNET`).
* 🧹 **Unlang MAC Address Sanitization**: Standardizes any incoming MAC delimiter format from UniFi APs (`AA-BB-CC-DD-EE-FF`, `aabbccddeeff`, `AA:BB:CC:DD:EE:FF`) to normalized `AA:BB:CC:DD:EE:FF`.
* 🗄️ **phpMyAdmin Integration (Port 8080)**: Includes an integrated Web Database Manager container for instant MariaDB inspection.
* ⚡ **Streamlined Interactive Installation**: 6 simple keyboard prompts for ultra-fast Ubuntu Server deployment.
* 🧹 **Automated Log Pruning**: Daily automated pruning task (`radius:prune-logs`) to keep database size lean and prevent disk saturation.
* 🎨 **Futuristic Glassmorphism Web Dashboard**: High-tech UI featuring Google Fonts (Outfit & Inter), responsive offcanvas navigation for mobile devices, and Chart.js analytics.

---

## 📐 Ubiquiti UniFi RADIUS & Multi-SSID Architecture

```
+----------------+            +-----------------------+            +---------------------+            +-------------------+
|  Client Device |  802.1X /  |  Ubiquiti UniFi AP /  | RADIUS UDP |     FreeRADIUS      | SQL Query  | MariaDB Database  |
| (Laptop/Phone) | ---------> | UniFi Controller (NAS)| ---------> |   (UDP 1812/1813)   | ---------> |  (`radius` db)    |
+----------------+  MAC Auth  +-----------------------+ 1812 / 1813+---------------------+            +-------------------+
                                                                          |
                                                                          v
                                                                 1. Extract Calling-Station-Id (Device MAC)
                                                                 2. Parse Target SSID from Called-Station-Id (AP-MAC:SSID)
                                                                 3. Normalize MAC to AA:BB:CC:DD:EE:FF
                                                                 4. Query `devices` & `ssids` tables
                                                                 5. Log attempt in `radius_log`
                                                                          |
                                                     +--------------------+--------------------+
                                                     |                                         |
                                              [Status = active]                         [Status = inactive/not found]
                                                     |                                         |
                                                     v                                         v
                                         Access-Accept + Dynamic VLAN                   Access-Reject
```

---

## 🛠️ Step-by-Step Ubuntu Installation Modes

Deploy **MACSON** on your fresh **Ubuntu Server 26.04 / 24.04 / 22.04 LTS** using any of the following modes:

### Mode 1: Interactive Installation (6 Simple Prompts)
*Recommended for quick interactive installation with on-screen prompts:*

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh -o /tmp/install.sh && sudo bash /tmp/install.sh
```

**Interactive Prompts Displayed on Screen:**
1. `1. Enter NAS Network Subnet for RADIUS (e.g., 192.168.1.0/24) [192.168.1.0/24]:`
2. `2. Enter SSH Allowed Subnet for Port 22 (e.g., 192.168.1.0/24) [192.168.1.0/24]:`
3. `3. Enter Admin Web UI Allowed Subnet (e.g., 192.168.1.0/24) [192.168.1.0/24]:`
4. `4. Enter RADIUS Shared Secret Key [RadiusSecretKey2026!]:`
5. `5. Enter MariaDB Root User Password (min 8 chars) [default: RootPassword2026!]:` *(hidden input)*
6. `6. Enter Web Admin Login Password (min 8 chars):` *(hidden input for admin@macson.local)*

---

### Mode 2: One-Liner Automatic Silent Mode
*Zero-touch installation using secure default parameters:*

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh | sudo bash -s -- --auto
```

---

### Mode 3: Parameterized One-Liner Custom Mode
*Pass custom subnets, RADIUS secret, and admin credentials in 1 line without interactive prompts:*

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh | sudo bash -s -- --auto \
  --nas-subnet 192.168.1.0/24 \
  --ssh-subnet 192.168.1.50/32 \
  --admin-subnet 192.168.1.0/24 \
  --secret UniFiRadiusSecret2026! \
  --db-root-password "MyMariaDBRootPass123!" \
  --admin-password "MySecurePass123!"
```

---

### Mode 4: Local Git Clone & Custom Execution
```bash
git clone https://github.com/technogithub/MACSON.git /opt/macson
cd /opt/macson
sudo bash scripts/install.sh
```

---

## 🗄️ phpMyAdmin Database Management (Port 8080)

MACSON includes **phpMyAdmin** pre-configured out-of-the-box for visual database administration:

- **Web Access URL**: `http://<SERVER-IP>:8080`
- **Server**: `mariadb`
- **Username**: `root` (or `radius_user`)
- **Password**: The custom MariaDB root password configured during `install.sh`.

---

## 🧹 Automated Log Pruning & Scaling

To prevent database disk saturation on high-density networks (handling thousands of authentication attempts daily), MACSON includes an automated Artisan log pruning task:

- **Automated Schedule**: Runs daily at **02:00 AM** to purge logs older than 30 days.
- **Manual Execution**:
  ```bash
  # Prune logs older than 30 days
  docker exec radius_laravel_app php artisan radius:prune-logs

  # Prune logs older than N days (e.g., 7 days)
  docker exec radius_laravel_app php artisan radius:prune-logs --days=7
  ```

---

## 📶 Ubiquiti UniFi Controller Setup Guide

To connect your **Ubiquiti UniFi Controller** to **MACSON**:

1. **Create RADIUS Profile in UniFi Network**:
   - Open UniFi Network Controller ➔ **Settings** ➔ **Profiles** ➔ **RADIUS**.
   - Click **Create New Profile**:
     - **Profile Name**: `MACSON-RADIUS`
     - **Authentication Host**: `<YOUR-UBUNTU-SERVER-IP>`
     - **Port**: `1812`
     - **Shared Secret**: Your configured RADIUS secret (e.g. `RadiusSecretKey2026!`)
     - **Accounting Host**: `<YOUR-UBUNTU-SERVER-IP>`
     - **Accounting Port**: `1813`
2. **Configure UniFi WiFi Wireless Network**:
   - Go to **Settings** ➔ **WiFi**.
   - Select your WiFi Network (e.g. `SSID-Staff`) ➔ Edit:
     - **Authentication**: Set to `MAC ID Authentication` or `802.1X Enterprise`
     - **RADIUS Profile**: Select `MACSON-RADIUS`
     - **MAC Address Format**: `AA:BB:CC:DD:EE:FF` or `AA-BB-CC-DD-EE-FF`
     - **Enable Dynamic VLAN**: Check **Use RADIUS assigned VLANs** *(Required for Dynamic VLAN tagging)*

---

## 📁 Project Directory Structure

```
MACSON/
├── .github/
│   └── workflows/ci.yml           # GitHub Actions Automated CI Build & Linter
├── backend-laravel/               # Laravel 12 Web UI & REST API Application
│   ├── app/
│   │   ├── Http/Controllers/      # Dashboard, Device, Log, Ssid & API Controllers
│   │   └── Models/                # Device, Ssid, RadiusLog, AuditLog Models
│   ├── resources/views/           # Futuristic Glassmorphism Dark Views
│   └── routes/                    # Web, Console & Sanctum API Routes
├── freeradius/                    # FreeRADIUS v3 Configuration
│   ├── clients.conf               # UniFi NAS AP & Gateway Definitions
│   ├── queries.conf               # Custom Multi-SSID & Dynamic VLAN SQL Queries
│   └── sites-available/default    # Virtual Server Policy & Unlang Logic
├── sql/
│   └── schema.sql                 # Production MariaDB Database DDL & Seeders
├── docker/
│   ├── docker-compose.yml         # Microservices (Nginx, PHP, MariaDB, FreeRADIUS, Redis, phpMyAdmin)
│   ├── Dockerfile                 # PHP 8.3 FPM Image Build File
│   ├── freeradius.Dockerfile      # FreeRADIUS Container Build File
│   └── nginx.conf                 # Nginx SSL & Security Headers Config
├── scripts/
│   ├── install.sh                 # Automated 6-Prompt Ubuntu Installer
│   ├── uninstall.sh               # Automated Clean Uninstaller Script
│   ├── health_check.sh            # Service Diagnostic & Port Monitoring Script
│   ├── backup_db.sh               # Automated Database Backup Utility
│   └── restore_db.sh              # Database Restore Utility
└── README.md                      # Production Operations & Setup Manual
```

---

## ⚡ FreeRADIUS Unlang & Dynamic VLAN Tagging

FreeRADIUS inspects `Called-Station-Id` sent by UniFi APs and injects IEEE 802.1Q reply attributes upon successful authentication:

```unlang
# Inject Dynamic IEEE 802.1Q VLAN reply attributes for UniFi APs
if ("%{request:Tmp-String-5}" != "0" && "%{request:Tmp-String-5}" != "") {
    update reply {
        Tunnel-Type := VLAN
        Tunnel-Medium-Type := IEEE-802
        Tunnel-Private-Group-Id := "%{request:Tmp-String-5}"
    }
}
```

---

## 🧪 Testing RADIUS Authentication (`radtest`)

Execute `radtest` from host or inside FreeRADIUS container to simulate a UniFi AP request:

```bash
# Option A: Test from Ubuntu Host with production secret
radtest testing testing 127.0.0.1 1812 RadiusSecretKey2026!

# Option B: Test inside FreeRADIUS container
docker exec -it radius_freeradius radtest testing testing 127.0.0.1 1812 testing123
```

Expected Terminal Output:
```text
Sending Access-Request of id 198 to 127.0.0.1 port 1812
        User-Name = "testing"
        User-Password = "testing"
        NAS-IP-Address = 127.0.0.1
Received Access-Accept Id 198 from 127.0.0.1:1812 in 2ms
        Tunnel-Type:0 = VLAN
        Tunnel-Medium-Type:0 = IEEE-802
        Tunnel-Private-Group-Id:0 = "10"
```

---

## 📌 Restoring Stable Release (`v1.0.0-stable`)

MACSON maintains permanent git release tags. To revert your codebase to the verified stable release at any time:

```bash
cd /opt/macson
git checkout v1.0.0-stable
docker compose -f docker/docker-compose.yml restart freeradius
```

---

## 🗑️ Automated Uninstallation

To cleanly purge containers, data, and firewall rules from Ubuntu:

```bash
sudo bash scripts/uninstall.sh --auto
```

---

## 👤 Author & License

* **Author**: Santos ([@technogithub](https://github.com/technogithub))
* **Project Repository**: [https://github.com/technogithub/MACSON](https://github.com/technogithub/MACSON)
* **License**: MIT License


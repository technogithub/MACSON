# 🛡️ MACSON (MAC Authentication Centralized Santos Operations Network)

> **Enterprise Multi-SSID RADIUS & Dynamic VLAN Management System for Ubiquiti UniFi & Enterprise Access Points**

![Release](https://img.shields.io/github/v/release/technogithub/MACSON?color=green&label=Release)
![Ubuntu](https://img.shields.io/badge/Ubuntu-26.04%20%7C%2024.04%20LTS-orange?logo=ubuntu)
![UniFi](https://img.shields.io/badge/UniFi-Network%20Integration-055BF6?logo=ubiquiti)
![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-v3.0-blue?logo=freeradius)
![Laravel](https://img.shields.io/badge/Laravel-12.x-red?logo=laravel)
![Docker](https://img.shields.io/badge/Docker-Microservices-2496ED?logo=docker)
![License](https://img.shields.io/badge/License-MIT-brightgreen)

---

## 📋 Table of Contents
1. [Overview & Key Features](#-overview--key-features)
2. [Ubiquiti UniFi RADIUS & Multi-SSID Architecture](#-ubiquiti-unifi-radius--multi-ssid-architecture)
3. [Step-by-Step Ubuntu Installation Modes](#-step-by-step-ubuntu-installation-modes)
   - [Mode 1: Interactive Installation (Terminal Keyboard Prompts)](#mode-1-interactive-installation-terminal-keyboard-prompts)
   - [Mode 2: One-Liner Automatic Silent Mode](#mode-2-one-liner-automatic-silent-mode)
   - [Mode 3: Parameterized One-Liner Custom Mode](#mode-3-parameterized-one-liner-custom-mode)
   - [Mode 4: Local Git Clone & Custom Execution](#mode-4-local-git-clone--custom-execution)
4. [Ubiquiti UniFi Controller Setup Guide](#-ubiquiti-unifi-controller-setup-guide)
5. [Project Directory Structure](#-project-directory-structure)
6. [Database Schema (MariaDB `radius`)](#-database-schema-mariadb-radius)
7. [FreeRADIUS Unlang & Dynamic VLAN Tagging](#-freeradius-unlang--dynamic-vlan-tagging)
8. [Testing RADIUS Authentication (`radtest`)](#-testing-radius-authentication-radtest)
9. [Automated Uninstallation](#-automated-uninstallation)
10. [Author & License](#-author--license)

---

## ✨ Overview & Key Features

**MACSON** is a production-ready, enterprise-grade Network Access Control (NAC) system engineered for high-density **Ubiquiti UniFi Access Points**, UniFi Dream Machines (UDM), and enterprise Wireless Access Points. It handles central MAC address authentication, Multi-SSID filtering, dynamic IEEE 802.1Q VLAN assignment, strict SSH IP restrictions, and comprehensive access logging.

* 📶 **Multi-SSID MAC Address Filtering**: Grant or restrict specific device MAC addresses to one or multiple UniFi SSIDs (e.g. `SSID-Staff`, `SSID-IoT`, `SSID-VIP`, `SSID-Guest`).
* 🏷️ **UniFi Dynamic VLAN Assignment**: Automatically returns RADIUS `Tunnel-Private-Group-Id` to place connected devices into designated UniFi VLANs (e.g., VLAN 10, 20, 30).
* 🔒 **Strict SSH & Network Firewall Restrictions**: Enforces UFW Firewall rules separating **Port 22 SSH** (`$SSH_SUBNET`), **Web Admin UI** (`$ADMIN_SUBNET`), and **RADIUS 1812/1813 UDP** (`$NAS_SUBNET`).
* 🧹 **Unlang MAC Address Sanitization**: Standardizes any incoming MAC delimiter format from UniFi APs (`AA-BB-CC-DD-EE-FF`, `aabbccddeeff`, `AA:BB:CC:DD:EE:FF`) to normalized `AA:BB:CC:DD:EE:FF`.
* ⚡ **Flexible Installation Modes**: Supports Interactive Keyboard Prompts, Automated Silent Mode, and One-Liner Parameterized Setup for **Ubuntu Server 26.04 / 24.04 / 22.04 LTS**.
* 🐳 **Docker Microservices Architecture**: Isolated container stack featuring Nginx (HTTPS SSL), PHP 8.3 FPM, MariaDB 10.11, FreeRADIUS v3, and Redis.
* 💻 **Laravel 12 Dashboard & REST API**: Responsive Bootstrap 5 dark-mode management portal and Sanctum-secured REST API.

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

### Mode 1: Interactive Installation (Terminal Keyboard Prompts)
*Recommended if you want the installer to ask you on-screen for custom subnets and secrets:*

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh -o /tmp/install.sh && sudo bash /tmp/install.sh
```

**Interactive Prompts Displayed on Screen:**
1. `Enter NAS Network Subnet for RADIUS (e.g., 192.168.1.0/24) [192.168.1.0/24]:`
2. `Enter SSH Allowed Subnet for Port 22 (e.g., 192.168.1.50/32) [192.168.1.0/24]:`
3. `Enter Admin Web UI Allowed Subnet (e.g., 192.168.1.0/24) [192.168.1.0/24]:`
4. `Enter RADIUS Shared Secret Key [RadiusSecretKey2026!]:`
5. `Enter Super Admin Name [Super Administrator]:`
6. `Enter Super Admin Email [admin@macson.local]:`
7. `Enter Super Admin Password (min 8 chars):` *(hidden input)*
8. `Confirm Super Admin Password:` *(hidden input)*
9. `Enter Operator Name [Operator User]:`
10. `Enter Operator Email [operator@macson.local]:`
11. `Enter Operator Password (min 8 chars):` *(hidden input)*
12. `Confirm Operator Password:` *(hidden input)*

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
  --admin-email admin@mycompany.local \
  --admin-password "MySecurePass123!" \
  --operator-email operator@mycompany.local \
  --operator-password "OpSecurePass123!"
```

---

### Mode 4: Local Git Clone & Custom Execution
```bash
git clone https://github.com/technogithub/MACSON.git /opt/macson
cd /opt/macson
sudo bash scripts/install.sh
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
     - **Shared Secret**: Your configured secret (e.g. `UniFiRadiusSecret2026!`)
     - **Accounting Host**: `<YOUR-UBUNTU-SERVER-IP>`
     - **Accounting Port**: `1813`
2. **Configure UniFi WiFi Wireless Network**:
   - Go to **Settings** ➔ **WiFi**.
   - Select your WiFi Network (e.g. `SSID-Staff`) ➔ Edit:
     - **Authentication**: Set to `MAC ID Authentication` or `802.1X Enterprise`
     - **RADIUS Profile**: Select `MACSON-RADIUS`
     - **MAC Address Format**: `AA:BB:CC:DD:EE:FF` or `AA-BB-CC-DD-EE-FF`
     - **Enable Dynamic VLAN**: `Enabled` (Check *Use RADIUS assigned VLANs*)

---

## 🔐 Admin Authentication & Login

MACSON includes a **secure login system** — credentials are set **by you** during the installation process.

### Login Credentials
Credentials are entered by you during `install.sh`. After installation, the summary screen will display your email and the password reminder.

> ⚠️ Passwords are stored as **bcrypt hash** in the database — never in plain text.

### Resetting Password
```bash
bash /opt/macson/scripts/reset_admin_password.sh
```

### Manual Reset via Docker (emergency)
```bash
docker exec radius_laravel_app php artisan tinker
# Inside tinker:
$u = App\Models\User::where('email','your@email.local')->first();
$u->password = bcrypt('NewPassword123!');
$u->save();
```

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
│   ├── resources/views/           # Dark Mode Dashboard & Management Views
│   └── routes/                    # Web & Sanctum API Routes
├── freeradius/                    # FreeRADIUS v3 Configuration
│   ├── clients.conf               # UniFi NAS AP & Gateway Definitions
│   ├── queries.conf               # Custom Multi-SSID & Dynamic VLAN SQL Queries
│   └── sites-available/default    # Virtual Server Policy & Unlang Logic
├── sql/
│   └── schema.sql                 # Production MariaDB Database DDL & Seeders
├── docker/
│   ├── docker-compose.yml         # Container Stack (Nginx, PHP, MariaDB, FreeRADIUS, Redis)
│   ├── Dockerfile                 # PHP 8.3 FPM Image Build File
│   ├── freeradius.Dockerfile      # FreeRADIUS Container Build File
│   └── nginx.conf                 # Nginx SSL & Security Headers Config
├── scripts/
│   ├── install.sh                 # Automated Ubuntu 26.04 Installer Script
│   ├── uninstall.sh               # Automated Clean Uninstaller Script
│   ├── health_check.sh            # Service Diagnostic & Port Monitoring Script
│   ├── backup_db.sh               # Automated Database Backup Utility
│   └── restore_db.sh              # Database Restore Utility
└── README.md                      # Production Operations & Setup Manual
```

---

## 🗄️ Database Schema (MariaDB `radius`)

### 1. `ssids` Table (Master SSID Inventory & Dynamic VLAN)
| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment primary key |
| `ssid_name` | VARCHAR(64) | Broadcasted UniFi SSID (e.g. `SSID-Staff`, `SSID-IoT`) |
| `vlan_id` | INT UNSIGNED | IEEE 802.1Q Dynamic VLAN ID (e.g. `10`, `20`, `30`) |
| `status` | ENUM | `active` / `inactive` |

### 2. `devices` Table (MAC Address Inventory)
| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | BIGINT (PK) | Auto increment primary key |
| `mac_address` | VARCHAR(17) | Formatted MAC (`AA:BB:CC:DD:EE:FF`) |
| `ssid` | VARCHAR(64) | Primary SSID or `ALL` |
| `device_name` | VARCHAR(100) | Human readable device name |
| `status` | ENUM | `active` / `inactive` |

### 3. `device_ssids` Table (Multi-SSID Pivot Mapping)
| Field | Type | Description |
| :--- | :--- | :--- |
| `device_id` | BIGINT (FK) | Reference to `devices.id` |
| `ssid_id` | BIGINT (FK) | Reference to `ssids.id` (NULL = Authorized for `ALL` SSIDs) |

---

## ⚡ FreeRADIUS Unlang & Dynamic VLAN Tagging

FreeRADIUS inspects `Called-Station-Id` sent by UniFi APs and injects IEEE 802.1Q reply attributes upon successful authentication:

```unlang
# Inject Dynamic IEEE 802.1Q VLAN reply attributes for UniFi APs
if ("%{control:Assigned-VLAN-ID}" != "0" && "%{control:Assigned-VLAN-ID}" != "") {
    update reply {
        Tunnel-Type := VLAN
        Tunnel-Medium-Type := IEEE-802
        Tunnel-Private-Group-Id := "%{control:Assigned-VLAN-ID}"
    }
}
```

---

## 🧪 Testing RADIUS Authentication (`radtest`)

Execute `radtest` directly inside the FreeRADIUS container to simulate a UniFi AP request:

```bash
# Test Authorized Device on UniFi SSID-Staff (Returns Access-Accept & Dynamic VLAN 10)
docker exec -it radius_freeradius radtest AA:BB:CC:DD:EE:01 "" localhost 1812 testing123
```

Expected Terminal Output:
```text
Sending Access-Request of id 198 to 127.0.0.1 port 1812
        User-Name = "AA:BB:CC:DD:EE:01"
        User-Password = ""
        NAS-IP-Address = 127.0.0.1
Received Access-Accept Id 198 from 127.0.0.1:1812 in 2ms
        Tunnel-Type:0 = VLAN
        Tunnel-Medium-Type:0 = IEEE-802
        Tunnel-Private-Group-Id:0 = "10"
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

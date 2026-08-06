# 🛡️ MACSON (MAC Authentication Centralized Santos Operations Network)

> **Enterprise Multi-SSID RADIUS & Dynamic VLAN Management System**

![Release](https://img.shields.io/github/v/release/technogithub/MACSON?color=green&label=Release)
![Ubuntu](https://img.shields.io/badge/Ubuntu-26.04%20%7C%2024.04%20LTS-orange?logo=ubuntu)
![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-v3.0-blue?logo=freeradius)
![Laravel](https://img.shields.io/badge/Laravel-12.x-red?logo=laravel)
![Docker](https://img.shields.io/badge/Docker-Microservices-2496ED?logo=docker)
![License](https://img.shields.io/badge/License-MIT-brightgreen)

---

## 📋 Table of Contents
1. [Overview & Key Features](#-overview--key-features)
2. [One-Liner Automatic Installation](#-one-liner-automatic-installation)
3. [Architecture & Authentication Flow](#-architecture--authentication-flow)
4. [Project Directory Structure](#-project-directory-structure)
5. [Database Schema (MariaDB `radius`)](#-database-schema-mariadb-radius)
6. [FreeRADIUS Unlang & Dynamic VLAN Tagging](#-freeradius-unlang--dynamic-vlan-tagging)
7. [Testing RADIUS Authentication (`radtest`)](#-testing-radius-authentication-radtest)
8. [Automated Uninstallation](#-automated-uninstallation)
9. [Author & License](#-author--license)

---

## ✨ Overview & Key Features

**MACSON** is a production-ready, enterprise-grade Network Access Control (NAC) system engineered for high-density WiFi environments (MikroTik, UniFi, Cisco, Aruba, Ruckus). It handles MAC address authentication, Multi-SSID filtering, dynamic VLAN assignment, and comprehensive access logging.

* 📶 **Multi-SSID MAC Address Filtering**: Grant or restrict specific device MAC addresses to one or multiple SSIDs (e.g. `SSID-Staff`, `SSID-IoT`, `SSID-VIP`, `SSID-Guest`).
* 🏷️ **Dynamic IEEE 802.1Q VLAN Assignment**: Automatically returns RADIUS `Tunnel-Private-Group-Id` to place connected devices into their designated VLANs.
* 🧹 **Unlang MAC Address Sanitization**: Standardizes any incoming MAC delimiter format (`AA-BB-CC-DD-EE-FF`, `aabbccddeeff`, `AA:BB:CC:DD:EE:FF`) to normalized `AA:BB:CC:DD:EE:FF`.
* ⚡ **One-Liner Automated Installer**: Zero-touch installation for **Ubuntu Server 26.04 / 24.04 / 22.04 LTS**.
* 🐳 **Docker Microservices Architecture**: Isolated container stack featuring Nginx (HTTPS SSL), PHP 8.3 FPM, MariaDB 10.11, FreeRADIUS v3, and Redis.
* 💻 **Laravel 12 Dashboard & REST API**: Responsive Bootstrap 5 dark-mode management portal and Sanctum-secured REST API.

---

## 🚀 One-Liner Automatic Installation

Run this single command on a fresh **Ubuntu Server 26.04 / 24.04 / 22.04 LTS** instance as `root` / `sudo`:

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh | sudo bash -s -- --auto
```

### Manual Clone & Custom Installation:
```bash
git clone https://github.com/technogithub/MACSON.git /opt/macson
cd /opt/macson
sudo bash scripts/install.sh --nas-subnet 192.168.1.0/24 --admin-subnet 192.168.1.0/24 --secret MySecretKey2026!
```

---

## 📐 Architecture & Authentication Flow

```
+----------------+            +-------------------+            +---------------------+            +-------------------+
|  Client Device |  802.1X /  | NAS Access Point  | RADIUS UDP |     FreeRADIUS      | SQL Query  | MariaDB Database  |
| (Laptop/Phone) | ---------> | (Mikrotik/Cisco)  | ---------> |   (UDP 1812/1813)   | ---------> |  (`radius` db)    |
+----------------+  MAC Auth  +-------------------+ 1812 / 1813+---------------------+            +-------------------+
                                                                      |
                                                                      v
                                                             1. Extract Calling-Station-Id
                                                             2. Parse Target SSID from Called-Station-Id
                                                             3. Normalize MAC to AA:BB:CC:DD:EE:FF
                                                             4. Query `devices` & `ssids` tables
                                                             5. Log attempt in `radius_log`
                                                                      |
                                                 +--------------------+--------------------+
                                                 |                                         |
                                          [Status = active]                         [Status = inactive/not found]
                                                 |                                         |
                                                 v                                         v
                                     Access-Accept + VLAN Tag                       Access-Reject
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
│   ├── clients.conf               # NAS Access Points & Network Gateway Definitions
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
| `ssid_name` | VARCHAR(64) | Broadcasted SSID (e.g. `SSID-Staff`, `SSID-IoT`) |
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

FreeRADIUS inspects `Called-Station-Id` and injects IEEE 802.1Q reply attributes upon successful authentication:

```unlang
# Inject Dynamic IEEE 802.1Q VLAN reply attributes if VLAN ID > 0
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

Execute `radtest` directly inside the FreeRADIUS container:

```bash
# Test Authorized Device on SSID-Staff (Returns Access-Accept & VLAN 10)
docker exec -it radius_freeradius radtest AA:BB:CC:DD:EE:01 "" localhost 1812 testing123
```

Expected Output:
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

To cleanly purge containers, data, and firewall rules:

```bash
sudo bash scripts/uninstall.sh --auto
```

---

## 👤 Author & License

* **Author**: Santos ([@technogithub](https://github.com/technogithub))
* **Project Repository**: [https://github.com/technogithub/MACSON](https://github.com/technogithub/MACSON)
* **License**: MIT License

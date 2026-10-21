# 🛡️ SANTAFE NAC (Santos Advanced Network Traffic & Access Filtering Engine)

> **Enterprise Multi-SSID RADIUS & Dynamic VLAN Management System for Ubiquiti UniFi & Enterprise Access Points**

![Release](https://img.shields.io/github/v/release/technogithub/MACSON?color=green&label=Release)
![Ubuntu](https://img.shields.io/badge/Ubuntu-24.04%20%7C%2022.04%20LTS-orange?logo=ubuntu)
![UniFi](https://img.shields.io/badge/UniFi-Network%20Integration-055BF6?logo=ubiquiti)
![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-v3.0-blue?logo=freeradius)
![Kea DHCP](https://img.shields.io/badge/Kea-DHCPv4-blueviolet)
![Laravel](https://img.shields.io/badge/Laravel-11.x-red?logo=laravel)
![Docker](https://img.shields.io/badge/Docker-Microservices-2496ED?logo=docker)
![License](https://img.shields.io/badge/License-MIT-brightgreen)

---

## 📋 Table of Contents

1. [Overview & Key Features](#-overview--key-features)
2. [Ubiquiti UniFi RADIUS & Multi-SSID Architecture](#-ubiquiti-unifi-radius--multi-ssid-architecture)
3. [Standard Network Subnets & VLANs Reference](#-standard-network-subnets--vlans-reference)
4. [Step-by-Step Ubuntu Installation Modes](#-step-by-step-ubuntu-installation-modes)
   - [Mode 1: Interactive Installation (Terminal Prompts)](#mode-1-interactive-installation-terminal-prompts)
   - [Mode 2: One-Liner Automatic Silent Mode](#mode-2-one-liner-automatic-silent-mode)
   - [Mode 3: Parameterized One-Liner Custom Mode](#mode-3-parameterized-one-liner-custom-mode)
   - [Mode 4: Local Git Clone & Custom Execution](#mode-4-local-git-clone--custom-execution)
5. [Ubiquiti UniFi Controller Setup Guide](#-ubiquiti-unifi-controller-setup-guide)
6. [Kea DHCPv4 Server Integration](#-kea-dhcpv4-server-integration)
7. [Database Schema (MariaDB `radius`)](#-database-schema-mariadb-radius)
8. [FreeRADIUS Unlang & Dynamic VLAN Tagging](#-freeradius-unlang--dynamic-vlan-tagging)
9. [Project Directory Structure](#-project-directory-structure)
10. [Testing RADIUS Authentication (`radtest`)](#-testing-radius-authentication-radtest)
11. [Author & License](#-author--license)

---

## ✨ Overview & Key Features

**SANTAFE NAC** is a production-ready, enterprise-grade Network Access Control (NAC) system engineered for high-density **Ubiquiti UniFi Access Points**, UniFi Switches, UniFi Gateways, and enterprise Wireless Access Points. It handles central MAC address authentication bypass (MAB), multi-SSID access filtering, dynamic IEEE 802.1Q VLAN assignment, native Kea DHCPv4 pool & reservation management, UniFi Hotspot Voucher provisioning, and audit logging.

* 📶 **Multi-SSID MAC Address Filtering**: Grant or restrict specific device MAC addresses to one or multiple UniFi SSIDs (e.g. `CORP-WIFI`, `GUEST-WIFI`, `IOT-WIFI`) or `ALL`.
* 🏷️ **Dynamic IEEE 802.1Q VLAN Assignment**: Automatically returns RADIUS `Tunnel-Private-Group-Id` to place connected devices into designated UniFi VLANs (e.g., VLAN 10, 20, 30).
* 🌐 **High-Performance Kea DHCPv4**: Integrated multi-subnet management, static IP reservations with auto-detect matching, live dynamic lease polling, and subnet-isolated clear lease mechanisms.
* 🎫 **UniFi Hotspot Voucher Engine**: Bulk voucher generation with customizable time duration, data quota, and upload/download bandwidth throttles, supporting thermal slip & A4 print formats.
* 🎨 **Multi-Theme UI System**: Real-time theme switching (Cyber Blue, Emerald Shield, Crimson Protocol, Purple Void) persistent across sessions.
* 🔄 **Interactive Column Sorting**: Instant ascending and descending sort on all data tables for smooth enterprise inventory navigation.
* 🔒 **Strict Network Security**: Enforces granular firewall rules separating SSH access, Web Administration UI, and RADIUS UDP traffic.
* 🧹 **Unlang MAC Address Sanitization**: Standardizes any incoming MAC delimiter format from UniFi APs (`AA-BB-CC-DD-EE-FF`, `aabbccddeeff`, `AA:BB:CC:DD:EE:FF`) to normalized `AA:BB:CC:DD:EE:FF`.
* 🐳 **Docker Microservices Architecture**: Isolated container stack featuring Nginx, PHP 8.3 FPM, MariaDB 10.11, FreeRADIUS v3, and Kea DHCPv4.

---

## 📐 Ubiquiti UniFi RADIUS & Multi-SSID Architecture

```
+----------------+            +-----------------------+            +---------------------+            +-------------------+
|  Client Device |  802.1X /  |  Ubiquiti UniFi AP /  | RADIUS UDP |     FreeRADIUS      | SQL Query  | MariaDB Database  |
| (Laptop/Phone) | ---------> | UniFi Controller (NAS)| ---------> |   (UDP 1812/1813)   | ---------> |   (`radius` DB)   |
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
                                                     |
                                                     v
                                         +-----------------------+
                                         |   Kea DHCPv4 Server   |
                                         |  (Dynamic & Static)   |
                                         +-----------------------+
```

---

## 🌐 Standard Network Subnets & VLANs Reference

All examples throughout SANTAFE configurations use standard RFC 1918 private subnets:

| VLAN ID | Subnet Name | Target SSID | Subnet CIDR | Dynamic DHCP Pool Range | Default Gateway |
|:---:|:---|:---|:---|:---|:---|
| **10** | Corporate Staff | `CORP-WIFI` | `192.168.10.0/24` | `192.168.10.100 - 192.168.10.200` | `192.168.10.1` |
| **20** | Guest / Visitors | `GUEST-WIFI` | `192.168.20.0/24` | `192.168.20.50 - 192.168.20.250` | `192.168.20.1` |
| **30** | IoT & Devices | `IOT-WIFI` | `192.168.30.0/24` | `192.168.30.10 - 192.168.30.200` | `192.168.30.1` |
| **99** | Management Net | *(Internal Wired)* | `192.168.99.0/24` | `Static IP Allocations Only` | `192.168.99.1` |

---

## 🛠️ Step-by-Step Ubuntu Installation Modes

Deploy **SANTAFE NAC** on your fresh **Ubuntu Server 24.04 / 22.04 LTS** using any of the following modes:

### Mode 1: Interactive Installation (Terminal Prompts)
*Recommended for initial setup with on-screen guidance:*

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh -o /tmp/install.sh && sudo bash /tmp/install.sh
```

**Interactive Prompts:**
1. `Enter NAS Network Subnet for RADIUS (e.g., 192.168.1.0/24) [192.168.1.0/24]:`
2. `Enter SSH Allowed Subnet for Port 22 (e.g., 192.168.1.50/32) [192.168.1.0/24]:`
3. `Enter Admin Web UI Allowed Subnet (e.g., 192.168.1.0/24) [192.168.1.0/24]:`
4. `Enter RADIUS Shared Secret Key [SantafeRadiusSecret2026!]:`
5. `Enter MariaDB Root Password:`
6. `Enter Admin Web Login Password:`

---

### Mode 2: One-Liner Automatic Silent Mode
*Zero-touch deployment with secure defaults:*

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh | sudo bash -s -- --auto
```

---

### Mode 3: Parameterized One-Liner Custom Mode
*Pass all parameters directly via command-line arguments:*

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh | sudo bash -s -- --auto \
  --nas-subnet 192.168.1.0/24 \
  --ssh-subnet 192.168.1.50/32 \
  --admin-subnet 192.168.1.0/24 \
  --secret SantafeRadiusSecret2026! \
  --db-root-password "SecureDbRootPass2026!" \
  --admin-password "SecureAdminWebPass2026!"
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

To connect your **Ubiquiti UniFi Controller** to **SANTAFE NAC**:

1. **Create RADIUS Profile in UniFi Network**:
   - Open UniFi Network Controller ➔ **Settings** ➔ **Profiles** ➔ **RADIUS**.
   - Click **Create New Profile**:
     - **Profile Name**: `SANTAFE-RADIUS`
     - **Authentication Host**: `<YOUR-SANTAFE-SERVER-IP>` (e.g., `192.168.1.10`)
     - **Port**: `1812`
     - **Shared Secret**: Your configured secret
     - **Accounting Host**: `<YOUR-SANTAFE-SERVER-IP>`
     - **Accounting Port**: `1813`
2. **Configure UniFi WiFi Wireless Network**:
   - Go to **Settings** ➔ **WiFi**.
   - Select your target SSID (e.g. `CORP-WIFI`) ➔ Edit:
     - **Authentication**: Set to `MAC ID Authentication` or `802.1X Enterprise`
     - **RADIUS Profile**: Select `SANTAFE-RADIUS`
     - **MAC Address Format**: `AA:BB:CC:DD:EE:FF` or `AA-BB-CC-DD-EE-FF`
     - **Enable Dynamic VLAN**: `Enabled` (Check *Use RADIUS assigned VLANs*)

---

## 🌐 Kea DHCPv4 Server Integration

SANTAFE automatically generates Kea JSON configuration upon any subnet or static reservation update:

```json
{
  "Dhcp4": {
    "subnet4": [
      {
        "id": 10,
        "subnet": "192.168.10.0/24",
        "pools": [
          { "pool": "192.168.10.100 - 192.168.10.200" }
        ],
        "option-data": [
          { "name": "routers", "data": "192.168.10.1" },
          { "name": "domain-name-servers", "data": "1.1.1.1, 8.8.8.8" }
        ],
        "reservations": [
          {
            "hw-address": "00:11:22:33:44:55",
            "ip-address": "192.168.10.55",
            "hostname": "PC-STAFF-01"
          }
        ]
      }
    ]
  }
}
```

---

## 🗄️ Database Schema (MariaDB `radius`)

### Core Tables Summary:
- `users`: Administrator and Operator credentials.
- `devices`: Whitelisted MAC addresses, designated SSID target, location, device name, and optional custom VLAN ID.
- `ssids`: Registered enterprise SSIDs, default VLAN ID, and operational state.
- `dhcp_subnets`: Subnet configuration, IP ranges, router, DNS, and lease times.
- `dhcp_static_leases`: Static DHCP reservations linked to registered devices.
- `unifi_vouchers`: Hotspot guest vouchers generated and synced with UniFi Controller API.
- `unifi_config`: Connection parameters for UniFi Network Application.
- `radius_log`: Comprehensive authentication audit trail.

---

## ⚡ FreeRADIUS Unlang & Dynamic VLAN Tagging

```unlang
# 1. Normalize MAC delimiter format
update request {
    Tmp-String-0 := "%{tolower:%{Calling-Station-Id}}"
}

# 2. Extract SSID from Called-Station-Id (Format: "AP-MAC:SSID")
# ...

# 3. Dynamic IEEE 802.1Q VLAN reply injection
if ("%{request:Tmp-String-5}" != "0" && "%{request:Tmp-String-5}" != "") {
    update reply {
        Tunnel-Type := VLAN
        Tunnel-Medium-Type := IEEE-802
        Tunnel-Private-Group-Id := "%{request:Tmp-String-5}"
    }
}
```

---

## 📁 Project Directory Structure

```
SANTAFE/
├── backend-laravel/          # Laravel 11 Application & API
│   ├── app/                  # Controllers, Models, Services
│   ├── resources/views/      # Glassmorphism Dark Mode Views
│   └── routes/web.php        # Application Web Routes
├── freeradius/               # FreeRADIUS v3 Configuration
│   ├── clients.conf          # NAS AP Subnet Definitions
│   ├── queries.conf          # Custom SQL Auth & VLAN Queries
│   └── sites-available/      # Virtual Server Policies
├── docker/                   # Docker Compose & Container Configurations
│   └── docker-compose.yml    # Microservices Stack
├── scripts/                  # Management & Diagnostic Shell Scripts
│   ├── install.sh            # Ubuntu Automated Installer
│   └── health_check.sh       # Service & Port Monitoring
└── README.md                 # Project Documentation
```

---

## 🧪 Testing RADIUS Authentication (`radtest`)

```bash
radtest 00:11:22:33:44:55 00:11:22:33:44:55 127.0.0.1 1812 <SHARED_SECRET>
```

**Expected Successful Response:**
```text
Sending Access-Request of id 1 to 127.0.0.1 port 1812
        User-Name = "00:11:22:33:44:55"
        User-Password = "00:11:22:33:44:55"
Received Access-Accept Id 1 from 127.0.0.1:1812 in 2ms
        Tunnel-Type:0 = VLAN
        Tunnel-Medium-Type:0 = IEEE-802
        Tunnel-Private-Group-Id:0 = "10"
```

---

## 👤 Author & License

- **Author**: Santos ([@technogithub](https://github.com/technogithub))
- **Repository**: [https://github.com/technogithub/MACSON](https://github.com/technogithub/MACSON)
- **License**: MIT License

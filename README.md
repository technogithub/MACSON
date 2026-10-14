# 🛡️ SANTAFE NAC (Santos Advanced Network Traffic & Access Filtering Engine)

> **Enterprise Multi-SSID RADIUS, Dynamic VLAN, DHCP Management & UniFi Hotspot Voucher System for Ubiquiti UniFi & Enterprise Access Points**

![Release](https://img.shields.io/github/v/release/technogithub/MACSON?color=green&label=Release)
![Ubuntu](https://img.shields.io/badge/Ubuntu-26.04%20%7C%2024.04%20LTS-orange?logo=ubuntu)
![UniFi](https://img.shields.io/badge/UniFi-Network%20Integration-055BF6?logo=ubiquiti)
![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-v3.0-blue)
![Laravel](https://img.shields.io/badge/Laravel-12.x-red?logo=laravel)
![Kea DHCP](https://img.shields.io/badge/Kea-DHCPv4-blueviolet)
![Docker](https://img.shields.io/badge/Docker-Microservices-2496ED?logo=docker)
![phpMyAdmin](https://img.shields.io/badge/phpMyAdmin-Port%208080-6C78AF?logo=phpmyadmin)
![License](https://img.shields.io/badge/License-MIT-brightgreen)

---

## 📋 Table of Contents

1. [Overview & Key Features](#-overview--key-features)
2. [Architecture Overview](#-architecture-overview)
3. [Web Application Modules](#-web-application-modules)
   - [Dashboard](#1-dashboard)
   - [MAC Address Device Management](#2-mac-address-device-management)
   - [Multi-SSID Management](#3-multi-ssid-management)
   - [DHCP Server Management (Kea DHCPv4)](#4-dhcp-server-management-kea-dhcpv4)
   - [RADIUS Authentication Logs](#5-radius-authentication-logs)
   - [UniFi Hotspot Voucher Management](#6-unifi-hotspot-voucher-management)
4. [Role-Based Access Control (RBAC)](#-role-based-access-control-rbac)
5. [CSV Import & Export Reference](#-csv-import--export-reference)
6. [Database Schema](#-database-schema)
7. [Installation Guide](#-installation-guide)
8. [UniFi Controller Setup Guide](#-unifi-controller-setup-guide)
9. [Kea DHCP Integration](#-kea-dhcp-integration)
10. [FreeRADIUS Unlang & Dynamic VLAN](#-freeradius-unlang--dynamic-vlan)
11. [Project Directory Structure](#-project-directory-structure)
12. [Testing RADIUS Authentication](#-testing-radius-authentication)
13. [Maintenance & Operations](#-maintenance--operations)
14. [Author & License](#-author--license)

---

## ✨ Overview & Key Features

**SANTAFE NAC** adalah sistem **Network Access Control (NAC)** enterprise yang didesain untuk jaringan WiFi berbasis **Ubiquiti UniFi** berskala besar. Sistem ini mengintegrasikan MAC-based RADIUS authentication, manajemen DHCP, manajemen SSID & VLAN dinamis, voucher hotspot, dan audit log dalam satu platform terpadu.

### Fitur Utama:

| Modul | Keterangan |
|---|---|
| 🔐 **RADIUS MAC Auth** | Otentikasi perangkat berdasarkan MAC Address lewat FreeRADIUS |
| 📡 **Multi-SSID & VLAN** | Pembatasan device ke SSID tertentu dengan VLAN tagging dinamis |
| 🖥️ **DHCP Management** | Manajemen subnet & reservasi static IP via Kea DHCPv4 |
| 📤 **CSV Bulk Import** | Import massal device dan reservasi IP via file CSV |
| 🎫 **Hotspot Voucher** | Generate & cetak voucher WiFi tamu dengan integrasi UniFi API |
| 📊 **Analytics Dashboard** | Real-time chart autentikasi (Accept/Reject) per jam |
| 📋 **Audit Log** | Log semua aktivitas RADIUS dengan filter & export CSV |
| 🔒 **RBAC** | Dua level role: Super Admin & Operator |

---

## 🏗️ Architecture Overview

```
+----------------+   MAC Auth  +-----------------------+   RADIUS UDP  +---------------------+   SQL Query   +-------------------+
|  Client Device |  ---------> |  Ubiquiti UniFi AP /  |  ---------->  |     FreeRADIUS      |  ---------->  | MariaDB Database  |
| (Laptop/Phone) |             | UniFi Controller (NAS) |  1812 / 1813  |   (UDP 1812/1813)   |               |   (radius db)     |
+----------------+             +-----------------------+               +---------------------+               +-------------------+
                                                                               |
                                                                               v
                                                                  1. Extract MAC dari Calling-Station-Id
                                                                  2. Parse SSID dari Called-Station-Id
                                                                  3. Normalize MAC → AA:BB:CC:DD:EE:FF
                                                                  4. Query tabel devices & ssids
                                                                  5. Log hasil ke tabel radius_log
                                                                               |
                                                           +-------------------+--------------------+
                                                           |                                        |
                                                    [Status = active]                     [Status = inactive/not found]
                                                           |                                        |
                                                           v                                        v
                                               Access-Accept + Dynamic VLAN               Access-Reject
                                                           |
                                                           v
                                          +-----------------------------+
                                          |   Kea DHCPv4 Server         |
                                          |  (Static IP Reservations)   |
                                          +-----------------------------+
```

---

## 🖥️ Web Application Modules

### 1. Dashboard

**Rute**: `GET /`  
**Akses**: Super Admin

Halaman utama yang menampilkan ringkasan operasional jaringan secara real-time:

- **Total Device Aktif / Nonaktif** — jumlah device yang terdaftar dan status aktifnya
- **Log Autentikasi Hari Ini** — total autentikasi (Accept + Reject) dalam 24 jam terakhir
- **Grafik Autentikasi per Jam** — line chart interaktif (Chart.js) menampilkan Accept vs Reject per jam
- **Activity Feed Terbaru** — 8 log autentikasi RADIUS terkini dalam format readable

---

### 2. MAC Address Device Management

**Rute**: `GET /devices`  
**Akses**: Super Admin

Modul inti untuk manajemen daftar perangkat yang diizinkan mengakses jaringan WiFi melalui RADIUS.

#### Sub-Fitur:

##### 🔎 Pencarian & Filter
- **Pencarian real-time**: Filter device berdasarkan MAC Address, SSID, Device Name, Lokasi, atau Deskripsi
- **Tab SSID Navigation**: Navigasi cepat berdasarkan kelompok SSID utama (SJA REG, SJA AP, DEVICE OPS AP, dan lainnya)
- **Filter Status**: Tampilkan semua / hanya device aktif / hanya device nonaktif
- **Pagination**: Pilih tampilan 15 / 50 / 100 baris per halaman

##### ➕ Tambah Device Baru (`POST /devices`)
- Input **MAC Address** (format otomatis dinormalisasi ke `AA:BB:CC:DD:EE:FF`)
- Pilih **Target SSID** — dari daftar SSID yang terdaftar, atau gunakan `ALL` untuk semua SSID
- Input **Device Name**, **Lokasi**, **Deskripsi**
- Atur **Status** (Aktif / Nonaktif)
- Input **VLAN ID Override** opsional (1–4094)
- **Reservasi IP Static Opsional**: Langsung reservasi IP DHCP saat menambah device:
  - Pilih **Subnet/Pool IP** dari daftar subnet yang terdaftar
  - Input IP manual **atau** klik tombol **"Saran IP"** untuk saran IP kosong otomatis
  - SJA AP: dropdown pool otomatis dicocokkan jika ada keyword `SJA AP` dalam nama pool

##### ✏️ Edit Device (`PUT /devices/{id}`)
- Semua field yang sama dengan tambah device
- Edit / perbarui / hapus **Reservasi Static IP** yang sudah ada dari form edit yang sama

##### 🔄 Toggle Status (`PATCH /devices/{id}/toggle`)
- Aktifkan / nonaktifkan device dalam satu klik tanpa membuka form edit

##### 📤 Import Device via CSV (`POST /devices/import`)
- Upload file CSV untuk mendaftarkan banyak device sekaligus
- **Kolom CSV**:

  ```csv
  MAC Address, SSID, Device Name, Location, Description, Status, VLAN ID, IP Address, Subnet
  ```

- `IP Address` dan `Subnet` bersifat **opsional** — jika diisi, sistem otomatis membuat reservasi static IP DHCP
- Kolom `Subnet` bisa berisi nama subnet, VLAN, atau CIDR; jika kosong, sistem **auto-detect** subnet dari range IP
- Duplikat (MAC + SSID) otomatis dilewati (skip)
- Jika device sudah terdaftar tetapi ada IP baru, reservasi IP dibuatkan otomatis
- Kea DHCP di-sync 1 kali di akhir proses batch (hemat resource)
- **Download Template CSV**: Tersedia tombol download template langsung dari UI modal

##### 📥 Download Template CSV (`GET /devices/template-csv`)
- Unduh file template CSV siap pakai dengan header dan contoh data baris

##### 📊 Export Device ke CSV (`GET /devices/export`)
- Export semua device termasuk kolom:
  - MAC Address, SSID, Device Name, Location, Description, Status, VLAN ID
  - **Reserved IP** dan **Subnet Pool** (kolom baru — menampilkan reservasi static IP yang terhubung)

---

### 3. Multi-SSID Management

**Rute**: `GET /ssids`  
**Akses**: Super Admin

Manajemen daftar SSID yang dikenali oleh sistem RADIUS.

- **Tambah SSID** baru dengan nama, VLAN ID, deskripsi, dan status (Aktif/Nonaktif)
- **Edit SSID** yang sudah ada
- **Hapus SSID** (jika tidak ada device yang terhubung)
- **Pencarian** berdasarkan nama SSID, VLAN ID, atau deskripsi
- Menampilkan **jumlah device** yang terhubung ke tiap SSID

> SSID yang ditambahkan melalui CSV Import device akan otomatis dibuat jika belum ada.

---

### 4. DHCP Server Management (Kea DHCPv4)

**Rute**: `GET /dhcp`  
**Akses**: Super Admin

Modul manajemen DHCP terintegrasi dengan **Kea DHCPv4** server. Semua perubahan pada subnet dan reservasi IP otomatis di-generate ke file konfigurasi Kea dan di-reload.

#### 4a. Manajemen Subnet / Pool (Tab: Subnets)

| Fitur | Keterangan |
|---|---|
| **Tambah Subnet** | Buat subnet baru dengan nama, CIDR (e.g. `192.168.27.0/24`), pool start/end, gateway, DNS, lease time |
| **Edit Subnet** | Perbarui konfigurasi subnet; form dilengkapi toggle **"Subnet Aktif"** |
| **Hapus Subnet** | Hapus subnet dari sistem |
| **Status Badge** | Subnet dengan `is_active = false` ditampilkan badge **"Nonaktif"** di tabel |
| **Kea Sync Otomatis** | Setiap perubahan subnet otomatis men-generate ulang konfigurasi Kea DHCPv4 |

> **Fix Penting**: Status `is_active` subnet kini dipreservasi dengan benar saat edit. Jika form tidak menyertakan toggle, nilai asal dipertahankan (tidak di-reset ke false).

#### 4b. Manajemen Reservasi Static IP (Tab: Reservasi Static IP)

Reservasi IP static memungkinkan device tertentu selalu mendapat IP yang sama dari DHCP server.

| Fitur | Keterangan |
|---|---|
| **Tambah Reservasi** | Form dengan searchable device picker, pilih subnet, input IP manual |
| **Edit Reservasi** | Perbarui MAC, IP, hostname, deskripsi, status aktif |
| **Hapus Reservasi** | Hapus reservasi dan sync ulang ke Kea |
| **Filter per Pool** | Filter tampilan reservasi berdasarkan subnet tertentu |
| **Pencarian** | Cari berdasarkan IP, MAC, hostname, nama device |
| **Link ke Device** | Reservasi otomatis terhubung ke data device jika MAC cocok |

##### 📤 Import Reservasi Static IP via CSV (`POST /dhcp/static/import-csv`)
- Upload CSV untuk membuat banyak reservasi IP sekaligus
- **Kolom CSV**:

  ```csv
  mac_address, ip_address, subnet, hostname, description
  ```

- **Auto-detect Subnet**: Jika kolom `subnet` kosong, sistem otomatis mencocokkan IP ke subnet yang sesuai berdasarkan range pool atau CIDR
- **Auto-link Device**: Jika MAC address sudah terdaftar di daftar device, reservasi otomatis terhubung (`device_id`)
- Duplikat IP atau MAC dilewati otomatis
- Kea DHCP di-sync 1 kali di akhir proses batch
- **Download Template CSV**: Tombol download template tersedia di modal

##### 📥 Download Template CSV Reservasi (`GET /dhcp/static/template-csv`)
- Template dengan contoh baris siap pakai (format UTF-8 BOM, aman untuk Excel)

##### 🔁 Manual Sync ke Kea (`POST /dhcp/sync`)
- Tombol untuk men-trigger ulang regenerasi konfigurasi Kea DHCPv4 secara manual

---

### 5. RADIUS Authentication Logs

**Rute**: `GET /logs`  
**Akses**: Super Admin

Halaman log aktivitas autentikasi RADIUS yang dicatat oleh FreeRADIUS.

- **Filter Pencarian**: Cari berdasarkan MAC address, username, NAS IP, atau alasan penolakan
- **Filter Hasil**: Tampilkan semua / hanya ACCEPT / hanya REJECT
- **Filter Rentang Tanggal**: Tampilkan log dalam rentang tanggal tertentu
- **Pagination**: 15 baris per halaman default
- **Export CSV**: Unduh log yang difilter ke file `.csv`
- **Hapus Semua Log**: Bersihkan seluruh log (`DELETE /logs/clear`)
- **Pruning Otomatis**: Jadwal artisan `radius:prune-logs` berjalan setiap hari pukul 02:00 untuk menghapus log lebih dari 30 hari

---

### 6. UniFi Hotspot Voucher Management

**Rute**: `GET /vouchers`  
**Akses**: Super Admin & Operator

Modul manajemen voucher WiFi tamu yang terintegrasi dengan **Ubiquiti UniFi Controller API**.

| Fitur | Keterangan |
|---|---|
| **Generate Voucher** | Buat batch voucher dengan durasi (menit/jam/hari), batas penggunaan, kuota data (MB), limit bandwidth (Kbps upload/download) |
| **Cetak Voucher** | Preview dan cetak voucher dalam format A4 atau slip thermal |
| **Revoke Voucher** | Cabut voucher satuan (sync ke UniFi API) |
| **Batch Revoke** | Pilih dan cabut beberapa voucher sekaligus via checkbox |
| **Sync ke UniFi** | Push manual voucher pending ke UniFi Controller API |
| **Mode Offline** | Jika UniFi Controller offline, voucher disimpan lokal dan di-queue untuk sync otomatis saat controller kembali online |
| **Konfigurasi UniFi** | Update URL controller, Site ID, username, password langsung dari UI |

#### Status Voucher:
- `unused` — belum pernah digunakan
- `revoked` — sudah dicabut
- `pending_create` — menunggu push ke UniFi (mode offline)
- `pending_revoke` — menunggu pencabutan di UniFi (mode offline)

---

## 🔒 Role-Based Access Control (RBAC)

| Role | Akses |
|---|---|
| **Super Admin** | Akses penuh: Dashboard, Device, SSID, DHCP, Log, Voucher, Konfigurasi UniFi |
| **Operator** | Terbatas: Hanya menu Voucher (generate, cetak, revoke) |

Login menggunakan **username** (contoh: `admin`, `operator`) atau **email** (contoh: `admin@macson.local`).

---

## 📂 CSV Import & Export Reference

### A. Import Device (`/devices/import`)

```csv
MAC Address,SSID,Device Name,Location,Description,Status,VLAN ID,IP Address,Subnet
00:11:22:33:44:55,SJA AP,AP Warehouse Lt 1,Warehouse,AP Operasional,active,27,10.14.27.50,SJA AP
AA:BB:CC:DD:EE:FF,SJA REG,Tablet Kasir Pos 1,Kasir,Device POS,active,,10.14.28.10,TAB PWD
11:22:33:44:55:66,ALL,Laptop Staff IT,Kantor IT,Tanpa IP,active,,,
```

| Kolom | Wajib | Keterangan |
|---|---|---|
| MAC Address | ✅ | Format bebas (otomatis dinormalisasi) |
| SSID | ✅ | Nama SSID atau `ALL` |
| Device Name | — | Default: `Imported Device` |
| Location | — | |
| Description | — | Default: `CSV Import` |
| Status | — | `active` atau `inactive` (default: `active`) |
| VLAN ID | — | 1–4094 |
| IP Address | — | Jika diisi → buat reservasi static DHCP otomatis |
| Subnet | — | Nama/VLAN/CIDR subnet; kosong = auto-detect dari IP |

---

### B. Import Reservasi Static IP (`/dhcp/static/import-csv`)

```csv
mac_address,ip_address,subnet,hostname,description
00:11:22:33:44:55,10.14.27.50,SJA AP,AP-GUDANG-01,Access Point Gudang 1
AA:BB:CC:DD:EE:FF,10.14.28.10,TAB PWD,TAB-POS-01,Tablet Kasir Pos 1
11:22:33:44:55:66,10.14.27.51,,,Auto detect pool dari range IP
```

| Kolom | Wajib | Keterangan |
|---|---|---|
| mac_address | ✅ | Format bebas |
| ip_address | ✅ | IPv4 valid |
| subnet | — | Nama/VLAN/CIDR; kosong = auto-detect |
| hostname | — | Label device di DHCP server |
| description | — | Default: `CSV Import` |

---

### C. Export Device (`/devices/export`)

File CSV berisi kolom: `MAC Address`, `Target SSID`, `Device Name`, `Location`, `Description`, `Status`, `VLAN ID`, **`Reserved IP`**, **`Subnet Pool`**, `ID`, `Created At`

---

## 🗄️ Database Schema

### Tabel Utama

| Tabel | Deskripsi |
|---|---|
| `users` | Akun Super Admin & Operator |
| `devices` | Daftar MAC Address device yang terdaftar |
| `ssids` | Daftar SSID yang dikelola |
| `radius_log` | Log hasil autentikasi RADIUS (ACCEPT/REJECT) |
| `dhcp_subnets` | Subnet / Pool DHCP (Kea DHCPv4) |
| `dhcp_static_leases` | Reservasi Static IP per device per subnet |
| `unifi_vouchers` | Voucher hotspot WiFi tamu |
| `unifi_config` | Konfigurasi koneksi ke UniFi Controller API |

### Relasi Kunci

```
devices ──< dhcp_static_leases >── dhcp_subnets
devices >── ssids (via kolom ssid)
dhcp_static_leases.device_id → devices.id  (nullable)
dhcp_static_leases.dhcp_subnet_id → dhcp_subnets.id (nullable)
```

---

## 🛠️ Installation Guide

Deploy **SANTAFE / MACSON** di Ubuntu Server 26.04 / 24.04 / 22.04 LTS:

### Mode 1: Interactive (6 Prompt)
```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh -o /tmp/install.sh && sudo bash /tmp/install.sh
```

**Prompt yang akan muncul:**
1. NAS Network Subnet untuk RADIUS (e.g., `192.168.1.0/24`)
2. SSH Allowed Subnet untuk Port 22
3. Admin Web UI Allowed Subnet
4. RADIUS Shared Secret Key
5. MariaDB Root Password *(hidden input)*
6. Web Admin Login Password *(hidden input)*

### Mode 2: One-Liner Silent (Default Config)
```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh | sudo bash -s -- --auto
```

### Mode 3: One-Liner Custom Parameter
```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh | sudo bash -s -- --auto \
  --nas-subnet 192.168.1.0/24 \
  --ssh-subnet 192.168.1.50/32 \
  --admin-subnet 192.168.1.0/24 \
  --secret UniFiRadiusSecret2026! \
  --db-root-password "MyMariaDBRootPass123!" \
  --admin-password "MySecurePass123!"
```

### Mode 4: Git Clone & Manual
```bash
git clone https://github.com/technogithub/MACSON.git /opt/macson
cd /opt/macson
sudo bash scripts/install.sh
```

---

## 📶 UniFi Controller Setup Guide

1. Buka **UniFi Network Controller** ➔ **Settings** ➔ **Profiles** ➔ **RADIUS**
2. Klik **Create New Profile**:
   - **Authentication Host**: `<IP Ubuntu Server>`
   - **Port**: `1812`
   - **Shared Secret**: Sesuai yang dikonfigurasi saat install
   - **Accounting Host**: `<IP Ubuntu Server>` | **Port**: `1813`
3. Buka **Settings** ➔ **WiFi** ➔ Edit SSID yang diinginkan:
   - **Authentication**: `MAC ID Authentication` atau `802.1X Enterprise`
   - **RADIUS Profile**: Pilih profile yang dibuat di atas
   - **MAC Address Format**: `AA:BB:CC:DD:EE:FF`
   - **Enable Dynamic VLAN**: Centang **Use RADIUS assigned VLANs**

---

## 🌐 Kea DHCP Integration

SANTAFE terintegrasi langsung dengan **Kea DHCPv4** server. Setiap kali subnet atau reservasi static IP diubah, sistem otomatis:

1. Men-generate ulang file konfigurasi JSON Kea DHCPv4 ke path yang dapat ditulis (`storage/app/dhcp/`)
2. Me-reload konfigurasi ke Kea DHCP daemon

**Logika Auto-Detect Subnet dari IP:**
Sistem menggunakan helper `DhcpSubnet::findByHintOrIp()` yang mencari subnet cocok berdasarkan:
1. Nama subnet (case-insensitive match)
2. VLAN ID
3. Notasi CIDR subnet
4. Fallback: pencocokan IP dalam range pool atau dalam network CIDR

**Format Konfigurasi Kea yang Di-generate:**
```json
{
  "Dhcp4": {
    "subnet4": [
      {
        "subnet": "10.14.27.0/24",
        "pools": [{ "pool": "10.14.27.50 - 10.14.27.200" }],
        "option-data": [
          { "name": "routers", "data": "10.14.27.1" },
          { "name": "domain-name-servers", "data": "1.1.1.1, 8.8.8.8" }
        ],
        "reservations": [
          { "hw-address": "00:11:22:33:44:55", "ip-address": "10.14.27.50" }
        ]
      }
    ]
  }
}
```

---

## ⚡ FreeRADIUS Unlang & Dynamic VLAN

FreeRADIUS memeriksa `Called-Station-Id` dari UniFi AP dan menyuntikkan atribut IEEE 802.1Q reply saat autentikasi berhasil:

```unlang
# Sanitasi format MAC Address dari Calling-Station-Id
update request {
    Tmp-String-0 := "%{tolower:%{Calling-Station-Id}}"
}
# Ganti semua pemisah menjadi tanda titik dua
# ...

# Inject Dynamic IEEE 802.1Q VLAN reply
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
MACSON/
├── .github/
│   └── workflows/ci.yml                    # GitHub Actions CI Build & Linter
├── backend-laravel/                         # Laravel 12 Web UI Application
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php       # Login & Logout
│   │   │   │   ├── DashboardController.php  # Statistik & Chart
│   │   │   │   ├── DeviceController.php     # MAC Device Management + CSV Import/Export
│   │   │   │   ├── DhcpController.php       # Kea DHCP Subnet & Static Lease Management + CSV
│   │   │   │   ├── LogController.php        # RADIUS Log Viewer & Export
│   │   │   │   ├── SsidController.php       # Multi-SSID Management
│   │   │   │   └── VoucherController.php    # UniFi Hotspot Voucher
│   │   │   └── Middleware/
│   │   │       └── EnsureSuperAdmin.php     # Middleware RBAC Super Admin
│   │   ├── Models/
│   │   │   ├── Device.php                   # MAC Device Model + formatMacAddress()
│   │   │   ├── DhcpSubnet.php               # Subnet Model + containsIp() + findByHintOrIp()
│   │   │   ├── DhcpStaticLease.php          # Static DHCP Lease Model
│   │   │   ├── RadiusLog.php                # RADIUS Authentication Log Model
│   │   │   ├── Ssid.php                     # SSID Model
│   │   │   ├── UnifiVoucher.php             # Voucher Model
│   │   │   └── User.php                     # User / Auth Model
│   │   └── Services/
│   │       ├── DhcpConfigService.php        # Generate Kea DHCPv4 JSON Config & Sync
│   │       └── UniFiService.php             # UniFi Controller API Integration
│   ├── resources/views/
│   │   ├── layouts/app.blade.php            # Master Layout (Glassmorphism Dark UI)
│   │   ├── auth/login.blade.php             # Halaman Login
│   │   ├── dashboard.blade.php              # Dashboard dengan Chart.js
│   │   ├── devices/index.blade.php          # Device Management UI (Add/Edit/Import/Export)
│   │   ├── dhcp/index.blade.php             # DHCP Subnet & Static Lease Management UI
│   │   ├── ssids/index.blade.php            # Multi-SSID Management UI
│   │   ├── logs/index.blade.php             # RADIUS Log Viewer UI
│   │   └── vouchers/
│   │       ├── index.blade.php              # Voucher List & Generate UI
│   │       └── print.blade.php              # Print Preview Voucher (A4/Thermal)
│   ├── database/migrations/                 # Database Migration Files
│   └── routes/web.php                       # Semua Route Aplikasi
├── freeradius/                              # FreeRADIUS v3 Configuration
│   ├── clients.conf                         # NAS Client Definitions
│   ├── queries.conf                         # Multi-SSID & VLAN SQL Queries
│   └── sites-available/default             # Virtual Server Policy & Unlang Logic
├── sql/
│   └── schema.sql                           # MariaDB DDL & Seeders
├── docker/
│   ├── docker-compose.yml                   # Microservices (Nginx, PHP, MariaDB, FreeRADIUS, Kea, phpMyAdmin)
│   ├── Dockerfile                           # PHP 8.3 FPM Image
│   ├── freeradius.Dockerfile                # FreeRADIUS Container
│   └── nginx.conf                           # Nginx SSL & Security Headers
├── scripts/
│   ├── install.sh                           # Automated Ubuntu Installer (4 Modes)
│   ├── uninstall.sh                         # Clean Uninstaller
│   ├── health_check.sh                      # Service Diagnostic & Port Monitoring
│   ├── backup_db.sh                         # Database Backup Utility
│   └── restore_db.sh                        # Database Restore Utility
└── README.md                                # Dokumentasi ini
```

---

## 🧪 Testing RADIUS Authentication

```bash
# Dari host Ubuntu
radtest testing testing 127.0.0.1 1812 RadiusSecretKey2026!

# Dari dalam container FreeRADIUS
docker exec -it radius_freeradius radtest testing testing 127.0.0.1 1812 testing123
```

**Expected Output (berhasil):**
```text
Sending Access-Request of id 198 to 127.0.0.1 port 1812
        User-Name = "testing"
        User-Password = "testing"
Received Access-Accept Id 198 from 127.0.0.1:1812 in 2ms
        Tunnel-Type:0 = VLAN
        Tunnel-Medium-Type:0 = IEEE-802
        Tunnel-Private-Group-Id:0 = "10"
```

---

## 🔧 Maintenance & Operations

### phpMyAdmin (Port 8080)
- **URL**: `http://<SERVER-IP>:8080`
- **Username**: `root`
- **Password**: Password MariaDB yang dikonfigurasi saat install

### Log Pruning Otomatis
```bash
# Pruning log > 30 hari (berjalan otomatis setiap hari jam 02:00)
docker exec radius_laravel_app php artisan radius:prune-logs

# Pruning log > N hari (manual)
docker exec radius_laravel_app php artisan radius:prune-logs --days=7
```

### Backup & Restore Database
```bash
sudo bash scripts/backup_db.sh
sudo bash scripts/restore_db.sh
```

### Cek Kesehatan Layanan
```bash
sudo bash scripts/health_check.sh
```

### Update ke Versi Terbaru
```bash
cd /opt/macson
git pull origin main
docker compose -f docker/docker-compose.yml restart
docker exec radius_laravel_app php artisan migrate --force
```

### Rollback ke Stable Release
```bash
cd /opt/macson
git checkout v1.0.0-stable
docker compose -f docker/docker-compose.yml restart freeradius
```

### Uninstall Lengkap
```bash
sudo bash scripts/uninstall.sh --auto
```

---

## 📅 Changelog Fitur Terbaru

| Versi / Commit | Fitur |
|---|---|
| `e1370d6` | **feat**: Import Reservasi Static IP via CSV (DHCP) + Import Device dengan Reservasi IP Sekaligus (Devices) |
| `3b9b8b5` | **fix**: Semua subnet tampil di dropdown pool device modal; is_active dipreservasi saat edit subnet |
| `051f802` | **fix**: Perbaikan layar buram (stuck backdrop) saat modal edit device dibuka/ditutup |
| `f920c56` | **feat**: Edit reservasi static IP dari modal edit device; auto-match SJA AP pool |
| `7fb8906` | **feat**: Tambah reservasi static IP opsional saat tambah/edit device |
| `9d9ea36` | **feat**: Filter pool button, searchable device picker, pagination 15/50/100 di DHCP |
| `6402752` | **feat**: Tab navigasi SSID besar (SJA REG, SJA AP, DEVICE OPS AP) dan pagination di Devices |
| `f3feca7` | **feat**: Integrasi Kea DHCPv4 — multi-subnet pool, static reservations, dan UI manajemen |

---

## 👤 Author & License

- **Author**: Santos ([@technogithub](https://github.com/technogithub))
- **Project Repository**: [https://github.com/technogithub/MACSON](https://github.com/technogithub/MACSON)
- **License**: MIT License

# 🛡️ SANTAFE NAC (Santos Advanced Network Traffic & Access Filtering Engine)

> **Enterprise Multi-SSID RADIUS (MAB / 802.1X), Dynamic 802.1Q VLAN Steering, Kea DHCPv4 Management & UniFi Hotspot Voucher System for Ubiquiti UniFi & Enterprise Networks**

![Release](https://img.shields.io/github/v/release/technogithub/MACSON?color=green&label=Release)
![Ubuntu](https://img.shields.io/badge/Ubuntu-24.04%20%7C%2022.04%20LTS-orange?logo=ubuntu)
![UniFi](https://img.shields.io/badge/UniFi-Network%20Integration-055BF6?logo=ubiquiti)
![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-v3.0-blue?logo=freeradius)
![Kea DHCP](https://img.shields.io/badge/Kea-DHCPv4-blueviolet)
![Laravel](https://img.shields.io/badge/Laravel-11.x-red?logo=laravel)
![Docker](https://img.shields.io/badge/Docker-Microservices-2496ED?logo=docker)
![License](https://img.shields.io/badge/License-MIT-brightgreen)

---

## 📋 Daftar Isi

1. [Tentang SANTAFE](#-tentang-santafe)
2. [Fitur Unggulan](#-fitur-unggulan)
3. [Arsitektur Sistem](#-arsitektur-sistem)
4. [Standar Segmen Jaringan & VLAN (Contoh Standar)](#-standar-segmen-jaringan--vlan-contoh-standar)
5. [Modul Aplikasi Web](#-modul-aplikasi-web)
   - [Dashboard & Real-time Analytics](#1-dashboard--real-time-analytics)
   - [Manajemen MAC Address & Device](#2-manajemen-mac-address--device)
   - [Multi-SSID & Dynamic VLAN Tagging](#3-multi-ssid--dynamic-vlan-tagging)
   - [DHCP Server Management (Kea DHCPv4)](#4-dhcp-server-management-kea-dhcpv4)
   - [UniFi Hotspot Voucher System](#5-unifi-hotspot-voucher-system)
   - [RADIUS Authentication Audit Logs](#6-radius-authentication-audit-logs)
   - [Multi-Theme UI & Interactive Table Sorting](#7-multi-theme-ui--interactive-table-sorting)
6. [Role-Based Access Control (RBAC)](#-role-based-access-control-rbac)
7. [Panduan Format CSV Import & Export](#-panduan-format-csv-import--export)
8. [Panduan Instalasi & Deployment](#-panduan-instalasi--deployment)
9. [Konfigurasi Ubiquiti UniFi Controller](#-konfigurasi-ubiquiti-unifi-controller)
10. [Struktur Direktori Proyek](#-struktur-direktori-proyek)
11. [Pengujian & Verifikasi](#-pengujian--verifikasi)
12. [Pemeliharaan & Operasional](#-pemeliharaan--operasional)
13. [Author & Lisensi](#-author--lisensi)

---

## 💡 Tentang SANTAFE

**SANTAFE NAC** (**S**antos **A**dvanced **N**etwork **T**raffic & **A**ccess **F**iltering **E**ngine) adalah sistem **Network Access Control (NAC)** komprehensif tingkat enterprise yang dirancang khusus untuk ekosistem **Ubiquiti UniFi** (AP, Switch, Gateway) dan jaringan nirkabel perusahaan berskala besar.

SANTAFE mengintegrasikan autentikasi berbasis MAC Address (MAB), penyaringan multi-SSID, pembagian VLAN dinamis IEEE 802.1Q, server DHCP berkinerja tinggi (Kea DHCPv4), sistem voucher hotspot tamu, serta visualisasi audit log ke dalam satu antarmuka modern bernuansa *dark glassmorphism*.

---

## ⚡ Fitur Unggulan

| Modul / Fitur | Deskripsi Singkat |
|---|---|
| 🔐 **RADIUS MAC Authentication (MAB)** | Otentikasi perangkat otomatis berdasarkan MAC Address melalui FreeRADIUS v3 tanpa memerlukan software client tambahan (supplicant). Mendukung auto-sanitasi format MAC (`AA:BB:CC:DD:EE:FF`, `AA-BB-CC-DD-EE-FF`, `aabbccddeeff`). |
| 🏷️ **Dynamic 802.1Q VLAN Steering** | Memetakan perangkat secara otomatis ke VLAN tujuan (`Tunnel-Private-Group-Id`) berdasarkan konfigurasi device atau profil SSID yang terhubung. |
| 📡 **Multi-SSID Filtering** | Mengatur izin akses perangkat hanya ke SSID tertentu (e.g. `CORP-WIFI`, `GUEST-WIFI`, `IOT-WIFI`) atau `ALL` untuk akses universal. |
| 🌐 **Kea DHCPv4 Integration** | Manajemen multi-subnet DHCP pool, reservasi IP static, monitoring dynamic leases aktif secara real-time, dan fitur *Clear Leases* yang terisolasi aman per-subnet. |
| 🎫 **UniFi Hotspot Voucher Engine** | Generate batch voucher WiFi tamu langsung tersinkronisasi ke UniFi Controller API, lengkap dengan opsi cetak thermal slip (58mm/80mm) & format A4 grid. |
| 🎨 **Multi-Theme Engine** | Pilihan tema visual interaktif: **Cyber Blue** (Default), **Emerald Shield**, **Crimson Protocol**, dan **Purple Void** yang tersimpan di LocalStorage per pengguna. |
| 🔄 **Interactive Table Sorting** | Kemampuan pengurutan kolom tabel (ASC/DESC) interaktif pada seluruh tabel: MAC Devices, DHCP Subnets, Static Leases, Dynamic Leases (dengan polling live), dan Vouchers. |
| 📊 **Real-time Analytics** | Visualisasi grafik autentikasi per jam (Accept vs Reject) menggunakan Chart.js dan ringkasan metrik kesehatan jaringan. |
| 📤 **Smart CSV Bulk Operations** | Import & Export data massal dengan fitur auto-detect subnet dari range IP dan auto-link reservasi DHCP ke tabel perangkat. |
| 🛡️ **Role-Based Access Control (RBAC)** | Pemisahan hak akses antara **Super Admin** (akses penuh infrastruktur) dan **Operator** (khusus generate & cetak voucher tamu). |

---

## 🏗️ Arsitektur Sistem

```
+--------------------+        +-------------------------+        +-----------------------+        +-----------------------+
|   Client Device    |  WLAN  |   Ubiquiti UniFi AP /   | RADIUS |   FreeRADIUS v3 Core  |  SQL   |   MariaDB Database    |
| (Laptop, HP, IoT)  | -----> |    UniFi Switch (NAS)   | -----> |   (UDP 1812 / 1813)   | -----> |     (`radius` DB)     |
+--------------------+        +-------------------------+        +-----------------------+        +-----------------------+
                                           |                                 |
                                           | Dynamic 802.1Q VLAN             | 1. Sanitize MAC Delimiter
                                           | Tagging Reply                   | 2. Extract SSID from Called-Station-Id
                                           v                                 | 3. Query Device Authorization
                                +-----------------------+                    | 4. Return Access-Accept + VLAN ID
                                |      Kea DHCPv4       | <------------------+
                                |  (Pools & Static IP)  |
                                +-----------------------+
                                           ^
                                           | JSON Config Sync
                                +-----------------------+        +-----------------------+
                                |   SANTAFE Web UI      | -----> |   UniFi Controller    |
                                | (Laravel 11 / Nginx)  |  API   |  (Hotspot & Vouchers) |
                                +-----------------------+        +-----------------------+
```

---

## 🌐 Standar Segmen Jaringan & VLAN (Contoh Standar)

SANTAFE menggunakan standar pengalamatan privat RFC 1918 yang fleksibel dan dapat disesuaikan dengan arsitektur jaringan kantor Anda:

| VLAN ID | Nama Jaringan | Target SSID | Subnet CIDR (Contoh) | DHCP Range / Pool | Gateway / Router | Keterangan |
|:---:|:---|:---|:---|:---|:---|:---|
| **10** | **Corporate Staff** | `CORP-WIFI` | `192.168.10.0/24` | `192.168.10.100 - 192.168.10.200` | `192.168.10.1` | Perangkat karyawan kantor & laptop operasional |
| **20** | **Guest / Tamu** | `GUEST-WIFI` | `192.168.20.0/24` | `192.168.20.50 - 192.168.20.250` | `192.168.20.1` | Tamu dengan portal voucher UniFi Hotspot |
| **30** | **IoT & CCTV** | `IOT-WIFI` | `192.168.30.0/24` | `192.168.30.10 - 192.168.30.200` | `192.168.30.1` | Kamera IP, Printer Wireless, Smart Display |
| **99** | **Management** | *(Ethernet Only)* | `192.168.99.0/24` | `Static Reservation Only` | `192.168.99.1` | Server, AP Controller, Switch Management |

---

## 🖥️ Modul Aplikasi Web

### 1. Dashboard & Real-time Analytics
- **Metrik Utama**: Total device aktif/nonaktif, log autentikasi 24 jam terakhir, persentase keberhasilan autentikasi.
- **Grafik Interaktif**: Line chart real-time per jam (Accept vs Reject).
- **Live Activity Feed**: 8 aktivitas autentikasi terbaru dengan indikator badge MAC, SSID, dan status koneksi.

### 2. Manajemen MAC Address & Device
- **Registrasi Cepat**: Tambah device dengan validasi MAC instan, pemilihan SSID target (`ALL` atau SSID spesifik), penamaan, lokasi, dan VLAN ID override.
- **Reservasi IP Terintegrasi**: Pilihan untuk langsung mengalokasikan Static IP DHCP pada saat mendaftarkan device baru, dilengkapi tombol **"Saran IP"** otomatis.
- **Pencarian & Filter Multi-Kriteria**: Cari berdasarkan MAC, nama, lokasi, deskripsi, atau filter via Tab Navigasi SSID.
- **Quick Action**: Toggle status aktif/nonaktif dalam 1 klik tanpa memuat ulang form.
- **Sorting Kolom**: Urutkan kolom Device, MAC, SSID, VLAN, dan Status secara ascending/descending.

### 3. Multi-SSID & Dynamic VLAN Tagging
- Pengelolaan daftar SSID yang diakui oleh pengontrol RADIUS.
- Pemetaan otomatis VLAN ID default untuk setiap SSID.
- Perangkat dapat dikunci hanya pada satu SSID atau diizinkan ke beberapa SSID sekaligus dengan VLAN ID yang terisolasi.

### 4. DHCP Server Management (Kea DHCPv4)
Terintegrasi secara native dengan **Kea DHCPv4 engine**:
- **Manajemen Subnet / Pool**: Tambah dan atur subnet CIDR, range IP dynamic pool, router gateway, DNS server, dan durasi lease.
- **Reservasi Static IP**: Ikat alamat IP permanen ke MAC Address tertentu. Dilengkapi searchable picker device dan auto-detect subnet.
- **Dynamic Leases Live Monitoring**: Tabel sewa dinamis aktif yang diperbarui secara langsung melalui polling otomatis.
- **Subnet-Isolated Clear Leases**: Fitur penghapusan lease dinamis yang aman — membersihkan IP sewa aktif hanya pada subnet yang dipilih tanpa mengganggu lease di subnet lainnya.
- **Sinkronisasi Konfigurasi Kea**: Konfigurasi diexport ke format JSON terstruktur Kea dan di-reload secara mulus ke container daemon.

### 5. UniFi Hotspot Voucher System
- **Generator Batch**: Buat voucher dengan parameter durasi waktu (jam/hari), kuota bandwidth upload/download (Kbps), kuota data (MB), dan limit pemakaian (single/multi-use).
- **Template Cetak Fleksibel**:
  - Slip Thermal (58mm / 80mm) siap cetak via printer thermal kasir/resepsionis.
  - Lembar A4 Grid untuk pencetakan massal dengan garis potong rapi.
- **Revoke & Batch Revoke**: Pencabutan akses voucher secara individual maupun massal dengan sinkronisasi langsung ke UniFi Controller.
- **Offline Resilience Mode**: Jika koneksi ke UniFi Controller terputus, voucher tersimpan secara lokal dan otomatis masuk antrean sinkronisasi saat controller kembali online.

### 6. RADIUS Authentication Audit Logs
- Pencatatan lengkap setiap request autentikasi: Timestamp, MAC Address, Target SSID, Status (`ACCEPT`/`REJECT`), NAS IP, dan Alasan Penolakan.
- Filter tanggal kustom, pencarian bebas, dan tombol export ke format CSV.
- Pembersihan log otomatis (auto-prune) via scheduled artisan job untuk menjaga performa database.

### 7. Multi-Theme UI & Interactive Table Sorting
- **Theme Switcher**: Pilih dari 4 tema elegan (Cyber Blue, Emerald Shield, Crimson Protocol, Purple Void). Seluruh elemen UI, badge, tombol aksen, dan background berpindah warna secara konsisten.
- **Interaktivitas Tabel**: Seluruh tabel data dilengkapi indikator panah sort (`▲`/`▼`) interaktif yang memudahkan penelusuran data dalam jumlah besar.

---

## 🔒 Role-Based Access Control (RBAC)

| Role | Hak Akses | Deskripsi |
|---|:---:|---|
| **Super Admin** | 👑 Penuh | Akses seluruh modul: Dashboard, Devices, SSIDs, DHCP Server, Logs, Vouchers, dan Konfigurasi Integrasi UniFi. |
| **Operator** | 🎫 Terbatas | Dikhususkan untuk staf resepsionis/kasir: Hanya dapat membuka modul Voucher (Generate, Cetak, dan Revoke voucher tamu). |

---

## 📂 Panduan Format CSV Import & Export

### A. Template Import Device (`/devices/import`)

Gunakan file `.csv` dengan header berikut:

```csv
MAC Address,SSID,Device Name,Location,Description,Status,VLAN ID,IP Address,Subnet
00:11:22:33:44:55,CORP-WIFI,Laptop Staff IT,Lantai 2,Laptop Lenovo Operasional,active,10,192.168.10.55,VLAN-10-CORP
AA:BB:CC:DD:EE:FF,GUEST-WIFI,Tablet Resepsionis,Lobby,Tablet Pendaftaran Tamu,active,20,192.168.20.15,VLAN-20-GUEST
11:22:33:44:55:66,ALL,Printer Wireless,Ruang Keuangan,Printer Epson L-Series,active,10,,
```

*Catatan: Kolom `IP Address` dan `Subnet` bersifat opsional. Jika diisi, sistem otomatis membuat reservasi static IP di server DHCP.*

### B. Template Import Reservasi Static DHCP (`/dhcp/static/import-csv`)

```csv
mac_address,ip_address,subnet,hostname,description
00:11:22:33:44:55,192.168.10.55,VLAN-10-CORP,PC-STAFF-01,Komputer Staff Accounting
AA:BB:CC:DD:EE:FF,192.168.20.15,VLAN-20-GUEST,TAB-LOBBY,Tablet Registrasi
11:22:33:44:55:66,192.168.10.60,,PRINTER-FINANCE,Auto detect subnet pool dari IP
```

---

## 🚀 Panduan Instalasi & Deployment

### Persyaratan Sistem:
- **OS**: Ubuntu Server 24.04 LTS / 22.04 LTS (x86_64)
- **CPU / RAM**: Minimal 2 Core / 2 GB RAM (Rekomendasi 4 GB RAM untuk produksi)
- **Storage**: Minimal 20 GB SSD
- **Port Jaringan Terbuka**:
  - `UDP 1812` & `UDP 1813` (FreeRADIUS Auth & Acct)
  - `UDP 67` (Kea DHCPv4 Server)
  - `TCP 80` & `TCP 443` (Nginx Web Portal)
  - `TCP 8080` (phpMyAdmin Database Explorer - Opsional)

### Metode 1: One-Liner Installer Otomatis

Jalankan perintah berikut di terminal Ubuntu Server Anda:

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh -o /tmp/install.sh && sudo bash /tmp/install.sh
```

Ikuti panduan interaktif pada layar untuk menentukan:
1. Subnet jaringan NAS / Access Point (contoh: `192.168.1.0/24`)
2. Subnet yang diizinkan untuk SSH & Web Admin
3. Shared Secret RADIUS
4. Password administrator Web & Database MariaDB

### Metode 2: Manual via Git Clone & Docker Compose

```bash
# 1. Clone repositori ke direktori /opt/macson
sudo git clone https://github.com/technogithub/MACSON.git /opt/macson
cd /opt/macson

# 2. Siapkan file konfigurasi environment
cp backend-laravel/.env.example backend-laravel/.env

# 3. Jalankan container microservices
docker compose -f docker/docker-compose.yml up -d --build

# 4. Generate application key dan jalankan migrasi database
docker exec radius_laravel_app php artisan key:generate
docker exec radius_laravel_app php artisan migrate --force
docker exec radius_laravel_app php artisan db:seed --force
```

---

## 📶 Konfigurasi Ubiquiti UniFi Controller

Untuk menghubungkan Access Point UniFi ke SANTAFE NAC:

1. **Buat RADIUS Profile**:
   - Buka **UniFi Network Application** ➔ **Settings** ➔ **Profiles** ➔ **RADIUS**.
   - Klik **Create New Profile**:
     - **Profile Name**: `SANTAFE-RADIUS`
     - **Authentication Host**: Alamat IP Server SANTAFE (e.g. `192.168.1.10`)
     - **Authentication Port**: `1812`
     - **Shared Secret**: Masukkan password secret yang telah dibuat
     - **Accounting**: Aktifkan dengan IP Host yang sama di Port `1813`
2. **Konfigurasikan SSID / WiFi**:
   - Masuk ke menu **Settings** ➔ **WiFi** ➔ Pilih atau buat SSID baru.
   - **Authentication / Security**: Pilih **WPA2/WPA3 Enterprise** ATAU aktifkan **MAC ID Authentication**.
   - **RADIUS Profile**: Pilih `SANTAFE-RADIUS`.
   - **MAC Address Format**: Pilih `AA:BB:CC:DD:EE:FF` atau `AA-BB-CC-DD-EE-FF`.
   - **Dynamic VLAN Assignment**: Centang opsi **"Use RADIUS assigned VLANs"**.

---

## 📁 Struktur Direktori Proyek

```
SANTAFE/
├── backend-laravel/                 # Aplikasi Laravel 11 (Glassmorphism Dark UI & API)
│   ├── app/
│   │   ├── Http/Controllers/        # Controller: Auth, Dashboard, Devices, DHCP, Logs, SSIDs, Vouchers
│   │   ├── Models/                  # Model Eloquent: Device, DhcpSubnet, DhcpStaticLease, RadiusLog, dll.
│   │   └── Services/                # Service Kea DHCP Config Generator & UniFi API Client
│   ├── resources/views/             # Blade Views (Master Layouts, Modals, Print Previews)
│   ├── database/migrations/         # Migrasi Database
│   └── routes/web.php               # Routing Aplikasi Web
├── freeradius/                      # Konfigurasi FreeRADIUS v3
│   ├── clients.conf                 # Definisi Client NAS / AP Subnet
│   ├── queries.conf                 # Query SQL Autentikasi MAB & VLAN Tagging
│   └── sites-available/default      # Virtual Server & Unlang Logic
├── docker/                          # Definisi Docker & Microservices
│   ├── docker-compose.yml           # Compose file (Nginx, PHP, MariaDB, FreeRADIUS, Kea, phpMyAdmin)
│   ├── Dockerfile                   # PHP 8.3 FPM Image Build
│   └── freeradius.Dockerfile        # FreeRADIUS Container Build
├── scripts/                         # Script Otomasi & Pemeliharaan
│   ├── install.sh                   # Script instalasi Ubuntu otomatis
│   ├── health_check.sh              # Diagnostik port & service container
│   ├── backup_db.sh                 # Backup database MariaDB terjadwal
│   └── restore_db.sh                # Restore database
└── README.md                        # Dokumentasi Utama
```

---

## 🧪 Pengujian & Verifikasi

### Pengujian RADIUS dari Server Host:
```bash
radtest 00:11:22:33:44:55 00:11:22:33:44:55 127.0.0.1 1812 <SHARED_SECRET_ANDA>
```

**Output Berhasil (Access-Accept):**
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

## 🔧 Pemeliharaan & Operasional

### Melakukan Update Sistem ke Versi Terbaru:
```bash
cd /opt/macson
git pull origin main
docker compose -f docker/docker-compose.yml restart
docker exec radius_laravel_app php artisan migrate --force
```

### Pembersihan Log Terjadwal (Pruning):
```bash
# Otomatis membersihkan log autentikasi lebih dari 30 hari
docker exec radius_laravel_app php artisan radius:prune-logs --days=30
```

### Cek Kesehatan Layanan:
```bash
sudo bash /opt/macson/scripts/health_check.sh
```

---

## 👤 Author & Lisensi

- **Author**: Santos ([@technogithub](https://github.com/technogithub))
- **Proyek Repository**: [https://github.com/technogithub/MACSON](https://github.com/technogithub/MACSON)
- **Lisensi**: [MIT License](LICENSE)

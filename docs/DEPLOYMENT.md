# 🚀 Panduan Deployment Production: SANTAFE NAC

**SANTAFE NAC** (**S**antos **A**dvanced **N**etwork **T**raffic & **A**ccess **F**iltering **E**ngine) dirancang dengan arsitektur *microservices* berbasis Docker berkinerja tinggi, mencakup **FreeRADIUS v3** (Core RADIUS Engine), **Kea DHCPv4** (High-Performance DHCP Server), **MariaDB 10.11** (Database), **Nginx**, dan **Laravel 11** (Web Management UI & API).

Panduan ini ditujukan untuk deployment di lingkungan server produksi berbasis **Ubuntu Server 24.04 / 22.04 LTS**.

---

## 1. Persyaratan Sistem (Prerequisites)

- **Sistem Operasi:** Ubuntu Server 24.04 LTS atau 22.04 LTS (x86_64).
- **Spesifikasi Hardware:**
  - Minimal: 2 vCPU, 2 GB RAM, 20 GB SSD.
  - Rekomendasi Produksi: 4 vCPU, 4 GB RAM, 40 GB SSD.
- **Port Jaringan yang Wajib Dibuka di Firewall (UFW):**
  - `1812/UDP` : FreeRADIUS Authentication
  - `1813/UDP` : FreeRADIUS Accounting
  - `67/UDP` : Kea DHCPv4 Server (Broadcast / Relay)
  - `80/TCP` & `443/TCP` : Web Dashboard Laravel / Nginx
  - `8080/TCP` : phpMyAdmin Database Management (Opsional / Restricted)
  - `22/TCP` : SSH Remote Access (Dibatasi ke IP Management)

---

## 2. Contoh Standar Segmen Jaringan & VLAN (RFC 1918)

Dalam seluruh konfigurasi jaringan dan dokumentasi SANTAFE, gunakan panduan segmen jaringan standar berikut:

| Segmen / Keperluan | VLAN ID | Subnet CIDR (Contoh) | Alokasi DHCP Pool | Gateway / Router |
|---|:---:|---|---|---|
| **Karyawan / Corporate Staff** | `10` | `192.168.10.0/24` | `192.168.10.100 - 192.168.10.200` | `192.168.10.1` |
| **Tamu / UniFi Hotspot Voucher** | `20` | `192.168.20.0/24` | `192.168.20.50 - 192.168.20.250` | `192.168.20.1` |
| **IoT / Kamera IP / Printer** | `30` | `192.168.30.0/24` | `192.168.30.10 - 192.168.30.200` | `192.168.30.1` |
| **Server & Switch Management** | `99` | `192.168.99.0/24` | *Reservasi Static Saja* | `192.168.99.1` |

---

## 3. Langkah-Langkah Deployment

### Opsi A: Instalasi Otomatis (Rekomendasi)

Gunakan script instalasi otomatis yang telah disediakan:

```bash
curl -fsSL https://raw.githubusercontent.com/technogithub/MACSON/main/scripts/install.sh -o /tmp/install.sh && sudo bash /tmp/install.sh
```

Script akan memandu Anda mengatur:
1. Subnet Access Point / NAS Controller (misal: `192.168.1.0/24`)
2. Subnet IP yang diizinkan untuk SSH & Web Admin
3. RADIUS Shared Secret Key
4. Password Database MariaDB & Web Admin

---

### Opsi B: Instalasi Manual via Git & Docker Compose

#### Langkah 1: Persiapan Server & Dependensi
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl ufw docker.io docker-compose-v2
sudo systemctl enable --now docker
```

#### Langkah 2: Clone Repositori Proyek
```bash
sudo git clone https://github.com/technogithub/MACSON.git /opt/macson
cd /opt/macson
```

#### Langkah 3: Konfigurasi Environment (`.env`)
```bash
cp backend-laravel/.env.example backend-laravel/.env
nano backend-laravel/.env
```
Sesuaikan parameter database, secret key, dan konfigurasi UniFi Controller API (`UNIFI_URL`, `UNIFI_USER`, `UNIFI_PASS`).

#### Langkah 4: Build & Jalankan Microservices
```bash
docker compose -f docker/docker-compose.yml up -d --build
```

#### Langkah 5: Inisialisasi Database & Key Laravel
```bash
docker exec radius_laravel_app php artisan key:generate
docker exec radius_laravel_app php artisan migrate --force
docker exec radius_laravel_app php artisan db:seed --force
```

---

## 4. Verifikasi Layanan

Pastikan seluruh container microservices berjalan normal (`healthy` / `Up`):

```bash
docker compose -f docker/docker-compose.yml ps
```

Container yang harus aktif:
- `radius_nginx` : Web server reverse proxy SSL (Port 80/443)
- `radius_laravel_app` : Backend PHP 8.3 FPM
- `radius_mariadb` : Database server MariaDB 10.11
- `radius_freeradius` : Engine FreeRADIUS v3 (Port 1812, 1813 UDP)
- `radius_kea_dhcp` : Engine Kea DHCPv4 Server (Port 67 UDP)
- `radius_phpmyadmin` : Database explorer (Port 8080 TCP)

### Pengujian Otentikasi RADIUS:
```bash
radtest 00:11:22:33:44:55 00:11:22:33:44:55 127.0.0.1 1812 <SHARED_SECRET_ANDA>
```

---

## 5. Manajemen & Operasional Server

### Update Versi Terbaru dari GitHub:
```bash
cd /opt/macson
git pull origin main
docker compose -f docker/docker-compose.yml restart
docker exec radius_laravel_app php artisan migrate --force
```

### Pembersihan Log Terjadwal (Pruning):
Sistem memiliki artisan command untuk membersihkan log autentikasi lama:
```bash
# Hapus log yang lebih tua dari 30 hari
docker exec radius_laravel_app php artisan radius:prune-logs --days=30
```

### Backup & Restore Database:
```bash
# Backup
sudo bash /opt/macson/scripts/backup_db.sh

# Restore
sudo bash /opt/macson/scripts/restore_db.sh /path/to/backup.sql.gz
```

### Memeriksa Log Container:
```bash
# Log FreeRADIUS
docker logs -f radius_freeradius

# Log Kea DHCPv4
docker logs -f radius_kea_dhcp

# Log Web Laravel
docker logs -f radius_laravel_app
```

---

## 6. Integrasi dengan Ubiquiti UniFi

1. **RADIUS Profile**: Arahkan Authentication Server UniFi ke IP Server SANTAFE port `1812` dan Accounting Server ke port `1813`.
2. **WiFi Network**: Aktifkan **MAC ID Authentication** atau **WPA Enterprise** pada SSID yang dituju.
3. **Dynamic VLAN**: Centang **Use RADIUS assigned VLANs** pada pengaturan WiFi Network di UniFi Controller.

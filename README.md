# 🌿 PETALELE.NET - Pemantau Tanah Lembab Lewat Internet

PETALELE.NET adalah ekosistem IoT (*Internet of Things*) terintegrasi yang dirancang untuk memantau kelembaban tanah dan intensitas cahaya perkebunan secara *real-time*. Proyek ini menggabungkan ketangguhan mikrokontroler **ESP32**, protokol komunikasi data **MQTT**, serta fitur analisis cerdas berbasis **Gemini AI Assistant** untuk memberikan rekomendasi perawatan tanaman secara presisi.

---

## 🚀 Fitur Utama Sistem

* **Real-time Monitoring & Control**: Antarmuka web interaktif yang menampilkan log data kelembaban tanah dan cahaya secara instan tanpa perlu memuat ulang halaman (*zero-reload*).
* **Smart Calibration Engine**: Mengonversi nilai mentah analog (*raw ADC*) dari ESP32 secara akurat ke dalam satuan persentase ($0\% - 100\%$).
* **Two-Way Command Broker**: Kendali jarak jauh (*remote control*) untuk menyalakan atau mematikan modul relay pompa air langsung dari dasbor web.
* **Petalele AI Assistant**: Integrasi mutakhir dengan *Generative AI* untuk menganalisis kondisi tanaman saat ini dan memberikan saran irigasi otomatis dalam hitungan detik.
* **Modern UI/UX**: Tampilan responsif yang dilengkapi dengan efek animasi saat digulir (*AOS Library*), indikator status koneksi *Live*, serta fitur sakelar mode gelap/terang (*Dark/Light Mode*).

---

## 🔌 Arsitektur Perangkat Keras (ESP32 Pinout)

Berikut adalah pemetaan pin komponen fisik yang terhubung pada papan pengembang ESP32:

| Komponen | Jenis Pin | Pin ESP32 | Rentang Kalibrasi Web |
| :--- | :--- | :--- | :--- |
| **Sensor Soil Moisture** | Analog Input | `GPIO 35` | `2900` (Kering $0\%$) s.d `1000` (Basah $100\%$) |
| **Sensor Cahaya LDR** | Analog Input | `GPIO 34` | `4095` (Gelap $0\%$) s.d `0` (Terang $100\%$) |
| **Modul Relay Pompa** | Digital Output | `GPIO 26` | Status Kontrol: `1` (ON) / `0` (OFF) |

---

## 🌐 Konfigurasi Jaringan & Topik MQTT

Aplikasi web dan firmware ESP32 berkomunikasi melalui perantara *Broker* MQTT publik dengan ketentuan struktur data berikut:

* **MQTT Broker Address**: `broker.hivemq.com`
* **Port Jaringan**: `1883` (TCP untuk ESP32) | `8000` / `443` (WebSockets untuk Aplikasi Web)
* **Alur Topik Kontrol:**

[ESP32] --(Publish: iot/kebun/soil)-----------> [ Broker MQTT ] ---> [Web Dashboard]

[ESP32] --(Publish: iot/kebun/ldr)------------> [ broker.hivemq.com ] -> [Web Dashboard]

[ESP32] <-(Subscribe: iot/kebun/pompa)-------- [ Port 1883 ] <--- [Web Dashboard (Button ON/OFF)]


---

## 🛠️ Langkah Pemasangan & Penggunaan

### 1. Sisi Perangkat Keras (Hardware)
1. Buka berkas kode program ESP32 melalui **Arduino IDE**.
2. Pastikan pustaka `PubSubClient` sudah terinstal di komputer.
3. Sesuaikan variabel kredensial Wi-Fi lokal Anda:
   ```cpp
   const char* ssid     = "NAMA_WIFI_KAMU";
   const char* password = "PASSWORD_WIFI_KAMU";
4. Unggah (compile & upload) kode ke papan ESP32 Anda.

### 2. Sisi Perangkat Keras (Hardware)
1. Letakkan berkas `index.html` dari dasbor Petelele ke dalam direktori proyek bersama dengan berkas pendukung style.css.
2. Buka berkas `index.html`, lalu cari variabel GEMINI_KEY di dalam blok skrip bagian bawah.
3. Masukkan kunci API Gemini Anda yang valid agar fitur asisten cerdas dapat bekerja:
   ```cpp
    const GEMINI_KEY = "ISI_API_KEY_GEMINI_KAMU_DISINI";
4. Jalankan berkas `index.html` menggunakan peramban web (browser) kesayangan Anda atau gunakan ekstensi _Live Server_.

---

## 📊 Manajemen Riwayat Data (Live Log)
Dasbor dilengkapi dengan komponen tabel dinamis yang berfungsi mencatat rekaman data terbaru yang dikirim oleh ESP32. Demi menjaga stabilitas performa memori pada peramban web, data log diatur menggunakan metode antrean First-In-First-Out (FIFO) dengan batas maksimal penampilan sebanyak 10 baris data terbaru.

---
## 👥 Kontribusi Tim
- Mugni Ramdani (23552011224)
- Ridho Kurniawan (23552011073)
- Sri Lestari (23552011426)

Proyek ini dikembangkan dan dikelola sepenuhnya oleh Kelompok 3 - Team Petalele © 2026.

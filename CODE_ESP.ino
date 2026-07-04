#include <WiFi.h>
#include <PubSubClient.h>

// ==========================================
// 1. PENGATURAN WI-FI & MQTT (SILAKAN DISESUAIKAN)
// ==========================================
const char* ssid     = "iPhone";     // Ganti dengan nama Wi-Fi/Hotspot kamu
const char* password = "Ri1234567"; // Ganti dengan password Wi-Fi kamu

const char* mqtt_server = "broker.hivemq.com";
const int mqtt_port    = 1883; // Port standar TCP untuk mikrokontroler

// Topic MQTT - Harus sama persis dengan yang ada di JavaScript Website
const char* topic_soil  = "iot/kebun/soil";
const char* topic_ldr   = "iot/kebun/ldr";
const char* topic_pompa = "iot/kebun/pompa";

// ==========================================
// 2. DEFINISI PIN KOMPONEN ESP32
// ==========================================
const int pinSoil  = 35; // Sensor Kelembaban Tanah (Analog)
const int pinLDR   = 34; // Sensor Cahaya LDR (Analog)
const int pinRelay = 26; // Output Relay untuk Pompa

WiFiClient espClient;
PubSubClient client(espClient);

unsigned long lastMsg = 0;
const long interval = 5000; // Interval pengiriman data ke web (5000 ms = 5 detik)

// ==========================================
// 3. FUNGSI KONEKSI WI-FI
// ==========================================
void setup_wifi() {
  delay(10);
  Serial.println();
  Serial.print("Menghubungkan ke Wi-Fi: ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\n[Wi-Fi] Terhubung!");
  Serial.print("[Wi-Fi] IP Address: ");
  Serial.println(WiFi.localIP());
}

// ==========================================
// 4. FUNGSI MENERIMA PERINTAH DARI WEB (CALLBACK)
// ==========================================
void callback(char* topic, byte* payload, unsigned long length) {
  Serial.print("[MQTT] Pesan masuk di topic [");
  Serial.print(topic);
  Serial.print("]: ");

  // Konversi payload byte menjadi String
  String messageTemp;
  for (int i = 0; i < length; i++) {
    messageTemp += (char)payload[i];
  }
  Serial.println(messageTemp);

  // Cek apakah pesan berasal dari topic pompa
  if (String(topic) == topic_pompa) {
    if (messageTemp == "1") {
      Serial.println("-> STATUS: MENYALAKAN POMPA");
      digitalWrite(pinRelay, LOW); // Ubah ke LOW jika modul relay kamu tipe Active Low
    } else if (messageTemp == "0") {
      Serial.println("-> STATUS: MEMATIKAN POMPA");
      digitalWrite(pinRelay, HIGH);  // Ubah ke HIGH jika modul relay kamu tipe Active Low
    }
  }
}

// ==========================================
// 5. FUNGSI KONEKSI ULANG MQTT BROKER
// ==========================================
void reconnect() {
  // Looping sampai ESP32 benar-benar terhubung ke Broker
  while (!client.connected()) {
    Serial.print("[MQTT] Mencoba koneksi ke broker...");
    
    // Membuat Client ID acak agar tidak bentrok dengan Client ID Website
    String clientId = "PetaleleESP32-" + String(random(0, 10000));
    
    if (client.connect(clientId.c_str())) {
      Serial.println("Terhubung!");
      
      // WAJIB Subscribe ke topic pompa supaya ESP32 mendengarkan klik tombol dari web
      client.subscribe(topic_pompa);
      Serial.println("[MQTT] Sukses Subscribe ke topic kontrol pompa.");
    } else {
      Serial.print("Gagal, status=");
      Serial.print(client.state());
      Serial.println(". Mencoba lagi dalam 5 detik...");
      delay(5000);
    }
  }
}

// ==========================================
// 6. SETUP AWAL (DIJALANKAN 1 KALi)
// ==========================================
void setup() {
  Serial.begin(115200);
  delay(1000); // Beri jeda kestabilan serial monitor setelah boot
  
  // 1. Konfigurasi Pin Relay Lebih Awal
  pinMode(pinRelay, OUTPUT);
  digitalWrite(pinRelay, LOW); // Pompa default mati (Ubah HIGH jika Active Low)
  
  // 2. Hubungkan Wi-Fi sampai tuntas mendapat IP
  setup_wifi();
  
  // 3. Daftarkan Alamat Broker MQTT
  client.setServer(mqtt_server, mqtt_port);
  
  // 4. WAJIB DI SINI: Daftarkan fungsi callback tepat setelah server siap
  client.setCallback(callback); 
  
  Serial.println("[SYSTEM] Setup selesai, siap memproses loop.");
}

// ==========================================
// 7. LOOP UTAMA (DIJALANKAN TERUS-MENERUS)
// ==========================================
void loop() {
  // Pastikan ESP32 selalu terkoneksi ke Broker MQTT
  if (!client.connected()) {
    reconnect();
  }
  client.loop(); // Memproses data masuk dan menjaga koneksi keep-alive

  // Mengirim data sensor secara berkala (Non-blocking menggunakan millis)
  unsigned long now = millis();
  if (now - lastMsg > interval) {
    lastMsg = now;

    // Membaca nilai analog dari sensor asli
    int nilaiSoil = analogRead(pinSoil);
    int nilaiLDR  = analogRead(pinLDR);

    // Mengubah nilai data integer menjadi bentuk String/Char Array
    char msgSoil[8];
    char msgLDR[8];
    dtostrf(nilaiSoil, 1, 0, msgSoil);
    dtostrf(nilaiLDR, 1, 0, msgLDR);

    // Mengirimkan (Publish) data ke Broker MQTT
    Serial.print("[Publish] Soil: "); Serial.println(msgSoil);
    client.publish(topic_soil, msgSoil);

    Serial.print("[Publish] LDR : "); Serial.println(msgLDR);
    client.publish(topic_ldr, msgLDR);
    
    Serial.println("------------------------------------");
  }
}
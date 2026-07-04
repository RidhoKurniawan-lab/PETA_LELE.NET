<?php

define('LLM_API_KEY', 'AIzaSy...');
define('LLM_URL', '#' . LLM_API_KEY);

//Proses simpan data dari ESP32
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soil']) && isset($_POST['light'])) {
    $soil = floatval($_POST['soil']);
    $light = floatval($_POST['light']);
    $timestamp = date('Y-m-d H:i:s');

    $dataFile = 'data.json';
    $existingData = [];
    if (file_exists($dataFile)) {
        $existingData = json_decode(file_get_contents($dataFile), true) ?? [];
    }

    $existingData[] = [
        'timestamp' => $timestamp,
        'soil' => $soil,
        'light' => $light
    ];

    file_put_contents($dataFile, json_encode($existingData));

    http_response_code(200);
    echo "OK";
    exit;
}


//Ambil data 7 hari terakhir
$dataFile = 'data.json';
$history = [];
$prediction = "Belum ada data. Sambungkan ESP32.";

if (file_exists($dataFile)) {
    $allData = json_decode(file_get_contents($dataFile), true) ?? [];
    
    $cutoff = strtotime('-7 days');
    $history = array_filter($allData, function($item) use ($cutoff) {
        return strtotime($item['timestamp']) >= $cutoff;
    });
    $history = array_values($history);

    if (count($history) > 0) {
        $logText = "Data kelembaban tanah (%) dan cahaya selama 7 hari terakhir (timestamp, soil, light):\n";
        foreach ($history as $row) {
            $logText .= "- {$row['timestamp']}, Soil: {$row['soil']}%, Light: {$row['light']} lux\n";
        }

        $prompt = $logText . "\nBerdasarkan data di atas, prediksikan kapan waktu terbaik untuk menyiram tanaman dalam format jam (misal: 'Sekarang', '3 jam lagi', 'Besok pagi'). 
        Berikan alasannya singkat dalam 1 kalimat. Jawab langsung tanpa markdown.";

        $postData = json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ]);

        $ch = curl_init(LLM_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                $prediction = $result['candidates'][0]['content']['parts'][0]['text'];
            } else {
                $prediction = "LLM merespons, tapi format tidak dikenali.";
            }
        } else {
            $prediction = "Gagal terhubung ke API LLM.";
        }
    }
}

$latest = !empty($history) ? end($history) : null;
$totalPoints = count($history);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petalele</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body class="dark">
    <div class="container">
        <!-- Header dengan toggle -->
        <header data-aos="fade-down">
            <div class="header-top">
                <div class="header-title">
                    <h1>🌱 PETALELE.NET</h1>
                    <span class="badge-live">● Live</span>
                </div>
                <div class="theme-toggle">
                    <input type="checkbox" id="themeSwitch" />
                    <label for="themeSwitch" class="toggle-label">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
            <p class="subtitle">Pemantau Tanah Lembab Lewat Internet</p>
        </header>

        <div class="cards">
            <div class="card card-soil" data-aos="fade-up" data-aos-delay="100">
                <div class="card-icon">💧</div>
                <h3>Kelembaban Tanah</h3>
                <p class="value"><?= $latest ? $latest['soil'] : '--' ?><span>%</span></p>
                <small>Terakhir: <?= $latest ? $latest['timestamp'] : '-' ?></small>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $latest ? min($latest['soil'], 100) : 0 ?>%;"></div>
                </div>
            </div>

            <div class="card card-light" data-aos="fade-up" data-aos-delay="200">
                <div class="card-icon">☀️</div>
                <h3>Intensitas Cahaya</h3>
                <p class="value"><?= $latest ? $latest['light'] : '--' ?><span>lux</span></p>
                <small>Total data: <?= $totalPoints ?> titik</small>
                <div class="light-indicator">
                    <span class="dot" style="background: <?= ($latest && $latest['light'] > 500) ? '#10b981' : '#6b7280' ?>;"></span>
                    <?= ($latest && $latest['light'] > 500) ? 'Cerah' : 'Redup' ?>
                </div>
            </div>

            <div class="card card-prediction" data-aos="fade-up" data-aos-delay="300">
                <div class="card-icon">🤖</div>
                <h3>Prediksi AI<span class="pulse-dot"></span></h3>
                <p class="value prediction-text"><?= htmlspecialchars($prediction) ?></p>
                <small>Berdasarkan tren 7 hari terakhir</small>
            </div>
        </div>

        <div class="table-wrapper" data-aos="fade-up" data-aos-delay="200">
            <div class="table-header">
                <h2>📊 Riwayat 7 Hari</h2>
                <span class="count-badge"><?= $totalPoints ?> data</span>
            </div>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Kelembaban</th>
                            <th>Cahaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($history)): ?>
                            <?php foreach (array_reverse($history) as $row): ?>
                            <tr>
                                <td><?= $row['timestamp'] ?></td>
                                <td><span class="badge-soil"><?= $row['soil'] ?>%</span></td>
                                <td><?= $row['light'] ?> lux</td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="empty-state">⏳ Belum ada data. Hubungkan ESP32.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="info-box" data-aos="fade-up" data-aos-delay="300">
            <p><center>Copyright &copy;<?php echo date("Y"); ?> Team Petalele - Kelompok 3</center></p>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ once: true, duration: 800, easing: 'ease-out-cubic' });

        const switchInput = document.getElementById('themeSwitch');
        const body = document.body;

        if (localStorage.getItem('theme') === 'light') {
            body.classList.remove('dark');
            body.classList.add('light');
            switchInput.checked = true;
        }

        switchInput.addEventListener('change', function() {
            if (this.checked) {
                body.classList.remove('dark');
                body.classList.add('light');
                localStorage.setItem('theme', 'light');
            } else {
                body.classList.remove('light');
                body.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    </script>
</body>
</html>
<?php
// File untuk mengecek config.php yang sedang digunakan

echo "<h1>🔍 Cek Config.php yang Sedang Digunakan</h1>";
echo "<hr>";

// Cek apakah config.php ada
if (!file_exists('config.php')) {
    echo "<p style='color: red; font-size: 18px;'>❌ File config.php TIDAK DITEMUKAN!</p>";
    die();
}

echo "<p style='color: green; font-size: 18px;'>✅ File config.php ditemukan</p>";

// Baca isi config.php
$config_content = file_get_contents('config.php');

// Cek apakah ada komentar FINAL VERSION
if (strpos($config_content, 'FINAL VERSION') !== false) {
    echo "<p style='color: green; font-size: 18px;'>✅ Config.php SUDAH BENAR (ada komentar 'FINAL VERSION')</p>";
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ Config.php masih VERSI LAMA (tidak ada komentar 'FINAL VERSION')</p>";
}

// Cek fungsi getPemeriksaanFromDB
if (strpos($config_content, 'function getPemeriksaanFromDB') !== false) {
    echo "<p style='color: green;'>✅ Fungsi getPemeriksaanFromDB() ada</p>";
    
    // Cek apakah ada filter jenis pemeriksaan yang ketat
    if (strpos($config_content, 'if (!empty($jenis_final))') !== false) {
        echo "<p style='color: red; font-size: 18px;'>❌ MASALAH DITEMUKAN! Config.php masih ada filter jenis pemeriksaan yang KETAT</p>";
        echo "<p>Baris kode yang salah: <code>if (!empty(\$jenis_final))</code></p>";
    } else {
        echo "<p style='color: green;'>✅ Tidak ada filter jenis pemeriksaan yang ketat</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Fungsi getPemeriksaanFromDB() TIDAK ADA!</p>";
}

// Test langsung query
echo "<h2>Test Query Langsung</h2>";

require_once 'config.php';

try {
    $filters = [
        'jenis_rawat' => 'rajal',
        'tgl_dari' => '2020-01-01',
        'tgl_sampai' => '2026-02-10'
    ];
    
    $data = getPemeriksaanFromDB($filters);
    $total = count($data);
    
    echo "<p style='font-size: 24px;'>Total data yang dihasilkan fungsi getPemeriksaanFromDB(): <strong style='color: " . ($total == 77 ? 'green' : 'red') . ";'>$total</strong></p>";
    
    if ($total == 77) {
        echo "<div style='background: #e8f5e9; padding: 20px; border-left: 4px solid #4caf50; margin: 20px 0;'>";
        echo "<h3>✅ FUNGSI SUDAH BENAR!</h3>";
        echo "<p>Config.php menghasilkan <strong>77 data</strong> seperti yang diharapkan.</p>";
        echo "<p><strong>Tapi dashboard masih menampilkan 71 data.</strong></p>";
        echo "<p><strong>Kemungkinan penyebab:</strong></p>";
        echo "<ul>";
        echo "<li>Dashboard.php masih versi lama</li>";
        echo "<li>Ada cache di dashboard.php</li>";
        echo "<li>Browser cache belum ter-clear</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #ffebee; padding: 20px; border-left: 4px solid #f44336; margin: 20px 0;'>";
        echo "<h3>❌ FUNGSI MASIH SALAH!</h3>";
        echo "<p>Config.php hanya menghasilkan <strong>$total data</strong> (seharusnya 77).</p>";
        echo "<p><strong>Ini berarti config.php yang lama masih digunakan atau ada masalah dengan fungsi getPemeriksaanFromDB().</strong></p>";
        echo "</div>";
    }
    
    // Tampilkan 10 data pertama
    if ($total > 0) {
        echo "<h3>Sample 10 Data Pertama:</h3>";
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
        echo "<tr style='background: #667eea; color: white;'>";
        echo "<th>No Order</th><th>No Rawat</th><th>Nama</th><th>Jenis Pemeriksaan</th></tr>";
        
        for ($i = 0; $i < min(10, $total); $i++) {
            $row = $data[$i];
            $bg = ($row['jenis_pemeriksaan'] == '-') ? 'background: #ffe5e5;' : '';
            echo "<tr style='$bg'>";
            echo "<td>" . htmlspecialchars($row['noorder'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['no_rawat'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_pasien'] ?? '-') . "</td>";
            echo "<td><strong>" . htmlspecialchars($row['jenis_pemeriksaan'] ?? '-') . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-size: 18px;'>❌ ERROR: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>📋 Informasi File config.php</h2>";
echo "<p>Ukuran file: <strong>" . filesize('config.php') . " bytes</strong></p>";
echo "<p>Terakhir dimodifikasi: <strong>" . date('d/m/Y H:i:s', filemtime('config.php')) . "</strong></p>";

// Tampilkan 50 baris pertama config.php
echo "<h3>50 Baris Pertama config.php:</h3>";
echo "<pre style='background: #f5f5f5; padding: 15px; border: 1px solid #ddd; overflow-x: auto; font-size: 11px;'>";
$lines = explode("\n", $config_content);
for ($i = 0; $i < min(50, count($lines)); $i++) {
    echo htmlspecialchars(($i + 1) . ": " . $lines[$i]) . "\n";
}
echo "</pre>";

?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f7fa; }
    h1 { color: #667eea; }
    h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    h3 { color: #555; }
    table { background: white; margin: 15px 0; }
    th { font-weight: bold; padding: 10px; }
    td { padding: 8px; }
    tr:nth-child(even) { background: #f9f9f9; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>

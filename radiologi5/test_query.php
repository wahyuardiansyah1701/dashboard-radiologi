<?php
// Test Query Database SIK - Version 3 (Tanpa tabel pemeriksaan_radiologi)
require_once 'config.php';

echo "<h1>Test Query Database SIK - Versi Aman</h1>";
echo "<hr>";

// Test 1: Koneksi
echo "<h2>1. ✅ Test Koneksi Database</h2>";
try {
    $conn = getConnection();
    echo "<p style='color: green; font-weight: bold;'>Koneksi Berhasil ke database: sik</p>";
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>Koneksi Gagal: " . $e->getMessage() . "</p>";
    die();
}

// Test 2: Query sederhana dari permintaan_radiologi
echo "<h2>2. Test Data Permintaan Radiologi</h2>";
$sql_simple = "SELECT COUNT(*) as total FROM permintaan_radiologi";
$result = $conn->query($sql_simple);
$total = $result->fetch_assoc()['total'];
echo "<p>Total data di tabel permintaan_radiologi: <strong>$total</strong> baris</p>";

// Test 3: Query dengan JOIN (tanpa pemeriksaan_radiologi)
echo "<h2>3. Test Query dengan JOIN (Tanpa pemeriksaan_radiologi)</h2>";

$sql_test = "SELECT 
    pr.noorder,
    pr.no_rawat,
    pr.informasi_tambahan,
    p.nm_pasien,
    p.no_rkm_medis,
    pol.nm_poli,
    d.nm_dokter,
    pj.png_jawab
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN poliklinik pol ON rp.kd_poli = pol.kd_poli
LEFT JOIN dokter d ON pr.dokter_perujuk = d.kd_dokter
LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
LIMIT 5";

$result_test = $conn->query($sql_test);

if ($result_test === false) {
    echo "<p style='color: red;'><strong>❌ Query Error:</strong> " . $conn->error . "</p>";
} else {
    echo "<p style='color: green;'><strong>✅ Query Berhasil!</strong></p>";
    echo "<p>Jumlah data: <strong>" . $result_test->num_rows . "</strong> baris</p>";
    
    if ($result_test->num_rows > 0) {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; margin-top: 15px;'>";
        echo "<tr style='background: #667eea; color: white;'>";
        echo "<th>No Order</th><th>No Rawat</th><th>Nama Pasien</th><th>No RM</th>";
        echo "<th>Info Tambahan</th><th>Poli</th><th>Dokter</th><th>Cara Bayar</th></tr>";
        
        while ($row = $result_test->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . ($row['noorder'] ?? '-') . "</td>";
            echo "<td>" . ($row['no_rawat'] ?? '-') . "</td>";
            echo "<td>" . ($row['nm_pasien'] ?? '-') . "</td>";
            echo "<td>" . ($row['no_rkm_medis'] ?? '-') . "</td>";
            echo "<td>" . ($row['informasi_tambahan'] ?? '-') . "</td>";
            echo "<td>" . ($row['nm_poli'] ?? '-') . "</td>";
            echo "<td>" . ($row['nm_dokter'] ?? '-') . "</td>";
            echo "<td>" . ($row['png_jawab'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Test 4: Fungsi getPemeriksaanFromDB
echo "<h2>4. Test Fungsi getPemeriksaanFromDB()</h2>";
try {
    $data = getPemeriksaanFromDB([]);
    echo "<p style='color: green;'><strong>✅ Fungsi Berhasil!</strong></p>";
    echo "<p>Jumlah data yang dihasilkan: <strong>" . count($data) . "</strong> baris</p>";
    
    if (count($data) > 0) {
        echo "<h3>Preview 3 Data Pertama:</h3>";
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr style='background: #667eea; color: white;'>";
        echo "<th>No Rawat</th><th>Nama Pasien</th><th>No RM</th><th>Jenis Pemeriksaan</th>";
        echo "<th>Kamar</th><th>Dokter Perujuk</th><th>Cara Bayar</th></tr>";
        
        for ($i = 0; $i < min(3, count($data)); $i++) {
            $row = $data[$i];
            echo "<tr>";
            echo "<td>{$row['no_rawat']}</td>";
            echo "<td>{$row['nama_pasien']}</td>";
            echo "<td>{$row['no_rm']}</td>";
            echo "<td>{$row['jenis_pemeriksaan']}</td>";
            echo "<td>{$row['kamar']}</td>";
            echo "<td>{$row['dokter_perujuk']}</td>";
            echo "<td>{$row['cara_bayar']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Tidak ada data yang dihasilkan</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}

// Test 5: Cek tabel yang tersedia
echo "<h2>5. Tabel yang Berkaitan dengan Radiologi</h2>";
$sql_tables = "SHOW TABLES";
$result_tables = $conn->query($sql_tables);

$radiologi_tables = [];
while ($row = $result_tables->fetch_array()) {
    $table = $row[0];
    if (stripos($table, 'radiologi') !== false || 
        stripos($table, 'periksa') !== false ||
        stripos($table, 'kamar') !== false ||
        stripos($table, 'perawatan') !== false) {
        $radiologi_tables[] = $table;
    }
}

if (!empty($radiologi_tables)) {
    echo "<ul>";
    foreach ($radiologi_tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
} else {
    echo "<p>Tidak ada tabel yang berkaitan dengan radiologi</p>";
}

echo "<hr>";
echo "<h2>📊 Kesimpulan:</h2>";
echo "<div style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; border-radius: 5px;'>";
echo "<p><strong>✅ Tabel yang TERSEDIA dan digunakan:</strong></p>";
echo "<ul>";
echo "<li>permintaan_radiologi ✅</li>";
echo "<li>reg_periksa ✅</li>";
echo "<li>pasien ✅</li>";
echo "<li>poliklinik ✅</li>";
echo "<li>dokter ✅</li>";
echo "<li>penjab ✅</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3e0; padding: 15px; border-left: 4px solid #ff9800; border-radius: 5px; margin-top: 15px;'>";
echo "<p><strong>⚠️ Tabel yang TIDAK TERSEDIA:</strong></p>";
echo "<ul>";
echo "<li>pemeriksaan_radiologi ❌ (tidak ada di database)</li>";
echo "<li>kamar ❓ (perlu dicek)</li>";
echo "</ul>";
echo "<p><em>Dashboard akan tetap berfungsi tanpa tabel-tabel ini.</em></p>";
echo "</div>";

$conn->close();
?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f7fa; line-height: 1.6; }
    h1 { color: #667eea; }
    h2 { color: #333; margin-top: 30px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
    h3 { color: #555; }
    table { background: white; margin-top: 10px; width: 100%; }
    th { font-weight: bold; padding: 10px; }
    td { padding: 8px; }
    tr:nth-child(even) { background: #f9f9f9; }
    ul { line-height: 1.8; }
</style>

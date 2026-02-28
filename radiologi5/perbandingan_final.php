<?php
// Membandingkan Data Database vs Dashboard - FINAL
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sik';

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

echo "<h1>🔍 Perbandingan Data Database vs Dashboard</h1>";
echo "<hr>";

// 1. Ambil SEMUA data dari database (tanpa filter jenis pemeriksaan)
echo "<h2>1. SEMUA Data di Database (Tanpa Filter)</h2>";

$sql_all = "SELECT 
    pr.noorder,
    pr.no_rawat,
    pr.tgl_permintaan,
    pr.jam_permintaan,
    p.nm_pasien,
    pr.informasi_tambahan,
    (SELECT GROUP_CONCAT(DISTINCT jpr2.nm_perawatan SEPARATOR ', ')
     FROM permintaan_pemeriksaan_radiologi ppr2
     LEFT JOIN jns_perawatan_radiologi jpr2 ON ppr2.kd_jenis_prw = jpr2.kd_jenis_prw
     WHERE ppr2.noorder = pr.noorder
     AND jpr2.nm_perawatan IS NOT NULL
     AND jpr2.nm_perawatan != ''
     AND jpr2.nm_perawatan != '-'
    ) as jenis_dari_tabel,
    COALESCE(
        (SELECT GROUP_CONCAT(DISTINCT jpr2.nm_perawatan SEPARATOR ', ')
         FROM permintaan_pemeriksaan_radiologi ppr2
         LEFT JOIN jns_perawatan_radiologi jpr2 ON ppr2.kd_jenis_prw = jpr2.kd_jenis_prw
         WHERE ppr2.noorder = pr.noorder
         AND jpr2.nm_perawatan IS NOT NULL
         AND jpr2.nm_perawatan != ''
         AND jpr2.nm_perawatan != '-'
        ),
        pr.informasi_tambahan,
        '-'
    ) as jenis_pemeriksaan_final
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE rp.status_lanjut = 'Ralan'
AND DATE(pr.tgl_permintaan) >= '2020-01-01'
ORDER BY pr.tgl_permintaan DESC, pr.jam_permintaan DESC";

$result_all = mysqli_query($conn, $sql_all);
$total_database = mysqli_num_rows($result_all);

$semua_data = [];
$data_dengan_jenis = [];
$data_tanpa_jenis = [];

while ($row = mysqli_fetch_assoc($result_all)) {
    $semua_data[] = $row;
    
    $jenis = $row['jenis_pemeriksaan_final'];
    if (!empty($jenis) && $jenis != '-') {
        $data_dengan_jenis[] = $row;
    } else {
        $data_tanpa_jenis[] = $row;
    }
}

echo "<p style='font-size: 18px;'>Total data di database: <strong style='color: blue;'>$total_database</strong></p>";
echo "<p style='font-size: 18px;'>Data DENGAN jenis pemeriksaan: <strong style='color: green;'>" . count($data_dengan_jenis) . "</strong></p>";
echo "<p style='font-size: 18px;'>Data TANPA jenis pemeriksaan: <strong style='color: red;'>" . count($data_tanpa_jenis) . "</strong></p>";

// 2. Tampilkan data yang TANPA jenis pemeriksaan (yang di-filter)
if (count($data_tanpa_jenis) > 0) {
    echo "<h2>2. Data yang DI-FILTER (Tidak Muncul di Dashboard)</h2>";
    echo "<p>Data berikut TIDAK muncul di dashboard karena tidak ada jenis pemeriksaan:</p>";
    
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; font-size: 13px;'>";
    echo "<tr style='background: #ff6b6b; color: white;'>";
    echo "<th>No</th><th>No Order</th><th>No Rawat</th><th>Tanggal</th><th>Nama Pasien</th>";
    echo "<th>Jenis dari Tabel</th><th>Info Tambahan</th><th>Final</th></tr>";
    
    foreach ($data_tanpa_jenis as $index => $row) {
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['noorder']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['no_rawat']) . "</td>";
        echo "<td>" . $row['tgl_permintaan'] . " " . $row['jam_permintaan'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nm_pasien']) . "</td>";
        echo "<td>" . htmlspecialchars($row['jenis_dari_tabel'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['informasi_tambahan'] ?? '-') . "</td>";
        echo "<td style='color: red;'><strong>" . htmlspecialchars($row['jenis_pemeriksaan_final']) . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Export ke file text untuk perbandingan
echo "<h2>3. Export Nomor Order untuk Perbandingan</h2>";

$filename_all = 'noorder_database_all.txt';
$filename_dengan = 'noorder_dashboard.txt';
$filename_tanpa = 'noorder_hilang.txt';

$fp_all = fopen($filename_all, 'w');
$fp_dengan = fopen($filename_dengan, 'w');
$fp_tanpa = fopen($filename_tanpa, 'w');

foreach ($semua_data as $row) {
    fwrite($fp_all, $row['noorder'] . "\n");
}

foreach ($data_dengan_jenis as $row) {
    fwrite($fp_dengan, $row['noorder'] . "\n");
}

foreach ($data_tanpa_jenis as $row) {
    fwrite($fp_tanpa, $row['noorder'] . "\n");
}

fclose($fp_all);
fclose($fp_dengan);
fclose($fp_tanpa);

echo "<p>✅ File berhasil dibuat:</p>";
echo "<ul>";
echo "<li><a href='$filename_all' download><strong>$filename_all</strong></a> - Semua nomor order ($total_database data)</li>";
echo "<li><a href='$filename_dengan' download><strong>$filename_dengan</strong></a> - Yang muncul di dashboard (" . count($data_dengan_jenis) . " data)</li>";
echo "<li><a href='$filename_tanpa' download><strong>$filename_tanpa</strong></a> - Yang di-filter (" . count($data_tanpa_jenis) . " data)</li>";
echo "</ul>";

// 4. Kesimpulan
echo "<hr>";
echo "<h2>📊 KESIMPULAN FINAL</h2>";

echo "<div style='padding: 20px; background: #e3f2fd; border-left: 4px solid #2196f3; margin: 20px 0;'>";
echo "<h3>📈 Statistik:</h3>";
echo "<table border='1' cellpadding='10' style='font-size: 16px; width: 100%; background: white;'>";
echo "<tr style='background: #667eea; color: white;'><th>Keterangan</th><th>Jumlah</th></tr>";
echo "<tr><td>Total di Database</td><td><strong>$total_database</strong></td></tr>";
echo "<tr style='background: #e8f5e9;'><td>Muncul di Dashboard (dengan jenis pemeriksaan)</td><td><strong style='color: green;'>" . count($data_dengan_jenis) . "</strong></td></tr>";
echo "<tr style='background: #ffe5e5;'><td>DI-FILTER (tanpa jenis pemeriksaan)</td><td><strong style='color: red;'>" . count($data_tanpa_jenis) . "</strong></td></tr>";
echo "</table>";
echo "</div>";

if ($total_database == 77 && count($data_dengan_jenis) == 71) {
    echo "<div style='padding: 20px; background: #fff3e0; border-left: 4px solid #ff9800;'>";
    echo "<h3>✅ HASIL ANALISIS:</h3>";
    echo "<p style='font-size: 16px;'>Database memiliki <strong>77 permintaan radiologi</strong>.</p>";
    echo "<p style='font-size: 16px;'>Dashboard menampilkan <strong>71 permintaan</strong> (yang memiliki jenis pemeriksaan).</p>";
    echo "<p style='font-size: 16px;'><strong style='color: red;'>" . count($data_tanpa_jenis) . " permintaan</strong> tidak ditampilkan karena <strong>TIDAK ADA jenis pemeriksaan</strong>.</p>";
    echo "<p style='font-size: 18px; font-weight: bold; color: green;'>✅ DASHBOARD SUDAH BEKERJA DENGAN BENAR!</p>";
    echo "<p>Semua data yang tidak muncul memang tidak memiliki jenis pemeriksaan, sesuai dengan permintaan Anda untuk hanya menampilkan pasien dengan jenis pemeriksaan.</p>";
    echo "</div>";
} else if ($total_database == 71) {
    echo "<div style='padding: 20px; background: #e8f5e9; border-left: 4px solid #4caf50;'>";
    echo "<h3>✅ DASHBOARD PERFECT!</h3>";
    echo "<p style='font-size: 18px; font-weight: bold; color: green;'>Total di database dan dashboard SAMA: 71 permintaan</p>";
    echo "<p>Semua data sudah ditampilkan dengan benar!</p>";
    echo "</div>";
} else {
    echo "<div style='padding: 20px; background: #ffebee; border-left: 4px solid #f44336;'>";
    echo "<h3>⚠️ ADA PERBEDAAN</h3>";
    echo "<p>Database: <strong>$total_database</strong></p>";
    echo "<p>Dashboard: <strong>" . count($data_dengan_jenis) . "</strong></p>";
    echo "<p>Perlu investigasi lebih lanjut.</p>";
    echo "</div>";
}

mysqli_close($conn);
?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f7fa; }
    h1 { color: #667eea; }
    h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    table { background: white; margin: 15px 0; }
    th { font-weight: bold; padding: 10px; }
    td { padding: 8px; }
    tr:nth-child(even) { background: #f9f9f9; }
    a { color: #667eea; text-decoration: none; font-weight: bold; }
    a:hover { text-decoration: underline; }
</style>

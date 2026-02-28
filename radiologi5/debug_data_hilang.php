<?php
// Test Standalone - Tidak perlu config.php eksternal
// Database Configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sik';

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

echo "<h1>🔍 Analisis Data Radiologi - Mencari 6 Data yang Hilang</h1>";
echo "<hr>";

// Test 1: Total di permintaan_radiologi
echo "<h2>1. Total Data Permintaan Radiologi (Rawat Jalan)</h2>";
$sql1 = "SELECT COUNT(*) as total 
         FROM permintaan_radiologi pr
         LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
         WHERE rp.status_lanjut = 'Ralan'
         AND DATE(pr.tgl_permintaan) >= '2020-01-01'";
$result1 = mysqli_query($conn, $sql1);
$total_permintaan = mysqli_fetch_assoc($result1)['total'];
echo "<p style='font-size: 20px;'>Total permintaan radiologi: <strong style='color: blue;'>$total_permintaan</strong></p>";

// Test 2: Data dengan jenis pemeriksaan dari jns_perawatan_radiologi
echo "<h2>2. Data DENGAN Jenis Pemeriksaan dari jns_perawatan_radiologi</h2>";
$sql2 = "SELECT COUNT(DISTINCT pr.noorder) as total
         FROM permintaan_radiologi pr
         LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
         LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
         LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
         WHERE rp.status_lanjut = 'Ralan'
         AND DATE(pr.tgl_permintaan) >= '2020-01-01'
         AND jpr.nm_perawatan IS NOT NULL
         AND jpr.nm_perawatan != ''
         AND jpr.nm_perawatan != '-'";
$result2 = mysqli_query($conn, $sql2);
$total_ada_jenis = mysqli_fetch_assoc($result2)['total'];
echo "<p style='font-size: 20px;'>Total dengan jenis pemeriksaan: <strong style='color: green;'>$total_ada_jenis</strong></p>";

// Test 3: Data TANPA jenis pemeriksaan (yang hilang)
echo "<h2>3. Data TANPA Jenis Pemeriksaan (Yang Hilang)</h2>";
$sql3 = "SELECT 
    pr.noorder,
    pr.no_rawat,
    pr.tgl_permintaan,
    pr.jam_permintaan,
    p.nm_pasien,
    pr.informasi_tambahan,
    jpr.nm_perawatan
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
WHERE rp.status_lanjut = 'Ralan'
AND DATE(pr.tgl_permintaan) >= '2020-01-01'
AND (
    jpr.nm_perawatan IS NULL 
    OR jpr.nm_perawatan = '' 
    OR jpr.nm_perawatan = '-'
)
ORDER BY pr.tgl_permintaan DESC, pr.jam_permintaan DESC";

$result3 = mysqli_query($conn, $sql3);
$total_hilang = mysqli_num_rows($result3);

echo "<p style='font-size: 20px;'>Total TANPA jenis pemeriksaan: <strong style='color: red;'>$total_hilang</strong></p>";

if ($total_hilang > 0) {
    echo "<h3>📋 Daftar Data yang Hilang:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; font-size: 13px; width: 100%;'>";
    echo "<tr style='background: #ff6b6b; color: white;'>";
    echo "<th>No</th><th>No Order</th><th>No Rawat</th><th>Tanggal</th><th>Nama Pasien</th><th>Info Tambahan</th><th>nm_perawatan</th></tr>";
    
    $no = 1;
    while ($row = mysqli_fetch_assoc($result3)) {
        echo "<tr>";
        echo "<td>$no</td>";
        echo "<td>" . htmlspecialchars($row['noorder']) . "</td>";
        echo "<td>" . htmlspecialchars($row['no_rawat']) . "</td>";
        echo "<td>" . $row['tgl_permintaan'] . " " . $row['jam_permintaan'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nm_pasien']) . "</td>";
        echo "<td>" . htmlspecialchars($row['informasi_tambahan'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['nm_perawatan'] ?? 'NULL') . "</td>";
        echo "</tr>";
        $no++;
    }
    echo "</table>";
}

// Test 4: Cek kolom informasi_tambahan yang terisi
echo "<h2>4. Cek Kolom informasi_tambahan</h2>";
$sql4 = "SELECT COUNT(*) as total
         FROM permintaan_radiologi pr
         LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
         WHERE rp.status_lanjut = 'Ralan'
         AND DATE(pr.tgl_permintaan) >= '2020-01-01'
         AND pr.informasi_tambahan IS NOT NULL
         AND pr.informasi_tambahan != ''
         AND pr.informasi_tambahan != '-'";
$result4 = mysqli_query($conn, $sql4);
$total_info_tambahan = mysqli_fetch_assoc($result4)['total'];
echo "<p style='font-size: 18px;'>Data dengan informasi_tambahan terisi: <strong>$total_info_tambahan</strong></p>";

// Test 5: Simulasi query dashboard (yang sekarang)
echo "<h2>5. Simulasi Query Dashboard (Config Lama)</h2>";
$sql5 = "SELECT COUNT(DISTINCT pr.noorder) as total
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
WHERE rp.status_lanjut = 'Ralan'
AND DATE(pr.tgl_permintaan) >= '2020-01-01'
GROUP BY pr.noorder
HAVING COALESCE(
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
) != '-'
AND COALESCE(
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
) != ''";

$result5 = mysqli_query($conn, $sql5);
$total_dashboard = mysqli_num_rows($result5);
echo "<p style='font-size: 18px;'>Total yang muncul di dashboard: <strong style='color: " . ($total_dashboard == 77 ? 'green' : 'red') . ";'>$total_dashboard</strong></p>";

echo "<hr>";
echo "<h2>📊 RINGKASAN</h2>";
echo "<table border='1' cellpadding='10' style='font-size: 16px; width: 100%;'>";
echo "<tr style='background: #667eea; color: white;'><th>Keterangan</th><th>Jumlah</th></tr>";
echo "<tr><td>Total Permintaan Radiologi</td><td><strong>$total_permintaan</strong></td></tr>";
echo "<tr><td>Dengan Jenis Pemeriksaan (jns_perawatan_radiologi)</td><td><strong>$total_ada_jenis</strong></td></tr>";
echo "<tr><td>Dengan informasi_tambahan Terisi</td><td><strong>$total_info_tambahan</strong></td></tr>";
echo "<tr style='background: #ffe5e5;'><td>TANPA Jenis Pemeriksaan (Hilang)</td><td><strong style='color: red;'>$total_hilang</strong></td></tr>";
echo "<tr style='background: #e8f5e9;'><td>Yang Muncul di Dashboard</td><td><strong style='color: " . ($total_dashboard == 77 ? 'green' : 'red') . ";'>$total_dashboard</strong></td></tr>";
echo "<tr style='background: #fff3e0;'><td><strong>TARGET (Sistem Asli)</strong></td><td><strong style='font-size: 20px;'>77</strong></td></tr>";
echo "</table>";

echo "<div style='margin-top: 20px; padding: 20px; background: #fff3e0; border-left: 4px solid #ff9800;'>";
echo "<h3>💡 ANALISIS:</h3>";

if ($total_hilang == 6 && $total_dashboard == 71) {
    echo "<p style='color: red; font-size: 16px;'><strong>✅ MASALAH DITEMUKAN!</strong></p>";
    echo "<p>Ada <strong>6 permintaan radiologi</strong> yang TIDAK memiliki jenis pemeriksaan, baik di tabel <code>jns_perawatan_radiologi</code> maupun di kolom <code>informasi_tambahan</code>.</p>";
    echo "<p><strong>Solusi:</strong></p>";
    echo "<ol>";
    echo "<li>Lihat daftar 6 data di atas</li>";
    echo "<li>Periksa apakah data tersebut memang tidak perlu ditampilkan, atau</li>";
    echo "<li>Isi jenis pemeriksaan untuk 6 data tersebut di database</li>";
    echo "</ol>";
} else if ($total_dashboard == 77) {
    echo "<p style='color: green; font-size: 16px;'><strong>✅ DATA SUDAH LENGKAP!</strong></p>";
    echo "<p>Dashboard menampilkan 77 data sesuai dengan sistem asli.</p>";
} else {
    echo "<p style='color: orange; font-size: 16px;'><strong>⚠️ ADA MASALAH LAIN</strong></p>";
    echo "<p>Selisih data tidak sesuai dengan yang diharapkan. Perlu investigasi lebih lanjut.</p>";
}

echo "</div>";

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
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>

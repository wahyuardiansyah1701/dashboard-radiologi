<?php
// Test Detail untuk Membandingkan Data dengan Sistem Asli
require_once 'config.php';

echo "<h1>Test Detail Data Radiologi - Perbandingan dengan Sistem Asli</h1>";
echo "<p><strong>Target:</strong> 77 permintaan radiologi</p>";
echo "<hr>";

// Test 1: Total permintaan_radiologi
echo "<h2>1. Total Data di Tabel permintaan_radiologi</h2>";
$sql1 = "SELECT COUNT(*) as total FROM permintaan_radiologi pr
         LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
         WHERE rp.status_lanjut = 'Ralan'
         AND DATE(pr.tgl_permintaan) >= '2020-01-01'";
$result1 = mysqli_query($conn, $sql1);
$total1 = mysqli_fetch_assoc($result1)['total'];
echo "<p><strong>Total permintaan radiologi rawat jalan:</strong> <span style='font-size: 24px; color: " . ($total1 == 77 ? 'green' : 'red') . ";'>$total1</span> record</p>";
echo "<p>" . ($total1 == 77 ? "✅ SESUAI dengan sistem asli" : "❌ TIDAK SESUAI - Expected: 77") . "</p>";

// Test 2: Cek duplikasi noorder
echo "<h2>2. Cek Duplikasi Nomor Permintaan (noorder)</h2>";
$sql2 = "SELECT noorder, COUNT(*) as jumlah
         FROM permintaan_radiologi pr
         LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
         WHERE rp.status_lanjut = 'Ralan'
         AND DATE(pr.tgl_permintaan) >= '2020-01-01'
         GROUP BY noorder
         HAVING jumlah > 1";
$result2 = mysqli_query($conn, $sql2);
$duplikat = mysqli_num_rows($result2);

if ($duplikat > 0) {
    echo "<p style='color: orange;'><strong>⚠️ Ditemukan $duplikat noorder yang duplikat:</strong></p>";
    echo "<table border='1' cellpadding='5'><tr><th>No Order</th><th>Jumlah Duplikat</th></tr>";
    while ($row = mysqli_fetch_assoc($result2)) {
        echo "<tr><td>{$row['noorder']}</td><td>{$row['jumlah']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'><strong>✅ Tidak ada duplikasi noorder</strong></p>";
}

// Test 3: Data TANPA filter jenis pemeriksaan
echo "<h2>3. Data Rawat Jalan TANPA Filter Jenis Pemeriksaan</h2>";
$sql3 = "SELECT 
    pr.noorder,
    pr.no_rawat,
    p.nm_pasien,
    COALESCE(
        GROUP_CONCAT(DISTINCT jpr.nm_perawatan SEPARATOR ', '),
        pr.informasi_tambahan,
        '-'
    ) as jenis_pemeriksaan
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
WHERE rp.status_lanjut = 'Ralan'
AND DATE(pr.tgl_permintaan) >= '2020-01-01'
GROUP BY pr.noorder
ORDER BY pr.tgl_permintaan DESC, pr.jam_permintaan DESC";

$result3 = mysqli_query($conn, $sql3);
$total3 = mysqli_num_rows($result3);
echo "<p><strong>Total:</strong> <span style='font-size: 24px; color: blue;'>$total3</span> record</p>";

// Tampilkan 15 data pertama
if ($total3 > 0) {
    echo "<h3>Sample 15 Data Pertama:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; font-size: 12px;'>";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>No</th><th>No Order</th><th>No Rawat</th><th>Nama Pasien</th><th>Jenis Pemeriksaan</th></tr>";
    
    $count = 0;
    mysqli_data_seek($result3, 0);
    while ($row = mysqli_fetch_assoc($result3)) {
        if ($count >= 15) break;
        $bg = ($row['jenis_pemeriksaan'] == '-') ? 'background: #ffe5e5;' : '';
        echo "<tr style='$bg'>";
        echo "<td>" . ($count + 1) . "</td>";
        echo "<td>" . htmlspecialchars($row['noorder']) . "</td>";
        echo "<td>" . htmlspecialchars($row['no_rawat']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nm_pasien']) . "</td>";
        echo "<td>" . htmlspecialchars($row['jenis_pemeriksaan']) . "</td>";
        echo "</tr>";
        $count++;
    }
    echo "</table>";
}

// Test 4: Data DENGAN filter jenis pemeriksaan
echo "<h2>4. Data DENGAN Filter Jenis Pemeriksaan (Yang Muncul di Dashboard)</h2>";
$sql4 = "SELECT 
    pr.noorder,
    pr.no_rawat,
    p.nm_pasien,
    COALESCE(
        GROUP_CONCAT(DISTINCT jpr.nm_perawatan SEPARATOR ', '),
        pr.informasi_tambahan,
        '-'
    ) as jenis_pemeriksaan
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
WHERE rp.status_lanjut = 'Ralan'
AND DATE(pr.tgl_permintaan) >= '2020-01-01'
GROUP BY pr.noorder
HAVING jenis_pemeriksaan IS NOT NULL 
AND jenis_pemeriksaan != '' 
AND jenis_pemeriksaan != '-'
ORDER BY pr.tgl_permintaan DESC, pr.jam_permintaan DESC";

$result4 = mysqli_query($conn, $sql4);
$total4 = mysqli_num_rows($result4);
echo "<p><strong>Total:</strong> <span style='font-size: 24px; color: " . ($total4 == 77 ? 'green' : 'red') . ";'>$total4</span> record</p>";
echo "<p>" . ($total4 == 77 ? "✅ SESUAI dengan target" : "❌ KURANG " . (77 - $total4) . " record") . "</p>";

// Test 5: Data yang HILANG (jenis pemeriksaan kosong)
echo "<h2>5. Data yang HILANG (Jenis Pemeriksaan Kosong)</h2>";
$sql5 = "SELECT 
    pr.noorder,
    pr.no_rawat,
    p.nm_pasien,
    pr.informasi_tambahan,
    COALESCE(
        GROUP_CONCAT(DISTINCT jpr.nm_perawatan SEPARATOR ', '),
        pr.informasi_tambahan,
        '-'
    ) as jenis_pemeriksaan
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
WHERE rp.status_lanjut = 'Ralan'
AND DATE(pr.tgl_permintaan) >= '2020-01-01'
GROUP BY pr.noorder
HAVING jenis_pemeriksaan IS NULL 
OR jenis_pemeriksaan = '' 
OR jenis_pemeriksaan = '-'
ORDER BY pr.tgl_permintaan DESC";

$result5 = mysqli_query($conn, $sql5);
$total5 = mysqli_num_rows($result5);

if ($total5 > 0) {
    echo "<p style='color: red;'><strong>❌ Ditemukan $total5 data TANPA jenis pemeriksaan yang di-filter:</strong></p>";
    echo "<table border='1' cellpadding='5' style='font-size: 12px;'>";
    echo "<tr style='background: #ff6b6b; color: white;'>";
    echo "<th>No Order</th><th>No Rawat</th><th>Nama Pasien</th><th>Info Tambahan</th><th>Jenis Pemeriksaan</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result5)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['noorder']) . "</td>";
        echo "<td>" . htmlspecialchars($row['no_rawat']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nm_pasien']) . "</td>";
        echo "<td>" . htmlspecialchars($row['informasi_tambahan'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['jenis_pemeriksaan']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'><strong>✅ Tidak ada data yang hilang</strong></p>";
}

// Test 6: Breakdown per jenis pemeriksaan
echo "<h2>6. Breakdown Berdasarkan Jenis Pemeriksaan</h2>";
$sql6 = "SELECT 
    COALESCE(
        GROUP_CONCAT(DISTINCT jpr.nm_perawatan SEPARATOR ', '),
        pr.informasi_tambahan,
        '-'
    ) as jenis_pemeriksaan,
    COUNT(*) as jumlah
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON pr.noorder = ppr.noorder
LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
WHERE rp.status_lanjut = 'Ralan'
AND DATE(pr.tgl_permintaan) >= '2020-01-01'
GROUP BY pr.noorder
ORDER BY jumlah DESC";

$result6 = mysqli_query($conn, $sql6);

// Group by jenis pemeriksaan
$breakdown = [];
while ($row = mysqli_fetch_assoc($result6)) {
    $jenis = $row['jenis_pemeriksaan'];
    if (!isset($breakdown[$jenis])) {
        $breakdown[$jenis] = 0;
    }
    $breakdown[$jenis]++;
}

arsort($breakdown);

echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr style='background: #667eea; color: white;'><th>Jenis Pemeriksaan</th><th>Jumlah</th></tr>";

foreach ($breakdown as $jenis => $jumlah) {
    $bg = ($jenis == '-') ? 'background: #ffe5e5;' : '';
    echo "<tr style='$bg'>";
    echo "<td>" . htmlspecialchars($jenis) . "</td>";
    echo "<td><strong>$jumlah</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<h2>📊 RINGKASAN</h2>";
echo "<table border='1' cellpadding='10' style='font-size: 14px; width: 100%;'>";
echo "<tr style='background: #667eea; color: white;'><th>Keterangan</th><th>Jumlah</th><th>Status</th></tr>";
echo "<tr><td>Total di tabel permintaan_radiologi</td><td><strong>$total1</strong></td><td>" . ($total1 == 77 ? '✅' : '❌') . "</td></tr>";
echo "<tr><td>Total TANPA filter jenis pemeriksaan</td><td><strong>$total3</strong></td><td>" . ($total3 == 77 ? '✅' : '❌') . "</td></tr>";
echo "<tr><td>Total DENGAN filter jenis pemeriksaan</td><td><strong>$total4</strong></td><td>" . ($total4 == 77 ? '✅' : '❌') . "</td></tr>";
echo "<tr style='background: #ffe5e5;'><td>Data yang hilang (no jenis pemeriksaan)</td><td><strong>$total5</strong></td><td>" . ($total5 == 0 ? '✅' : '⚠️') . "</td></tr>";
echo "<tr style='background: #e8f5e9;'><td><strong>TARGET (dari sistem asli)</strong></td><td><strong style='font-size: 18px;'>77</strong></td><td>🎯</td></tr>";
echo "</table>";

echo "<div style='margin-top: 20px; padding: 15px; background: #fff3e0; border-left: 4px solid #ff9800;'>";
echo "<h3>💡 Kesimpulan:</h3>";
if ($total4 == 77) {
    echo "<p style='color: green; font-size: 16px;'><strong>✅ DATA SUDAH SESUAI! Dashboard akan menampilkan 77 permintaan radiologi.</strong></p>";
} else {
    $selisih = 77 - $total4;
    echo "<p style='color: red; font-size: 16px;'><strong>❌ DATA KURANG $selisih RECORD</strong></p>";
    echo "<p><strong>Penyebab:</strong> $total5 data tidak memiliki jenis pemeriksaan</p>";
    echo "<p><strong>Solusi:</strong> Pastikan semua permintaan radiologi memiliki jenis pemeriksaan di tabel <code>permintaan_pemeriksaan_radiologi</code> atau di kolom <code>informasi_tambahan</code></p>";
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

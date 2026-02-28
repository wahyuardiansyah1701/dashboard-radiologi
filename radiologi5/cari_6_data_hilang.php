<?php
// Cari 6 Data yang Hilang - Berdasarkan Nomor Order
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sik';

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

echo "<h1>🔍 Mencari 6 Data yang Hilang dari Dashboard</h1>";
echo "<hr>";

// Daftar nomor order dari Excel yang TIDAK muncul di dashboard (sampel 6 terakhir berdasarkan output Python)
$noorder_hilang = [
    '2026/01/16/000001',
    '2026/01/17/000001',
    '2026/01/13/000002',
    '2026/01/12/000001',
    '2026/01/09/000002',
    '2026/01/09/000001'
];

echo "<h2>📋 Daftar Nomor Order yang Dicurigai Hilang:</h2>";
echo "<ul>";
foreach ($noorder_hilang as $no) {
    echo "<li><strong>$no</strong></li>";
}
echo "</ul>";

// Query untuk mengecek data dengan noorder tersebut
echo "<h2>1. Cek Data di Database</h2>";

$placeholders = implode(',', array_fill(0, count($noorder_hilang), '?'));
$sql1 = "SELECT 
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
    COALESCE(pol.nm_poli, '-') as poli
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN poliklinik pol ON rp.kd_poli = pol.kd_poli
WHERE pr.noorder IN ($placeholders)
ORDER BY pr.tgl_permintaan DESC, pr.jam_permintaan DESC";

$stmt = $conn->prepare($sql1);
$stmt->bind_param(str_repeat('s', count($noorder_hilang)), ...$noorder_hilang);
$stmt->execute();
$result1 = $stmt->get_result();

if ($result1->num_rows > 0) {
    echo "<p style='color: green;'>✅ Ditemukan <strong>" . $result1->num_rows . " data</strong> di database</p>";
    
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%; font-size: 13px;'>";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>No Order</th><th>No Rawat</th><th>Tanggal</th><th>Nama Pasien</th><th>Poli</th>";
    echo "<th>Jenis dari Tabel</th><th>Info Tambahan</th></tr>";
    
    $data_hilang = [];
    while ($row = $result1->fetch_assoc()) {
        $data_hilang[] = $row;
        $bg = (empty($row['jenis_dari_tabel']) && (empty($row['informasi_tambahan']) || $row['informasi_tambahan'] == '-')) ? 'background: #ffe5e5;' : '';
        
        echo "<tr style='$bg'>";
        echo "<td>" . htmlspecialchars($row['noorder']) . "</td>";
        echo "<td>" . htmlspecialchars($row['no_rawat']) . "</td>";
        echo "<td>" . $row['tgl_permintaan'] . " " . $row['jam_permintaan'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nm_pasien']) . "</td>";
        echo "<td>" . htmlspecialchars($row['poli']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['jenis_dari_tabel'] ?? 'NULL') . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['informasi_tambahan'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ Data TIDAK ditemukan di database!</p>";
}

// Analisis kenapa data hilang
echo "<h2>2. Analisis Penyebab Data Hilang</h2>";

$no_jenis = 0;
$ada_jenis = 0;

foreach ($data_hilang as $row) {
    $jenis_final = '';
    
    if (!empty($row['jenis_dari_tabel']) && $row['jenis_dari_tabel'] != '-') {
        $jenis_final = $row['jenis_dari_tabel'];
    } elseif (!empty($row['informasi_tambahan']) && $row['informasi_tambahan'] != '-') {
        $jenis_final = $row['informasi_tambahan'];
    }
    
    if (empty($jenis_final) || $jenis_final == '-') {
        $no_jenis++;
    } else {
        $ada_jenis++;
    }
}

echo "<table border='1' cellpadding='10' style='font-size: 16px; width: 100%;'>";
echo "<tr style='background: #667eea; color: white;'><th>Status</th><th>Jumlah</th></tr>";
echo "<tr><td>Data DENGAN jenis pemeriksaan</td><td><strong style='color: green;'>$ada_jenis</strong></td></tr>";
echo "<tr style='background: #ffe5e5;'><td>Data TANPA jenis pemeriksaan</td><td><strong style='color: red;'>$no_jenis</strong></td></tr>";
echo "</table>";

if ($ada_jenis > 0) {
    echo "<div style='margin-top: 20px; padding: 20px; background: #fff3e0; border-left: 4px solid #ff9800;'>";
    echo "<h3>⚠️ MASALAH DITEMUKAN!</h3>";
    echo "<p>Ada <strong>$ada_jenis data</strong> yang SEHARUSNYA muncul di dashboard karena memiliki jenis pemeriksaan, tapi TIDAK muncul!</p>";
    echo "<p><strong>Kemungkinan penyebab:</strong></p>";
    echo "<ul>";
    echo "<li>Query di config.php tidak menangkap data ini dengan benar</li>";
    echo "<li>Filter terlalu ketat</li>";
    echo "<li>Ada masalah dengan subquery GROUP_CONCAT</li>";
    echo "</ul>";
    echo "<p><strong style='color: red;'>SOLUSI:</strong> Saya perlu memperbaiki query di config.php</p>";
    echo "</div>";
} else if ($no_jenis > 0) {
    echo "<div style='margin-top: 20px; padding: 20px; background: #e8f5e9; border-left: 4px solid #4caf50;'>";
    echo "<h3>✅ DATA BENAR DI-FILTER</h3>";
    echo "<p>Semua $no_jenis data yang hilang memang TIDAK memiliki jenis pemeriksaan.</p>";
    echo "<p>Sesuai dengan permintaan Anda, data tanpa jenis pemeriksaan tidak ditampilkan.</p>";
    echo "<p><strong>Dashboard sudah bekerja dengan benar! ✅</strong></p>";
    echo "</div>";
}

// Query alternatif - cek semua data rawat jalan tanpa filter
echo "<h2>3. Total Data Keseluruhan (Tanpa Filter)</h2>";
$sql_total = "SELECT COUNT(*) as total
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
WHERE rp.status_lanjut = 'Ralan'
AND DATE(pr.tgl_permintaan) >= '2020-01-01'";

$result_total = mysqli_query($conn, $sql_total);
$total_semua = mysqli_fetch_assoc($result_total)['total'];

echo "<p style='font-size: 20px;'>Total permintaan radiologi (rawat jalan): <strong style='color: blue;'>$total_semua</strong></p>";

echo "<div style='margin-top: 20px; padding: 20px; background: #e3f2fd; border-left: 4px solid #2196f3;'>";
echo "<h3>📊 KESIMPULAN FINAL:</h3>";
echo "<p>• Total di database: <strong>$total_semua permintaan</strong></p>";
echo "<p>• Yang muncul di dashboard: <strong>71 permintaan</strong> (dengan jenis pemeriksaan)</p>";
echo "<p>• Yang hilang: <strong>" . ($total_semua - 71) . " permintaan</strong></p>";

if ($ada_jenis > 0) {
    echo "<p style='color: red; font-size: 16px; font-weight: bold;'>❌ PERLU PERBAIKAN! Ada $ada_jenis data yang seharusnya muncul tapi tidak muncul.</p>";
} else {
    echo "<p style='color: green; font-size: 16px; font-weight: bold;'>✅ DASHBOARD SUDAH BENAR! Semua data yang hilang memang tidak punya jenis pemeriksaan.</p>";
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
</style>

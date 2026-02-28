<?php
// Cek Total Data Sesuai Sistem Asli
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'sik';

$conn = mysqli_connect($host, $user, $pass, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

echo "<h1>📊 Perbandingan Data dengan Sistem Asli</h1>";
echo "<hr>";

// Dari gambar sistem asli, filter yang digunakan:
// - Tanggal: 01-01-2020 s/d 09-02-2026
// - Tab: Rawat Jalan

echo "<h2>1. Query Sesuai Sistem Asli (Tab Rawat Jalan)</h2>";

// Query persis seperti di sistem asli (dari tabel permintaan_radiologi)
$sql1 = "SELECT COUNT(*) as total
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
WHERE DATE(pr.tgl_permintaan) >= '2020-01-01'
AND DATE(pr.tgl_permintaan) <= '2026-02-09'
AND rp.status_lanjut = 'Ralan'";

$result1 = mysqli_query($conn, $sql1);
$total_sistem_asli = mysqli_fetch_assoc($result1)['total'];

echo "<p style='font-size: 20px;'>Total di sistem asli (semua permintaan): <strong style='color: blue;'>$total_sistem_asli</strong></p>";

// Query dengan filter jenis pemeriksaan
$sql2 = "SELECT 
    pr.noorder,
    pr.no_rawat,
    pr.tgl_permintaan,
    p.nm_pasien,
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
    ) as jenis_pemeriksaan
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
WHERE DATE(pr.tgl_permintaan) >= '2020-01-01'
AND DATE(pr.tgl_permintaan) <= '2026-02-09'
AND rp.status_lanjut = 'Ralan'
ORDER BY pr.tgl_permintaan DESC, pr.jam_permintaan DESC";

$result2 = mysqli_query($conn, $sql2);

$data_dengan_jenis = [];
$data_tanpa_jenis = [];

while ($row = mysqli_fetch_assoc($result2)) {
    if (!empty($row['jenis_pemeriksaan']) && $row['jenis_pemeriksaan'] != '-') {
        $data_dengan_jenis[] = $row;
    } else {
        $data_tanpa_jenis[] = $row;
    }
}

$total_dengan_jenis = count($data_dengan_jenis);
$total_tanpa_jenis = count($data_tanpa_jenis);

echo "<h2>2. Breakdown Data</h2>";
echo "<p style='font-size: 18px;'>Data DENGAN jenis pemeriksaan: <strong style='color: green;'>$total_dengan_jenis</strong></p>";
echo "<p style='font-size: 18px;'>Data TANPA jenis pemeriksaan: <strong style='color: red;'>$total_tanpa_jenis</strong></p>";

if ($total_tanpa_jenis > 0) {
    echo "<h3>📋 Daftar Data TANPA Jenis Pemeriksaan:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #ff6b6b; color: white;'>";
    echo "<th>No</th><th>No Order</th><th>No Rawat</th><th>Tanggal</th><th>Nama Pasien</th><th>Jenis Pemeriksaan</th></tr>";
    
    $no = 1;
    foreach ($data_tanpa_jenis as $row) {
        echo "<tr>";
        echo "<td>$no</td>";
        echo "<td>" . htmlspecialchars($row['noorder']) . "</td>";
        echo "<td>" . htmlspecialchars($row['no_rawat']) . "</td>";
        echo "<td>" . $row['tgl_permintaan'] . "</td>";
        echo "<td>" . htmlspecialchars($row['nm_pasien']) . "</td>";
        echo "<td style='color: red;'>" . htmlspecialchars($row['jenis_pemeriksaan']) . "</td>";
        echo "</tr>";
        $no++;
    }
    echo "</table>";
}

// Cek apakah ada data rawat inap yang mungkin dihitung di sistem asli
echo "<h2>3. Cek Data Rawat Inap</h2>";
$sql3 = "SELECT COUNT(*) as total
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
WHERE DATE(pr.tgl_permintaan) >= '2020-01-01'
AND DATE(pr.tgl_permintaan) <= '2026-02-09'
AND rp.status_lanjut = 'Ranap'";

$result3 = mysqli_query($conn, $sql3);
$total_ranap = mysqli_fetch_assoc($result3)['total'];
echo "<p style='font-size: 18px;'>Total rawat INAP: <strong>$total_ranap</strong></p>";

echo "<hr>";
echo "<h2>📊 RINGKASAN</h2>";
echo "<table border='1' cellpadding='10' style='font-size: 16px; width: 100%;'>";
echo "<tr style='background: #667eea; color: white;'><th>Keterangan</th><th>Jumlah</th></tr>";
echo "<tr><td>Total di Sistem Asli (SEMUA permintaan rawat jalan)</td><td><strong>$total_sistem_asli</strong></td></tr>";
echo "<tr><td>Rawat Jalan DENGAN jenis pemeriksaan</td><td><strong style='color: green;'>$total_dengan_jenis</strong></td></tr>";
echo "<tr><td>Rawat Jalan TANPA jenis pemeriksaan</td><td><strong style='color: red;'>$total_tanpa_jenis</strong></td></tr>";
echo "<tr><td>Total Rawat INAP</td><td><strong>$total_ranap</strong></td></tr>";
echo "<tr style='background: #fff3e0;'><td><strong>TOTAL KESELURUHAN (Rajal + Ranap)</strong></td><td><strong style='font-size: 20px;'>" . ($total_sistem_asli + $total_ranap) . "</strong></td></tr>";
echo "<tr style='background: #e8f5e9;'><td><strong>Yang Tampil di Dashboard (dengan filter jenis pemeriksaan)</strong></td><td><strong style='color: green; font-size: 20px;'>$total_dengan_jenis</strong></td></tr>";
echo "</table>";

echo "<div style='margin-top: 20px; padding: 20px; background: #e8f5e9; border-left: 4px solid #4caf50;'>";
echo "<h3>✅ KESIMPULAN:</h3>";

if ($total_sistem_asli == 77 && $total_dengan_jenis == 71) {
    echo "<p style='font-size: 16px;'><strong>DITEMUKAN!</strong></p>";
    echo "<p>Sistem asli menampilkan <strong>77 permintaan</strong> (termasuk yang tanpa jenis pemeriksaan).</p>";
    echo "<p>Dashboard saat ini menampilkan <strong>71 permintaan</strong> (hanya yang ada jenis pemeriksaan).</p>";
    echo "<p><strong>Selisih: $total_tanpa_jenis permintaan</strong> tidak ditampilkan karena tidak ada jenis pemeriksaan.</p>";
    echo "<p><strong style='color: red;'>SOLUSI:</strong> Jika Anda ingin dashboard menampilkan SEMUA 77 data (termasuk yang tanpa jenis pemeriksaan), saya perlu mengubah konfigurasi filter.</p>";
} else if ($total_sistem_asli == $total_dengan_jenis) {
    echo "<p style='font-size: 16px; color: green;'><strong>✅ DATA SUDAH BENAR!</strong></p>";
    echo "<p>Sistem asli dan dashboard menampilkan jumlah data yang sama: <strong>$total_sistem_asli</strong></p>";
} else if (($total_sistem_asli + $total_ranap) == 77) {
    echo "<p style='font-size: 16px;'><strong>DITEMUKAN!</strong></p>";
    echo "<p>Record 77 di sistem asli kemungkinan adalah <strong>gabungan</strong> rawat jalan ($total_sistem_asli) + rawat inap ($total_ranap).</p>";
} else {
    echo "<p style='font-size: 16px; color: orange;'><strong>PERLU PENGECEKAN LEBIH LANJUT</strong></p>";
    echo "<p>Total di sistem asli: $total_sistem_asli</p>";
    echo "<p>Total dengan jenis pemeriksaan: $total_dengan_jenis</p>";
    echo "<p>Selisih: " . ($total_sistem_asli - $total_dengan_jenis) . "</p>";
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

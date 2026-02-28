<?php
// Test untuk mengecek data radiologi
require_once 'config.php';

echo "<h1>Test Data Radiologi - Debug</h1>";
echo "<hr>";

// Test 1: Total data di permintaan_radiologi
echo "<h2>1. Total Data di Tabel permintaan_radiologi</h2>";
$sql1 = "SELECT COUNT(*) as total FROM permintaan_radiologi WHERE DATE(tgl_permintaan) >= '2020-01-01'";
$result1 = mysqli_query($conn, $sql1);
$total1 = mysqli_fetch_assoc($result1)['total'];
echo "<p><strong>Total data di permintaan_radiologi:</strong> $total1 baris</p>";

// Test 2: Data dengan JOIN tapi tanpa filter jenis pemeriksaan
echo "<h2>2. Data Rawat Jalan TANPA Filter Jenis Pemeriksaan</h2>";
$sql2 = "SELECT 
    rp.no_rawat,
    p.nm_pasien as nama_pasien,
    rp.no_rkm_medis as no_rm,
    COALESCE(pr.nm_perawatan, '-') as jenis_pemeriksaan,
    COALESCE(pol.nm_poli, '-') as kamar,
    COALESCE(pj.png_jawab, 'Umum') as cara_bayar
FROM reg_periksa rp
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN poliklinik pol ON rp.kd_poli = pol.kd_poli
LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
LEFT JOIN periksa_radiologi pradiologi ON rp.no_rawat = pradiologi.no_rawat
LEFT JOIN jns_perawatan_radiologi pr ON pradiologi.kd_jenis_prw = pr.kd_jenis_prw
WHERE rp.status_lanjut = 'Ralan'
AND DATE(rp.tgl_registrasi) >= '2020-01-01'
GROUP BY rp.no_rawat
ORDER BY rp.tgl_registrasi DESC";

$result2 = mysqli_query($conn, $sql2);
$total2 = mysqli_num_rows($result2);
echo "<p><strong>Total data rawat jalan (TANPA filter jenis pemeriksaan):</strong> $total2 baris</p>";

// Tampilkan 10 data pertama
if ($total2 > 0) {
    echo "<h3>Sample 10 Data Pertama:</h3>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
    echo "<tr style='background: #667eea; color: white;'>";
    echo "<th>No Rawat</th><th>Nama Pasien</th><th>No RM</th><th>Jenis Pemeriksaan</th><th>Poli</th><th>Cara Bayar</th></tr>";
    
    $count = 0;
    mysqli_data_seek($result2, 0);
    while ($row = mysqli_fetch_assoc($result2)) {
        if ($count >= 10) break;
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['no_rawat']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nama_pasien']) . "</td>";
        echo "<td>" . htmlspecialchars($row['no_rm']) . "</td>";
        echo "<td>" . htmlspecialchars($row['jenis_pemeriksaan']) . "</td>";
        echo "<td>" . htmlspecialchars($row['kamar']) . "</td>";
        echo "<td>" . htmlspecialchars($row['cara_bayar']) . "</td>";
        echo "</tr>";
        $count++;
    }
    echo "</table>";
}

// Test 3: Data DENGAN filter jenis pemeriksaan
echo "<h2>3. Data Rawat Jalan DENGAN Filter Jenis Pemeriksaan</h2>";
$sql3 = "SELECT 
    rp.no_rawat,
    p.nm_pasien as nama_pasien,
    rp.no_rkm_medis as no_rm,
    COALESCE(pr.nm_perawatan, '-') as jenis_pemeriksaan,
    COALESCE(pol.nm_poli, '-') as kamar,
    COALESCE(pj.png_jawab, 'Umum') as cara_bayar
FROM reg_periksa rp
LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
LEFT JOIN poliklinik pol ON rp.kd_poli = pol.kd_poli
LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
LEFT JOIN periksa_radiologi pradiologi ON rp.no_rawat = pradiologi.no_rawat
LEFT JOIN jns_perawatan_radiologi pr ON pradiologi.kd_jenis_prw = pr.kd_jenis_prw
WHERE rp.status_lanjut = 'Ralan'
AND DATE(rp.tgl_registrasi) >= '2020-01-01'
AND pr.nm_perawatan IS NOT NULL
AND pr.nm_perawatan != ''
AND pr.nm_perawatan != '-'
GROUP BY rp.no_rawat
ORDER BY rp.tgl_registrasi DESC";

$result3 = mysqli_query($conn, $sql3);
$total3 = mysqli_num_rows($result3);
echo "<p><strong>Total data rawat jalan (DENGAN filter jenis pemeriksaan):</strong> $total3 baris</p>";

// Test 4: Breakdown berdasarkan jenis pemeriksaan
echo "<h2>4. Breakdown Berdasarkan Jenis Pemeriksaan</h2>";
$sql4 = "SELECT 
    COALESCE(pr.nm_perawatan, '-') as jenis_pemeriksaan,
    COUNT(*) as jumlah
FROM reg_periksa rp
LEFT JOIN periksa_radiologi pradiologi ON rp.no_rawat = pradiologi.no_rawat
LEFT JOIN jns_perawatan_radiologi pr ON pradiologi.kd_jenis_prw = pr.kd_jenis_prw
WHERE rp.status_lanjut = 'Ralan'
AND DATE(rp.tgl_registrasi) >= '2020-01-01'
GROUP BY COALESCE(pr.nm_perawatan, '-')
ORDER BY jumlah DESC";

$result4 = mysqli_query($conn, $sql4);
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr style='background: #667eea; color: white;'>";
echo "<th>Jenis Pemeriksaan</th><th>Jumlah</th></tr>";

while ($row = mysqli_fetch_assoc($result4)) {
    $bg = ($row['jenis_pemeriksaan'] == '-') ? 'background: #ffe5e5;' : '';
    echo "<tr style='$bg'>";
    echo "<td>" . htmlspecialchars($row['jenis_pemeriksaan']) . "</td>";
    echo "<td><strong>" . $row['jumlah'] . "</strong></td>";
    echo "</tr>";
}
echo "</table>";

// Test 5: Cek tabel periksa_radiologi
echo "<h2>5. Cek Tabel periksa_radiologi</h2>";
$sql5 = "SELECT COUNT(*) as total FROM periksa_radiologi";
$result5 = mysqli_query($conn, $sql5);
if ($result5) {
    $total5 = mysqli_fetch_assoc($result5)['total'];
    echo "<p><strong>Total data di tabel periksa_radiologi:</strong> $total5 baris</p>";
} else {
    echo "<p style='color: red;'><strong>ERROR:</strong> Tabel periksa_radiologi tidak ada atau tidak bisa diakses</p>";
}

// Test 6: Alternatif - Cek dari permintaan_radiologi
echo "<h2>6. Alternatif Query dari permintaan_radiologi</h2>";
$sql6 = "SELECT COUNT(*) as total 
FROM permintaan_radiologi pr
LEFT JOIN reg_periksa rp ON pr.no_rawat = rp.no_rawat
WHERE rp.status_lanjut = 'Ralan'
AND DATE(pr.tgl_permintaan) >= '2020-01-01'";

$result6 = mysqli_query($conn, $sql6);
if ($result6) {
    $total6 = mysqli_fetch_assoc($result6)['total'];
    echo "<p><strong>Total dari permintaan_radiologi (rawat jalan):</strong> $total6 baris</p>";
}

echo "<hr>";
echo "<h2>📊 Kesimpulan</h2>";
echo "<div style='background: #fff3e0; padding: 15px; border-left: 4px solid #ff9800; margin: 15px 0;'>";
echo "<p><strong>Kemungkinan penyebab data berkurang:</strong></p>";
echo "<ul>";
echo "<li>❌ Data pasien belum ter-link ke tabel <code>periksa_radiologi</code></li>";
echo "<li>❌ Data jenis pemeriksaan masih kosong/NULL di tabel <code>jns_perawatan_radiologi</code></li>";
echo "<li>❌ Filter yang terlalu ketat menghilangkan data yang seharusnya muncul</li>";
echo "</ul>";
echo "<p><strong>Solusi:</strong> Gunakan query dari tabel <code>permintaan_radiologi</code> sebagai sumber data utama</p>";
echo "</div>";

mysqli_close($conn);
?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f7fa; }
    h1 { color: #667eea; }
    h2 { color: #333; margin-top: 30px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    table { background: white; margin: 15px 0; width: 100%; }
    th { font-weight: bold; padding: 10px; }
    td { padding: 8px; }
    tr:nth-child(even) { background: #f9f9f9; }
    code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
</style>

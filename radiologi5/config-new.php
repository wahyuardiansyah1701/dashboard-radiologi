<?php
// Database Configuration
$host = 'localhost';
$user = 'root'; // Sesuaikan dengan username database Anda
$pass = ''; // Sesuaikan dengan password database Anda
$dbname = 'sik'; // Nama database

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8");

// Timezone
date_default_timezone_set('Asia/Jakarta');

/**
 * Fungsi untuk mendapatkan data pemeriksaan dari database
 * DIPERBAIKI: Menggunakan tabel permintaan_radiologi dan permintaan_pemeriksaan_radiologi
 */
function getPemeriksaanFromDB($filters) {
    global $conn;
    
    $jenis_rawat = $filters['jenis_rawat'] ?? 'rajal';
    $tgl_dari = $filters['tgl_dari'] ?? date('Y-m-d');
    $tgl_sampai = $filters['tgl_sampai'] ?? date('Y-m-d');
    $kamar = $filters['kamar'] ?? '';
    $cara_bayar = $filters['cara_bayar'] ?? '';
    $jenis_pemeriksaan = $filters['jenis_pemeriksaan'] ?? '';
    $search = $filters['search'] ?? '';
    
    // Query berbeda untuk rawat jalan dan rawat inap
    if ($jenis_rawat === 'rajal') {
        // Query untuk Rawat Jalan - menggunakan permintaan_radiologi
        $query = "
            SELECT 
                permintaan.no_rawat,
                permintaan.noorder,
                rp.no_rkm_medis as no_rm,
                p.nm_pasien as nama_pasien,
                p.tgl_lahir,
                COALESCE(jpr.nm_perawatan, permintaan.informasi_tambahan, '-') as jenis_pemeriksaan,
                COALESCE(pol.nm_poli, '-') as kamar,
                CONCAT(permintaan.tgl_permintaan, ' ', permintaan.jam_permintaan) as tgl_periksa,
                COALESCE(d.nm_dokter, '-') as dokter_perujuk,
                COALESCE(pj.png_jawab, 'Umum') as cara_bayar
            FROM permintaan_radiologi permintaan
            LEFT JOIN reg_periksa rp ON permintaan.no_rawat = rp.no_rawat
            LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            LEFT JOIN poliklinik pol ON rp.kd_poli = pol.kd_poli
            LEFT JOIN dokter d ON permintaan.dokter_perujuk = d.kd_dokter
            LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON permintaan.noorder = ppr.noorder
            LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
            WHERE rp.status_lanjut = 'Ralan'
        ";
    } else {
        // Query untuk Rawat Inap - menggunakan permintaan_radiologi
        $query = "
            SELECT 
                permintaan.no_rawat,
                permintaan.noorder,
                rp.no_rkm_medis as no_rm,
                p.nm_pasien as nama_pasien,
                p.tgl_lahir,
                COALESCE(jpr.nm_perawatan, permintaan.informasi_tambahan, '-') as jenis_pemeriksaan,
                COALESCE(CONCAT(b.nm_bangsal, ' - ', k.kd_kamar), '-') as kamar,
                CONCAT(permintaan.tgl_permintaan, ' ', permintaan.jam_permintaan) as tgl_periksa,
                COALESCE(d.nm_dokter, '-') as dokter_perujuk,
                COALESCE(pj.png_jawab, 'Umum') as cara_bayar
            FROM permintaan_radiologi permintaan
            LEFT JOIN reg_periksa rp ON permintaan.no_rawat = rp.no_rawat
            LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat AND ki.stts_pulang = '-'
            LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            LEFT JOIN dokter d ON permintaan.dokter_perujuk = d.kd_dokter
            LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            LEFT JOIN permintaan_pemeriksaan_radiologi ppr ON permintaan.noorder = ppr.noorder
            LEFT JOIN jns_perawatan_radiologi jpr ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
            WHERE rp.status_lanjut = 'Ranap'
        ";
    }
    
    // Filter tanggal
    if (!empty($tgl_dari) && !empty($tgl_sampai)) {
        $query .= " AND DATE(permintaan.tgl_permintaan) BETWEEN '" . mysqli_real_escape_string($conn, $tgl_dari) . "' 
                    AND '" . mysqli_real_escape_string($conn, $tgl_sampai) . "'";
    }
    
    // Filter poliklinik/kamar
    if (!empty($kamar)) {
        if ($jenis_rawat === 'rajal') {
            $query .= " AND pol.nm_poli = '" . mysqli_real_escape_string($conn, $kamar) . "'";
        } else {
            $query .= " AND (b.nm_bangsal LIKE '%" . mysqli_real_escape_string($conn, $kamar) . "%' 
                        OR k.kd_kamar LIKE '%" . mysqli_real_escape_string($conn, $kamar) . "%')";
        }
    }
    
    // Filter cara bayar dengan mapping
    if (!empty($cara_bayar)) {
        switch ($cara_bayar) {
            case 'BPJS':
                $query .= " AND (LOWER(pj.png_jawab) LIKE '%bpjs%' OR LOWER(pj.png_jawab) LIKE '%jkn%')";
                break;
            case 'ASURANSI':
                $query .= " AND LOWER(pj.png_jawab) LIKE '%asuransi%'";
                break;
            case 'JAMKESDA':
                $query .= " AND (LOWER(pj.png_jawab) LIKE '%jamkesda%' OR LOWER(pj.png_jawab) LIKE '%jamkesmas%')";
                break;
            case 'UMUM':
                $query .= " AND LOWER(pj.png_jawab) NOT LIKE '%bpjs%' 
                            AND LOWER(pj.png_jawab) NOT LIKE '%jkn%'
                            AND LOWER(pj.png_jawab) NOT LIKE '%asuransi%' 
                            AND LOWER(pj.png_jawab) NOT LIKE '%jamkesda%'
                            AND LOWER(pj.png_jawab) NOT LIKE '%jamkesmas%'";
                break;
        }
    }
    
    // Filter jenis pemeriksaan
    if (!empty($jenis_pemeriksaan)) {
        $query .= " AND (jpr.nm_perawatan LIKE '%" . mysqli_real_escape_string($conn, $jenis_pemeriksaan) . "%'
                    OR permintaan.informasi_tambahan LIKE '%" . mysqli_real_escape_string($conn, $jenis_pemeriksaan) . "%')";
    }
    
    // FILTER: Hanya tampilkan yang ada jenis pemeriksaan
    $query .= " AND (
        (jpr.nm_perawatan IS NOT NULL AND jpr.nm_perawatan != '' AND jpr.nm_perawatan != '-')
        OR 
        (permintaan.informasi_tambahan IS NOT NULL AND permintaan.informasi_tambahan != '' AND permintaan.informasi_tambahan != '-')
    )";
    
    // Filter pencarian
    if (!empty($search)) {
        $search_escaped = mysqli_real_escape_string($conn, $search);
        $query .= " AND (
            p.nm_pasien LIKE '%{$search_escaped}%' OR
            rp.no_rkm_medis LIKE '%{$search_escaped}%' OR
            permintaan.no_rawat LIKE '%{$search_escaped}%' OR
            permintaan.noorder LIKE '%{$search_escaped}%' OR
            d.nm_dokter LIKE '%{$search_escaped}%'
        )";
    }
    
    $query .= " GROUP BY permintaan.noorder ORDER BY permintaan.tgl_permintaan DESC, permintaan.jam_permintaan DESC";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception("Query Error: " . mysqli_error($conn));
    }
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    
    return $data;
}

/**
 * Fungsi untuk mendapatkan list kamar/poliklinik
 */
function getKamarList($jenis_rawat) {
    global $conn;
    
    $list = [];
    
    if ($jenis_rawat === 'rajal') {
        // Ambil list poliklinik dari tabel poliklinik
        $query = "SELECT nm_poli FROM poliklinik WHERE status = '1' ORDER BY nm_poli";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $list[] = $row['nm_poli'];
            }
        }
    } else {
        // Ambil list bangsal untuk rawat inap
        $query = "SELECT nm_bangsal FROM bangsal WHERE status = '1' ORDER BY nm_bangsal";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $list[] = $row['nm_bangsal'];
            }
        }
    }
    
    return $list;
}

/**
 * Fungsi untuk mendapatkan list cara bayar
 */
function getCaraBayarList() {
    global $conn;
    
    $list = [];
    $query = "SELECT png_jawab FROM penjab WHERE status = '1' ORDER BY png_jawab";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row['png_jawab'];
        }
    }
    
    return $list;
}

/**
 * Fungsi untuk mendapatkan list jenis pemeriksaan radiologi
 */
function getJenisPemeriksaanList() {
    global $conn;
    
    $list = [];
    $query = "SELECT nm_perawatan FROM jns_perawatan_radiologi WHERE status = '1' ORDER BY nm_perawatan";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $list[] = $row['nm_perawatan'];
        }
    }
    
    return $list;
}
?>

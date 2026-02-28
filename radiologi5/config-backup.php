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
 * dengan filter yang sudah diperbaiki
 * MODIFIKASI: Hanya tampilkan pasien yang memiliki jenis pemeriksaan
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
        // Query untuk Rawat Jalan
        $query = "
            SELECT 
                rp.no_rawat,
                rp.no_rkm_medis as no_rm,
                p.nm_pasien as nama_pasien,
                p.tgl_lahir,
                COALESCE(pr.nm_perawatan, '-') as jenis_pemeriksaan,
                COALESCE(pol.nm_poli, '-') as kamar,
                rp.tgl_registrasi as tgl_periksa,
                COALESCE(d.nm_dokter, '-') as dokter_perujuk,
                COALESCE(pj.png_jawab, 'Umum') as cara_bayar
            FROM reg_periksa rp
            LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            LEFT JOIN poliklinik pol ON rp.kd_poli = pol.kd_poli
            LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            LEFT JOIN periksa_radiologi pradiologi ON rp.no_rawat = pradiologi.no_rawat
            LEFT JOIN jns_perawatan_radiologi pr ON pradiologi.kd_jenis_prw = pr.kd_jenis_prw
            WHERE rp.status_lanjut = 'Ralan'
            AND pr.nm_perawatan IS NOT NULL
            AND pr.nm_perawatan != ''
            AND pr.nm_perawatan != '-'
        ";
    } else {
        // Query untuk Rawat Inap
        $query = "
            SELECT 
                rp.no_rawat,
                rp.no_rkm_medis as no_rm,
                p.nm_pasien as nama_pasien,
                p.tgl_lahir,
                COALESCE(pr.nm_perawatan, '-') as jenis_pemeriksaan,
                COALESCE(CONCAT(b.nm_bangsal, ' - ', k.kd_kamar), '-') as kamar,
                rp.tgl_registrasi as tgl_periksa,
                COALESCE(d.nm_dokter, '-') as dokter_perujuk,
                COALESCE(pj.png_jawab, 'Umum') as cara_bayar
            FROM reg_periksa rp
            LEFT JOIN pasien p ON rp.no_rkm_medis = p.no_rkm_medis
            LEFT JOIN kamar_inap ki ON rp.no_rawat = ki.no_rawat
            LEFT JOIN kamar k ON ki.kd_kamar = k.kd_kamar
            LEFT JOIN bangsal b ON k.kd_bangsal = b.kd_bangsal
            LEFT JOIN dokter d ON rp.kd_dokter = d.kd_dokter
            LEFT JOIN penjab pj ON rp.kd_pj = pj.kd_pj
            LEFT JOIN periksa_radiologi pradiologi ON rp.no_rawat = pradiologi.no_rawat
            LEFT JOIN jns_perawatan_radiologi pr ON pradiologi.kd_jenis_prw = pr.kd_jenis_prw
            WHERE rp.status_lanjut = 'Ranap'
            AND pr.nm_perawatan IS NOT NULL
            AND pr.nm_perawatan != ''
            AND pr.nm_perawatan != '-'
        ";
    }
    
    // Filter tanggal
    if (!empty($tgl_dari) && !empty($tgl_sampai)) {
        $query .= " AND DATE(rp.tgl_registrasi) BETWEEN '" . mysqli_real_escape_string($conn, $tgl_dari) . "' 
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
        $query .= " AND pr.nm_perawatan LIKE '%" . mysqli_real_escape_string($conn, $jenis_pemeriksaan) . "%'";
    }
    
    // Filter pencarian
    if (!empty($search)) {
        $search_escaped = mysqli_real_escape_string($conn, $search);
        $query .= " AND (
            p.nm_pasien LIKE '%{$search_escaped}%' OR
            rp.no_rkm_medis LIKE '%{$search_escaped}%' OR
            rp.no_rawat LIKE '%{$search_escaped}%' OR
            d.nm_dokter LIKE '%{$search_escaped}%'
        )";
    }
    
    $query .= " GROUP BY rp.no_rawat ORDER BY rp.tgl_registrasi DESC";
    
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

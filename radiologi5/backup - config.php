<?php
// ============================================
// KONFIGURASI DATABASE SIK - FINAL
// ============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sik');

// Koneksi Database
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Koneksi gagal: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8");
        return $conn;
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

// ============================================
// FUNGSI: Ambil Data Permintaan Pemeriksaan Radiologi
// ✅ UPDATED: Sudah mendukung pemisahan RAJAL dan RANAP + Integrasi Tabel Kamar
// ============================================
function getPemeriksaanFromDB($filters = []) {
    $conn = getConnection();
    
    // Tentukan jenis rawat (default: semua)
    $jenis_rawat = $filters['jenis_rawat'] ?? 'all'; // 'rajal', 'ranap', atau 'all'
    
    $sql = "SELECT 
                permintaan_radiologi.noorder as no_order,
                permintaan_radiologi.no_rawat as no_rawat,
                permintaan_radiologi.tgl_permintaan,
                permintaan_radiologi.jam_permintaan,
                permintaan_radiologi.status,
                
                -- Data pasien
                COALESCE(pasien.nm_pasien, '-') as nama_pasien,
                COALESCE(pasien.no_rkm_medis, '-') as no_rm,
                COALESCE(pasien.tgl_lahir, '1900-01-01') as tgl_lahir,
                
                -- Jenis pemeriksaan dari tabel jns_perawatan_radiologi
                COALESCE(jns_perawatan_radiologi.nm_perawatan, 
                         permintaan_radiologi.informasi_tambahan, 
                         'Pemeriksaan Radiologi') as jenis_pemeriksaan,
                
                -- ✅ KAMAR: Untuk RANAP dari tabel kamar, untuk RAJAL dari poliklinik
                CASE 
                    WHEN reg_periksa.status_lanjut = 'Ranap' THEN 
                        COALESCE(CONCAT(kamar.kd_kamar, ' - ', bangsal.nm_bangsal), 'Kamar Belum Ditentukan')
                    ELSE 
                        COALESCE(poliklinik.nm_poli, 'Radiologi')
                END as kamar,
                
                -- Status rawat (RAJAL/RANAP)
                COALESCE(reg_periksa.status_lanjut, 'Ralan') as status_rawat,
                
                -- Gabungan tanggal dan jam
                CONCAT(permintaan_radiologi.tgl_permintaan, ' ', permintaan_radiologi.jam_permintaan) as tgl_periksa,
                
                -- Dokter perujuk
                COALESCE(dokter.nm_dokter, '-') as dokter_perujuk,
                
                -- Cara bayar
                COALESCE(penjab.png_jawab, 'Umum') as cara_bayar
            
            FROM permintaan_radiologi
            
            -- JOIN ke reg_periksa
            LEFT JOIN reg_periksa 
                ON permintaan_radiologi.no_rawat = reg_periksa.no_rawat
            
            -- JOIN ke pasien
            LEFT JOIN pasien 
                ON reg_periksa.no_rkm_medis = pasien.no_rkm_medis
            
            -- JOIN ke detail_permintaan_radiologi untuk mendapatkan kode jenis pemeriksaan
            LEFT JOIN permintaan_pemeriksaan_radiologi 
                ON permintaan_radiologi.noorder = permintaan_pemeriksaan_radiologi.noorder
            
            -- JOIN ke jns_perawatan_radiologi untuk nama pemeriksaan
            LEFT JOIN jns_perawatan_radiologi 
                ON permintaan_pemeriksaan_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw
            
            -- ✅ JOIN ke kamar_inap untuk mendapatkan kd_kamar (RANAP)
            LEFT JOIN kamar_inap 
                ON reg_periksa.no_rawat = kamar_inap.no_rawat 
                AND kamar_inap.stts_pulang = '-'
            
            -- ✅ JOIN ke kamar untuk mendapatkan detail kamar (RANAP)
            LEFT JOIN kamar 
                ON kamar_inap.kd_kamar = kamar.kd_kamar
            
            -- ✅ JOIN ke bangsal untuk mendapatkan nama bangsal (RANAP)
            LEFT JOIN bangsal 
                ON kamar.kd_bangsal = bangsal.kd_bangsal
            
            -- JOIN ke poliklinik (RAJAL)
            LEFT JOIN poliklinik 
                ON reg_periksa.kd_poli = poliklinik.kd_poli
            
            -- JOIN ke dokter
            LEFT JOIN dokter 
                ON permintaan_radiologi.dokter_perujuk = dokter.kd_dokter
            
            -- JOIN ke penjab
            LEFT JOIN penjab 
                ON reg_periksa.kd_pj = penjab.kd_pj
            
            WHERE 1=1";
    
    // ✅ Filter berdasarkan jenis rawat
    if ($jenis_rawat === 'rajal') {
        $sql .= " AND reg_periksa.status_lanjut = 'Ralan'";
    } elseif ($jenis_rawat === 'ranap') {
        $sql .= " AND reg_periksa.status_lanjut = 'Ranap'";
    }
    
    // Filter
    $params = [];
    $types = "";
    
    // Filter Tanggal
    if (!empty($filters['tgl_dari']) && !empty($filters['tgl_sampai'])) {
        $sql .= " AND DATE(permintaan_radiologi.tgl_permintaan) BETWEEN ? AND ?";
        $params[] = $filters['tgl_dari'];
        $params[] = $filters['tgl_sampai'];
        $types .= "ss";
    }
    
    // Filter Kamar (untuk RANAP, filter berdasarkan kd_kamar)
    if (!empty($filters['kamar'])) {
        if ($jenis_rawat === 'ranap') {
            $sql .= " AND kamar.kd_kamar = ?";
        } else {
            $sql .= " AND poliklinik.nm_poli = ?";
        }
        $params[] = $filters['kamar'];
        $types .= "s";
    }
    
    // Filter Cara Bayar
    if (!empty($filters['cara_bayar'])) {
        $sql .= " AND penjab.png_jawab = ?";
        $params[] = $filters['cara_bayar'];
        $types .= "s";
    }
    
    // Filter Jenis Pemeriksaan
    if (!empty($filters['jenis_pemeriksaan'])) {
        $sql .= " AND jns_perawatan_radiologi.nm_perawatan = ?";
        $params[] = $filters['jenis_pemeriksaan'];
        $types .= "s";
    }
    
    // Filter Search (SEARCHBAR)
    if (!empty($filters['search'])) {
        $sql .= " AND (pasien.nm_pasien LIKE ? 
                      OR pasien.no_rkm_medis LIKE ? 
                      OR permintaan_radiologi.no_rawat LIKE ?
                      OR permintaan_radiologi.noorder LIKE ?
                      OR dokter.nm_dokter LIKE ?)";
        $search = "%{$filters['search']}%";
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= "sssss";
    }
    
    $sql .= " GROUP BY permintaan_radiologi.noorder
              ORDER BY permintaan_radiologi.tgl_permintaan DESC, permintaan_radiologi.jam_permintaan DESC 
              LIMIT 100";
    
    // Execute
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error . "<br><br>SQL: <pre>" . $sql . "</pre>");
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $data;
}

// ============================================
// FUNGSI: Ambil List Kamar untuk Dropdown
// ✅ UPDATED: Sudah mendukung pemisahan RAJAL (poliklinik) dan RANAP (kamar)
// ============================================
function getKamarList($jenis_rawat = 'all') {
    $conn = getConnection();
    
    if ($jenis_rawat === 'ranap') {
        // Untuk RANAP, ambil dari tabel kamar
        $sql = "SELECT DISTINCT kamar.kd_kamar, bangsal.nm_bangsal
                FROM kamar
                LEFT JOIN bangsal ON kamar.kd_bangsal = bangsal.kd_bangsal
                WHERE kamar.statusdata = '1'
                ORDER BY kamar.kd_kamar";
        
        $result = $conn->query($sql);
        
        $kamar = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $kamar[$row['kd_kamar']] = $row['kd_kamar'] . ' - ' . ($row['nm_bangsal'] ?? '');
            }
        }
    } else {
        // Untuk RAJAL, ambil dari tabel poliklinik
        $sql = "SELECT DISTINCT nm_poli 
                FROM poliklinik 
                WHERE nm_poli LIKE '%radiologi%' 
                   OR nm_poli LIKE '%rontgen%'
                   OR nm_poli LIKE '%ct%'
                   OR nm_poli LIKE '%mri%'
                ORDER BY nm_poli";
        
        $result = $conn->query($sql);
        
        $kamar = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $kamar[] = $row['nm_poli'];
            }
        }
    }
    
    $conn->close();
    return $kamar;
}

// ============================================
// FUNGSI: Ambil List Cara Bayar
// ============================================
function getCaraBayarList() {
    $conn = getConnection();
    
    $sql = "SELECT DISTINCT png_jawab 
            FROM penjab 
            WHERE png_jawab IS NOT NULL AND png_jawab != ''
            ORDER BY png_jawab";
    
    $result = $conn->query($sql);
    
    $cara_bayar = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $cara_bayar[] = $row['png_jawab'];
        }
    }
    
    $conn->close();
    return $cara_bayar;
}

// ============================================
// FUNGSI: Ambil List Jenis Pemeriksaan dari jns_perawatan_radiologi (SUDAH DIKONFIGURASI ✅)
// ============================================
function getJenisPemeriksaanList() {
    $conn = getConnection();
    
    // Ambil dari tabel jns_perawatan_radiologi
    $sql = "SELECT DISTINCT nm_perawatan 
            FROM jns_perawatan_radiologi 
            WHERE nm_perawatan IS NOT NULL AND nm_perawatan != ''
            ORDER BY nm_perawatan";
    
    $result = $conn->query($sql);
    
    $jenis = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $jenis[] = $row['nm_perawatan'];
        }
    }
    
    $conn->close();
    return $jenis;
}

// Timezone
date_default_timezone_set('Asia/Jakarta');

// ============================================
// STATUS KONFIGURASI:
// ============================================
// ✅ 1. Jenis pemeriksaan dari tabel jns_perawatan_radiologi - SUDAH DIKONFIGURASI
// ✅ 2. Kamar dari tabel kamar (RANAP) dan poliklinik (RAJAL) - SUDAH DIKONFIGURASI
// ✅ 3. Pemisahan RAJAL dan RANAP - SUDAH DIKONFIGURASI
// ✅ 4. Label "Dokter Perujuk" - SUDAH DIKONFIGURASI
// ✅ 5. Searchbar - SUDAH DIKONFIGURASI
// ============================================
?>

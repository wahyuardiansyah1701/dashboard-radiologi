<?php
require_once 'auth.php';
require_once 'config.php';

// ========================================
// KONFIGURASI AUTO RELOAD
// ========================================
// Set ke 0 untuk disable auto reload
// 60 = 1 menit, 300 = 5 menit, 600 = 10 menit, 1800 = 30 menit, 3600 = 1 jam
$AUTO_RELOAD_SECONDS = 300; // Default: 5 menit
// Untuk DISABLE auto reload, ubah menjadi: $AUTO_RELOAD_SECONDS = 0;
// ========================================

// Tentukan tab aktif (default: rajal)
$active_tab = $_GET['tab'] ?? 'rajal';

// Ambil filter dari URL
$filters = [
    'jenis_rawat' => $active_tab, // Tambahkan filter jenis rawat
    'bulan_ini' => true // TAMBAHAN: Filter hanya bulan ini
];

if (isset($_GET['filter'])) {
    $filters = array_merge($filters, [
        'tgl_dari' => $_GET['tgl_dari'] ?? '',
        'tgl_sampai' => $_GET['tgl_sampai'] ?? '',
        'kamar' => $_GET['kamar'] ?? '',
        'cara_bayar' => $_GET['cara_bayar'] ?? '',
        'jenis_pemeriksaan' => $_GET['jenis_pemeriksaan'] ?? '',
        'search' => $_GET['search'] ?? ''
    ]);
} else {
    // Jika tidak ada filter, set default bulan ini
    $filters['tgl_dari'] = date('Y-m-01'); // Tanggal 1 bulan ini
    $filters['tgl_sampai'] = date('Y-m-t'); // Tanggal terakhir bulan ini
}

// Ambil data pemeriksaan dari database
try {
    $data_pemeriksaan = getPemeriksaanFromDB($filters);
    $filtered_data = $data_pemeriksaan;
} catch (Exception $e) {
    $error_message = "Error mengambil data: " . $e->getMessage();
    $filtered_data = [];
}

// Ambil list untuk dropdown filter (disesuaikan dengan jenis rawat)
try {
    $kamar_list = getKamarList($active_tab);
    $cara_bayar_list = getCaraBayarList();
    $jenis_pemeriksaan_list = getJenisPemeriksaanList();
} catch (Exception $e) {
    $kamar_list = [];
    $cara_bayar_list = [];
    $jenis_pemeriksaan_list = [];
}

// ========================================
// HITUNG STATISTIK DENGAN 4 KOLOM TETAP
// ========================================
$total_pemeriksaan = count($filtered_data);
$today = date('Y-m-d');
$today_count = 0;

// Inisialisasi 4 kategori cara bayar tetap
$cara_bayar_stats = [
    'UMUM' => 0,
    'BPJS' => 0,
    'ASURANSI' => 0,
    'JAMKESDA' => 0
];

foreach ($filtered_data as $item) {
    if (date('Y-m-d', strtotime($item['tgl_periksa'])) === $today) {
        $today_count++;
    }
    
    // Mapping cara bayar ke 4 kategori
    $cb = strtolower($item['cara_bayar'] ?? 'umum');
    
    if (strpos($cb, 'bpjs') !== false || strpos($cb, 'jkn') !== false) {
        $cara_bayar_stats['BPJS']++;
    } elseif (strpos($cb, 'asuransi') !== false) {
        $cara_bayar_stats['ASURANSI']++;
    } elseif (strpos($cb, 'jamkesda') !== false || strpos($cb, 'jamkesmas') !== false) {
        $cara_bayar_stats['JAMKESDA']++;
    } else {
        $cara_bayar_stats['UMUM']++;
    }
}

// Nama bulan dalam bahasa Indonesia
$bulan_indonesia = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$current_month = $bulan_indonesia[date('m')];
$current_year = date('Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Radiologi - Sistem Informasi Kesehatan</title>
    
    <!-- AUTO RELOAD CONFIGURATION -->
    <?php if ($AUTO_RELOAD_SECONDS > 0): ?>
    <meta http-equiv="refresh" content="<?php echo $AUTO_RELOAD_SECONDS; ?>">
    <?php endif; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root {
            /* Pastel Hijau & Orange Theme */
            --primary-green: #86C9A7;
            --primary-green-light: #A8DABD;
            --primary-green-dark: #5FA882;
            --secondary-orange: #FFB88C;
            --secondary-orange-light: #FFD4B0;
            --secondary-orange-dark: #FF9F66;
            --accent-mint: #B5E4CA;
            --accent-peach: #FFCBA4;
            --success: #7BC96F;
            --warning: #FFB347;
            --danger: #FF8A80;
            
            /* Neutrals */
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.08);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #E8F5E9 0%, #FFF3E0 50%, #E1F5DC 100%);
            background-attachment: fixed;
            color: var(--gray-900);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Decorative background patterns */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(134, 201, 167, 0.15), transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(255, 184, 140, 0.15), transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(181, 228, 202, 0.1), transparent 50%);
            pointer-events: none;
            z-index: 0;
        }
        
        /* Navbar dengan soft gradient */
        .navbar {
            background: linear-gradient(135deg, rgba(134, 201, 167, 0.95), rgba(255, 184, 140, 0.95));
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            padding: 1.25rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(134, 201, 167, 0.15);
            animation: slideDown 0.6s ease-out;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .navbar-content {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }
        
        .navbar-brand .icon {
            font-size: 2rem;
            filter: drop-shadow(0 2px 8px rgba(255, 255, 255, 0.5));
            animation: pulse 3s ease-in-out infinite;
        }
        
        /* TAMBAHAN: Logo Image Support */
        .navbar-brand img.logo-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(255, 255, 255, 0.5));
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 1.25rem;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .user-info:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-2px);
        }
        
        .user-info .avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-orange));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
            border: 2px solid white;
        }
        
        .user-info .username {
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        /* TAMBAHAN: Clock dengan detik */
        .user-info .clock {
            font-family: 'JetBrains Mono', monospace;
            color: white;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }
        
        .logout-btn {
            padding: 0.625rem 1.5rem;
            background: rgba(255, 138, 128, 0.9);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .logout-btn:hover {
            background: rgba(255, 99, 71, 0.95);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 99, 71, 0.3);
        }
        
        /* Main container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        /* Welcome card */
        .welcome-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .welcome-card h1 {
            background: linear-gradient(135deg, var(--primary-green-dark), var(--secondary-orange-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        
        .welcome-card p {
            color: var(--gray-600);
            font-size: 1.05rem;
            line-height: 1.7;
        }
        
        .welcome-card .date-info {
            color: var(--gray-700);
            font-weight: 600;
            margin-top: 0.5rem;
            font-size: 1.1rem;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        .tab-btn {
            flex: 1;
            padding: 1.25rem 2rem;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 2px solid transparent;
            border-radius: 16px;
            color: var(--gray-600);
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        
        .tab-btn:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            border-color: var(--primary-green-light);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-orange));
            color: white;
            border-color: transparent;
            box-shadow: 0 8px 24px rgba(134, 201, 167, 0.35);
        }
        
        .tab-btn .icon {
            font-size: 1.5rem;
        }
        
        /* Stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-green), var(--secondary-orange));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.1));
        }
        
        .stat-card h3 {
            color: var(--gray-600);
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number {
            font-size: 2.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-green-dark), var(--secondary-orange-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .stat-card .label {
            color: var(--gray-500);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        /* Filter section */
        .filter-section {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }
        
        .filter-section h2 {
            color: var(--gray-800);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .search-box {
            margin-bottom: 1.5rem;
        }
        
        .search-box input {
            width: 100%;
            padding: 1rem 1.25rem;
            padding-left: 3rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 4px rgba(134, 201, 167, 0.1);
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-group label {
            display: block;
            color: var(--gray-700);
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
            font-family: 'Inter', sans-serif;
        }
        
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 4px rgba(134, 201, 167, 0.1);
        }
        
        .filter-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(134, 201, 167, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(134, 201, 167, 0.4);
        }
        
        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }
        
        .btn-secondary:hover {
            background: var(--gray-300);
            transform: translateY(-2px);
        }
        
        /* Table container */
        .table-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.6s ease-out 0.5s both;
        }
        
        .table-header {
            margin-bottom: 1.5rem;
        }
        
        .table-header h2 {
            color: var(--gray-800);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        table thead {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-orange));
        }
        
        table th {
            padding: 1.25rem 1rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.95rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        table th:first-child {
            border-top-left-radius: 12px;
        }
        
        table th:last-child {
            border-top-right-radius: 12px;
        }
        
        table tbody tr {
            background: white;
            transition: all 0.2s ease;
        }
        
        table tbody tr:hover {
            background: var(--gray-50);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        table td {
            padding: 1.125rem 1rem;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.95rem;
            color: var(--gray-700);
        }
        
        table td strong {
            color: var(--gray-900);
            font-weight: 600;
        }
        
        /* Badge styles */
        .badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        /* MODIFIKASI: 4 Badge untuk 4 kategori cara bayar */
        .badge-umum {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
        }
        
        .badge-bpjs {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
        }
        
        .badge-asuransi {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }
        
        .badge-jamkesda {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        /* MODIFIKASI: Tombol aksi dalam satu baris */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
            align-items: center;
        }
        
        .btn-barcode {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        
        .btn-barcode:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(134, 201, 167, 0.3);
        }
        
        /* No data state */
        .no-data {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .no-data-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .no-data h3 {
            color: var(--gray-700);
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        
        .no-data p {
            color: var(--gray-500);
            font-size: 1.05rem;
        }
        
        /* Barcode Modal */
        .barcode-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .barcode-content {
            background: white;
            margin: 3% auto;
            padding: 2.5rem;
            border-radius: 24px;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .barcode-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .barcode-header h2 {
            color: var(--gray-800);
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .close-modal {
            font-size: 2.5rem;
            color: var(--gray-400);
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }
        
        .close-modal:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }
        
        .barcode-info {
            margin-bottom: 2rem;
        }
        
        .barcode-info p {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            background: var(--gray-50);
            border-radius: 8px;
            font-size: 0.95rem;
        }
        
        .barcode-info p span:first-child {
            color: var(--gray-600);
            font-weight: 600;
        }
        
        .barcode-info p strong {
            color: var(--gray-900);
        }
        
        .barcode-display {
            background: white;
            border: 2px dashed var(--gray-300);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        /* TAMBAHAN: Auto Reload Indicator */
        .auto-reload-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: 0 4px 20px rgba(134, 201, 167, 0.3);
            z-index: 1500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: fadeInUp 0.6s ease-out 0.8s both;
        }
        
        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .barcode-content,
            .barcode-content * {
                visibility: visible;
            }
            .barcode-content {
                position: absolute;
                left: 0;
                top: 0;
                margin: 0;
                box-shadow: none;
            }
            .barcode-header button,
            .filter-buttons {
                display: none;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .navbar {
                padding: 1rem;
            }
            
            .navbar-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .table-wrapper {
                overflow-x: scroll;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <!-- OPSI 1: Gunakan emoji (default) -->
                <span class="icon">🏥</span>
                
                <!-- OPSI 2: Gunakan logo image (uncomment baris di bawah dan sesuaikan path) -->
                <!-- <img src="logo-radiologi.svg" alt="Logo Radiologi" class="logo-img"> -->
                
                <span>Radiologi</span>
            </div>
            <div class="navbar-user">
                <div class="user-info">
                    <div class="avatar">
                        <?php echo strtoupper(substr(getLoggedInUser(), 0, 1)); ?>
                    </div>
                    <span class="username"><?php echo htmlspecialchars(getLoggedInUser()); ?></span>
                </div>
                <div class="user-info">
                    <span class="clock" id="liveClock">Loading...</span>
                </div>
                <button class="logout-btn" onclick="location.href='logout.php'">Logout</button>
            </div>
        </div>
    </nav>
    
    <!-- Main Container -->
    <div class="container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h1>🌟 Selamat Datang, <?php echo htmlspecialchars(getLoggedInUser()); ?>!</h1>
            <p>Dashboard Monitoring Pemeriksaan Radiologi</p>
            <p class="date-info">📅 <?php echo $current_month . ' ' . $current_year; ?> | ⏰ <span id="mainClock">Loading...</span></p>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <a href="?tab=rajal" class="tab-btn <?php echo $active_tab === 'rajal' ? 'active' : ''; ?>">
                <span class="icon">📋</span>
                <span>Rawat Jalan</span>
            </a>
            <a href="?tab=ranap" class="tab-btn <?php echo $active_tab === 'ranap' ? 'active' : ''; ?>">
                <span class="icon">🏥</span>
                <span>Rawat Inap</span>
            </a>
        </div>
        
        <!-- Stats Grid - MODIFIKASI: 4 kolom tetap untuk cara bayar -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="icon">📊</span>
                <h3>Total Pemeriksaan</h3>
                <div class="number"><?php echo $total_pemeriksaan; ?></div>
                <div class="label"><?php echo $active_tab === 'rajal' ? 'Rawat Jalan' : 'Rawat Inap'; ?> Bulan Ini</div>
            </div>
            
            <div class="stat-card">
                <span class="icon">📅</span>
                <h3>Hari Ini</h3>
                <div class="number"><?php echo $today_count; ?></div>
                <div class="label">Pemeriksaan Terbaru</div>
            </div>
            
            <div class="stat-card">
                <span class="icon">💳</span>
                <h3>UMUM</h3>
                <div class="number"><?php echo $cara_bayar_stats['UMUM']; ?></div>
                <div class="label">Pasien Terdaftar</div>
            </div>
            
            <div class="stat-card">
                <span class="icon">🏥</span>
                <h3>BPJS</h3>
                <div class="number"><?php echo $cara_bayar_stats['BPJS']; ?></div>
                <div class="label">Pasien Terdaftar</div>
            </div>
            
            <div class="stat-card">
                <span class="icon">🏢</span>
                <h3>ASURANSI</h3>
                <div class="number"><?php echo $cara_bayar_stats['ASURANSI']; ?></div>
                <div class="label">Pasien Terdaftar</div>
            </div>
            
            <div class="stat-card">
                <span class="icon">🏛️</span>
                <h3>JAMKESDA</h3>
                <div class="number"><?php echo $cara_bayar_stats['JAMKESDA']; ?></div>
                <div class="label">Pasien Terdaftar</div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <h2>🔍 Filter & Pencarian Data</h2>
            
            <div class="search-box">
                <input type="text" name="search" placeholder="🔎 Cari nama pasien, no. RM, no. rawat, atau dokter..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>
            
            <form method="GET" action="">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                <input type="hidden" name="filter" value="1">
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>📅 Tanggal Dari</label>
                        <input type="date" name="tgl_dari" value="<?php echo $_GET['tgl_dari'] ?? date('Y-m-01'); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>📅 Tanggal Sampai</label>
                        <input type="date" name="tgl_sampai" value="<?php echo $_GET['tgl_sampai'] ?? date('Y-m-t'); ?>">
                    </div>
                    
                    <?php if ($active_tab === 'ranap'): ?>
                    <div class="filter-group">
                        <label>🛏️ Kamar</label>
                        <select name="kamar">
                            <option value="">Semua Kamar</option>
                            <?php foreach ($kamar_list as $kd => $nm): ?>
                            <option value="<?php echo htmlspecialchars($kd); ?>" <?php echo ($_GET['kamar'] ?? '') === $kd ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nm); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="filter-group">
                        <label>🏠 Poliklinik</label>
                        <select name="kamar">
                            <option value="">Semua Poliklinik</option>
                            <?php foreach ($kamar_list as $poli): ?>
                            <option value="<?php echo htmlspecialchars($poli); ?>" <?php echo ($_GET['kamar'] ?? '') === $poli ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($poli); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="filter-group">
                        <label>💳 Cara Bayar</label>
                        <select name="cara_bayar">
                            <option value="">Semua Cara Bayar</option>
                            <option value="UMUM">UMUM</option>
                            <option value="BPJS">BPJS</option>
                            <option value="ASURANSI">ASURANSI</option>
                            <option value="JAMKESDA">JAMKESDA</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>🔬 Jenis Pemeriksaan</label>
                        <select name="jenis_pemeriksaan">
                            <option value="">Semua Jenis</option>
                            <?php foreach ($jenis_pemeriksaan_list as $jp): ?>
                            <option value="<?php echo htmlspecialchars($jp); ?>" <?php echo ($_GET['jenis_pemeriksaan'] ?? '') === $jp ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($jp); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <span>🔍</span>
                        <span>Terapkan Filter</span>
                    </button>
                    <a href="?tab=<?php echo $active_tab; ?>" class="btn btn-secondary">
                        <span>🔄</span>
                        <span>Reset Filter</span>
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Data Table -->
        <div class="table-container">
            <div class="table-header">
                <h2>📊 Data Pemeriksaan <?php echo $active_tab === 'rajal' ? 'Rawat Jalan' : 'Rawat Inap'; ?></h2>
            </div>
            <div class="table-wrapper">
                <?php if (count($filtered_data) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No. Rawat</th>
                            <th>Nama Pasien</th>
                            <th>No. RM</th>
                            <th>Tgl Lahir</th>
                            <th>Jenis Pemeriksaan</th>
                            <th><?php echo $active_tab === 'ranap' ? 'Kamar' : 'Poliklinik'; ?></th>
                            <th>Tgl & Jam Periksa</th>
                            <th>Dokter Perujuk</th>
                            <th>Cara Bayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_data as $index => $row): 
                            // Mapping cara bayar ke kategori
                            $cb_lower = strtolower($row['cara_bayar'] ?? 'umum');
                            if (strpos($cb_lower, 'bpjs') !== false || strpos($cb_lower, 'jkn') !== false) {
                                $badge_class = 'bpjs';
                                $badge_text = 'BPJS';
                            } elseif (strpos($cb_lower, 'asuransi') !== false) {
                                $badge_class = 'asuransi';
                                $badge_text = 'ASURANSI';
                            } elseif (strpos($cb_lower, 'jamkesda') !== false || strpos($cb_lower, 'jamkesmas') !== false) {
                                $badge_class = 'jamkesda';
                                $badge_text = 'JAMKESDA';
                            } else {
                                $badge_class = 'umum';
                                $badge_text = 'UMUM';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['no_rawat']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['nama_pasien']); ?></td>
                            <td><?php echo htmlspecialchars($row['no_rm']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tgl_lahir'])); ?></td>
                            <td><?php echo htmlspecialchars($row['jenis_pemeriksaan']); ?></td>
                            <td><?php echo htmlspecialchars($row['kamar']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['tgl_periksa'])); ?></td>
                            <td><?php echo htmlspecialchars($row['dokter_perujuk']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $badge_class; ?>">
                                    <?php echo $badge_text; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-barcode" onclick="showBarcode(<?php echo $index; ?>)">
                                        <span>📄</span>
                                        <span>Barcode</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📭</div>
                    <h3>Tidak Ada Data</h3>
                    <p>Tidak ada data <?php echo $active_tab === 'rajal' ? 'rawat jalan' : 'rawat inap'; ?> yang sesuai dengan filter yang Anda pilih.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Auto Reload Indicator -->
    <?php if ($AUTO_RELOAD_SECONDS > 0): ?>
    <div class="auto-reload-indicator">
        <span>🔄</span>
        <span>Auto reload: <?php echo floor($AUTO_RELOAD_SECONDS / 60); ?> menit</span>
    </div>
    <?php endif; ?>
    
    <!-- Barcode Modal -->
    <div id="barcodeModal" class="barcode-modal">
        <div class="barcode-content">
            <div class="barcode-header">
                <h2>📄 Barcode Pasien</h2>
                <span class="close-modal" onclick="closeModal()">×</span>
            </div>
            <div id="barcodeInfo" class="barcode-info"></div>
            <div class="barcode-display">
                <svg id="barcode"></svg>
            </div>
            <div class="filter-buttons">
                <button onclick="printBarcode()" class="btn btn-primary">
                    <span>🖨️</span>
                    <span>Print Barcode</span>
                </button>
                <button onclick="closeModal()" class="btn btn-secondary">
                    <span>✖️</span>
                    <span>Tutup</span>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        const data = <?php echo json_encode(array_values($filtered_data)); ?>;
        
        // TAMBAHAN: Update clock dengan detik
        function updateClock() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            
            const dateTimeString = now.toLocaleDateString('id-ID', options);
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            
            const liveClockEl = document.getElementById('liveClock');
            const mainClockEl = document.getElementById('mainClock');
            
            if (liveClockEl) liveClockEl.textContent = timeString;
            if (mainClockEl) mainClockEl.textContent = timeString;
        }
        
        // Update clock setiap detik
        setInterval(updateClock, 1000);
        updateClock(); // Call immediately
        
        function showBarcode(index) {
            const item = data[index];
            const modal = document.getElementById('barcodeModal');
            const infoDiv = document.getElementById('barcodeInfo');
            
            infoDiv.innerHTML = `
                <p><span>No. Rawat:</span> <strong>${item.no_rawat}</strong></p>
                <p><span>Nama Pasien:</span> <strong>${item.nama_pasien}</strong></p>
                <p><span>No. RM:</span> <strong>${item.no_rm}</strong></p>
                <p><span>Tanggal Lahir:</span> <strong>${new Date(item.tgl_lahir).toLocaleDateString('id-ID')}</strong></p>
                <p><span>Jenis Pemeriksaan:</span> <strong>${item.jenis_pemeriksaan}</strong></p>
                <p><span><?php echo $active_tab === 'ranap' ? 'Kamar' : 'Poliklinik'; ?>:</span> <strong>${item.kamar}</strong></p>
                <p><span>Tanggal Periksa:</span> <strong>${new Date(item.tgl_periksa).toLocaleString('id-ID')}</strong></p>
                <p><span>Dokter Perujuk:</span> <strong>${item.dokter_perujuk}</strong></p>
                <p><span>Cara Bayar:</span> <strong>${item.cara_bayar}</strong></p>
            `;
            
            JsBarcode("#barcode", item.no_rawat, {
                format: "CODE128",
                width: 2,
                height: 100,
                displayValue: true,
                fontSize: 16,
                margin: 10
            });
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('barcodeModal').style.display = 'none';
        }
        
        function printBarcode() {
            window.print();
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('barcodeModal');
            if (event.target == modal) {
                closeModal();
            }
        }
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>

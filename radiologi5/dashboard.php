<?php
require_once 'auth.php';
require_once 'config.php';

// ========================================
// KONFIGURASI AUTO RELOAD
// ========================================
$AUTO_RELOAD_SECONDS = 300; // Default: 300 untuk 5 menit (Set 0 untuk disable)
// ========================================

// Tentukan tab aktif (default: rajal)
$active_tab = $_GET['tab'] ?? 'rajal';

// Tanggal hari ini untuk default filter
$today_date = date('Y-m-d');

// Ambil filter dari URL
$filters = [
    'jenis_rawat' => $active_tab,
    'bulan_ini' => true
];

if (isset($_GET['filter'])) {
    $filters = array_merge($filters, [
        'tgl_dari' => $_GET['tgl_dari'] ?? $today_date,
        'tgl_sampai' => $_GET['tgl_sampai'] ?? $today_date,
        'kamar' => $_GET['kamar'] ?? '',
        'cara_bayar' => $_GET['cara_bayar'] ?? '',
        'jenis_pemeriksaan' => $_GET['jenis_pemeriksaan'] ?? '',
        'search' => $_GET['search'] ?? ''
    ]);
} else {
    // Default: hari ini saja
    $filters['tgl_dari'] = $today_date;
    $filters['tgl_sampai'] = $today_date;
}

// Ambil data pemeriksaan dari database
try {
    $data_pemeriksaan = getPemeriksaanFromDB($filters);
    $filtered_data = $data_pemeriksaan;
} catch (Exception $e) {
    $error_message = "Error mengambil data: " . $e->getMessage();
    $filtered_data = [];
}

// Ambil list untuk dropdown filter
try {
    $kamar_list = getKamarList($active_tab);
    $cara_bayar_list = getCaraBayarList();
    $jenis_pemeriksaan_list = getJenisPemeriksaanList();
} catch (Exception $e) {
    $kamar_list = [];
    $cara_bayar_list = [];
    $jenis_pemeriksaan_list = [];
}

// Hitung statistik dengan 4 kategori cara bayar tetap
$total_pemeriksaan = count($filtered_data);
$today = date('Y-m-d');
$today_count = 0;

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

// Nama bulan dan hari dalam bahasa Indonesia
$hari_indonesia = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulan_indonesia = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

$day_of_week = $hari_indonesia[date('w')];
$current_date = date('d');
$current_month = $bulan_indonesia[date('m')];
$current_year = date('Y');
$full_date_text = "$day_of_week, $current_date $current_month $current_year";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Radiologi - Sistem Informasi Kesehatan</title>
    
    <?php if ($AUTO_RELOAD_SECONDS > 0): ?>
    <meta http-equiv="refresh" content="<?php echo $AUTO_RELOAD_SECONDS; ?>">
    <?php endif; ?>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        :root {
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
        
        .navbar {
            background: linear-gradient(135deg, rgba(134, 201, 167, 0.95), rgba(255, 184, 140, 0.95));
            backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            padding: 1.25rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(134, 201, 167, 0.15);
            animation: slideDown 0.6s ease-out;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
        
        .navbar-brand img.logo-img {
            width: 80px;
            height: 80px;
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
            gap: 1rem;
        }
        
        /* REVISI 2: User info styling yang lebih baik */
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .user-info .avatar {
            width: 36px;
            height: 36px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-green-dark);
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .clock-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
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
        }
        
        .logout-btn:hover {
            background: rgba(255, 99, 71, 0.95);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 99, 71, 0.3);
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .welcome-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            border-radius: 24px;
            padding: 2rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        /* REVISI 1: Tambah tanggal lengkap */
        .welcome-card .date-info {
            color: var(--gray-700);
            font-weight: 600;
            margin-top: 0.5rem;
            font-size: 1.1rem;
        }
        
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
            text-decoration: none;
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
        
        /* REVISI 3: Stats grid dalam 1 baris dengan ukuran lebih kecil */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
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
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .stat-card h3 {
            color: var(--gray-600);
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-green-dark), var(--secondary-orange-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
            line-height: 1;
        }
        
        .stat-card .label {
            color: var(--gray-500);
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .filter-section {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
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
        
        .table-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.6s ease-out 0.5s both;
            margin-bottom: 2rem;
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
        
        .badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
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
        
        /* REVISI 8: Barcode modal yang lebih kecil */
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
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .barcode-content {
            background: white;
            margin: 5% auto;
            padding: 1.5rem;
            border-radius: 16px;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }
        
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .barcode-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .barcode-header h2 {
            color: var(--gray-800);
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .close-modal {
            font-size: 2rem;
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
            margin-bottom: 1rem;
        }
        
        .barcode-info p {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.25rem;
            background: var(--gray-50);
            border-radius: 6px;
            font-size: 0.85rem;
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
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .barcode-display svg {
            max-width: 100%;
            height: auto;
        }
        
        .auto-reload-indicator {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));
            color: white;
            padding: 0.3rem 1.25rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: 0 4px 20px rgba(134, 201, 167, 0.3);
            z-index: 1500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: fadeInUp 0.6s ease-out 0.8s both;
        }
        
        /* REVISI 9: Footer copyright */
        .footer {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            border-radius: 16px;
            padding: 0.2rem;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            margin-top: 2rem;
        }
        
        .footer p {
            color: var(--gray-600);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .footer strong {
            color: var(--primary-green-dark);
            font-weight: 700;
        }
        
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
                max-width: 100%;
                padding: 20mm;
            }
            .barcode-header button,
            .filter-buttons {
                display: none !important;
            }
            .close-modal {
                display: none !important;
            }
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
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
                grid-template-columns: repeat(2, 1fr);
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
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <img src="logo RSKHS.jpg" class="logo-img" alt="Logo">
                <!-- <img src="logo-radiologi.svg" alt="Logo Radiologi" class="logo-img"> -->
                <span>Radiologi - Kartika Husada Setu</span>
            </div>
            <div class="navbar-user">
                <div class="user-info">
                    <div class="avatar">
                        <?php echo strtoupper(substr(getLoggedInUser(), 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars(getLoggedInUser()); ?></span>
                </div>
                <div class="clock-info">
                    <span>⏰</span>
                    <span id="liveClock">Loading...</span>
                </div>
                <button class="logout-btn" onclick="location.href='logout.php'">Logout</button>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="welcome-card">
            <h1>🌟 Selamat Datang, <?php echo htmlspecialchars(getLoggedInUser()); ?>!</h1>
            <p>Dashboard Monitoring Pemeriksaan Radiologi</p>
            <p class="date-info">📅 <?php echo $full_date_text; ?> | ⏰ <span id="mainClock">Loading...</span></p>
        </div>
        
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
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="icon">📊</span>
                <h3>Total Pemeriksaan</h3>
                <div class="number"><?php echo $total_pemeriksaan; ?></div>
                <div class="label"><?php echo $active_tab === 'rajal' ? 'Rawat Jalan' : 'Rawat Inap'; ?></div>
            </div>
            
            <div class="stat-card">
                <span class="icon">📅</span>
                <h3>Hari Ini</h3>
                <div class="number"><?php echo $today_count; ?></div>
                <div class="label">Pemeriksaan</div>
            </div>
            
            <div class="stat-card">
                <span class="icon">💳</span>
                <h3>UMUM</h3>
                <div class="number"><?php echo $cara_bayar_stats['UMUM']; ?></div>
                <div class="label">Pasien</div>
            </div>
            
            <div class="stat-card">
                <span class="icon">🏥</span>
                <h3>BPJS</h3>
                <div class="number"><?php echo $cara_bayar_stats['BPJS']; ?></div>
                <div class="label">Pasien</div>
            </div>
            
            <div class="stat-card">
                <span class="icon">🏢</span>
                <h3>ASURANSI</h3>
                <div class="number"><?php echo $cara_bayar_stats['ASURANSI']; ?></div>
                <div class="label">Pasien</div>
            </div>
            
            <div class="stat-card">
                <span class="icon">🏛️</span>
                <h3>JAMKESDA</h3>
                <div class="number"><?php echo $cara_bayar_stats['JAMKESDA']; ?></div>
                <div class="label">Pasien</div>
            </div>
        </div>
        
        <div class="filter-section">
            <h2>🔍 Filter & Pencarian Data</h2>
            
            <form method="GET" action="" id="filterForm">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                <input type="hidden" name="filter" value="1">
                
                <div class="search-box">
                    <input type="text" name="search" id="searchInput" placeholder="🔎 Cari nama pasien, no. RM, no. rawat, atau dokter..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>📅 Tanggal Dari</label>
                        <input type="date" name="tgl_dari" id="tglDari" value="<?php echo $_GET['tgl_dari'] ?? $today_date; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>📅 Tanggal Sampai</label>
                        <input type="date" name="tgl_sampai" id="tglSampai" value="<?php echo $_GET['tgl_sampai'] ?? $today_date; ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label><?php echo $active_tab === 'ranap' ? '🛏️ Kamar' : '🏠 Poliklinik'; ?></label>
                        <select name="kamar" id="kamarSelect">
                            <option value="">Semua <?php echo $active_tab === 'ranap' ? 'Kamar' : 'Poliklinik'; ?></option>
                            <?php foreach ($kamar_list as $kamar): ?>
                            <option value="<?php echo htmlspecialchars($kamar); ?>" <?php echo ($_GET['kamar'] ?? '') === $kamar ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($kamar); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>💳 Cara Bayar</label>
                        <select name="cara_bayar" id="caraBayarSelect">
                            <option value="">Semua Cara Bayar</option>
                            <option value="UMUM" <?php echo ($_GET['cara_bayar'] ?? '') === 'UMUM' ? 'selected' : ''; ?>>UMUM</option>
                            <option value="BPJS" <?php echo ($_GET['cara_bayar'] ?? '') === 'BPJS' ? 'selected' : ''; ?>>BPJS</option>
                            <option value="ASURANSI" <?php echo ($_GET['cara_bayar'] ?? '') === 'ASURANSI' ? 'selected' : ''; ?>>ASURANSI</option>
                            <option value="JAMKESDA" <?php echo ($_GET['cara_bayar'] ?? '') === 'JAMKESDA' ? 'selected' : ''; ?>>JAMKESDA</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label>🔬 Jenis Pemeriksaan</label>
                        <select name="jenis_pemeriksaan" id="jenisSelect">
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
                    <button type="button" class="btn btn-secondary" onclick="resetFilter()">
                        <span>🔄</span>
                        <span>Reset Filter</span>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2>📊 Data Pemeriksaan <?php echo $active_tab === 'rajal' ? 'Rawat Jalan' : 'Rawat Inap'; ?></h2>
            </div>
            <div class="table-wrapper">
                <?php if (count($filtered_data) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No. Permintaan</th>
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
                            <td><strong><?php echo htmlspecialchars($row['noorder']); ?></strong></td>
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
        
        <!-- REVISI 9: Footer Copyright -->
        <div class="footer">
            <p>© 2026 <strong>Wahyu Ardiansyah</strong> / <strong>Information Technology</strong>. All Rights Reserved.</p>
        </div>
    </div>
    
    <?php if ($AUTO_RELOAD_SECONDS > 0): ?>
    <div class="auto-reload-indicator">
        <span>🔄</span>
        <span>Auto reload: <?php echo floor($AUTO_RELOAD_SECONDS / 60); ?> menit</span>
    </div>
    <?php endif; ?>
    
    <!-- REVISI 8: Barcode Modal yang lebih kecil -->
    <div id="barcodeModal" class="barcode-modal">
        <div class="barcode-content">
            <div class="barcode-header">
                <h2>📄 Barcode Pasien</h2>
                <span class="close-modal" onclick="closeModal()">×</span>
            </div>
            <div id="barcodeInfo" class="barcode-info"></div>
            <div class="barcode-display">
                <canvas id="barcodeCanvas"></canvas>
            </div>
            <div class="filter-buttons">
                <button onclick="printBarcode()" class="btn btn-primary">
                    <span>🖨️</span>
                    <span>Print</span>
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
        const today = '<?php echo $today_date; ?>';
        
        // Update clock dengan detik
        function updateClock() {
            const now = new Date();
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
        
        setInterval(updateClock, 1000);
        updateClock();
        
        // REVISI 7: Reset filter ke hari ini
        function resetFilter() {
            window.location.href = '?tab=<?php echo $active_tab; ?>';
        }
        
        // REVISI 8: Barcode dengan data lengkap
        function showBarcode(index) {
            const item = data[index];
            const modal = document.getElementById('barcodeModal');
            const infoDiv = document.getElementById('barcodeInfo');
            
            // Data untuk QR Code
            const barcodeData = `NO_RAWAT:${item.no_rawat}|NAMA:${item.nama_pasien}|NO_RM:${item.no_rm}|TGL_LAHIR:${item.tgl_lahir}|JENIS:${item.jenis_pemeriksaan}|KAMAR:${item.kamar}|TGL_PERIKSA:${item.tgl_periksa}|DOKTER:${item.dokter_perujuk}|CARA_BAYAR:${item.cara_bayar}`;
            
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
            
            // Generate barcode dengan data lengkap
            JsBarcode("#barcodeCanvas", barcodeData, {
                format: "CODE128",
                width: 1.5,
                height: 60,
                displayValue: false,
                margin: 5
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

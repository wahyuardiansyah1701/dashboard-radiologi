<?php
require_once 'auth.php';
require_once 'config.php';

// Tentukan tab aktif (default: rajal)
$active_tab = $_GET['tab'] ?? 'rajal';

// Ambil filter dari URL
$filters = [
    'jenis_rawat' => $active_tab // Tambahkan filter jenis rawat
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

// Hitung statistik
$total_pemeriksaan = count($filtered_data);
$today = date('Y-m-d');
$today_count = 0;
$cara_bayar_stats = [];

foreach ($filtered_data as $item) {
    if (date('Y-m-d', strtotime($item['tgl_periksa'])) === $today) {
        $today_count++;
    }
    
    $cb = $item['cara_bayar'] ?? 'Tidak Diketahui';
    if (!isset($cara_bayar_stats[$cb])) {
        $cara_bayar_stats[$cb] = 0;
    }
    $cara_bayar_stats[$cb]++;
}

$top_cara_bayar = array_slice($cara_bayar_stats, 0, 3, true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Radiologi - Sistem Informasi Kesehatan</title>
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
            color: white;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .user-avatar {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.875rem;
            border: 2px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-logout {
            padding: 0.625rem 1.5rem;
            background: rgba(255, 138, 128, 0.25);
            color: white;
            border: 1px solid rgba(255, 138, 128, 0.4);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-logout:hover {
            background: rgba(255, 138, 128, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 138, 128, 0.3);
        }
        
        /* Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }
        
        /* Welcome Card */
        .welcome-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            padding: 2rem 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            animation: fadeInUp 0.6s ease-out 0.1s backwards;
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
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }
        
        .welcome-card p {
            color: var(--gray-700);
            font-size: 1rem;
            font-weight: 500;
        }
        
        .welcome-card .date-time {
            margin-top: 0.5rem;
            color: var(--gray-600);
            font-size: 0.875rem;
            font-family: 'JetBrains Mono', monospace;
        }
        
        /* Tab Navigation */
        .tab-navigation {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 0.5rem;
            margin-bottom: 2rem;
            display: inline-flex;
            gap: 0.5rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeInUp 0.6s ease-out 0.2s backwards;
        }
        
        .tab-btn {
            padding: 0.875rem 2rem;
            background: transparent;
            color: var(--gray-700);
            border: none;
            border-radius: 14px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.625rem;
            font-family: inherit;
        }
        
        .tab-btn:hover {
            background: rgba(134, 201, 167, 0.1);
            color: var(--primary-green-dark);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-orange));
            color: white;
            box-shadow: 0 4px 12px rgba(134, 201, 167, 0.3);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.6);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out backwards;
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.3s; }
        .stat-card:nth-child(2) { animation-delay: 0.4s; }
        .stat-card:nth-child(3) { animation-delay: 0.5s; }
        .stat-card:nth-child(4) { animation-delay: 0.6s; }
        .stat-card:nth-child(5) { animation-delay: 0.7s; }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color), var(--card-color-light));
            transition: height 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card:hover::before {
            height: 6px;
        }
        
        .stat-card.green {
            --card-color: #86C9A7;
            --card-color-light: #A8DABD;
        }
        
        .stat-card.orange {
            --card-color: #FFB88C;
            --card-color-light: #FFD4B0;
        }
        
        .stat-card.mint {
            --card-color: #B5E4CA;
            --card-color-light: #D4F1E3;
        }
        
        .stat-card.peach {
            --card-color: #FFCBA4;
            --card-color-light: #FFE4CD;
        }
        
        .stat-card.lime {
            --card-color: #B8E986;
            --card-color-light: #D0F0AD;
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--card-color), var(--card-color-light));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .stat-label {
            color: var(--gray-600);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            color: var(--gray-900);
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .stat-description {
            color: var(--gray-500);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* Filter Section */
        .filter-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.6);
            animation: fadeInUp 0.6s ease-out 0.8s backwards;
        }
        
        .filter-section h2 {
            color: var(--gray-900);
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
        
        .search-box label {
            display: block;
            color: var(--gray-700);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .search-wrapper {
            position: relative;
        }
        
        .search-wrapper::before {
            content: '🔍';
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.25rem;
            pointer-events: none;
        }
        
        .search-input {
            width: 100%;
            padding: 0.875rem 1.25rem 0.875rem 3rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 0.9375rem;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
        }
        
        .search-input:focus {
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
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 0.9375rem;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
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
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-family: inherit;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-orange));
            color: white;
            box-shadow: 0 4px 12px rgba(134, 201, 167, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(134, 201, 167, 0.4);
        }
        
        .btn-secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        
        .btn-secondary:hover {
            background: var(--gray-200);
            transform: translateY(-2px);
        }
        
        /* Table Container */
        .table-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.6);
            animation: fadeInUp 0.6s ease-out 0.9s backwards;
        }
        
        .table-header {
            padding: 2rem;
            border-bottom: 1px solid var(--gray-200);
            background: linear-gradient(to right, rgba(134, 201, 167, 0.08), rgba(255, 184, 140, 0.08));
        }
        
        .table-header h2 {
            color: var(--gray-900);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: linear-gradient(to right, rgba(134, 201, 167, 0.1), rgba(255, 184, 140, 0.1));
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        th {
            padding: 1.25rem 1.5rem;
            text-align: left;
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--gray-700);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        tbody tr {
            border-bottom: 1px solid var(--gray-100);
            transition: all 0.2s;
        }
        
        tbody tr:hover {
            background: linear-gradient(to right, rgba(134, 201, 167, 0.05), rgba(255, 184, 140, 0.05));
        }
        
        td {
            padding: 1.25rem 1.5rem;
            font-size: 0.9375rem;
            color: var(--gray-800);
        }
        
        td strong {
            color: var(--gray-900);
            font-weight: 600;
            font-family: 'JetBrains Mono', monospace;
        }
        
        .badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 50px;
            font-size: 0.8125rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .badge-bpjs {
            background: linear-gradient(135deg, #86C9A7, #A8DABD);
            color: white;
        }
        
        .badge-umum {
            background: linear-gradient(135deg, #FFB88C, #FFD4B0);
            color: white;
        }
        
        .badge-asuransi {
            background: linear-gradient(135deg, #B5E4CA, #D4F1E3);
            color: var(--gray-800);
        }
        
        .btn-barcode {
            padding: 0.5rem 1rem;
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-orange));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
        }
        
        .btn-barcode:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(134, 201, 167, 0.3);
        }
        
        /* No Data State */
        .no-data {
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .no-data-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.4;
        }
        
        .no-data h3 {
            color: var(--gray-700);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .no-data p {
            color: var(--gray-500);
            font-size: 0.9375rem;
        }
        
        /* Barcode Modal */
        .barcode-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(8px);
            z-index: 2000;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .barcode-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translate(-50%, -40%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        
        .barcode-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-100);
        }
        
        .barcode-header h2 {
            color: var(--gray-900);
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .close-modal {
            width: 36px;
            height: 36px;
            background: var(--gray-100);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1.5rem;
            color: var(--gray-600);
        }
        
        .close-modal:hover {
            background: var(--gray-200);
            transform: rotate(90deg);
        }
        
        .barcode-info {
            background: var(--gray-50);
            border-radius: 14px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .barcode-info p {
            display: flex;
            justify-content: space-between;
            padding: 0.625rem 0;
            border-bottom: 1px solid var(--gray-200);
            font-size: 0.9375rem;
        }
        
        .barcode-info p:last-child {
            border-bottom: none;
        }
        
        .barcode-info span:first-child {
            color: var(--gray-600);
            font-weight: 500;
        }
        
        .barcode-info strong {
            color: var(--gray-900);
            font-weight: 600;
        }
        
        .barcode-display {
            background: white;
            padding: 2rem;
            border-radius: 14px;
            text-align: center;
            margin-bottom: 1.5rem;
            border: 2px dashed var(--gray-200);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }
            
            .navbar-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .container {
                padding: 1rem;
            }
            
            .welcome-card {
                padding: 1.5rem;
            }
            
            .welcome-card h1 {
                font-size: 1.5rem;
            }
            
            .tab-navigation {
                width: 100%;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            th, td {
                padding: 1rem;
                font-size: 0.875rem;
            }
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white;
            }
            
            .navbar,
            .welcome-card,
            .tab-navigation,
            .stats-grid,
            .filter-section,
            .table-header,
            .btn-barcode {
                display: none;
            }
            
            .barcode-modal {
                display: block;
                position: static;
                background: white;
            }
            
            .barcode-content {
                position: static;
                transform: none;
                max-width: 100%;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <span class="icon">🏥</span>
                <span>Radiologi</span>
            </div>
            <div class="navbar-user">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr(getLoggedInUser(), 0, 1)); ?>
                    </div>
                    <span><?php echo htmlspecialchars(getLoggedInUser()); ?></span>
                </div>
                <a href="logout.php" class="btn-logout">
                    <span>🚪</span>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Container -->
    <div class="container">
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h1>👋 Selamat Datang, <?php echo htmlspecialchars(getLoggedInUser()); ?>!</h1>
            <p>Dashboard Monitoring Pemeriksaan Radiologi</p>
            <div class="date-time">
                📅 <?php echo date('l, d F Y'); ?> • ⏰ <?php echo date('H:i'); ?> WIB
            </div>
        </div>
        
        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <a href="?tab=rajal" class="tab-btn <?php echo $active_tab === 'rajal' ? 'active' : ''; ?>">
                <span>🏥</span>
                <span>Rawat Jalan</span>
            </a>
            <a href="?tab=ranap" class="tab-btn <?php echo $active_tab === 'ranap' ? 'active' : ''; ?>">
                <span>🛏️</span>
                <span>Rawat Inap</span>
            </a>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon">📊</div>
                <div class="stat-label">Total Pemeriksaan</div>
                <div class="stat-value"><?php echo number_format($total_pemeriksaan); ?></div>
                <div class="stat-description"><?php echo $active_tab === 'rajal' ? 'Rawat Jalan' : 'Rawat Inap'; ?></div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-icon">📅</div>
                <div class="stat-label">Hari Ini</div>
                <div class="stat-value"><?php echo number_format($today_count); ?></div>
                <div class="stat-description">Pemeriksaan terbaru</div>
            </div>
            
            <?php 
            $colors = ['mint', 'peach', 'lime'];
            $icons = ['💳', '🏦', '💼'];
            $index = 0;
            foreach ($top_cara_bayar as $cb => $count): 
                if ($index >= 3) break;
            ?>
            <div class="stat-card <?php echo $colors[$index]; ?>">
                <div class="stat-icon"><?php echo $icons[$index]; ?></div>
                <div class="stat-label"><?php echo htmlspecialchars($cb); ?></div>
                <div class="stat-value"><?php echo number_format($count); ?></div>
                <div class="stat-description">Pasien terdaftar</div>
            </div>
            <?php 
                $index++;
            endforeach; 
            ?>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <h2>🔍 Filter & Pencarian Data</h2>
            <form method="GET" action="">
                <input type="hidden" name="tab" value="<?php echo $active_tab; ?>">
                <input type="hidden" name="filter" value="1">
                
                <div class="search-box">
                    <label>Pencarian Cepat</label>
                    <div class="search-wrapper">
                        <input type="text" 
                               name="search" 
                               class="search-input"
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                               placeholder="Cari nama pasien, no. RM, no. rawat, atau dokter...">
                    </div>
                </div>
                
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>📅 Tanggal Dari</label>
                        <input type="date" name="tgl_dari" value="<?php echo htmlspecialchars($_GET['tgl_dari'] ?? ''); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>📅 Tanggal Sampai</label>
                        <input type="date" name="tgl_sampai" value="<?php echo htmlspecialchars($_GET['tgl_sampai'] ?? ''); ?>">
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
                            <?php foreach ($cara_bayar_list as $cb): ?>
                            <option value="<?php echo htmlspecialchars($cb); ?>" <?php echo ($_GET['cara_bayar'] ?? '') === $cb ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cb); ?>
                            </option>
                            <?php endforeach; ?>
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
                        <?php foreach ($filtered_data as $index => $row): ?>
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
                                <span class="badge badge-<?php echo strtolower(str_replace(' ', '', $row['cara_bayar'])); ?>">
                                    <?php echo htmlspecialchars($row['cara_bayar']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-barcode" onclick="showBarcode(<?php echo $index; ?>)">
                                    <span>📄</span>
                                    <span>Barcode</span>
                                </button>
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

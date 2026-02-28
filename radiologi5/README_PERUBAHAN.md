# 📋 PERUBAHAN KONFIGURASI DASHBOARD RADIOLOGI

## ✅ PERUBAHAN YANG SUDAH DITERAPKAN

### 1️⃣ Filter Hanya Pasien dengan Jenis Pemeriksaan
**File**: `config.php`
**Lokasi**: Line 56-61 (Rawat Jalan) dan Line 87-92 (Rawat Inap)

**Perubahan**:
Ditambahkan kondisi filter untuk menampilkan **HANYA** pasien yang memiliki jenis pemeriksaan:

```php
// Untuk Rawat Jalan
AND pr.nm_perawatan IS NOT NULL
AND pr.nm_perawatan != ''
AND pr.nm_perawatan != '-'

// Untuk Rawat Inap  
AND pr.nm_perawatan IS NOT NULL
AND pr.nm_perawatan != ''
AND pr.nm_perawatan != '-'
```

**Hasil**:
- ✅ Pasien **TANPA** jenis pemeriksaan radiologi akan **DISEMBUNYIKAN**
- ✅ Hanya pasien **DENGAN** jenis pemeriksaan yang akan muncul di tabel
- ✅ Berlaku untuk **RAWAT JALAN** dan **RAWAT INAP**

---

### 2️⃣ Format Input Tanggal Menggunakan Date Picker
**File**: `dashboard.php`
**Lokasi**: Line 1043-1050

**Format Input**:
```html
<!-- Tanggal Dari -->
<input type="date" name="tgl_dari" id="tglDari" value="<?php echo $_GET['tgl_dari'] ?? $today_date; ?>">

<!-- Tanggal Sampai -->
<input type="date" name="tgl_sampai" id="tglSampai" value="<?php echo $_GET['tgl_sampai'] ?? $today_date; ?>">
```

**Fitur Date Picker**:
- ✅ Kalender popup yang mudah digunakan
- ✅ Format tanggal otomatis (YYYY-MM-DD)
- ✅ Validasi tanggal otomatis
- ✅ Kompatibel dengan semua browser modern
- ✅ Mobile-friendly (touch support)

---

## 🚀 CARA INSTALL

### File yang Perlu Diupload:
1. **config.php** (file baru yang sudah dimodifikasi)
2. **dashboard.php** (tidak ada perubahan, sudah menggunakan date picker)

### Langkah Install:

```bash
# 1. Backup file lama (PENTING!)
mv config.php config_backup_old.php
mv dashboard.php dashboard_backup_old.php

# 2. Upload file baru
# - Upload config.php (yang sudah dimodifikasi)
# - Upload dashboard.php (tidak ada perubahan)

# 3. Set permission (jika perlu)
chmod 644 config.php
chmod 644 dashboard.php
```

---

## 🔍 CARA MENGGUNAKAN DATE PICKER

### Desktop:
1. Klik pada field "Tanggal Dari" atau "Tanggal Sampai"
2. Kalender akan muncul
3. Pilih tanggal yang diinginkan
4. Tanggal otomatis terisi

### Mobile:
1. Tap pada field tanggal
2. Kalender native akan muncul
3. Swipe atau scroll untuk memilih tanggal
4. Tap "OK" atau "Done"

### Keyboard:
- Bisa langsung ketik format: YYYY-MM-DD
- Contoh: 2026-02-09

---

## 🧪 CARA TEST

### Test Filter Jenis Pemeriksaan:
1. Buka dashboard
2. Pilih tab "Rawat Jalan" atau "Rawat Inap"
3. **VERIFIKASI**: Semua data yang muncul harus memiliki jenis pemeriksaan
4. **TIDAK BOLEH** ada data dengan jenis pemeriksaan "-" atau kosong

### Test Date Picker:
1. Klik field "Tanggal Dari"
2. **VERIFIKASI**: Kalender popup muncul
3. Pilih tanggal yang berbeda
4. **VERIFIKASI**: Tanggal terisi otomatis
5. Klik "Terapkan Filter"
6. **VERIFIKASI**: Data sesuai dengan range tanggal yang dipilih

### Test Reset Filter:
1. Set filter tanggal
2. Klik "Reset Filter"
3. **VERIFIKASI**: Tanggal kembali ke hari ini
4. **VERIFIKASI**: Hanya data hari ini yang muncul

---

## 📊 QUERY DATABASE

### Query yang Digunakan (Rawat Jalan):
```sql
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
AND pr.nm_perawatan IS NOT NULL    -- ✅ FILTER BARU
AND pr.nm_perawatan != ''          -- ✅ FILTER BARU
AND pr.nm_perawatan != '-'         -- ✅ FILTER BARU
```

### Query yang Digunakan (Rawat Inap):
```sql
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
AND pr.nm_perawatan IS NOT NULL    -- ✅ FILTER BARU
AND pr.nm_perawatan != ''          -- ✅ FILTER BARU
AND pr.nm_perawatan != '-'         -- ✅ FILTER BARU
```

---

## 🛠️ TROUBLESHOOTING

### Masalah: Tidak ada data yang muncul
**Penyebab**: Kemungkinan tidak ada pasien dengan jenis pemeriksaan di database
**Solusi**: 
1. Cek tabel `periksa_radiologi` dan `jns_perawatan_radiologi`
2. Pastikan ada data pemeriksaan radiologi yang ter-link
3. Jalankan query manual untuk verifikasi:
```sql
SELECT COUNT(*) FROM periksa_radiologi 
LEFT JOIN jns_perawatan_radiologi ON periksa_radiologi.kd_jenis_prw = jns_perawatan_radiologi.kd_jenis_prw
WHERE jns_perawatan_radiologi.nm_perawatan IS NOT NULL;
```

### Masalah: Date picker tidak muncul
**Penyebab**: Browser lama atau tidak support HTML5
**Solusi**:
1. Update browser ke versi terbaru
2. Gunakan Chrome, Firefox, Edge, atau Safari modern
3. Jika browser sudah modern, cek console browser (F12)

### Masalah: Tanggal tidak terfilter dengan benar
**Penyebab**: Format tanggal tidak sesuai
**Solusi**:
1. Pastikan format input: YYYY-MM-DD
2. Cek nilai `$_GET['tgl_dari']` dan `$_GET['tgl_sampai']`
3. Verifikasi query di config.php

---

## 📝 CATATAN PENTING

### Apa yang DIUBAH:
✅ Filter di config.php (hanya tampilkan pasien dengan jenis pemeriksaan)
✅ Sudah menggunakan `type="date"` untuk date picker (tidak ada perubahan di dashboard.php)

### Apa yang TIDAK DIUBAH:
❌ Desain UI/UX (tetap sama)
❌ Koneksi database (tetap sama)
❌ Struktur tabel (tetap sama)
❌ Fitur barcode (tetap sama)
❌ Auto reload (tetap sama)
❌ Statistik (tetap sama)
❌ Search bar (tetap sama)

---

## ✨ KEUNTUNGAN PERUBAHAN INI

1. **Data Lebih Bersih**: Hanya pasien dengan jenis pemeriksaan yang ditampilkan
2. **Mudah Digunakan**: Date picker lebih user-friendly daripada input manual
3. **Akurat**: Validasi tanggal otomatis, mengurangi error input
4. **Responsif**: Date picker bekerja baik di desktop dan mobile
5. **Konsisten**: Format tanggal selalu konsisten (YYYY-MM-DD)

---

## 📞 DUKUNGAN

Jika ada pertanyaan atau error:
1. Cek error log PHP
2. Cek browser console (F12)
3. Verifikasi koneksi database
4. Pastikan tabel `periksa_radiologi` dan `jns_perawatan_radiologi` ada dan terisi

---

**Status**: ✅ SEMUA PERUBAHAN SUDAH DITERAPKAN DAN SIAP DIGUNAKAN!

**Tanggal**: 09 Februari 2026
**Versi**: 1.1 (Filter Jenis Pemeriksaan + Date Picker)

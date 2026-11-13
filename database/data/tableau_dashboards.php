<?php

declare(strict_types=1);

/**
 * Data awal Katalog Dashboard Tableau.
 *
 * Sumber: sheet "Daily Check Tableau 13-11-2026 - List" (159 dashboard).
 * Dipakai oleh Database\Seeders\TableauDashboardSeeder dan migrasi seed
 * production. Kolom mengikuti sheet: Num, Dashboard Name, Dashboard Definition
 * (Definisi / Isi / Tujuan), Project Tableau Existing, Mapping, User Permission,
 * User Mapping, Dashboard Owner.
 *
 * @return list<array{
 *     number: int,
 *     name: string,
 *     definition: string,
 *     contents: string,
 *     purpose: string,
 *     tableau_project: string,
 *     mapping: string,
 *     user_permission: string,
 *     user_mapping: string,
 *     owner: string
 * }>
 */
$dpkAll = 'KC, Divisi Akuntansi dan Manajemen Keuangan, Divisi Audit Intern, Divisi Dana dan Jasa, Divisi Digital Banking, Divisi Manajemen Risiko, Divisi Manajemen Jaringan dan Layanan, Divisi Operasi, Divisi Perencanaan Strategis & Manajemen, Unit Usaha Syariah';
$dpkDivisi = 'Divisi Akuntansi dan Manajemen Keuangan, Divisi Audit Intern, Divisi Dana dan Jasa, Divisi Digital Banking, Divisi Manajemen Risiko, Divisi Manajemen Jaringan dan Layanan, Divisi Operasi, Divisi Perencanaan Strategis & Manajemen, Unit Usaha Syariah';
$kreditAll = 'KC, Kredit, CABANG UTAMA SURABAYA, Divisi Administrasi Kredit, Divisi Akuntansi dan Manajemen Keuangan, Divisi Audit Intern, Divisi Dana dan Jasa, Divisi Digital Banking, Divisi Kredit Komersial, Korporasi dan Sindikasi, Divisi Kredit Konsumer, Divisi Kredit Mikro, Divisi Kredit Ritel dan Menengah, Divisi Manajemen Risiko, Divisi Manajemen Jaringan dan Layanan, Divisi Perencanaan Strategis & Manajemen, Unit Usaha Syariah';
$kreditDivisi = 'Kredit, Divisi Administrasi Kredit, Divisi Akuntansi dan Manajemen Keuangan, Divisi Audit Intern, Divisi Dana dan Jasa, Divisi Digital Banking, Divisi Kredit Komersial, Korporasi dan Sindikasi, Divisi Kredit Konsumer, Divisi Kredit Mikro, Divisi Kredit Ritel dan Menengah, Divisi Manajemen Risiko, Divisi Manajemen Jaringan dan Layanan, Divisi Perencanaan Strategis & Manajemen, Unit Usaha Syariah';
$kreditCabangUtama = 'Kredit, CABANG UTAMA SURABAYA, Divisi Administrasi Kredit, Divisi Akuntansi dan Manajemen Keuangan, Divisi Audit Intern, Divisi Dana dan Jasa, Divisi Digital Banking, Divisi Kredit Komersial, Korporasi dan Sindikasi, Divisi Kredit Konsumer, Divisi Kredit Mikro, Divisi Kredit Ritel dan Menengah, Divisi Manajemen Risiko, Divisi Manajemen Jaringan dan Layanan, Divisi Perencanaan Strategis & Manajemen, Unit Usaha Syariah';
$fds = 'Divisi Audit Intern, Divisi Manajemen Risiko, Divisi Teknologi Informasi';
$earlyWarning = 'All Users, Divisi Audit Intern, Divisi Manajemen Risiko, Divisi Teknologi Informasi (Monitoring)';
$rekonStandar = 'data biller vs CBS per transaksi: tanggal, nomor referensi, rekening debet dan kredit, channel, keterangan status rekon, nominal.';

$viewOnly = 'View Data Only';
$viewDownload = 'View Data & Download Data';
$downloadOnly = 'Download Data Only';

$row = static fn (
    int $number,
    string $name,
    string $definition,
    string $contents,
    string $purpose,
    string $project,
    string $mapping,
    string $permission,
    string $userMapping,
    string $owner,
): array => [
    'number' => $number,
    'name' => $name,
    'definition' => $definition,
    'contents' => $contents,
    'purpose' => $purpose,
    'tableau_project' => $project,
    'mapping' => $mapping,
    'user_permission' => $permission,
    'user_mapping' => $userMapping,
    'owner' => $owner,
];

$rekon = static fn (int $number, string $name, string $definition, string $permission, string $userMapping): array => $row(
    $number,
    $name,
    $definition,
    $rekonStandar,
    $viewDownload,
    'Dashboard Rekonsiliasi',
    'Finance Performance',
    $permission,
    $userMapping,
    'Operasi',
);

$fdsRow = static fn (int $number, string $name, string $definition, string $contents): array => $row(
    $number,
    $name,
    $definition,
    $contents,
    $viewDownload,
    'Dashboard FDS',
    'Risk',
    $fds,
    'Divisi',
    'Manajemen Risiko',
);

$earlyWarningRow = static fn (int $number, string $name, string $definition, string $contents): array => $row(
    $number,
    $name,
    $definition,
    $contents,
    $viewDownload,
    'Early Warning Dashboard',
    'Risk',
    $earlyWarning,
    'Divisi & Cabang',
    'Manajemen Risiko',
);

return [
    // ===================== Dashboard DPK (Business Funding) =====================
    $row(1, 'Dash DPK Kantor Fungsional', 'Rincian posisi DPK per kantor fungsional (KF).', 'nama kantor, jenis dan tipe rekening, keterangan tipe, mata uang, nominal dan nominal ekuivalen IDR.', $viewDownload, 'Dashboard DPK', 'Business Funding', 'KC', 'Cabang', 'Dana & Jasa, Hubungan Kelembagaan'),
    $row(2, 'Dash DPK Kantor Fungsional Harian', 'Perbandingan posisi DPK harian per kantor fungsional.', 'NOA dan nominal per mata uang dan jenis DPK pada dua tanggal posisi (mis. 29 vs 30 Oktober) beserta growth-nya.', $viewOnly, 'Dashboard DPK', 'Business Funding', $dpkAll, 'Divisi & Cabang', 'Dana & Jasa, Hubungan Kelembagaan'),
    $row(3, 'Dash DPK Valas', 'Monitoring DPK valuta asing.', 'posisi DPK valas per status rekening, wilayah dan cabang pada tanggal data terpilih.', $viewOnly, 'Dashboard DPK', 'Business Funding', $dpkAll, 'Divisi & Cabang', 'Dana & Jasa'),
    $row(4, 'Dash Kelolaan DPK FO - Versi MJL', 'Dashboard Funding Officer untuk DPK kelolaan (versi MJL).', 'jumlah NOA dan nominal DPK kelolaan per jenis DPK (giro, tabungan, deposito) per wilayah, cabang/capem dan pegawai FO, dibandingkan H-1 dan M-1.', $viewOnly, 'Dashboard DPK', 'Business Funding', $dpkAll, 'Divisi & Cabang', 'Dana & Jasa, Hubungan Kelembagaan'),
    $row(5, 'Dash Performance DPK Cabang (DJS)', 'Kinerja DPK cabang dan Funding Officer versi Divisi Dana & Jasa.', 'performance DPK konvensional/syariah per wilayah dan cabang, tab detail tabungan, giro, deposito, QRIS, agen, transaksi dan fee tabungan/giro, serta detail nasabah.', $viewDownload, 'Dashboard DPK', 'Business Funding', $dpkAll, 'Divisi & Cabang', 'Dana & Jasa'),
    $row(6, 'Dash Performance FO (maintenance)', 'Kinerja Funding Officer per cabang (status maintenance).', 'per FO (NIP, nama): NOA dan saldo tabungan, giro, deposito, QRIS, agen, JConnect, nasabah prioritas, jumlah transaksi serta fee based tabungan dan giro.', $viewOnly, 'Dashboard DPK', 'Business Funding', 'admin', 'Admin', 'Sudah ada dashboard pengganti'),
    $row(7, 'Dash Report DPK Rate Per Tipe', 'Laporan DPK per tipe produk berdasarkan tiering suku bunga.', 'nominal DPK EOM per tipe produk (deposito Pemda, giro umum, giro rumah sakit, giro universitas, tabungan, dll); tiering rate deposito per jangka waktu (<2% s.d. >6%) dan tiering rate giro/tabungan (0% s.d. >2%).', $viewDownload, 'Dashboard DPK', 'Business Funding', $dpkDivisi, 'Divisi', 'Dana & Jasa, Hubungan Kelembagaan, Unit Usaha Syariah'),
    $row(8, 'Dash Tabungan Haji', 'Monitoring produk Tabungan Haji, termasuk Tabungan Haji iB Amanah.', 'NOA dan nominal per wilayah, transaksi tabungan haji, dan tiering saldo.', $viewOnly, 'Dashboard DPK', 'Business Funding', 'Divisi Audit Intern', 'Divisi', 'Unit Usaha Syariah'),
    $row(9, 'Dashboard NOA', 'Monitoring jumlah rekening (NOA) DPK per kantor fungsional.', 'NOA per wilayah, cabang/capem, kantor fungsional, jenis dan tipe rekening, serta grup NOA.', $viewDownload, 'Dashboard DPK', 'Business Funding', 'KC', 'Cabang', 'Owner TI'),
    $row(10, 'Dashboard APOLO SIMPEL', 'Laporan APOLO untuk Simpanan Pelajar (SimPel dan SimPel iB).', 'laporan APOLO SimPel dan SimPel iB per periode serta detail rekening.', $viewDownload, 'Dashboard DPK', 'Business Funding', $dpkAll, 'Divisi & Cabang', 'Dana & Jasa'),
    $row(11, 'Dashboard Hubla', 'Dashboard Divisi Hubungan Kelembagaan.', 'rekap produk kelembagaan (month to month dan year on year), deposito 5 lembaga, deposito korporasi (Pemda, swasta), nominatif deposito, giro dan transaksi outgoing.', $viewDownload, 'Dashboard DPK', 'Business Funding', $dpkDivisi, 'Divisi', 'Hubungan Kelembagaan'),
    $row(12, 'Dashboard Perolehan FO Akumulasi', 'Akumulasi perolehan Funding Officer terhadap baseline.', 'realisasi nasabah prioritas dan non prioritas, baseline prioritas dan non prioritas, nominatif data kelolaan FO per wilayah/cabang dan tahun baseline.', $viewDownload, 'Dashboard DPK', 'Business Funding', $dpkAll, 'Divisi & Cabang', 'Dana & Jasa'),
    $row(13, 'Dashboard Portofolio', 'Portofolio nasabah DPK.', 'dua view: Dashboard Portofolio Nasabah dan Dashboard TOP Portofolio (nasabah dengan portofolio terbesar).', $viewOnly, 'Dashboard DPK', 'Business Funding', 'Divisi Manajemen Risiko', 'Divisi', 'Dana & Jasa'),
    $row(14, 'Div Opr', 'Histori saldo COA (Divisi Operasi).', 'saldo per COA, mata uang dan wilayah dalam rentang tanggal: tanggal data, nama COA, nominal dan nominal ekuivalen.', $viewDownload, 'Dashboard DPK', 'Business Funding', $dpkDivisi, 'Divisi', 'Operasi'),
    $row(15, 'divopr_depo_spesialrate', 'Daftar deposito dengan special rate (Divisi Operasi).', 'detail per deposito: cabang, nomor dan nama deposan, rate, mata uang, tanggal mulai, nominal ekuivalen per tanggal data.', $viewDownload, 'Dashboard DPK', 'Business Funding', $dpkDivisi, 'Divisi', 'Operasi'),
    $row(16, 'divopr-saldo-adktnp', 'Saldo harian ADKTNP (Divisi Operasi).', 'per tanggal: bank, jenis bank, cabang, wilayah, rekening dan nama rekening, dalam format Excel dan textfile ADKTNP.', $downloadOnly, 'Dashboard DPK', 'Business Funding', $dpkDivisi, 'Divisi', 'Operasi'),
    $row(17, 'Golden Record Nasabah', 'Golden record nasabah giro dan tabungan terkait kepemilikan pinjaman.', 'jumlah rekening dan saldo, jumlah transaksi per bulan, tiering saldo, serta detail nasabah yang sudah dan belum memiliki pinjaman.', $viewDownload, 'Dashboard DPK', 'Business Funding', 'Divisi Audit Intern, Divisi Dana dan Jasa, Divisi Manajemen Jaringan dan Layanan', 'Divisi', 'Dana & Jasa'),
    $row(18, 'perolehan baru FO New', 'Perolehan rekening baru oleh Funding Officer.', 'jumlah rekening baru deposito, giro, tabungan, tabungan prioritas dan JConnect Mobile v.2 per FO dan cabang per bulan.', $viewOnly, 'Dashboard DPK', 'Business Funding', $dpkAll, 'Divisi & Cabang', 'Distribusi Bisnis & Service Excellent'),
    $row(19, 'TKB - Divisi Operasi', 'Data laporan TKB Divisi Operasi.', 'dana Pemda, data DPK Antasena, dan 50 top deposan per bulan.', $viewDownload, 'Dashboard DPK', 'Business Funding', $dpkDivisi, 'Divisi', 'Operasi'),
    $row(20, 'Dashboard Siklus', 'Dashboard Siklus (total dan per cabang).', 'NOA dan saldo (miliar Rp) per periode, wilayah, cabang, tipe nasabah dan keterangan.', $viewOnly, '# DEV', 'Business Funding', 'admin', 'Admin', 'Owner TI - Permintaan Direksi'),

    // ===================== Dashboard Kredit (Business Lending) =====================
    $row(21, 'dagulir exe', 'Rekonsiliasi Kredit Dana Bergulir (Dagulir) executing.', 'rekap loan vs rekening: pembayaran pokok loan vs pembayaran ke biro dan selisihnya, serta rincian per loan dan cabang.', $viewDownload, 'Dashboard Kredit', 'Business Lending', 'Divisi Administrasi Kredit, Divisi Kredit Ritel dan Menengah', 'Divisi', 'Ritel'),
    $row(22, 'Dashboard Konsumer', 'Laporan Divisi Kredit Konsumer per debitur.', 'NOA, plafond, outstanding pokok; detail debitur: sub segmen, skim, AO, sektor ekonomi, asuransi, tanggal realisasi dan jatuh tempo, biaya admin, pendapatan bunga.', $viewDownload, 'Dashboard Kredit', 'Business Lending', $kreditDivisi, 'Divisi', 'Konsumer'),
    $row(23, 'dashboard konsumer extd', 'Data loan konsumer extended.', 'detail per loan: CIF, nama, tipe, skim, pekerjaan, polis asuransi, AO, kolektibilitas, jangka waktu, rate, plafond.', $viewDownload, 'Dashboard Kredit', 'Business Lending', 'Divisi Audit Intern, Divisi Dana dan Jasa, Divisi Digital Banking, Divisi Kredit Komersial, Korporasi dan Sindikasi, Divisi Kredit Konsumer, Divisi Kredit Mikro, Divisi Kredit Ritel dan Menengah', 'Divisi', 'Konsumer'),
    $row(24, 'Dashboard Kredit Cabang', 'Dashboard kredit dan nominatif kredit per cabang.', 'KYD posisi Desember tahun lalu, KYD kontraktual, realisasi dan perolehan kredit per periode, growth (%), realisasi per bulan; per konvensional/syariah, wilayah, cabang/capem, segmen dan skim; tab nominatif kredit.', $viewDownload, 'Dashboard Kredit', 'Business Lending', $kreditAll, 'Divisi & Cabang', 'Konsumer, Mikro, Ritel'),
    $row(25, 'Dashboard Mikro', 'Monitoring kualitas dan pencapaian kredit segmen mikro.', 'NOA, nominal dan growth kredit Dalam Pengawasan Khusus (kol 2) dan NPL per cabang/capem; target vs pencapaian KYD per segmen dan cabang (fintek/non fintek), growth YtD dan MtM, pertumbuhan kredit dan pencapaian kinerja.', $viewOnly, 'Dashboard Kredit', 'Business Lending', 'Kredit', 'Divisi', 'Mikro'),
    $row(26, 'Dashboard New Customer Validation - Fronting Agent', 'Validasi calon nasabah baru melalui fronting agent.', 'pencarian NIK; nama, dinas/instansi, kota/kabupaten, status pegawai, no HP, email, cabang rekening dan cabang payroll.', $viewOnly, 'Dashboard Kredit', 'Business Lending', 'Kredit', 'Divisi', 'Konsumer'),
    $row(27, 'Dashboard Penagihan', 'Penagihan pinjaman angsuran.', 'status rekening pencairan dan rekening angsuran (saldo cukup/kurang) serta total tunggakan per jenis dan cabang.', $viewDownload, 'Dashboard Kredit', 'Business Lending', 'KC', 'Cabang', 'Owner TI'),
    $row(28, 'Dashboard Performance AO', 'Rekap kelolaan Account Officer.', 'per AO (NIP, nama, cabang AO data HC, segmentasi AO): jumlah loan, outstanding, nilai wajar, nilai CKPN, plafond, pendapatan bunga akumulasi, tunggakan pokok dan bunga, per wilayah/cabang pinjaman dan tanggal realisasi.', $viewDownload, 'Dashboard Kredit', 'Business Lending', $kreditAll, 'Divisi & Cabang', 'Konsumer, Mikro, Ritel'),
    $row(29, 'Dashboard Realisasi AO Mikro', 'Realisasi kredit mikro per AO.', 'detail loan: CIF, nama, skim, segmen, kolektibilitas, tanggal mulai dan akhir, frekuensi, kode AO/NIP sales, plafond.', $viewDownload, 'Dashboard Kredit', 'Business Lending', 'Divisi Kredit Mikro', 'Divisi', 'Mikro'),
    $row(30, 'Dashboard rekap AO berdasarkan Cabang AO', 'Rekap realisasi AO berdasarkan cabang AO.', 'per wilayah, cabang/capem, NIP, nama AO dan segmentasi: jumlah realisasi dalam rentang tanggal.', $viewDownload, 'Dashboard Kredit', 'Business Lending', $kreditAll, 'Divisi & Cabang', 'Konsumer, Mikro, Ritel'),
    $row(31, 'Dashboard Top Up KMG', 'Top up Kredit Multiguna (KMG).', 'total nasabah dan nett per kategori (mis. top up); detail per nasabah: wilayah, cabang/capem, CIF, nama, tanggal top up, nett, plafond, OS belum lunas, per rentang tanggal realisasi.', $viewDownload, 'Dashboard Kredit', 'Business Lending', $kreditDivisi, 'Divisi', 'Mikro'),
    $row(32, 'krd', 'Pergerakan NPL dan pelunasan kredit.', 'NPL per bulan dan per segmen, NPL per wilayah/cabang, serta jumlah loan pelunasan kredit.', $viewOnly, 'Dashboard Kredit', 'Business Lending', 'Divisi Administrasi Kredit', 'Divisi', 'Administrasi Kredit'),
    $row(33, 'kredit cbt', 'Data kredit cabut.', 'detail per loan: wilayah, cabang, CIF, divisi, jenis guna, AO, kolektibilitas, segmen, skim, data asuransi (nopolis, asr_extend, asr_fasilitas).', $viewDownload, 'Dashboard Kredit', 'Business Lending', $kreditCabangUtama, 'Divisi & Cabang', 'Cabang Utama'),
    $row(34, 'unduh fas non loan', 'Fasilitas kredit yang belum ditarik (fasilitas tanpa loan).', 'fasilitas tanpa kredit dan fasilitas dalam CIF yang punya loan tapi belum ditarik: CIF, nomor fasilitas, nama, revolving, AO, tanggal approve dan expired, plafond dan used.', $downloadOnly, 'Dashboard Kredit', 'Business Lending', 'Divisi Administrasi Kredit, Divisi Kredit Konsumer, Divisi Kredit Ritel dan Menengah', 'Divisi', 'Konsumer, Mikro, Ritel'),
    $row(35, 'Unduh Kredit', 'Unduhan data kredit konvensional dan syariah.', 'rekap segmentasi KYD per wilayah; data kredit per loan: wilayah, cabang, CIF, jenis guna, sektor ekonomi, AO, kolektibilitas, skim, rate, plafond, OS pokok dan bunga, nilai wajar, CKPN, tunggakan, fasilitas.', $downloadOnly, 'Dashboard Kredit', 'Business Lending', $kreditDivisi, 'Divisi', 'Konsumer, Mikro, Ritel'),

    // ===================== Dashboard E-Channel / ETPD (Digital Services) =====================
    $row(36, 'Dashboard Agen Laku Pandai', 'Laporan agen Laku Pandai.', 'jumlah agen dan agen aktif, pencapaian NOA BSA (target, realisasi, gap), keaktifan agen per wilayah, cabang dan KF.', $viewDownload, 'Dashboard E-Channel', 'Digital Services', 'KC, Divisi Digital Banking, Divisi Manajemen Jaringan dan Layanan', 'Divisi & Cabang', 'Distribusi Bisnis & Service Excellent'),
    $row(37, 'Dashboard Fitur e-Channel', 'Pemakaian fitur e-channel.', 'jumlah transaksi dan nominal per fitur mobile banking, internet banking, SMS banking dan ATM per wilayah/cabang.', $viewOnly, 'Dashboard E-Channel', 'Digital Services', 'admin', 'Admin', 'Operasi'),
    $row(38, 'Dashboard Nasabah Kredit Mikro & Ritel non-Mobile Banking', 'Debitur kredit mikro/ritel yang belum menggunakan mobile banking.', 'total debitur dan debitur non mobile banking per segmen dan wilayah dalam periode tertentu.', $viewDownload, 'Dashboard E-Channel', 'Digital Services', 'admin', 'Admin', 'Digital Banking'),
    $row(39, 'Dashboard Perolehan e-Channel dan Fee', 'Dashboard e-channel: transaksi, user dan fee.', 'jumlah dan nominal transaksi per channel per bulan, user, fee base income, monitoring biaya kartu, trend line analysis.', $viewOnly, 'Dashboard E-Channel', 'Digital Services', 'admin', 'Admin', 'Dana & Jasa'),
    $row(40, 'Dashboard User, Transaksi dan Fee Base Channel', 'Rekap user, transaksi dan fee based per channel.', 'jumlah transaksi, nominal, fee based bruto dan user per channel (IB corporate, IB individu, mobile banking, SMS banking), detail transaksi, top 10.', $viewOnly, 'Dashboard E-Channel', 'Digital Services', 'KC, Divisi Dana dan Jasa, Divisi Digital Banking, Divisi Manajemen Risiko, Divisi Perencanaan Strategis & Manajemen, Divisi Teknologi Informasi (Monitoring)', 'Divisi & Cabang', 'Dana & Jasa'),
    $row(41, 'mb', 'Perkembangan user mobile banking.', 'user mobile banking per jenis MB dan cabang, akumulasi per periode.', $viewOnly, 'Dashboard E-Channel', 'Digital Services', 'admin', 'Admin', 'Digital Banking'),
    $row(42, 'New_Account_MB', 'Pembukaan rekening baru dan aktivasi mobile banking.', 'total pembukaan rekening, rekening yang sudah dan belum mobile banking per wilayah, cabang, direktur kelolaan dan tipe rekening.', $viewDownload, 'Dashboard E-Channel', 'Digital Services', 'admin', 'Admin', 'Digital Banking'),
    $row(43, 'Pelaporan QRIS (versi MJL)', 'Laporan QRIS versi MJL.', 'jumlah merchant (aktif/tidak aktif), jumlah dan nominal transaksi per wilayah, cabang, KF, serta detail per cabang.', $viewDownload, 'Dashboard E-Channel', 'Digital Services', 'KC, Divisi Dana dan Jasa, Divisi Digital Banking, Divisi Manajemen Jaringan dan Layanan, Divisi Perencanaan Strategis & Manajemen, Manajemen', 'Divisi & Cabang', 'Distribusi Bisnis & Service Excellent'),
    $row(44, 'Pelaporan QRIS versi 1.1', 'Laporan QRIS versi 1.1.', 'dashboard utama QRIS per kota/kabupaten, laporan penyelenggaraan QRIS format BI, dan daftar rekening merchant QRIS undefined.', $viewDownload, 'Dashboard E-Channel', 'Digital Services', 'Divisi Dana dan Jasa, Divisi Digital Banking, Divisi Perencanaan Strategis & Manajemen, Unit Usaha Syariah', 'Divisi', 'Dana & Jasa'),
    $row(45, 'Potensial MB', 'Rekening potensial mobile banking.', 'rekap user MB per wilayah/cabang, perolehan MB atas rekening potensial, dan daftar rekening potensial per jenis rekening.', $viewDownload, 'Dashboard E-Channel', 'Digital Services', 'admin', 'Admin', 'Digital Banking'),
    $row(46, 'QRIS Syariah', 'Transaksi QRIS Unit Usaha Syariah.', 'transaksi QRIS syariah per switching (AJ, Rintis) per tanggal: merchant, kategori, deskripsi, status, MPAN, nominal, nomor rekening.', $viewDownload, 'Dashboard E-Channel', 'Digital Services', 'Unit Usaha Syariah', 'Divisi', 'Unit Usaha Syariah'),
    $row(47, 'Transaksi harian QRIS Merchant', 'Detail transaksi harian QRIS per merchant.', 'reference number, nama merchant, customer PAN, merchant PAN dan nominal per tanggal data.', $viewDownload, 'Dashboard E-Channel', 'Digital Services', 'Divisi Dana dan Jasa', 'Divisi', 'Dana & Jasa'),
    $row(48, 'Dashboard ETPD', 'Monitoring Elektronifikasi Transaksi Pemerintah Daerah (ETPD).', 'nominal transaksi (juta Rp) per kanal: teller, agen bank, ATM/CRM, EDC, UE reader, e-banking, QRIS, e-commerce, per KPw (Surabaya, Malang, Kediri, Jember).', $viewOnly, 'Dashboard ETPD', 'Digital Services', 'KC, Divisi Audit Intern, Divisi Digital Banking', 'Divisi & Cabang', 'Digital Banking'),
    $row(49, 'Realisasi Rekening ETPD Detail Per Rekening', 'Realisasi ETPD per rekening Pemda.', 'jumlah rekening, nomor dan nama rekening, nama daerah, wilayah KPw, transaksi per kanal (agen bank, ATM/CRM, dst).', $viewDownload, 'Dashboard ETPD', 'Digital Services', 'KC, Divisi Manajemen Jaringan dan Layanan', 'Divisi & Cabang', 'Digital Banking'),
    $row(50, 'New JConnect MB', 'Konsolidasi perolehan JConnect di 5 segmen.', 'total perolehan JConnect total dan per cabang, total CIF, V3 konversi, V3 akuisisi, V2 only, unregistered dan new account per direktur, jenis bank, wilayah dan cabang.', $viewOnly, '# DEV', 'Digital Services', 'All Users', 'Divisi & Cabang', 'Digital Banking'),
    $row(51, 'DAILY GROWTH MB V3', 'Pertumbuhan harian user registrasi JConnect v3.0.', 'user registrasi harian per platform (Android, iOS) dan growth total user per tanggal.', $viewOnly, '# DEV', 'Digital Services', 'Divisi Digital Banking, Divisi Teknologi Informasi (Monitoring)', 'Divisi', 'Owner TI - Permintaan Direksi'),
    $row(52, 'DASHBOARD TRANSAKSI MERCHANT - BANK BANTEN', 'Transaksi merchant Bank Banten.', 'total nominal transaksi, growth nominal transaksi dan growth nominal fee, serta detail transaksi merchant per periode data.', $viewDownload, '# DEV', 'Digital Services', 'admin', 'Admin', 'Owner TI - Permintaan Direksi'),

    // ===================== Dashboard Keuangan / Rekonsiliasi / Treasuri (Finance Performance) =====================
    $row(53, 'Dashboard Keuangan [ALL]', 'Kinerja keuangan bank menyeluruh.', 'highlight, posisi dan YoY neraca, laba rugi, KYD, NPL, rasio, rupa-rupa aset, realisasi vs target RBB dan perbandingan peers per wilayah/cabang.', $viewOnly, 'Dashboard Keuangan', 'Finance Performance', 'admin', 'Admin', 'Perencanaan Strategis & Manajemen Kerja'),
    $row(54, 'divmjl coa kinerja daily', 'Nominal harian COA kinerja (versi MJL).', 'wilayah, cabang, COA, mata uang, nama COA, tanggal data, nominal dan nominal ekuivalen.', $viewDownload, 'Dashboard Keuangan', 'Finance Performance', 'Divisi Manajemen Jaringan dan Layanan, Divisi Operasi', 'Divisi', 'Distribusi Bisnis & Service Excellent'),
    $row(55, 'J-PRIME Jatim Performance Reporting and Integrated Monitoring Engine', 'Jatim Performance Reporting and Integrated Monitoring Engine (J-PRIME).', 'realisasi vs target dan deviasi (DtD, MtD, YoY) per direktorat, wilayah dan cabang; grafik DPK, kredit, laba rugi; cash in/out; QRIS; perkembangan DPK, kredit dan NPL cabang (juta Rp).', $viewOnly, 'Dashboard Keuangan', 'Finance Performance', 'KC, KP, Divisi Audit Intern, Divisi Digital Banking, Divisi Kredit Komersial, Korporasi dan Sindikasi, Manajemen, Unit Usaha Syariah', 'Divisi & Cabang', 'Perencanaan Strategis & Manajemen Kerja'),
    $row(56, 'Laporan Keuangan', 'Laporan keuangan neraca dan laba rugi.', 'saldo IDR, valas dan saldo akhir per pos; konvensional/syariah; data gabungan atau per cabang; per kode laporan dan tanggal.', $viewDownload, 'Dashboard Keuangan', 'Finance Performance', 'Divisi Perencanaan Strategis & Manajemen, Unit Usaha Syariah', 'Divisi', 'Akuntansi & Manajemen Keuangan'),
    $row(57, '(DEV) DASHBOARD REKON ENTERPRISE - KAI', 'Rekonsiliasi transaksi Bank Jatim dengan mitra KAI (versi development).', 'total record; data biller vs CBS per transaksi: tanggal, nomor referensi, kode transaksi, rekening debet dan kredit, channel, keterangan, nominal.', $viewDownload, 'Dashboard Rekonsiliasi', 'Finance Performance', 'Divisi Operasi', 'Divisi', 'Operasi'),
    $row(58, '(DEV) DASHBOARD REKON ENTERPRISE - TELKOM', 'Rekonsiliasi transaksi Bank Jatim dengan mitra Telkom (versi development).', 'data biller vs CBS per transaksi: tanggal, nomor Telkom, nomor referensi, rekening debet dan kredit, deskripsi, keterangan, nominal.', $viewDownload, 'Dashboard Rekonsiliasi', 'Finance Performance', 'Divisi Operasi', 'Divisi', 'Operasi'),
    $row(59, '(DEV) DASHBOARD REKON ENTERPRISE - TELKOMSEL', 'Rekonsiliasi transaksi Bank Jatim dengan mitra Telkomsel (versi development).', 'data biller vs CBS per transaksi: tanggal, nomor HP, nomor referensi, ID transaksi, rekening debet dan kredit, deskripsi, keterangan, tipe (mis. penyelesaian rekon), nominal.', $viewDownload, 'Dashboard Rekonsiliasi', 'Finance Performance', 'Divisi Operasi', 'Divisi', 'Operasi'),
    $row(60, '(DEV) DASHBOARD REKON ENTERPRISE - XL', 'Rekonsiliasi transaksi Bank Jatim dengan mitra XL (versi development).', 'data biller vs CBS per transaksi: tanggal, nomor referensi, nomor telepon, rekening nasabah dan tujuan, jenis transaksi, channel pembayaran, keterangan, nominal.', $viewDownload, 'Dashboard Rekonsiliasi', 'Finance Performance', 'admin', 'Admin', 'Operasi'),
    $rekon(61, 'DASHBOARD REKON - QRIS DISHUB', 'Rekonsiliasi transaksi Bank Jatim dengan Dinas Perhubungan untuk transaksi QRIS.', 'Divisi Operasi, Unit Usaha Syariah', 'Divisi'),
    $rekon(62, 'DASHBOARD REKON - RTGS', 'Rekonsiliasi transaksi Bank Jatim dengan data BI untuk transaksi RTGS.', 'Divisi Operasi, Unit Usaha Syariah', 'Divisi'),
    $rekon(63, 'DASHBOARD REKON - SKNBI', 'Rekonsiliasi transaksi Bank Jatim dengan data BI untuk transaksi SKNBI (kliring).', 'Divisi Operasi, Unit Usaha Syariah', 'Divisi'),
    $rekon(64, 'DASHBOARD REKON ENTERPRISE - ASABRI', 'Rekonsiliasi transaksi Bank Jatim dengan mitra ASABRI.', 'Divisi Operasi', 'Divisi'),
    $rekon(65, 'DASHBOARD REKON ENTERPRISE - BPJS KESEHATAN', 'Rekonsiliasi transaksi Bank Jatim dengan mitra BPJS Kesehatan.', 'Divisi Operasi', 'Divisi'),
    $rekon(66, 'DASHBOARD REKON ENTERPRISE - INDOSAT', 'Rekonsiliasi transaksi Bank Jatim dengan mitra Indosat.', 'Divisi Operasi', 'Divisi'),
    $rekon(67, 'DASHBOARD REKON ENTERPRISE - MEGA FINANCE', 'Rekonsiliasi transaksi Bank Jatim dengan mitra Mega Finance.', 'Divisi Operasi', 'Divisi'),
    $rekon(68, 'DASHBOARD REKON ENTERPRISE - MNC INDOVISION', 'Rekonsiliasi transaksi Bank Jatim dengan mitra MNC Indovision.', 'Divisi Operasi, Unit Usaha Syariah', 'Divisi'),
    $rekon(69, 'DASHBOARD REKON ENTERPRISE - QRIS AJ', 'Rekonsiliasi transaksi Bank Jatim dengan switching Artajasa (AJ) untuk transaksi QRIS.', 'Divisi Operasi, Unit Usaha Syariah', 'Divisi'),
    $rekon(70, 'DASHBOARD REKON ENTERPRISE - QRIS ON US', 'Rekonsiliasi transaksi Bank Jatim dengan data acquirer/issuer untuk transaksi QRIS on us (sesama Bank Jatim).', 'Divisi Operasi, Unit Usaha Syariah', 'Divisi'),
    $rekon(71, 'DASHBOARD REKON ENTERPRISE - QRIS RINTIS', 'Rekonsiliasi transaksi Bank Jatim dengan switching Rintis untuk transaksi QRIS.', 'Divisi Operasi', 'Divisi'),
    $rekon(72, 'DASHBOARD REKON ENTERPRISE - SMARTFREN', 'Rekonsiliasi transaksi Bank Jatim dengan mitra Smartfren.', 'Divisi Operasi, Unit Usaha Syariah', 'Divisi'),
    $rekon(73, 'DASHBOARD REKON ENTERPRISE - TASPEN', 'Rekonsiliasi transaksi Bank Jatim dengan mitra Taspen.', 'Divisi Operasi', 'Divisi'),
    $rekon(74, 'DASHBOARD REKON ENTERPRISE - THREE', 'Rekonsiliasi transaksi Bank Jatim dengan mitra Three.', 'admin', 'Admin'),
    $rekon(75, 'DASHBOARD REKON ENTERPRISE - VA E-SAMSAT', 'Rekonsiliasi transaksi Bank Jatim dengan Samsat untuk transaksi Virtual Account e-Samsat.', 'admin', 'Admin'),
    $rekon(76, 'REKON - SKNBI (PENDING)', 'Rekonsiliasi transaksi Bank Jatim dengan data BI untuk transaksi SKNBI (status pending).', 'admin', 'Admin'),
    $rekon(77, 'REKON ENTERPRISE - ACS (pending)', 'Rekonsiliasi transaksi Bank Jatim dengan mitra ACS (status pending).', 'admin', 'Admin'),
    $rekon(78, 'REKON ENTERPRISE - BPJS KETENAGAKERJAAN (pending)', 'Rekonsiliasi transaksi Bank Jatim dengan mitra BPJS Ketenagakerjaan (status pending).', 'admin', 'Admin'),
    $row(79, 'Dashboard Cash Management', 'Cash management harian (Divisi Treasury).', 'summary dan detail daily cash management: posisi kas dan DPK per wilayah dan cabang/capem per tanggal dan bulan, konvensional/syariah.', $viewDownload, 'Dashboard Treasuri', 'Finance Performance', 'Divisi Treasury', 'Divisi', 'Treasury'),
    $row(80, 'Dashboard Keuangan dan Rasio Likuiditas', 'Pos keuangan dan rasio likuiditas.', 'pos keuangan dan rasio dalam rupiah, valas dan total, dibandingkan antartanggal, per jenis/cabang.', $viewOnly, 'Dashboard Treasuri', 'Finance Performance', 'Divisi Treasury', 'Divisi', 'Treasury'),
    $row(81, 'Get Data Harian Treasuri', 'Penarikan data harian Treasury.', '13 view: kas harian, DPK harian, deposito Pemda, inflow/outflow, top 10 DPK, top 10 transaksi. Saat dicek, semua view kosong.', $downloadOnly, 'Dashboard Treasuri', 'Finance Performance', 'Divisi Treasury', 'Divisi', 'Treasury'),
    $row(82, 'Dashboard Anggaran dan Biaya TI', 'Anggaran dan realisasi biaya Divisi TI.', 'per rekening/nama anggaran: anggaran, realisasi, kelebihan, sisa dan persentase penggunaan anggaran; detail transaksi biaya Divisi TI.', $viewDownload, 'Rekening Biaya', 'Finance Performance', 'Divisi Teknologi Informasi', 'Divisi', 'Teknologi Informasi'),
    $row(83, 'DASHBOARD MONITORING REKENING BIAYA', 'Monitoring rekening biaya per divisi.', 'total anggaran, realisasi, sisa anggaran, melebihi anggaran dan pencapaian per divisi.', $viewOnly, 'Rekening Biaya', 'Finance Performance', 'Divisi International Banking, Divisi Kredit Komersial, Korporasi dan Sindikasi, Divisi Kredit Ritel dan Menengah, Divisi Manajemen Risiko, Divisi Perencanaan Strategis dan Manajemen, Divisi Teknologi Informasi, Unit Usaha Syariah', 'Divisi', 'Perencanaan Strategis & Manajemen Kerja'),
    $row(84, 'DASHBOARD KINERJA & PERKEMBANGAN NPL', 'Perkembangan NPL dan summary kinerja.', 'NPL tercatat dan kontraktual per kelompok (konsolidasi), jenis, wilayah dan cabang; perkembangan YtD, MtD dan DtD (juta Rp).', $viewOnly, '# DEV', 'Finance Performance', 'All Users', 'Divisi & Cabang', 'Owner TI - Permintaan Direksi'),

    // ===================== QOS / Default / Report =====================
    $row(85, 'QOS BI-Fast', 'Quality of service transaksi BI-FAST.', 'per BIC dan jam: jumlah dan nominal transaksi sukses/gagal, incoming dan outgoing sukses/gagal, total transaksi.', $viewOnly, 'Dashboard QOS Transaksi', 'Report', 'Divisi Digital Banking, Divisi Teknologi Informasi', 'Divisi', 'Owner TI'),
    $row(86, 'QOS QRIS DSI', 'Quality of service transaksi QRIS DSI.', 'transaksi QRIS off us dan on us, detail incoming/outgoing, rekap response code, jumlah transaksi per bank dan per jam.', $viewOnly, 'Dashboard QOS Transaksi', 'Report', 'Divisi Digital Banking, Divisi Teknologi Informasi', 'Divisi', 'Owner TI'),
    $row(87, 'Northwind Sales Dashboard', 'Dashboard contoh bawaan Tableau di project Default, bukan data bank.', 'data dummy Northwind (penjualan).', 'Tidak ada kebutuhan bisnis', 'Default', 'Report', 'ALL', 'Divisi & Cabang', 'Default Tableau'),
    $row(88, 'Portal Dashboard Tableau', 'Dashboard di project Default (bawaan Tableau).', 'perlu dicek.', $viewOnly, 'Default', 'Report', 'admin', 'Admin', 'Sudah ada dashboard pengganti'),
    $row(89, 'Dasboard Pendapatan dan Beban Bunga', 'Pendapatan bunga dan beban bunga bank.', 'pendapatan bunga per segmen dan growth, top 10 debitur, detail debitur pendapatan bunga; beban bunga per tipe nasabah dan tipe produk, top 10 CIF bunga terbesar, detail nasabah.', $viewDownload, 'Report', 'Report', 'Unit Usaha Syariah', 'Divisi', 'Perencanaan Strategis & Manajemen Kerja'),
    $row(90, 'Dash UUS Proyeksi Bagi Hasil', 'Proyeksi bagi hasil dana pihak ketiga Unit Usaha Syariah.', 'per jenis rekening: saldo rata-rata, alokasi pendapatan, persentase nisbah penyimpan dana dan proyeksi bagi hasil per periode data.', $viewOnly, 'Report', 'Report', 'Unit Usaha Syariah', 'Divisi', 'Unit Usaha Syariah'),
    $row(91, 'Dashboard BOPO', 'Rasio BOPO (beban operasional terhadap pendapatan operasional).', 'pendapatan bunga, pendapatan operasional selain bunga, pendapatan/beban penjabaran transaksi valas, beban bunga, beban operasional selain bunga, rasio BOPO dan YoY per jenis bank, wilayah dan cabang.', $viewOnly, 'Report', 'Report', 'Divisi Umum, Divisi Akuntansi Manajemen Keuangan', 'Divisi', 'Akuntansi & Manajemen Keuangan'),
    $row(92, 'Dashboard MTA', 'Mutasi rekening koran mitra JConnect Remittance (Money Transfer Agent).', 'rekap mutasi rekening; detail per transaksi: entry/posting date, referensi, rekening sumber dan rekening lawan, deskripsi, user, debet, kredit, saldo per rentang tanggal.', $viewDownload, 'Report', 'Report', 'Divisi International Banking', 'Divisi', 'International Banking'),
    $row(93, 'Dashboard Rekon Selisih PKB Agen Jatim', 'Rekonsiliasi selisih PBK untuk Agen Jatim.', 'per transaksi: tanggal, deskripsi, nomor referensi, user, cabang, program, nominal.', $viewDownload, 'Report', 'Report', 'Divisi Operasi', 'Divisi', 'Digital Banking'),
    $row(94, 'Ecosystem Pendidikan', 'Ekosistem pendidikan (portofolio dan rekanan education).', 'portofolio education per wilayah/cabang: CIF, rekening, nasabah, produk, nominal; transaksi rekanan education incoming (pengirim, bank, penerima, channel, nominal) per bulan.', $viewDownload, 'Report', 'Report', 'Unit Usaha Syariah', 'Divisi', 'Digital Banking'),
    $row(95, 'EOD BOD PUBLISH', 'Monitoring proses End of Day (EOD) dan Beginning of Day (BOD).', 'jam mulai, jam selesai dan durasi EOD dan BOD serta total durasi per tanggal; detail locking GL rekening debet/kredit (jumlah dan waktu locking).', $viewOnly, 'Report', 'Report', 'Divisi Teknologi Informasi', 'Divisi', 'Teknologi Informasi'),
    $row(96, 'Informasi Efilling', 'Monitoring surat masuk dan disposisi e-Filling.', 'jumlah surat masuk, diterima, sudah/belum disposisi dan sudah/belum action per pegawai dan sub divisi; lama disposisi (0-2, 3-5, 6-10 hari).', $viewOnly, 'Report', 'Report', 'admin', 'Admin', 'Teknologi Informasi'),
    $row(97, 'Monitoring Coa Biaya', 'Monitoring anggaran dan realisasi biaya per COA.', 'total anggaran vs realisasi per COA, realisasi per wilayah dan per cabang/capem, peta dan detail; filter tanggal, COA dan cabang.', $viewDownload, 'Report', 'Report', 'Divisi Audit Intern, Divisi Digital Banking, Divisi Umum', 'Divisi', 'Umum'),
    $row(98, 'Monitoring Log DAWER', 'Log harian proses data warehouse (DAWER).', 'per tabel: jam mulai, jam selesai, durasi, jumlah record asal dan tujuan, selisih, per tanggal.', $viewOnly, 'Report', 'Report', 'Divisi Teknologi Informasi', 'Divisi', 'Teknologi Informasi'),
    $row(99, 'monitoring saldo rkud', 'Monitoring saldo Rekening Kas Umum Daerah (RKUD) dan SKPD.', 'per cabang, pemda, nomor dan nama rekening: saldo per tanggal.', $viewDownload, 'Report', 'Report', 'Divisi Audit Intern, Divisi Manajemen dan Layanan, Divisi Operasi', 'Divisi', 'Hubungan Kelembagaan'),
    $row(100, 'Performance Pemda', 'Portofolio dan transaksi Pemda.', 'portofolio rekening Pemda dan ASN per kabupaten/kota (rekening, produk, nominal), transaksi rekanan Pemda per wilayah/cabang.', $viewDownload, 'Report', 'Report', 'Unit Usaha Syariah', 'Divisi', 'Digital Banking'),
    $row(101, 'Performance Per Customer CIF', 'Kinerja per nasabah (CIF).', 'detail customer CIF, detail rekening per CIF dan detail loan per CIF.', $viewDownload, 'Report', 'Report', 'admin', 'Admin', 'Dana & Jasa'),
    $row(102, 'Rek Escrow', 'Monitoring rekening escrow.', 'per wilayah dan cabang: tipe rekening, plafond dan nominal blokir.', $viewOnly, 'Report', 'Report', 'Divisi Audit Intern', 'Divisi', 'Audit Internal'),
    $row(103, 'Report ETL Baru', 'Monitoring proses ETL harian per modul.', 'per jenis bank (JTM konvensional, JAS syariah) dan modul (DP, LN, RT, SE, GL): tanggal job, tanggal sistem, jam selesai, status job (done, not ok, process, waiting) dan total job.', $viewOnly, 'Report', 'Report', 'admin', 'Admin', 'Owner TI'),
    $row(104, 'RKUD', 'Posisi RKUD per jam.', 'data RKUD pada tanggal dan jam yang dipilih.', $viewOnly, 'Report', 'Report', 'Divisi Operasi, Unit Usaha Syariah', 'Divisi', 'Operasi'),
    $row(105, 'RPK Kredit', 'Nominatif kredit kolektibilitas/NPL untuk penagihan dan pemulihan kredit.', 'detail per loan: cabang, CIF, nama, tipe, kolektibilitas, segmen, skim, tanggal mulai dan akhir, day past due, plafond, OS pokok, tunggakan pokok/bunga/denda, no HP.', $viewDownload, 'Report', 'Report', 'Divisi Akuntansi dan Manajemen Keuangan, Divisi Manajemen Risiko, Divisi Penagihan dan Pemulihan Kredit, Unit Usaha Syariah', 'Divisi', 'Penagihan & Pemulihan Kredit'),
    $row(106, 'RSSA', 'Dashboard RSUD Dr. Saiful Anwar Malang.', 'portofolio rekening RSSA: CIF, nomor dan nama rekening, tipe rekening, saldo per tanggal.', $viewOnly, 'Report', 'Report', 'Divisi Dana Jasa', 'Divisi', 'Digital Banking'),
    $row(107, 'RSUD JATIM', 'Portofolio rekening RSUD milik Pemprov Jatim (IHC).', 'per RSUD: CIF, rekening, nama, jenis rekening dan nominal.', $viewOnly, 'Report', 'Report', 'KC, Divisi Dana Jasa, Divisi Manajemen dan Layanan, Divisi Teknologi Informasi', 'Divisi & Cabang', 'Digital Banking'),
    $row(108, 'summary cif_dpk_debitur', 'Ringkasan CIF DPK dan debitur.', 'jumlah per tipe dan produk, konvensional vs syariah dan total.', $viewDownload, 'Report', 'Report', 'Unit Usaha Syariah', 'Divisi', 'Unit Usaha Syariah'),
    $row(109, 'TT & BN', 'Sistem informasi transaksi treasury: Bank Notes (BN) dan Telegraphic Transfer (TT).', 'kinerja transaksi BN dan TT (target, realisasi, % terhadap target); nilai original transaksi per mata uang (nominal, realisasi volume dalam rupiah) per wilayah, unit kerja dan tanggal.', $viewOnly, 'Report', 'Report', 'Divisi Audit Intern, Divisi Operasi, Divisi Treasury', 'Divisi', 'Treasury'),
    $row(110, 'TT & BN Cabang', 'Sistem informasi transaksi treasury BN dan TT untuk level cabang.', 'sama dengan TT & BN: target, realisasi, % terhadap target dan nilai transaksi per mata uang, per wilayah dan unit kerja/cabang.', $viewOnly, 'Report', 'Report', 'KC, Divisi Audit Intern, Divisi Digital Banking, Divisi Manajemen Jaringan dan Layanan, Divisi Operasi, Divisi Treasury, Manajemen', 'Divisi & Cabang', 'Treasury'),
    $row(111, 'VA UWK', 'Rekap dan rekonsiliasi transaksi Virtual Account UWK.', 'rekap pembayaran VA per nomor VA: tanggal, nominal masuk, nominal splitter, selisih; rekonsiliasi pembayaran VA splitter (rekening R/P vs rekening splitter) dan detail transaksi.', $viewDownload, 'Report', 'Report', 'Cabang Utama Surabaya', 'Cabang', 'Digital Banking'),
    $row(112, '[WIP] Dashboard Restruk Loan 1', 'Monitoring kredit restrukturisasi (masih WIP).', 'per loan lama dan baru: CIF, nama, skim, segmentasi, plafond, outstanding, kolektibilitas saat ini vs seharusnya, nominal repayment, tanggal real, jatuh tempo, day past due, per wilayah/cabang.', $viewDownload, '# DEV', 'Report', 'Kredit', 'Divisi', 'Penagihan & Pemulihan Kredit'),
    $row(113, 'QRIS Crossborder', 'Pelaporan QRIS Crossborder Inbound merchant Bank Jatim (Divisi Dana & Jasa).', 'total transaksi dan nominal inbound, rata-rata nominal per transaksi, merchant aktif, tren harian, komposisi nominal per negara asal; filter negara asal, network dan cabang.', $viewOnly, '# DEV', 'Report', 'Divisi Dana dan Jasa', 'Divisi', 'Dana & Jasa'),
    $row(114, 'Dashboard Pengguna Dukcapil', 'Pemantauan pemakaian kuota Dukcapil (web portal dan web service).', 'jumlah akses yang dihitung dan tidak dihitung kuota; detail per tanggal, wilayah, cabang, username, NIK, status dan keterangan.', $viewDownload, '# DEV', 'Report', 'Divisi Kepatuhan & APU PPT PPPSPM', 'Divisi', 'Kepatuhan'),
    $row(115, 'MONITORING TABLEAU', 'Monitoring workbook dan user Tableau.', 'daftar workbook (nama, tanggal dibuat), user, dan detail akses per workbook.', $viewOnly, '# DEV', 'Report', 'admin', 'Admin', 'Owner TI'),
    $row(116, 'jprime eom', 'J-PRIME versi posisi akhir bulan.', 'realisasi vs target, YTD, YoY dan deviasi per periode, jaringan, wilayah dan cabang.', $viewOnly, '# DEV', 'Report', 'Divisi Perencanaan Strategis & Manajemen', 'Divisi', 'Perencanaan Strategis & Manajemen Kerja'),
    $row(117, 'Dashboard MAP', 'Dashboard Manajemen Anak Perusahaan (MAP).', 'kinerja keuangan anak perusahaan (Bank Banten, Bank Lampung, Bank NTB Syariah, Bank NTT, Bank Sultra): DPK, kredit, laba rugi, ekuitas, modal, ATMR, serta rasio NPF, ROA, ROE dan BOPO per periode.', $viewOnly, '# DEV', 'Report', 'Divisi Teknologi Informasi (Monitoring)', 'Divisi', 'Manajemen Anak Perusahaan'),
    $row(118, '06082026 - Dashboard AUM Prioritas', 'Dashboard Jatim Prioritas (nasabah prioritas).', 'portofolio AUM rata-rata, summary rekening prioritas kelolaan FO, daftar rekening dengan saldo rata-rata di bawah 250 juta, daftar ulang tahun nasabah prioritas.', $viewDownload, '# DEV', 'Report', 'admin', 'Admin', 'Dana & Jasa'),
    $row(119, 'MONITORING INSIDEN TELKOM', 'Monitoring rekening insiden Telkom.', 'kecukupan saldo rekening (cukup/tidak cukup), total rekening dan total tagihan per update data.', $viewOnly, '# DEV', 'Report', 'Divisi Manajemen Jaringan dan Layanan, Divisi Operasi, Divisi Teknologi Informasi (Monitoring)', 'Divisi', 'Owner TI'),
    $row(120, '20260326_DASHBOARD REALISASI NETT_DIV. KONSUMER', 'Realisasi kredit nett Divisi Kredit Konsumer.', 'realisasi kredit konsumer nett per produk & cabang.', $viewOnly, '# DEV', 'Report', 'Divisi Kredit Konsumer', 'Divisi', 'Konsumer'),
    $row(121, '20260429_DASH REKONSILIASI ASURANSI_DIV.ADK_', 'Rekonsiliasi asuransi kredit Divisi Administrasi Kredit.', 'data debitur & premi asuransi vs data perusahaan asuransi, selisih.', $viewDownload, '# DEV', 'Report', 'Divisi Administrasi Kredit', 'Divisi', 'Administrasi Kredit'),

    // ===================== Dashboard FDS (Risk) =====================
    $fdsRow(122, '01. Dashboard Memasatikan Safety Margin Agunan dan Pengikatan Sesuai Ketentuan ✓', 'FDS, kredit dengan total nilai agunan dan/atau asuransi yang tidak sesuai ketentuan.', 'debitur per wilayah, cabang dan skim, nilai agunan vs threshold jaminan (%), tanggal realisasi.'),
    $fdsRow(123, '03. Monitoring Kredit Berdasarkan Kolektibilitas ✓', 'FDS, monitoring kredit berdasarkan kolektibilitas.', 'NOA, outstanding dan plafond per kolektibilitas; detail loan: debitur, skim, tanggal realisasi dan jatuh tempo, hari tunggakan.'),
    $fdsRow(124, '04. Monitoring Kredit Berdasarkan Tanggal Realisasi ✓', 'FDS, monitoring kredit berdasarkan tanggal realisasi.', 'daftar kredit yang direalisasi pada rentang tanggal, per wilayah, cabang, kolektibilitas dan skim.'),
    $fdsRow(125, '05. Dashboard Monitoring Kredit Dengan Kolektibilitas Berbeda Pada 1 CIF ✓', 'FDS, kredit dengan kolektibilitas berbeda dalam satu CIF.', 'CIF, nama debitur, nomor loan, tanggal realisasi dan jatuh tempo, kolektibilitas dan outstanding tiap fasilitas.'),
    $fdsRow(126, '06. Dashboard Monitoring Kredit Dengan Tunggakan > 0 dan Kolektibilitas 1 ✓', 'FDS, kredit dengan tunggakan > 0 namun masih kolektibilitas 1.', 'CIF, nama nasabah, nomor loan, tanggal realisasi, kolektibilitas, hari tunggakan, plafond.'),
    $fdsRow(127, '07. Dashboard Monitoring Kredit dengan Outstanding Tetap ✓', 'FDS, kredit dengan outstanding tidak berubah.', 'kredit yang outstanding-nya sama antara tanggal posisi 1 dan 2, per wilayah/cabang.'),
    $fdsRow(128, '08. Dashboard Pengecekan Faktor Risiko Nasabah yang Tidak Sesuai ✓', 'FDS, faktor risiko nasabah yang tidak sesuai.', 'nasabah per kode risiko: kode dan deskripsi risiko, jenis rekening, per wilayah/cabang.'),
    $fdsRow(129, '13. Dashboard Monitoring Transaksi Rekening Perantara ✓', 'FDS, monitoring transaksi rekening perantara.', 'transaksi dari/ke rekening perantara dengan frekuensi minimum, rekening asal dan tujuan, per periode.'),
    $fdsRow(130, '15. Monitoring Pembukaan Rekening Tabungan / Giro ✓', 'FDS, monitoring pembukaan rekening tabungan/giro.', 'rekening baru per tipe rekening dengan setoran minimal tertentu, per wilayah/cabang dan tanggal.'),
    $fdsRow(131, '16. Dashboard Transaksi Atas Rekening Pasif ✓', 'FDS, transaksi pada rekening pasif.', 'rekening pasif yang bertransaksi: tanggal terakhir aktif, selisih hari, total mutasi debit, per wilayah/cabang.'),
    $fdsRow(132, '17. Dashboard Monitoring Kredit Kol 5 Yang Belum Dihapus Buku ✓', 'FDS, kredit kol 5 yang belum dihapus buku.', 'loan kol 5 di atas threshold: CIF, tanggal realisasi dan jatuh tempo, outstanding, per wilayah/cabang dan skim.'),
    $fdsRow(133, '18. Dashboard Monitoring Saldo ATM X', 'FDS, monitoring saldo ATM.', 'ATM dengan saldo di bawah batas bawah per wilayah/cabang dan user ATM dalam rentang tanggal.'),
    $fdsRow(134, '19. Dashboard Pengecekan Duplikasi Blokir Saldo Pada Suatu Rekening X', 'FDS, duplikasi blokir saldo pada satu rekening.', 'rekening dengan blokir saldo ganda: nomor rekening, CIF, nama nasabah, nominal dan keterangan blokir.'),
    $fdsRow(135, '20. Pengecekkan Data CIF yang Tidak Lengkap ✓', 'FDS, data CIF tidak lengkap.', 'CIF per tipe nasabah dengan daftar field yang terisi dan yang tidak terisi, per wilayah/cabang.'),
    $fdsRow(136, '21. Dashboard Monitoring Transaksi Rekening perantara yang Menggunakan E-channel ✓', 'FDS, transaksi rekening perantara melalui e-channel.', 'transaksi rekening perantara via e-channel per rekening asal dan periode, per wilayah/cabang.'),
    $fdsRow(137, '22. Monitoring Transaksi Umum ✓', 'FDS, monitoring transaksi biaya umum.', 'jumlah, total dan rata-rata nominal transaksi biaya keperluan dapur, pemeliharaan AC dan souvenir dibandingkan penambahan NOA DPK, per wilayah/cabang.'),
    $fdsRow(138, '23. Dashboard Pengecekan Nasabah Prioritas yang Tidak Memenuhi Ketentuan ✓', 'FDS, nasabah prioritas yang tidak memenuhi ketentuan.', 'CIF dan rekening prioritas, jenis rekening, nominal dibandingkan syarat prioritas.'),
    $fdsRow(139, '24. Reporting "Kartu ATM Aktif" yang belum diserahterimakan kepada nasabah lebih dari n hari', 'FDS, kartu ATM aktif yang belum diserahterimakan lebih dari n hari.', 'nomor rekening, nomor kartu, tanggal cetak dan aktif, selisih hari di atas threshold.'),
    $fdsRow(140, '25. Monitoring History Aktivitas Link Unlink User CAM X', 'FDS, histori aktivitas link/unlink user CAM.', 'user ID dan jumlah aktivitas link/unlink per periode, beserta tabel detail.'),
    $fdsRow(141, '26. Monitoring History Aktivitas Transaksi Berdasarkan Kartu X', 'FDS, histori transaksi berdasarkan nomor kartu.', 'transaksi per nomor kartu: waktu dan detail transaksi dalam rentang tanggal.'),
    $fdsRow(142, '27. Reporting Permintaan Reissue PIN Dalam Periode Tertentu X', 'FDS, permintaan reissue PIN dalam periode tertentu.', 'jumlah aktivitas reissue PIN per kartu/nasabah per periode.'),
    $fdsRow(143, '28. Reporting Koneksi Antara Kartu ATM dengan Nomor Rekening X', 'FDS, koneksi kartu ATM dengan nomor rekening.', 'nomor kartu ATM, CIF, nomor rekening, tanggal cetak dan aktif kartu.'),
    $fdsRow(144, '29. Reporting Kartu ATM Aktif Tetapi Tidak Melakukan Transaksi X', 'FDS, kartu ATM aktif tanpa transaksi.', 'nomor rekening dan kartu, tanggal transaksi terakhir, selisih hari di atas threshold.'),
    $fdsRow(145, '30. Dashboard Reporting Transaksi Penarikan ATM Dibandingkan Limit Harian X', 'FDS, penarikan ATM dibandingkan limit harian.', 'data penarikan ATM vs limit harian (threshold %) dan detail transaksi.'),
    $fdsRow(146, '31. Reporting Transaksi ATM di Lokasi/Kota Yang Berbeda X', 'FDS, transaksi ATM di lokasi/kota berbeda.', 'nomor rekening, nomor kartu, lokasi/kota dan waktu transaksi.'),
    $fdsRow(147, '32. Transaksi Nasabah', 'FDS, transaksi nasabah.', 'rincian transaksi per nomor rekening dalam rentang tanggal.'),

    // ===================== Dashboard Manrisk / Early Warning (Risk) =====================
    $row(148, 'Dashboard Monitoring QA', 'Monitoring Quality Assurance data (Divisi Manajemen Risiko).', 'tab DPK (detail rekening: rate, rate ganti, flag biaya, tenor), kredit, kredit dengan outstanding nol, spool BO23 dan jam transaksi, serta laporan keuangan month to month.', $viewDownload, 'Dashboard Manrisk', 'Risk', 'Divisi Manajemen Risiko', 'Divisi', 'Manajemen Risiko'),
    $row(149, 'dashboard bo23', 'Selisih transaksi BO23 vs PR16.', 'total user cetak, selisih transaksi, total BO23 vs PR16K, top 10 wilayah, record per kota/kabupaten, grafik 30 hari terakhir, performance user; export Excel/PDF.', $viewDownload, 'Dashboard Manrisk', 'Risk', 'Divisi Akuntansi dan Manajemen Keuangan, Divisi Audit Intern, Divisi Manajemen Risiko, Divisi Operasi', 'Divisi', 'Manajemen Risiko'),
    $row(150, 'Dashboard BRO', 'Beban Risiko Operasional (BRO).', 'rekap dan detail transaksi BRO per wilayah, cabang dan kode GL (nama GL, modul, user, nominal), serta warning BRO berdasarkan warning days.', $viewDownload, 'Dashboard Manrisk', 'Risk', 'admin', 'Admin', 'Manajemen Risiko'),
    $row(151, 'Dashboard Divisi ManRisk dan Divisi RPK', 'Dashboard kinerja konsolidasi konvensional dan syariah.', 'pencapaian kredit (modal kerja, investasi, konsumtif), kolektibilitas, hapus buku, pendapatan bunga, total kredit, NPL, DPK, rasio keuangan vs target, laba kotor.', $viewOnly, 'Dashboard Manrisk', 'Risk', 'Divisi Audit Intern, Divisi Manajemen Risiko', 'Divisi', 'Manajemen Risiko'),
    $row(152, 'Dashboard Enterprise Risk Management', 'Enterprise Risk Management (konsentrasi nasabah).', 'top 10 deposan, debitur dan merchant QRIS per wilayah dan grand total; perolehan QRIS (jumlah merchant, transaksi, nominal).', $viewOnly, 'Dashboard Manrisk', 'Risk', 'Divisi Manajemen Risiko', 'Divisi', 'Manajemen Risiko'),
    $earlyWarningRow(153, '1. Pengecekkan transaksi yang dilakukan setelah generate spool BO23K terakhir', 'Early warning, transaksi setelah generate spool BO23K terakhir.', 'transaksi per cabang/capem setelah generate spool BO23: tanggal, nomor referensi, user, waktu.'),
    $earlyWarningRow(154, '2. Dashboard Pengecekan Rate Deposito Tidak Sesuai Ketentuan', 'Early warning, rate deposito tidak sesuai ketentuan.', 'deposito per nasabah/CIF: nomor, tanggal mulai dan jatuh tempo, jangka waktu, rate ALCO vs rate diberikan.'),
    $earlyWarningRow(155, '3. Dashboard Pengecekan Deposito Dengan Tanggal Pembukaan Mundur (Back Date)', 'Early warning, deposito dengan tanggal pembukaan mundur (back date).', 'deposito dengan tanggal valuta mundur: rekening, tanggal valuta vs tanggal input, user, per wilayah/cabang.'),
    $earlyWarningRow(156, '4. Dashboard Pengecekan Pencairan Deposito pada CIF yang Berbeda', 'Early warning, pencairan deposito ke CIF berbeda.', 'user penempatan dan pencairan, CIF, rekening, tanggal deposito dan pencairan, nama rekening pencairan.'),
    $earlyWarningRow(157, '5. Dashboard Monitoring Break Deposito', 'Early warning, deposito ditutup sebelum jatuh tempo (break).', 'deposito dengan selisih hari minimal tertentu: nomor, tanggal tutup, nominal, per wilayah/cabang.'),
    $earlyWarningRow(158, '6. Dashboard Pembatalan Transaksi User', 'Early warning, pembatalan transaksi oleh user.', 'transaksi yang dibatalkan per user maker dan jenis (non system), tanggal transaksi, per wilayah/cabang.'),
    $earlyWarningRow(159, '7. Dashboard User Overide', 'Early warning, transaksi dengan override user.', 'tanggal, cabang, waktu, referensi, keterangan transaksi dan program, nomor rekening, user transaksi dan user otorisasi.'),
];

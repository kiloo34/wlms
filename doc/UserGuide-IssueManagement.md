
## 1. Memahami Tipe Tiket (Issue Types)

Sistem membedakan pekerjaan ke dalam beberapa tipe agar manajemen dapat melacak metrik dengan baik (misal: membandingkan waktu pembuatan fitur baru vs perbaikan error). 

Gunakan panduan berikut saat membuat tiket baru:

| Ikon & Warna | Tipe Tiket | Kapan Harus Digunakan? | Aturan & Hirarki |
| :--- | :--- | :--- | :--- |
| 🗂️ **Ungu** | **Epic** | Untuk proyek besar, fitur skala luas, atau *milestone* yang butuh waktu berminggu-minggu (melebihi 1 sprint). | **Highest Level.** Harus dipecah menjadi beberapa *Story* atau *Task*. |
| 🔖 **Hijau** | **Story** | Untuk mendefinisikan bagian dari fitur (Epic) yang dilihat dari sudut pandang *User* atau klien. Menghasilkan *value* bisnis langsung. | Harus terkait dengan Epic. Format umum: *"Sebagai [X], saya ingin [Y], agar [Z]"*. |
| ☑️ **Biru** | **Task** | Untuk pekerjaan teknis internal yang harus diselesaikan tapi tidak berinteraksi langsung dengan *User* (contoh: setup server, riset database). | Berdiri sejajar dengan *Story*. Bisa diletakkan di bawah Epic. |
| 🐞 **Merah** | **Bug** | Khusus untuk melaporkan *error*, cacat sistem, atau fitur yang berjalan tidak semestinya di lingkungan *Production/Staging*. | Sangat penting untuk memisahkan Bug dari Task agar evaluasi *code quality* akurat. |
| ⤵️ **Abu** | **Subtask** | Untuk merinci pekerjaan teknis (checklist) yang dipegang 1 orang demi menyelesaikan sebuah Task/Story/Bug. | **Lowest Level.** Harus memiliki Induk (*Parent*). Tidak bisa berdiri sendiri. |

---

## 2. Menentukan Skala Prioritas (Prioritization Matrix)

Selain tipe tiket, menentukan **Priority** (Prioritas) sangat fatal perannya saat PM menyusun antrean pekerjaan *(Backlog Grooming)*. Jangan menandai semua tugas sebagai "Critical" hanya karena ingin cepat dikerjakan.

Gunakan skala matriks berikut:

> [!CAUTION] 
> **CRITICAL (Merah - Chevrons Up)**
> **Definisi:** Sistem lumpuh *(System Down)*, transaksi klien gagal, kebocoran data keamanan, atau operasional perusahaan terhenti. 
> **SLA Pengerjaan:** Harus segera dikerjakan saat itu juga (Drop everything else). 
> **Contoh:** "Server Production Mati", "User tidak bisa klik tombol Bayar".

> [!WARNING]
> **HIGH (Oranye - Chevron Up)**
> **Definisi:** Fungsi utama terganggu pada lingkungan production, tetapi ada jalan pintas sementara *(workaround)*, atau fitur krusial tertunda sebelum *deadline* rilis.
> **SLA Pengerjaan:** Masuk ke target *Sprint* yang sedang berjalan atau harus selesai hari ini/besok.
> **Contoh:** "Tombol cetak struk error, kasir terpaksa mencatat manual".

> [!IMPORTANT]
> **MEDIUM (Kuning - Minus)**
> **Definisi:** *Default priority*. Pekerjaan fitur biasa, bug minor yang tidak memblokir fungsi utama, atau pekerjaan operasional rutin.
> **SLA Pengerjaan:** Masuk dalam rencana antrean Sprint berikutnya.
> **Contoh:** "Pembuatan API Manajemen Karyawan", "Tombol login kurang responsif di iPad".

> [!NOTE]
> **LOW (Hijau - Chevron Down)**
> **Definisi:** Peningkatan minor (*enhancement*), *typo* pada teks, atau penyesuaian estetika (*UI polish*) yang tidak mengubah fungsionalitas.
> **SLA Pengerjaan:** Dikerjakan bila *developer* memiliki waktu luang di akhir Sprint.
> **Contoh:** "Warna teks keterangan di footer kurang cerah".

> [!NOTE]
> **LOWEST (Abu - Chevrons Down)**
> **Definisi:** Ide teknikal jarak jauh, angan-angan fitur *(nice-to-have)*, atau riset jangka panjang yang belum punya *business value* jelas.
> **Contoh:** "Riset penggunaan bahasa Rust untuk efisiensi server 3 tahun ke depan".

---

## 3. Contoh Skenario: Membangun Modul Keranjang Belanja

Berikut adalah contoh praktis bagaimana seorang *Project Manager* dibantu oleh *Tech Lead* mencatat pekerjaan yang masuk ke dalam sistem WLMS:

````carousel
![Mendefinisikan Epic](/absolute/path/placeholder-1.png)
<!-- slide -->
**1. Membuat Wadah Utama (Epic)**
PM merencanakan fitur e-commerce baru.
- **Tipe:** `Epic`
- **Judul:** Membangun Modul E-Commerce & Checkout
- **Prioritas:** `Medium` *(Karena ini adalah inisiatif normal jangka panjang)*

<!-- slide -->
**2. Memecah Kebutuhan Bisnis (Story)**
PM menerjemahkan kebutuhan klien.
- **Tipe:** `Story`
- **Judul:** Sebagai Pembeli, saya bisa menyimpan barang ke Keranjang
- **Prioritas:** `High` *(Fitur inti yang dibutuhkan segera)*
- **Epic Link:** Ke Epic E-Commerce

<!-- slide -->
**3. Memecah Kebutuhan Teknis (Task)**
Developer backend melihat bahwa server butuh memori penyimpanan cache untuk fungsi tersebut.
- **Tipe:** `Task`
- **Judul:** Setup & Integrasi Redis untuk sesi Keranjang
- **Prioritas:** `High`
- **Epic Link:** Ke Epic E-Commerce

<!-- slide -->
**4. Mengerjakan Rincian (Subtask)**
Programmer yang ditugaskan memecah `Task` Redis di atas menjadi pekerjaan hariannya:
- **Tipe:** `Subtask`
- **Judul:** Install Redis di Laravel Cloud
- **Parent:** Task (Setup & Integrasi Redis)

<!-- slide -->
**5. Terjadi Insiden Saat Rilis (Bug)**
Saat fitur dirilis, keranjang belanja pembeli hilang jika mereka me-refresh halaman pembayaran!
- **Tipe:** `Bug`
- **Judul:** Keranjang Kosong Secara Acak Saat Checkout!
- **Prioritas:** `Critical` *(Menyebabkan gagalnya pendapatan masuk)*
````

## 4. Rangkuman Siklus Kerja (Workflow)

Dengan mendaftarkan tugas secara akurat, alur kerja di WLMS menjadi sebagai berikut:
1. Pimpinan membuat tiket dan menentukan **Tipe** dan **Prioritas**.
2. Tiket masuk ke daftar **Backlog**.
3. Di dalam Backlog Manager, Anda bisa memfilter tiket dari `Critical` ke `Lowest` untuk memasukkan pekerjaan yang paling genting ke dalam **Sprint** bulan ini.
4. Tim mulai mengerjakan, memindahkan status *(To Do -> In Progress -> Done)*.
5. Dasbor utama (Global Issues) akan menghitung kalkulasi *Progress* berdasarkan tipe, menunjukkan apakah tim Anda seimbang antara "Membangun Fitur" vs "Membasmi Bug".

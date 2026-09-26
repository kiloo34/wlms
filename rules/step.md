Fase 1: Perencanaan Arsitektur Data (Database Agent)
Semuanya dimulai dari fondasi data. Anda berdiskusi dengan Database Agent sebelum baris kode PHP atau React ditulis.

Konteks Chat Baru: @rules/agent-database.md

Prompt Anda:

"Bertindaklah sebagai Database Agent. Rancang skema database untuk fitur 'Pembuatan Workspace'. Ingat aturan hierarki 5 level organisasi dan pembatasan data. Buatkan DDL/Migration Laravel yang dioptimalkan, tentukan jenis Primary Key, dan berikan contoh query efisien untuk mengambil data Workspace berdasarkan level VP. Jangan gunakan basic tutorial."

Hasil: Skema tabel, Migration, dan strategi Indexing.

Fase 2: Implementasi Logika Bisnis & API (Backend Agent)
Setelah skema database disetujui, bawa skema tersebut ke Backend Agent untuk membangun "mesinnya".

Konteks Chat Baru: @rules/architecture.md, @rules/agent-backend.md, dan (Hasil dari Fase 1).

Prompt Anda:

"Bertindaklah sebagai Backend Agent. Berdasarkan skema tabel berikut [paste hasil Fase 1], buatkan struktur Clean Architecture untuk pembuatan Workspace. Buatkan Domain Entity, Use Case (Command), dan API Controller. Ingat aturan Zero Data Breach, bungkus mutasi dengan DB::transaction, dan gunakan API Resource/DTO untuk response."

Hasil: Kode PHP (Controller, Use Case, Resource, Events).

Fase 3: Pengujian Keamanan & Ketahanan (Tester Agent)
Jangan biarkan Frontend menyentuh API yang belum teruji. Panggil Tester Agent untuk "menyerang" kode Backend.

Konteks Chat Baru: @rules/agent-tester.md dan (Hasil kode Backend dari Fase 2).

Prompt Anda:

"Bertindaklah sebagai Tester Agent. Evaluasi kode Backend pembuatan Workspace ini. Buatkan Integration Test menggunakan Pest PHP. Gunakan mindset Pentester: pastikan endpoint POST /api/workspaces ini kebal dari IDOR (user tidak berhak membuat workspace) dan pastikan tidak ada data sensitif atau kredensial yang terekspos di response JSON API."

Hasil: Test suite otomatis. Jika Tester menemukan celah, berikan laporannya kembali ke chat Backend untuk diperbaiki.

Fase 4: Pembangunan Antarmuka Pengguna (Frontend Agent)
Setelah API lulus tes dan aman, berikan kontrak API tersebut ke Frontend Agent.

Konteks Chat Baru: @rules/agent-frontend.md dan (Contoh JSON Response dari API Backend).

Prompt Anda:

"Bertindaklah sebagai Frontend Agent. Backend telah menyiapkan endpoint POST /api/workspaces (berikut payload dan response-nya). Buatkan form UI menggunakan Compound Components. Pisahkan server state (Gunakan React Query untuk fetch/mutate) dari UI murni. Pastikan UI bersifat dinamis dan ambil nilai limitasi (seperti max length) dari API, jangan di-hardcode."

Hasil: Komponen UI, Custom Hooks, dan State Management yang bersih.

Fase 5: Audit Akhir & Verifikasi (QA Agent)
Sebelum melakukan commit atau merge ke branch produksi, lakukan inspeksi akhir.

Konteks Chat Baru: @rules/agent-qa.md, (Kode Backend), dan (Kode Frontend).

Prompt Anda:

"Bertindaklah sebagai QA Agent. Lakukan audit menyeluruh pada fitur 'Pembuatan Workspace' ini. Periksa kepatuhan terhadap arsitektur Event-Driven, evaluasi sinkronisasi state antara UI dan Backend, dan pastikan tidak ada Data Breach. Berikan status APPROVE atau REJECT beserta Bug Report-nya."

Hasil: Lampu hijau untuk rilis, atau daftar revisi spesifik.

Fase 6: Tata Kelola & Dokumentasi Enterprise (Technical Writer Agent)
Fitur sudah selesai dan aman, kini saatnya memenuhi standar kepatuhan korporat (IT Compliance).

Konteks Chat Baru: @rules/agent-technical-writer.md dan (Rangkuman Sistem yang baru dibuat).

Prompt Anda:

"Bertindaklah sebagai Technical Writer. Fitur Workspace telah selesai dibangun. Tolong buatkan draf dokumen formal berikut:

Dokumen FSD lengkap dengan diagram Mermaid untuk alur pembuatan Workspace.

Dokumen BA UAT OK berisi tabel matriks pengujian untuk ditandatangani VP dan Direksi.

API Contract (OpenAPI) untuk dokumentasi internal.
Pastikan formatnya sangat profesional dan siap diaudit."

Hasil: Dokumen SDLC berstandar perbankan/BUMN siap cetak atau konversi ke PDF.

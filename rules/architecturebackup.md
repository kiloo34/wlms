# System Architecture Rules - Workload Management System

Dokumen ini mendefinisikan prinsip-prinsip arsitektur dan aturan pengembangan tingkat lanjut untuk proyek _Workload Management System_. Sistem ini dirancang sebagai **Modular Monolith** dengan penerapan **Clean Architecture**, **Domain-Driven Design (DDD)**, dan **Event-Driven Architecture**.

Seluruh agen AI, code generator, maupun pengembang manusia wajib memahami dan mematuhi "Arsitektur Matriks 2D" di bawah ini.

---

## 1. Arsitektur Matriks 2D (Core Paradigm)

Desain sistem ini bertumpu pada dua sumbu partisi utama:

- **Sumbu X - Modular Monolith (Horizontal Partitioning):** Membagi sistem menjadi modul-modul independen berdasarkan domain bisnis (misal: Identity, Workload, Notification, Analytic).
- **Sumbu Y - Clean Architecture (Vertical Partitioning):** Membagi setiap modul ke dalam 4 lapisan konsentris dengan _Dependency Rule_ yang ketat (Presentation, Application, Domain, Infrastructure).

---

## 2. Horizontal Partitioning: Modular Monolith & Bounded Contexts

Sistem tidak dibangun sebagai _big ball of mud_, melainkan kumpulan modul bisnis yang saling terisolasi dalam satu basis kode utama (Monolith).

**Aturan Antar-Modul:**

1. **Otonomi Modul:** Modul `Workload` tidak boleh memanggil logika internal, Repository, atau Eloquent Model milik modul `Identity` secara langsung.
2. **Komunikasi Lintas Modul:** Modul berkomunikasi **HANYA** melalui _Domain Events_ (Event-Driven). Jika modul A butuh memicu aksi di modul B, modul A mendispatch event, dan modul B merespons melalui Listener.
3. **Tidak Ada Cross-Join Lintas Modul:** Dilarang melakukan _Join SQL_ secara langsung antara tabel modul A dan tabel modul B. Relasikan hanya menggunakan referensi ID (contoh: menyimpan `assignee_id` sebagai statis `string/uuid`, bukan memanggil tabel `users`).

---

## 3. Vertical Partitioning: Clean Architecture & Ports/Adapters

Di dalam setiap modul, kode wajib dipecah menjadi 4 lapisan berikut dengan arah dependensi (panah) yang ketat menuju ke dalam (Domain Layer).

+-------------------------------------------------------------------------+
| MODULE (e.g., Workload) |
| |
| +-----------------------------------------------------------------+ |
| | 1. Presentation Layer (Inbound Adapters) | |
| | - HTTP Controllers, Form Requests, API Resources | |
| | - Console Commands, Queue Workers / Consumers | |
| +--------------------------------+--------------------------------+ |
| | (Calls) |
| +--------------------------------v--------------------------------+ |
| | 2. Application Layer (Use Cases / Orchestration) | |
| | - Use Cases / Command Handlers, DTOs, Application Services | |
| +--------------------------------+--------------------------------+ |
| | (Operates on) |
| +--------------------------------v--------------------------------+ |
| | 3. Domain Layer (Core Business Rules - Pure PHP) | |
| | - Entities, Value Objects, Domain Events | |
| | - Ports / Interfaces (Repositories, External Contracts) | |ubah str
| +--------------------------------^--------------------------------+ |
| | (Implements Interface) |
| +--------------------------------+--------------------------------+ |
| | 4. Infrastructure Layer (Outbound Adapters) | |
| | - Eloquent Repositories, Database Models & Mappings | |
| | - Event Listeners, Mailables, 3rd Party APIs | |
| +-----------------------------------------------------------------+ |
+-------------------------------------------------------------------------+

### Penjelasan Lapisan (Vertical Rules):

- **Layer 3 (Domain) ADALAH RAJA:** Harus berisi murni kode PHP. Dilarang keras mengimpor kelas Laravel (`Illuminate\...`) di sini.
- **Layer 4 (Infrastructure) ADALAH PELAYAN:** Eloquent ORM dan Database hanya detail implementasi. Mereka harus mematuhi (mengimplementasikan) antarmuka/Interface (Ports) yang dibuat oleh Layer 3.
- **Layer 2 (Application) ADALAH SUTRADARA:** Tidak boleh ada _business rule_ (if/else penentu logika bisnis utama) di sini. Tugasnya hanya memanggil Repository, mengeksekusi metode Entitas, menyimpannya, lalu melepaskan (_dispatch_) event.

---

## 4. The Golden Rule: Event-Driven Side Effects

Sistem ini menganut aturan ketat mengenai efek samping (mutasi di luar siklus utama Aggregate).

**SEMUA efek samping (side-effects) HARUS ditangani melalui Domain Events dan Event Listeners.**

1.  **Definisi Side-Effect:** Segala tindakan selain mengubah _state_ Entitas utama ke _database_. (Contoh: Kirim email, notifikasi push, pembaruan metrik _workload_, _sync_ ke ElasticSearch).
2.  **Mekanisme:**
    - Entitas di _Domain Layer_ merekam (record) event (contoh: `new TaskAssigned($taskId, $assigneeId)`).
    - _Application Layer_ melepaskan (dispatch) event tersebut setelah transaksi DB sukses.
    - _Event Listeners_ di _Infrastructure Layer_ menangkap event tersebut secara _asynchronous_ (wajib `ShouldQueue`).

---

## 5. Pedoman Praktis (Developer / AI Agent Checklist)

Sebelum membuat kode atau _Pull Request (PR)_, pastikan kode yang di-generate lolos ceklist ini:

- [ ] **DDD Tactical Check:** Apakah atribut yang kompleks (seperti Status, ID, Tanggal) sudah menggunakan _Value Objects_ yang _immutable_, bukan sekadar _primitive type_ (string/int)?
- [ ] **Dependency Check:** Apakah ada `use Illuminate\...` atau facade `DB::` di dalam folder `Domain/`? _(Jika ya, REJECT & Refactor)._
- [ ] **Side-Effect Check:** Apakah ada fungsi kirim notifikasi/email di dalam Controller atau Application Handler? _(Jika ya, pindahkan ke Infrastructure Listeners)._
- [ ] **Modular Check:** Apakah Model/Controller modul `Workload` secara langsung mengimpor kelas dari modul `Notification`? _(Jika ya, ubah menjadi event-driven)._
- [ ] **CQRS Pragmatism (Optional):** Untuk mutasi (Write/Command), 4 lapisan _Clean Architecture_ **wajib** digunakan. Namun, untuk sekadar menampilkan data tanpa mutasi (Read/Query), diperbolehkan mem-_bypass_ Domain Layer (Controller langsung memanggil antarmuka Read-Model/Query Builder khusus).

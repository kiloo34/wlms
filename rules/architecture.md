# System Architecture Documentation: Modular Monolith with Clean Architecture in Laravel (WLMS)

## 1. Executive Summary & Architectural Goals

Dokumen ini mendefinisikan standar arsitektur sistem berbasis **Modular Monolith** yang dipadukan dengan prinsip **Clean Architecture (Hexagonal / Ports & Adapters)** pada ekosistem **Laravel** untuk proyek **Workload Management System (WLMS)**.

### 1.1 Tujuan Utama

- **Maintainability & Decoupling:** Mengisolasi logika domain bisnis dari dependensi framework/infrastruktur (database, third-party API, HTTP request).
- **High Cohesion & Low Coupling:** Mengelompokkan kode berdasarkan batas domain (_Bounded Context_), bukan sekadar tipe file teknis.
- **Testability:** Memungkinkan unit testing murni pada Domain & Application layer tanpa mocking database atau overhead framework yang berat.
- **Evolutionary Path:** Memudahkan ekstraksi modul menjadi _Microservice_ independen di masa depan tanpa perlu _rewrite_ kode domain secara masif.

---

## 2. High-Level Architectural Patterns

Sistem menggabungkan dua pola arsitektur utama:

1. **Modular Monolith (Horizontal Partitioning):** Membagi sistem menjadi modul-modul independen berdasarkan domain bisnis (misal: `Identity`, `Workload`, `Notification`, `Analytic`).
2. **Clean Architecture (Vertical Partitioning):** Membagi setiap modul ke dalam 4 lapisan konsentris dengan _Dependency Rule_ yang ketat.

```text
+-------------------------------------------------------------------------+
|                              MODULE (e.g., Workload)                    |
|                                                                         |
|   +-----------------------------------------------------------------+   |
|   | 1. Presentation Layer (Inbound Adapters)                        |   |
|   |    - HTTP Controllers, Form Requests, API Resources             |   |
|   |    - Console Commands, Queue Workers / Consumers                |   |
|   +--------------------------------+--------------------------------+   |
|                                    | (Calls)                            |
|   +--------------------------------v--------------------------------+   |
|   | 2. Application Layer (Use Cases / Orchestration)                |   |
|   |    - Use Cases / Actions, DTOs, Application Services            |   |
|   +--------------------------------+--------------------------------+   |
|                                    | (Operates on)                      |
|   +--------------------------------v--------------------------------+   |
|   | 3. Domain Layer (Core Business Rules - Pure PHP)                |   |
|   |    - Entities, Value Objects, Domain Events                     |   |
|   |    - Ports / Interfaces (Repositories, External Contracts)      |   |
|   +--------------------------------^--------------------------------+   |
|                                    | (Implements Interface)             |
|   +--------------------------------+--------------------------------+   |
|   | 4. Infrastructure Layer (Outbound Adapters)                     |   |
|   |    - Eloquent Repositories, Database Models & Mappings          |   |
|   |    - Event Listeners, Third-party Adapters (Slack, Mail APIs)   |   |
|   +-----------------------------------------------------------------+   |
+-------------------------------------------------------------------------+
```

### 2.1 The Dependency Rule

- **Arah Ketergantungan:** Dependensi hanya boleh mengarah ke dalam (_inward_).
- `Presentation` bergantung pada `Application`.
- `Application` bergantung pada `Domain`.
- `Infrastructure` bergantung pada `Domain` (mengimplementasikan interface domain).
- **Domain Layer ADALAH RAJA:** Lapisan paling dalam yang **TIDAK BOLEH** memiliki dependensi ke layer lain atau framework eksternal (dilarang ada `use Illuminate\...`).

---

## 3. Layer Breakdown & Responsibilities

### 3.1 Domain Layer (Pure PHP Core)

Mengandung aturan bisnis inti WLMS.

- **Entities:** Objek domain dengan identitas unik (ID) dan state yang dimutasi melalui method bisnis, bukan public setter (misal: `Task`, `Project`).
- **Value Objects:** Objek _immutable_ tanpa identitas yang merepresentasikan konsep (misal: `TaskId`, `AssigneeId`, `TaskStatus`).
- **Domain Events:** Event yang merepresentasikan fakta bisnis yang telah terjadi (misal: `TaskAssigned`, `ProjectCreated`).
- **Domain Exceptions:** Exception khusus domain untuk memvalidasi invariant bisnis (misal: `InvalidTaskStatusTransitionException`).
- **Repository / Service Interfaces (Ports):** Kontrak abstraksi penyimpanan.

### 3.2 Application Layer (Use Cases)

Mengatur alur proses bisnis spesifik aplikasi.

- **Use Cases / Actions:** Kelas eksekutor tunggal (_Single Responsibility_) (misal: `AssignTaskUseCase`).
- **DTOs (Data Transfer Objects):** Objek pembawa data terstruktur antara Presentation dan Application layer.
- **Event Listeners / Subscribers:** Handler internal (jika ada) untuk merespons Domain Event secara sinkron.

### 3.3 Infrastructure Layer (Outbound Adapters)

Implementasi teknis dan integrasi eksternal (PELAYAN).

- **Persistence (Eloquent Models & Repositories):** Mengubah database rows menjadi Domain Entities (_Hydration_) dan menyimpan entity ke database (_Persistence_).
- **External Services:** Implementasi komunikasi ke Slack, Mail, WebSockets (Reverb).
- **Database Migrations:** Schema migration per modul.
- **Asynchronous Listeners:** Menangani _side-effects_ dari Domain Events di _background queue_.

### 3.4 Presentation Layer (Inbound Adapters)

Gerbang masuk request ke sistem.

- **HTTP Controllers:** Menerima HTTP request, memvalidasi input via Form Request, mapping ke DTO, dan memanggil Use Case.
- **API Resources:** Memformat output DTO menjadi respons JSON.
- **CLI Commands:** Pintu masuk via Artisan.

---

## 4. Directory Structure

Struktur direktori berbasis `app/Modules/` untuk WLMS:

```text
app/
├── Modules/
│   ├── Workload/
│   │   ├── Domain/
│   │   │   ├── Entities/
│   │   │   │   ├── Task.php
│   │   │   │   └── Project.php
│   │   │   ├── ValueObjects/
│   │   │   │   ├── TaskId.php
│   │   │   │   ├── AssigneeId.php
│   │   │   │   └── TaskStatus.php
│   │   │   ├── Events/
│   │   │   │   └── TaskAssigned.php
│   │   │   ├── Exceptions/
│   │   │   │   └── InvalidTaskStatusException.php
│   │   │   └── Repositories/
│   │   │       └── TaskRepositoryInterface.php
│   │   │
│   │   ├── Application/
│   │   │   ├── UseCases/
│   │   │   │   └── AssignTaskUseCase.php
│   │   │   ├── DTOs/
│   │   │   │   ├── AssignTaskInput.php
│   │   │   │   └── TaskOutput.php
│   │   │   └── Contracts/
│   │   │       └── WorkloadPublicApiInterface.php
│   │   │
│   │   ├── Infrastructure/
│   │   │   ├── Persistence/
│   │   │   │   ├── Eloquent/
│   │   │   │   │   ├── Models/
│   │   │   │   │   │   └── TaskModel.php
│   │   │   │   │   └── Mappers/
│   │   │   │   │       └── TaskMapper.php
│   │   │   │   └── Repositories/
│   │   │   │       └── EloquentTaskRepository.php
│   │   │   └── Listeners/
│   │   │       └── BroadcastTaskUpdatedListener.php
│   │   │
│   │   ├── Presentation/
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   └── AssignTaskController.php
│   │   │   │   └── Requests/
│   │   │   │       └── AssignTaskHttpRequest.php
│   │   │   └── CLI/
│   │   └── Providers/
│   │
│   ├── Identity/     # Mengurus User, Role, Auth
│   ├── Notification/ # Mengurus pengiriman Email, Slack
│   └── Analytic/     # Mengurus metrik beban kerja / team velocity
│
└── Shared/
    ├── Domain/
    └── Infrastructure/
```

---

## 5. Module Communication & Boundary Rules

Untuk menjaga integritas **Modular Monolith**, komunikasi diatur dengan ketat:

### 5.1 Rules of Isolation

1. **Dilarang Direct DB Query:** Modul `Notification` dilarang melakukan query SQL langsung ke tabel `tasks` milik modul `Workload`.
2. **Dilarang Cross-Domain Entity Dependency:** Domain layer suatu modul tidak boleh meng-import Domain Entity milik modul lain.
3. **Komunikasi Antarmodul hanya melalui 2 Jalur:**
    - **Sinkronus:** Memanggil _Public API Interface / Contract_ yang disediakan modul target (misal mengecek apakah User aktif di modul Identity).
    - **Asinkronus / Dekopel (Disarankan):** Menerbitkan (_publish_) dan mendengarkan (_subscribe_) _Domain Events_.

### 5.2 Event-Driven Architecture (The Golden Rule)

Seluruh efek samping lintas modul (_side-effects_) seperti mengirim email ke assignee, memperbarui metrik beban kerja, atau _broadcast websocket_ **WAJIB** dikelola melalui **Domain Events** dan **Event Listeners**.

1. Entitas merekam event menggunakan trait `HasDomainEvents` (tanpa payload Eloquent, gunakan _primitive types_ / _Value Objects_ sederhana).
2. Repositori di Infrastructure layer melepaskan event (`flushEvents()`) **setelah** transaksi database berhasil (wajib di dalam `DB::transaction`).

---

## 6. Implementation Code Guidelines (WLMS Context)

### 6.1 Domain Entity (Pure PHP)

```php
namespace App\Modules\Workload\Domain\Entities;

use App\Modules\Workload\Domain\Events\TaskAssigned;
use App\Modules\Workload\Domain\ValueObjects\TaskId;
use App\Modules\Workload\Domain\ValueObjects\AssigneeId;
use App\Modules\Workload\Domain\ValueObjects\TaskStatus;
use App\Modules\Workload\Domain\Exceptions\InvalidTaskStatusException;
use App\Shared\Domain\Traits\HasDomainEvents;

final class Task
{
    use HasDomainEvents;

    private function __construct(
        private readonly TaskId $id,
        private readonly string $projectId,
        private string $title,
        private ?AssigneeId $assigneeId,
        private TaskStatus $status,
        private readonly \DateTimeImmutable $createdAt
    ) {}

    public function assignTo(AssigneeId $assigneeId): void
    {
        if ($this->status === TaskStatus::DONE) {
            throw new InvalidTaskStatusException("Cannot assign a completed task.");
        }

        $this->assigneeId = $assigneeId;

        // Merekam fakta bisnis. TIDAK ADA PENGIRIMAN EMAIL DI SINI.
        $this->recordEvent(new TaskAssigned($this->id->value(), $this->assigneeId->value()));
    }

    // Getters...
    public function getId(): TaskId { return $this->id; }
    public function getAssigneeId(): ?AssigneeId { return $this->assigneeId; }
    public function getStatus(): TaskStatus { return $this->status; }
}
```

### 6.2 Port / Repository Interface

```php
namespace App\Modules\Workload\Domain\Repositories;

use App\Modules\Workload\Domain\Entities\Task;
use App\Modules\Workload\Domain\ValueObjects\TaskId;

interface TaskRepositoryInterface
{
    public function save(Task $task): void;
    public function findById(TaskId $id): ?Task;
}
```

### 6.3 Use Case (Application Layer)

```php
namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\AssignTaskInput;
use App\Modules\Workload\Application\DTOs\TaskOutput;
use App\Modules\Workload\Domain\ValueObjects\TaskId;
use App\Modules\Workload\Domain\ValueObjects\AssigneeId;
use App\Modules\Workload\Domain\Repositories\TaskRepositoryInterface;

final class AssignTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository
    ) {}

    public function execute(AssignTaskInput $input): TaskOutput
    {
        $task = $this->taskRepository->findById(new TaskId($input->taskId));

        if (!$task) {
            throw new \Exception("Task not found");
        }

        $task->assignTo(new AssigneeId($input->assigneeId));
        $this->taskRepository->save($task);

        return TaskOutput::fromDomain($task);
    }
}
```

### 6.4 Infrastructure Eloquent Adapter & Mapper

```php
namespace App\Modules\Workload\Infrastructure\Persistence\Repositories;

use App\Modules\Workload\Domain\Entities\Task;
use App\Modules\Workload\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\TaskId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\TaskModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Mappers\TaskMapper;
use Illuminate\Support\Facades\DB;

final class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function save(Task $task): void
    {
        DB::transaction(function () use ($task) {
            TaskModel::query()->updateOrCreate(
                ['id' => $task->getId()->value()],
                [
                    'project_id' => $task->getProjectId(),
                    'title' => $task->getTitle(),
                    'assignee_id' => $task->getAssigneeId()?->value(),
                    'status' => $task->getStatus()->value,
                ]
            );

            // Melepaskan Domain Events untuk ditangkap oleh Listeners (Side-Effects)
            foreach ($task->flushEvents() as $event) {
                event($event);
            }
        });
    }

    public function findById(TaskId $id): ?Task
    {
        $model = TaskModel::query()->find($id->value());
        return $model ? TaskMapper::toDomain($model) : null;
    }
}
```

---

## 7. Architecture Enforcement & Automated Testing

Gunakan **Pest Architecture Testing** (`pestphp/pest-plugin-arch`) untuk menjaga struktur arsitektur:

```php
// tests/Arch/ArchitectureTest.php

test('domain layer does not depend on infrastructure, presentation, or laravel framework')
    ->expect('App\\Modules\\*\\Domain')
    ->toOnlyDependOn([
        'App\\Modules\\*\\Domain',
        'App\\Shared\\Domain',
        'DateTimeImmutable',
        'InvalidArgumentException',
        'Exception',
    ]);

test('application layer does not depend on presentation or infrastructure')
    ->expect('App\\Modules\\*\\Application')
    ->not->toUse([
        'App\\Modules\\*\\Presentation',
        'App\\Modules\\*\\Infrastructure',
        'Illuminate\\Http',
        'Illuminate\\Database',
    ]);

test('presentation layer only calls application layer')
    ->expect('App\\Modules\\*\\Presentation')
    ->not->toUse('App\\Modules\\*\\Infrastructure');

test('modules do not cross-import internal domain entities')
    ->expect('App\\Modules\\Workload\\Domain')
    ->not->toUse([
        'App\\Modules\\Identity\\Domain',
        'App\\Modules\\Notification\\Domain'
    ]);
```

---

## 8. Summary Checklist for Code Reviews & AI Agents

Sebelum merilis kode atau _Pull Request (PR)_, verifikasi hal berikut:

- [ ] **Domain Purity:** Apakah `Domain Layer` benar-benar terisolasi dari Laravel Framework (tanpa facade/DB)?
- [ ] **Rich Domain Model:** Apakah logika bisnis diletakkan di `Domain Entity` menggunakan metode mutasi spesifik (bukan public setter)?
- [ ] **Value Objects:** Apakah transfer data dan atribut Entity menggunakan _Value Objects_ / DTO, bukan primitif berantakan?
- [ ] **Side-Effects:** Apakah semua pengiriman email, notifikasi, dan analitik dikelola via Event Listener di Infrastructure (TIDAK di Controller/Use Case)?
- [ ] **Modular Boundaries:** Apakah tidak ada join SQL langsung lintas modul?
- [ ] **CQRS Pragmatism:** Untuk operasi mutasi (Write) wajib melewati 4 Layer. Untuk Read (Query Data list), Controller diizinkan mem-bypass Domain Layer dan langsung memanggil Read-Model / Query Builder khusus.

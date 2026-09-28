# API Contracts - WLMS Project

Dokumen ini adalah gabungan dari semua dokumentasi terkait dalam proyek.

---

## Project Management

### API CONTRACT

**Modul:** Project Management (Manajemen Proyek)  
**Versi API:** v1.0  
**Format:** OpenAPI 3.0  

```yaml
openapi: 3.0.3
info:
  title: Project Management API
  description: API spesifikasi untuk modul Manajemen Proyek (Create, Read, Update, Archive/Delete). Dilengkapi dengan perlindungan RBAC dan Anti-IDOR.
  version: 1.0.0
servers:
  - url: https://api.wlms.internal/v1
    description: Internal Production Server
paths:
  /projects:
    get:
      summary: Mendapatkan daftar proyek
      description: Mengembalikan daftar proyek sesuai otorisasi pengguna. Superadmin melihat semua, User melihat proyeknya sendiri.
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Berhasil mendapatkan daftar proyek
          content:
            application/json:
              schema:
                type: array
                items:
                  $ref: '#/components/schemas/Project'
        '401':
          description: Tidak terotorisasi (Token tidak valid)
    post:
      summary: Membuat proyek baru
      description: Membuat proyek baru. Hanya Superadmin atau peran yang diizinkan yang dapat melakukan ini.
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ProjectCreate'
      responses:
        '201':
          description: Proyek berhasil dibuat
        '400':
          description: Permintaan tidak valid (Validasi gagal)
        '403':
          description: Dilarang (Akses ditolak)

  /projects/{id}:
    get:
      summary: Mendapatkan detail proyek
      description: Mengambil data satu proyek. Dilindungi oleh Anti-IDOR.
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
          description: ID dari Proyek
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Detail proyek ditemukan
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Project'
        '403':
          description: Dilarang (Akses ditolak karena IDOR)
        '404':
          description: Proyek tidak ditemukan
    put:
      summary: Memperbarui proyek
      description: Memperbarui informasi proyek. Dilindungi oleh Anti-IDOR.
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/ProjectCreate'
      responses:
        '200':
          description: Proyek berhasil diperbarui
        '403':
          description: Dilarang (Akses ditolak karena IDOR)
        '404':
          description: Proyek tidak ditemukan
    delete:
      summary: Mengarsipkan / Menghapus proyek
      description: Mengarsipkan atau menghapus proyek secara logis. Khusus Superadmin.
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
      security:
        - bearerAuth: []
      responses:
        '204':
          description: Proyek berhasil dihapus/diarsipkan
        '403':
          description: Dilarang (Akses ditolak)

components:
  schemas:
    Project:
      type: object
      properties:
        id:
          type: integer
          example: 101
        name:
          type: string
          example: "Migrasi Sistem Core Banking"
        description:
          type: string
          example: "Proyek pembaruan sistem inti ke arsitektur microservices."
        status:
          type: string
          example: "Active"
        owner_id:
          type: integer
          example: 42
        created_at:
          type: string
          format: date-time
    ProjectCreate:
      type: object
      required:
        - name
        - status
      properties:
        name:
          type: string
          example: "Migrasi Sistem Core Banking"
        description:
          type: string
        status:
          type: string
          example: "Active"
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
```

---

## Workflow State

### API CONTRACT & OPENAPI SPECIFICATION
#### MODUL WORKFLOW & STATE MACHINE

Dokumen ini memuat spesifikasi teknis dan Contract Interface API untuk manajemen **Statuses & Workflows** dalam ekosistem WLMS. Struktur API didesain dengan format spesifikasi OpenAPI 3.0 standar Enterprise (Secure, Restful, dan JSON-first).

### OpenAPI Specification (YAML)

```yaml
openapi: 3.0.3
info:
  title: WLMS - Workflow & State Machine API
  description: API Endpoint untuk konfigurasi dinamis State Machine, Status Entitas, dan aturan Workflow dalam sistem enterprise WLMS. Mendukung proteksi anti-IDOR dan validasi akses role-based.
  version: 1.0.0
  contact:
    name: Backend Architecture Team
    email: dev@wlms.local

servers:
  - url: https://api.wlms.local/v1
    description: Development / Staging Server
  - url: https://api.wlms.co.id/v1
    description: Production Server

tags:
  - name: Statuses
    description: Operasi CRUD untuk entitas Status
  - name: Workflows
    description: Operasi konfigurasi jalur transisi State Machine (Workflows)

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    Status:
      type: object
      properties:
        id:
          type: string
          format: uuid
          example: "550e8400-e29b-41d4-a716-446655440000"
        name:
          type: string
          example: "Pending Approval"
        slug:
          type: string
          example: "pending_approval"
        color_code:
          type: string
          example: "#FF9900"
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
    
    Workflow:
      type: object
      properties:
        id:
          type: string
          format: uuid
        name:
          type: string
          example: "Draft to Pending"
        from_status_id:
          type: string
          format: uuid
        to_status_id:
          type: string
          format: uuid
        created_at:
          type: string
          format: date-time

paths:
  /statuses:
    get:
      summary: Dapatkan Semua Status (Master Data)
      tags: [Statuses]
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Berhasil memuat daftar Status
          content:
            application/json:
              schema:
                type: array
                items:
                  $ref: '#/components/schemas/Status'
        '401':
          description: Unauthorized
    
    post:
      summary: Buat Status Baru
      tags: [Statuses]
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [name, color_code]
              properties:
                name:
                  type: string
                  example: "Approved"
                color_code:
                  type: string
                  example: "#00FF00"
      responses:
        '201':
          description: Status berhasil dibuat
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Status'
        '403':
          description: Forbidden - Privilege khusus Superadmin / Admin Cabang
        '422':
          description: Validasi input gagal

  /statuses/{id}:
    put:
      summary: Perbarui Data Status (Anti-IDOR Protected)
      tags: [Statuses]
      security:
        - bearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                name:
                  type: string
                color_code:
                  type: string
      responses:
        '200':
          description: Berhasil diperbarui
        '403':
          description: Forbidden (IDOR Attempt)
        '404':
          description: Status tidak ditemukan (Zero Data Leak)

    delete:
      summary: Hapus Status (Constrained / Soft Delete)
      tags: [Statuses]
      security:
        - bearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: string
            format: uuid
      responses:
        '204':
          description: Berhasil dihapus (No Content)
        '409':
          description: Conflict / Constrain Violation - Status sedang digunakan oleh record lain

  /workflows:
    get:
      summary: Dapatkan Aturan Workflow (Jalur State Machine)
      tags: [Workflows]
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Daftar ruleset Workflow

    post:
      summary: Daftarkan Jalur Transisi Status Baru
      tags: [Workflows]
      security:
        - bearerAuth: []
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required: [name, from_status_id, to_status_id]
              properties:
                name:
                  type: string
                  example: "Review Process"
                from_status_id:
                  type: string
                  format: uuid
                to_status_id:
                  type: string
                  format: uuid
      responses:
        '201':
          description: Relasi antar Status berhasil didaftarkan.
        '422':
          description: Invalid request (Status referensi tidak valid atau duplikat).

  /workflows/{id}:
    delete:
      summary: Hapus Aturan Workflow (Putus Jalur)
      tags: [Workflows]
      security:
        - bearerAuth: []
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: string
            format: uuid
      responses:
        '204':
          description: Berhasil menghapus relasi workflow.
```

---

## Issues Management

### API CONTRACT: Issues Management

**Standar Dokumentasi:** OpenAPI Specification (OAS) 3.0.3
**Versi API:** v1

Berikut adalah spesifikasi teknis API untuk modul manajemen Isu/Tiket. Spesifikasi ini digunakan sebagai acuan mutlak (Contract-First) bagi *Frontend Engineer* dan *Backend Engineer*.

```yaml
openapi: 3.0.3
info:
  title: WLMS Issues Management API
  description: Enterprise-grade API untuk manajemen kendala/tugas dalam suatu Project. Diperkuat dengan pengamanan RBAC dan anti-IDOR.
  version: 1.0.0
servers:
  - url: https://api.wlms.corporate.local/api/v1
    description: Production Server
  - url: https://staging-api.wlms.corporate.local/api/v1
    description: Staging / UAT Server

paths:
  /projects/{projectId}/issues:
    get:
      summary: Mendapatkan daftar Issues pada Project tertentu.
      description: Mengembalikan daftar issue berserta paginasi. Memerlukan hak akses pada Project.
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: projectId
          required: true
          schema:
            type: string
            format: uuid
          description: ID Project (UUID)
        - in: query
          name: status
          schema:
            type: string
          description: Filter berdasarkan Status (contoh; backlog, in_progress)
        - in: query
          name: page
          schema:
            type: integer
          description: Halaman paginasi
      responses:
        '200':
          description: OK
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/IssueListResponse'
        '403':
          description: Forbidden (Terdeteksi IDOR / Akses Ditolak)
          
    post:
      summary: Membuat Issue baru.
      description: Membuat record issue baru di dalam spesifik project.
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: projectId
          required: true
          schema:
            type: string
            format: uuid
          description: ID Project
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CreateIssueRequest'
      responses:
        '201':
          description: Created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/IssueResponse'
        '403':
          description: Forbidden (IDOR Prevention)
        '422':
          description: Unprocessable Entity (Validasi Gagal)

  /issues/{issueId}:
    patch:
      summary: Mengubah data / status Issue.
      description: Partial update data issue.
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: issueId
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/UpdateIssueRequest'
      responses:
        '200':
          description: Updated successfully
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/IssueResponse'
        '403':
          description: Forbidden

  /issues/{issueId}/comments:
    get:
      summary: Mendapatkan daftar komentar pada Issue tertentu.
      description: Mengembalikan daftar komentar berserta pembuatnya.
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: issueId
          required: true
          schema:
            type: string
            format: uuid
      responses:
        '200':
          description: OK
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CommentListResponse'
        '403':
          description: Forbidden
          
    post:
      summary: Menambahkan komentar pada Issue.
      description: Membuat record komentar baru pada sebuah issue.
      security:
        - BearerAuth: []
      parameters:
        - in: path
          name: issueId
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/CreateCommentRequest'
      responses:
        '201':
          description: Created
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/CommentResponse'
        '403':
          description: Forbidden
        '422':
          description: Unprocessable Entity

components:
  securitySchemes:
    BearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    IssueResponse:
      type: object
      properties:
        data:
          type: object
          properties:
            id:
              type: string
              format: uuid
            title:
              type: string
              maxLength: 255
            description:
              type: string
            status:
              type: string
              enum: [backlog, todo, in_progress, in_review, done]
            assignee:
              type: object
              properties:
                id:
                  type: string
                  format: uuid
                name:
                  type: string
            created_at:
              type: string
              format: date-time
    
    IssueListResponse:
      type: object
      properties:
        data:
          type: array
          items:
            $ref: '#/components/schemas/IssueResponse/properties/data'
        meta:
          type: object
          properties:
            current_page:
              type: integer
            last_page:
              type: integer
            total:
              type: integer

    CreateIssueRequest:
      type: object
      required:
        - title
      properties:
        title:
          type: string
          maxLength: 255
          description: Judul isu/tiket.
        description:
          type: string
          description: Deskripsi lengkap.
        assignee_id:
          type: string
          format: uuid
          description: Target penugasan (opsional).

    UpdateIssueRequest:
      type: object
      properties:
        title:
          type: string
          maxLength: 255
        description:
          type: string
        status:
          type: string
          enum: [backlog, todo, in_progress, in_review, done]
        assignee_id:
          type: string
          format: uuid

    CommentResponse:
      type: object
      properties:
        data:
          type: object
          properties:
            id:
              type: string
              format: uuid
            content:
              type: string
            author:
              type: object
              properties:
                id:
                  type: string
                  format: uuid
                name:
                  type: string
            created_at:
              type: string
              format: date-time

    CommentListResponse:
      type: object
      properties:
        data:
          type: array
          items:
            $ref: '#/components/schemas/CommentResponse/properties/data'

    CreateCommentRequest:
      type: object
      required:
        - content
      properties:
        content:
          type: string
          description: Isi komentar.
```

---

## Notifications

### API Contract: Notifications

#### 1. Get User Notifications
Mengambil daftar notifikasi milik pengguna yang sedang login.

**Endpoint**: `GET /api/notifications`  
**Auth**: Bearer Token / Sanctum Cookie

### Response (200 OK)
```json
{
    "data": [
        {
            "id": "9a3e6f9b-1234-4a2e-b6a9-8e2b1c4d5f6a",
            "type": "issue.assigned",
            "data": {
                "issue_number": 102
            },
            "read_at": null,
            "created_at": "2026-09-21T10:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 20,
        "total": 1
    },
    "unread_count": 1
}
```

#### 2. Mark All as Read
Menandai semua notifikasi milik pengguna yang belum dibaca menjadi sudah dibaca.

**Endpoint**: `PUT /api/notifications/read`  
**Auth**: Bearer Token / Sanctum Cookie

### Response (200 OK)
```json
{
    "message": "Notifications marked as read.",
    "affected": 1
}
```

#### 3. WebSocket Channel
**Broadcaster**: Laravel Reverb  
**Channel Name**: `private-user.{userId}`  
**Event Name**: `NewNotification`

### Event Payload
```json
{
    "id": "9a3e6f9b-...",
    "type": "comment.added",
    "data": {
        "body_preview": "I fixed this issue..."
    },
    "created_at": "2026-09-21T10:05:00Z"
}
```

---

## api_worklog

### API Contract: Log Work for Issue

#### **Log Work**
Mencatat waktu pengerjaan (worklog) untuk sebuah issue.

**URL**: `/api/issues/{id}/worklogs`  
**Method**: `POST`  
**Auth**: Bearer Token (Sanctum)

### **Request**
**Path Parameter:**
- `id` (integer) - ID Issue

**Body (JSON):**
```json
{
    "time_spent_seconds": 3600,
    "description": "Mengerjakan fitur login dan setup database",
    "started_at": "2023-10-25T08:00:00Z"
}
```
**Aturan Validasi:**
- `time_spent_seconds`: `required`, `integer`, `min:1`
- `description`: `required`, `string`
- `started_at`: `required`, `date`

### **Response**
**Success (201 Created)**
```json
{
    "message": "Work logged successfully"
}
```

**Error (404 Not Found)**
Jika Issue ID tidak valid atau tidak ditemukan (akan dilempar dari repository).

**Error (422 Unprocessable Entity)**
Jika validasi payload gagal.

**Error (401 Unauthorized)**
Jika belum login.

---

## workspace-api-contract

### Workspace API Contract

#### 1. Create Workspace

**Endpoint:** `POST /api/workspaces`
**Auth:** Bearer Token (Sanctum)

**Headers:**

```
Authorization: Bearer <token>
Accept: application/json
Content-Type: application/json
X-Idempotency-Key: <uuid> (optional, default generated)
```

**Request Body:**

```json
{
    "name": "Workspace Tim Alpha",
    "description": "Deskripsi opsional untuk workspace"
}
```

**Responses:**

- `201 Created`

```json
{
    "id": "uuid-v7",
    "name": "Workspace Tim Alpha",
    "status": "ACTIVE",
    "owner_group_id": "uuid-group-dari-user"
}
```

- `422 Unprocessable Entity` (Validation failed)
- `409 Conflict` (Max workspaces limit reached)
- `403 Forbidden` (User not authorized for this group)

---

#### 2. Get Workspaces

**Endpoint:** `GET /api/workspaces`
**Auth:** Bearer Token (Sanctum)

**Query Params:**

- `limit` (int, default: 50)
- `cursor` (string, optional, for pagination)

**Responses:**

- `200 OK`

```json
{
    "data": [
        {
            "id": "uuid-v7",
            "name": "Workspace Tim Alpha",
            "status": "ACTIVE",
            "owner_group_id": "uuid-group-dari-user"
        }
    ]
}
```

---

## Phase 6 Project Sprint

### API Contract Specification
#### WLMS - Project & Sprint Module

**Version:** 1.0.0
**Base URL:** `https://api.wlms.internal/v1`
**Authentication:** Bearer Token (JWT)

---

### 1. Projects API

#### 1.1 Create Project
Membuat inisiatif _Project_ baru.

*   **Endpoint:** `POST /projects`
*   **Headers:**
    *   `Authorization`: `Bearer <token>`
    *   `Content-Type`: `application/json`

**Request Body:**
```json
{
  "projectCode": "WLMS2026",
  "projectName": "Workload Management System Revamp",
  "description": "Enterprise grade workload system.",
  "startDate": "2026-09-15",
  "endDate": "2027-03-15"
}
```

**Responses:**
*   **201 Created**
    ```json
    {
      "meta": { "code": 201, "message": "Project created successfully" },
      "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "projectCode": "WLMS2026",
        "status": "PLANNED",
        "createdAt": "2026-09-14T10:00:00Z"
      }
    }
    ```
*   **422 Unprocessable Entity** (Bila `projectCode` sudah eksis atau invalid timeframe)

#### 1.2 Get Project Detail
Mengambil data detail berdasarkan ID.

*   **Endpoint:** `GET /projects/{id}`
*   **Path Parameter:**
    *   `id`: UUID dari _Project_

---

### 2. Sprints API

#### 2.1 Create Sprint
Mendaftarkan iterasi kerja dalam sebuah _Project_.

*   **Endpoint:** `POST /projects/{projectId}/sprints`
*   **Path Parameter:**
    *   `projectId`: UUID rujukan ke _Project_

**Request Body:**
```json
{
  "sprintName": "Sprint 1 - Foundation",
  "goal": "Setup repository and CI/CD pipelines",
  "startDate": "2026-09-16",
  "endDate": "2026-09-30"
}
```

**Responses:**
*   **201 Created**
    ```json
    {
      "meta": { "code": 201, "message": "Sprint created successfully" },
      "data": {
        "id": "660e8400-e29b-41d4-a716-446655441111",
        "projectId": "550e8400-e29b-41d4-a716-446655440000",
        "sprintName": "Sprint 1 - Foundation",
        "status": "DRAFT"
      }
    }
    ```
*   **400 Bad Request** (Bila rentang waktu `StartDate` dan `EndDate` berada di luar rentang waktu _Project_)
*   **404 Not Found** (Bila `projectId` tidak valid)

#### 2.2 Update Sprint Status
Transisi state pada siklus hidup _Sprint_.

*   **Endpoint:** `PATCH /sprints/{id}/status`
*   **Request Body:**
    ```json
    {
      "status": "ACTIVE"
    }
    ```

---

## Phase 7 Issues

### API Contract
#### Module: Issues/Tasks Management (Sprint 7)

OpenAPI 3.0 Specification.

```yaml
openapi: 3.0.3
info:
  title: WLMS Issues API
  version: 1.0.0
  description: API untuk manajemen tugas, Kanban Board, dan transisi status (Workflow Engine).
servers:
  - url: https://api.wlms.internal/v1

paths:
  /api/issues:
    post:
      summary: Membuat issue baru
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                projectId:
                  type: string
                  format: uuid
                title:
                  type: string
                description:
                  type: string
                issueTypeId:
                  type: string
                  format: uuid
      responses:
        '201':
          description: Issue berhasil dibuat (beserta auto-numbering IssueKey)
        '400':
          description: Bad Request (Validasi gagal)
          
  /api/issues/{id}/assign:
    post:
      summary: Menetapkan assignee ke issue
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                assigneeId:
                  type: string
                  format: uuid
      responses:
        '200':
          description: Assignee berhasil diperbarui
          
  /api/issues/{id}/transitions:
    post:
      summary: Memindahkan status issue berdasarkan Workflow
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              properties:
                targetStatusId:
                  type: string
                  format: uuid
      responses:
        '200':
          description: Transisi berhasil
        '409':
          description: Conflict (Transisi tidak diizinkan oleh WorkflowEngine)
          
  /api/projects/{id}/board:
    get:
      summary: Mengambil data Kanban Board untuk proyek
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
            format: uuid
      responses:
        '200':
          description: Data kolom dan issue dalam format yang siap digunakan oleh dnd-kit

  /api/projects/{id}/backlog:
    get:
      summary: Mengambil data Backlog yang belum masuk ke sprint/board
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: string
            format: uuid
      responses:
        '200':
          description: Daftar issue pada backlog
```

---

## Phase 8 Collaboration

### API Contract - Collaboration Module

#### 1. Get Issue Timeline
Memuat daftar komentar dan riwayat audit dari suatu Issue, diurutkan berdasarkan tanggal tertua ke terbaru.

**Request:**
- `GET /api/issues/{issueId}/timeline`
- Headers: `Authorization: Bearer {token}`

**Response (200 OK):**
```json
{
  "data": [
    {
      "type": "audit_log",
      "id": "uuid",
      "actorId": "uuid",
      "createdAt": "2026-09-14T10:00:00+07:00",
      "payload": {
        "event": "created",
        "old_values": null,
        "new_values": { "title": "Setup Server" }
      }
    },
    {
      "type": "comment",
      "id": "uuid",
      "actorId": "uuid",
      "createdAt": "2026-09-14T10:05:00+07:00",
      "payload": {
        "body": "Can someone assign this to me?",
        "is_edited": false,
        "parent_id": null
      }
    }
  ]
}
```

#### 2. Add Comment
Menambahkan komentar baru ke Issue.

**Request:**
- `POST /api/issues/{issueId}/comments`
- Body:
```json
{
  "body": "My comment here",
  "parent_id": "optional-uuid-for-reply"
}
```

**Response (201 Created):**
```json
{
  "message": "Comment added successfully.",
  "data": {
    "id": "uuid",
    "issue_id": "uuid",
    "author_id": "uuid",
    "parent_id": null,
    "body": "My comment here",
    "is_edited": false,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

#### 3. Edit Comment
Mengubah body dari sebuah komentar. Anti-IDOR enforced.

**Request:**
- `PUT /api/issues/{issueId}/comments/{commentId}`
- Body:
```json
{
  "body": "Edited comment"
}
```

**Response (200 OK):**
- Identik dengan struktur data Add Comment, dengan `is_edited: true`.

---


# Workspace Environment & Config Requirements

Fitur Workspace menggunakan konfigurasi dinamis (_Dynamic Configuration_) sesuai prinsip #5 `agent-backend.md`.

## Database Settings (Tabel `settings`)

Tidak ada variabel `.env` baru yang diperlukan. Konfigurasi diambil dari tabel `settings`.

| Key                                 | Tipe Data | Default | Keterangan                                                                |
| ----------------------------------- | --------- | ------- | ------------------------------------------------------------------------- |
| `workload.max_workspaces_per_group` | Integer   | 10      | Batas maksimum workspace yang boleh dibuat oleh sebuah Group (Otorisasi). |

## Queue Requirements

Event Listener berjalan secara _asynchronous_ (`ShouldQueue`).
Pastikan Queue Worker aktif:

```bash
php artisan queue:work
```

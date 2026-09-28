# Workspace Event Catalog

Domain Events yang dipicu oleh modul Workload (Workspace Feature).
Semua event ini bersifat _asynchronous_ dan ditangani via Queue.

| Event Class        | Publisher             | Payload Properties                                                      | Listener(s)                      | Tujuan                                                  |
| ------------------ | --------------------- | ----------------------------------------------------------------------- | -------------------------------- | ------------------------------------------------------- |
| `WorkspaceCreated` | `Workspace::create()` | `workspaceId`, `ownerGroupId`, `actorId`, `workspaceName`, `occurredAt` | `WriteWorkspaceAuditLogListener` | Mencatat histori ke `audit_logs` table (Zero Data Loss) |

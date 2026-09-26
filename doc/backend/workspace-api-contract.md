# Workspace API Contract

## 1. Create Workspace

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

## 2. Get Workspaces

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

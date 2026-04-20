# Organisation Module (Developer Documentation)

## Overview

The Organisation module provides APIs and data structures for managing organisations and teams in a multi-level hierarchy within a Laravel application.

---

## Folder Structure

```
docs/
├── user/
│   └── organisation.md
├── developer/
│   └── organisation.md
```

---

## API Endpoints

### Organisation APIs

* `GET /api/organisation/list` — List all organisations
* `GET /api/organisation/show/{uid}` — Get organisation by UID
* `POST /api/organisation/store` — Create organisation
* `PUT /api/organisation/update/{uid}` — Update organisation
* `DELETE /api/organisation/delete/{uid}` — Delete organisation

---

### Team APIs

* `GET /api/team/list`
* `POST /api/team/store`
* `PUT /api/team/update/{uid}`
* `DELETE /api/team/delete/{uid}`

---

## Data Structure

### Organisation Schema

| Field         | Type      | Description              |
| ------------- | --------- | ------------------------ |
| `uid`         | string    | Unique identifier        |
| `name`        | string    | Organisation name        |
| `description` | string    | Organisation description |
| `status`      | string    | active / inactive        |
| `created_at`  | timestamp | Creation timestamp       |
| `updated_at`  | timestamp | Last updated timestamp   |

---

## Request Examples

### Create Organisation

```json
{
    "name": "Example Organisation",
    "description": "This is an example organisation",
    "status": "active"
}
```

---

### Create Team

```json
{
    "name": "Engineering Team",
    "organisation_uid": "01KEH18TZWDA2FFPRXA3F60951",
    "status": "active"
}
```

---

## Response Format

All API responses follow this structure:

```json
{
    "status": 200,
    "message": "Request successful",
    "response_schema": {
        "data": []
    }
}
```

---

## Error Handling

| Code  | Meaning          |
| ----- | ---------------- |
| `400` | Bad Request      |
| `404` | Not Found        |
| `409` | Conflict         |
| `422` | Validation Error |

---

## Notes

* All endpoints follow REST conventions
* UID is required for update/delete operations
* Ensure validation before API calls
* Designed for scalability with multiple organisations

---

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

The module has no JSON API yet: organisations, teams and members are managed through its web
pages (`routes/web.php`).

To add one, follow the platform convention (foundation `docs/developer/api.md`): declare it in
`composer.json` under `extra.platform.api` with prefix `organisation`, put the routes in
`routes/api.v1.php`, and they are served at `/api/organisation/v1/...`.

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

# Organisation Module

## Overview

The Organisation package provides a structured way to manage multi-level organizational hierarchies within a Laravel application. It allows you to create and manage organisations, teams, and their associated members through a clean CRUD interface.

## Features

- Create and manage organisations
- Multi-level hierarchy support
- Team management within organisations
- Member assignment and role handling
- Status tracking for organisations and teams

## Endpoints

- `GET /api/organisation/list` — List all organisations
- `GET /api/organisation/show/{uid}` — Show a specific organisation
- `POST /api/organisation/store` — Create a new organisation
- `PUT /api/organisation/update/{uid}` — Update an existing organisation
- `DELETE /api/organisation/delete/{uid}` — Delete an organisation

## Data Structure

Each organisation record contains the following fields:

| Field | Type | Description |
|-------|------|-------------|
| `uid` | string | Unique identifier |
| `name` | string | Organisation name |
| `description` | string | Organisation description |
| `status` | string | active / inactive |
| `created_at` | timestamp | Creation date |
| `updated_at` | timestamp | Last updated date |

## Teams

Each organisation can have multiple teams. Teams follow the same CRUD pattern under:

- `GET /api/team/list`
- `POST /api/team/store`
- `PUT /api/team/update/{uid}`
- `DELETE /api/team/delete/{uid}`

## Usage

### Creating an Organisation

Send a `POST` request to `/api/organisation/store` with the following payload:

```json
{
    "name": "Example Organisation",
    "description": "This is an example organisation",
    "status": "active"
}
```

### Adding a Team to an Organisation

```json
{
    "name": "Engineering Team",
    "organisation_uid": "01KEH18TZWDA2FFPRXA3F60951",
    "status": "active"
}
```

## Response Format

All responses follow the standardized API response format:

```json
{
    "status": 200,
    "message": "Request successful",
    "response_schema": {
        "data": []
    }
}
```

## Error Handling

| Code | Meaning |
|------|---------|
| `400` | Bad Request — invalid input data |
| `404` | Not Found — organisation does not exist |
| `409` | Conflict — duplicate entry |
| `422` | Unprocessable Entity — validation failed |

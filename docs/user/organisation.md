# Organisation Module (User Guide)

## Overview

The Organisation module allows you to manage organisations, teams, and their members within the system. It supports structured hierarchies and helps organize users efficiently.

---

## What You Can Do

* Create and manage organisations
* Organize teams within an organisation
* Assign members to teams
* Track active/inactive status

---

## Creating an Organisation

To create an organisation, provide:

* Name
* Description
* Status (active/inactive)

### Example

```json
{
    "name": "Example Organisation",
    "description": "This is an example organisation",
    "status": "active"
}
```

---

## Managing Teams

Each organisation can have multiple teams.

### Example: Adding a Team

```json
{
    "name": "Engineering Team",
    "organisation_uid": "01KEH18TZWDA2FFPRXA3F60951",
    "status": "active"
}
```

---

## Key Concepts

* **Organisation** → Top-level entity
* **Team** → Group within an organisation
* **Status** → Controls whether entity is active or inactive

---

## Notes

* Deleting an organisation may affect associated teams
* Ensure correct organisation ID when creating teams
* Only valid data inputs are accepted

---

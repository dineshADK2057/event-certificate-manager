# Event Certificate Manager — Project Status

## Current Branch

`main`

## Current Development Stage

Sprint 05.09 — Builder UX & Interaction

## Completed Modules

- Plugin foundation
- Database schema
- Dashboard
- Events CRUD
- Event detail tabs
- Dynamic participant fields
- Participant CRUD
- Participant search
- Participant bulk delete
- Participant CSV import
- Sample CSV download
- Participant CSV export
- Session CRUD
- Session participant assignment
- AJAX participant search
- Session statistics
- Session settings
- Template CRUD
- Template background upload
- Automatic PDF preview generation
- Template preview
- Template Builder
- Template element CRUD
- Builder properties panel
- Live AJAX property editing
- Template module refactor

## Current Architecture

```text
includes/
└── modules/
    ├── overview/
    ├── participants/
    ├── sessions/
    ├── templates/
    │   ├── trait-event-templates.php
    │   ├── trait-template-builder.php
    │   ├── trait-template-elements.php
    │   ├── trait-template-preview.php
    │   └── trait-template-renderer.php
    ├── certificates/
    ├── settings/
    └── shared/


## Current Development Stage

Sprint 05 — Template Builder completed

## Next

Sprint 06 — Certificate Generation Engine
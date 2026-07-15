# Changelog

All notable changes to Event Certificate Manager will be documented here.

## Unreleased

### Added

- Template Builder
- Template element CRUD
- Builder properties panel
- Live AJAX property editing
- Automatic PDF preview generation
- Session participant assignment
- Participant CSV import and export

### Changed

- Refactored the event module into dedicated traits.
- Refactored template functionality into dedicated CRUD, builder, element, preview, and renderer traits.
- Moved session configuration into the main event Settings tab.

### Fixed

- Fixed session update creating duplicate records.
- Fixed AJAX session participant search registration.
- Fixed template element updates referencing a missing `updated_at` column.

### Added
- Added interaction frames and future resize handles for Builder elements.
- Added keyboard-accessible Builder element selection.

### Added
- Added native Pointer Events drag interaction for Builder elements.
- Added live X/Y synchronization and automatic position persistence.
- Added canvas containment for element movement.

### Changed
- Added a dedicated Builder workspace and zoom-wrapper structure.
- Prepared the certificate canvas for workspace zoom, grids, and alignment guides.

### Added
- Added Builder workspace zoom controls.
- Added Zoom In, Zoom Out, Reset, Fit Width, and Fit Page.
- Added zoom-aware pointer dragging while preserving certificate coordinates.

## Sprint 05.10.3 — Font Manager Foundation

### Added
- Introduced a new filesystem-based Font Manager module.
- Added `ECM_Font_Manager` for centralized font management.
- Implemented automatic font manifest (`fonts.json`) handling.
- Added support for registering and unregistering fonts.
- Added built-in font catalog management.
- Added helper methods for font paths, URLs and font-face CSS generation.
- Added support for custom and Google font storage.
- Created automatic `google/` and `custom/` font directories.
- Removed the need for a dedicated `wp_ecm_fonts` database table.

### Changed
- Migrated font architecture from database-driven to filesystem-driven.
- Builder is now prepared to consume fonts from the Font Manager.

### Added
- Added curated Google Fonts to the Builder font picker.
- Added live Google Font previews.
- Added secure local installation of selected Google Fonts.
- Added local stylesheet reuse for installed fonts.

### Changed
- Refactored the Participants module into dedicated UI, CRUD, CSV import, and CSV export traits.
- Added reusable participant field validation and participant-tab URL helpers.
- Improved participant action sanitization and module documentation.

### Changed
- Refactored the Sessions module into dedicated event UI, CRUD, session-participant UI, and participant-assignment traits.
- Moved session creation out of the central event controller.
- Added reusable session URL and request-sanitization helpers.
- Improved session module documentation and responsibility separation.
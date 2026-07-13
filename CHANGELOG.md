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
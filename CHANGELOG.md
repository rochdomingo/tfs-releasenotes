# Changelog

All notable changes to the TFS Release Notes Generator will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-26

### Added
- **PDF Export Functionality**
  - Integrated html2pdf.js library for client-side PDF generation
  - Added "Download PDF" button in WYSIWYG editor (Step 4)
  - Configured A4 format with proper margins and pagination
  - Support for high-quality image rendering in PDFs
  - Smart pagebreak handling for tables and images
  - Loading indicators during PDF generation

- **Modern UI Redesign**
  - Implemented Tailwind CSS framework for styling
  - TailAdmin-inspired design system with custom color scheme
  - Card-based layout with numbered step badges
  - Consistent spacing and padding across all sections
  - Improved form styling with focus states
  - Modern tabs, buttons, and status message components
  - Enhanced table styling with hover effects
  - Professional filter panels

- **Product Backlog Item (PBI) Support**
  - Full support for PBIs alongside Features
  - PBI-specific field mapping (Deltek.PBI.* fields)
  - Work Item Type filter to toggle between Features and PBIs
  - Updated UI labels to indicate PBI support
  - Conditional field handling based on work item type

- **Image Proxy System**
  - Created image_proxy.php for authenticated TFS image access
  - Automatic URL replacement in WYSIWYG editor
  - Original URL restoration in markdown exports
  - Support for both markdown and HTML image syntax

### Changed
- **UI/UX Improvements**
  - Replaced old styles.css with Tailwind CSS
  - Updated all sections to use card-based layout
  - Improved visual hierarchy with step numbers
  - Better button styling and positioning
  - Enhanced status messages (loading, success, error)

- **Export Workflow**
  - Renamed "Convert & Download Markdown" to "Download Markdown"
  - Added description text mentioning both export formats
  - Improved export status messages

- **Performance Optimizations**
  - Parallel fetching of Features and Bugs queries
  - Configurable hierarchy depth (1-3 levels)
  - Progress tracking during fetch operations
  - Optimized filtering for large datasets

### Fixed
- **PBI Query Issues**
  - Fixed 0 results when querying PBIs
  - Backend now accepts 'Product Backlog Item' type
  - Frontend filter properly handles both Features and PBIs
  - Correct field mapping for PBI documentation fields

- **Image Display Issues**
  - TFS images now display correctly in WYSIWYG editor
  - Proxy handles authentication transparently
  - Original URLs preserved in final exports

- **Layout Consistency**
  - All steps (1-4) maintain consistent width
  - Proper div nesting and structure
  - Fixed tab content padding and alignment

### Technical
- **Dependencies Added**
  - Tailwind CSS CDN v3.x
  - html2pdf.js v0.10.1
  - Maintained TinyMCE 6 and Turndown.js

- **CSS Architecture**
  - Replaced @apply directives with regular CSS
  - Better CDN compatibility
  - Custom utility classes for repeated patterns

## [0.2.0] - 2026-02-25

### Added
- **WYSIWYG Editor Integration**
  - TinyMCE editor for live editing of release notes
  - HTML to Markdown conversion using Turndown.js
  - Visual editing before export
  - Auto-hide editor after successful download

- **Performance Features**
  - Configurable hierarchy depth for feature fetching
  - Parallel query execution
  - Progress indicators and timing information
  - Detailed console logging for debugging

### Changed
- **Export Workflow**
  - Generate release notes opens in WYSIWYG editor
  - Markdown download happens from editor
  - Better user feedback during generation

## [0.1.0] - 2026-02-20

### Added
- Initial release of TFS Release Notes Generator
- **Core Features**
  - Fetch work items from Azure DevOps queries
  - Support for Features and Bugs queries
  - Hierarchy fetching for child items
  - Advanced filtering system
  - Direct markdown generation
  - JSON export for Claude Code processing

- **UI Components**
  - Step-by-step workflow interface
  - Tabbed view for Features and Bugs
  - Expandable work item tables
  - Changeset tracking display
  - Advanced filter panels

- **Backend APIs**
  - fetch_workitems.php for TFS integration
  - generate_release_notes.php for markdown generation
  - export_for_claude_slim.php for JSON export
  - TFS configuration via JSON file

### Documentation
- Initial README.md with setup instructions
- CLAUDE.md with AI generation instructions
- Configuration template

---

## Legend

- **Added**: New features
- **Changed**: Changes to existing functionality
- **Deprecated**: Soon-to-be removed features
- **Removed**: Removed features
- **Fixed**: Bug fixes
- **Security**: Security improvements
- **Technical**: Technical/infrastructure changes

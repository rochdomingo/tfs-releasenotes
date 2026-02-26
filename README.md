# TFS Release Notes Generator

A web-based tool for generating professional release notes from Azure DevOps (TFS) work items with WYSIWYG editing and multiple export formats.

## Overview

The TFS Release Notes Generator streamlines the process of creating release documentation by fetching work items directly from Azure DevOps queries, allowing team members to review, filter, and export release notes in Markdown or PDF format.

## Features

### 🎯 Work Item Management
- **Dual Query Support**: Fetch Features/PBIs and Bugs from separate queries
- **Hierarchy Fetching**: Configurable depth (1-3 levels) for child items
- **Smart Filtering**: Advanced filtering by state, tags, editing status, and more
- **Type Support**: Full support for Features and Product Backlog Items (PBIs)
- **Real-time Display**: Interactive tables with expandable child items
- **Changeset Tracking**: View associated code changes for each work item

### 🎨 Modern UI
- **TailAdmin-Inspired Design**: Clean, professional interface with Tailwind CSS
- **Card-Based Layout**: Organized step-by-step workflow
- **Tabbed Interface**: Separate tabs for Features and Bugs
- **Responsive Design**: Works on desktop and tablet devices
- **Status Indicators**: Clear visual feedback for loading, success, and errors

### ✏️ WYSIWYG Editor
- **TinyMCE Integration**: Full-featured rich text editor
- **Live Preview**: See exactly how your release notes will look
- **Image Support**: Proxied TFS images with authentication
- **Flexible Editing**: Modify content before export

### 📥 Export Options
- **Markdown Export**: Clean `.md` files for documentation platforms
- **PDF Export**: Professional A4-formatted PDFs with proper pagination
- **JSON Export**: Raw data export for Claude Code processing
- **Auto-naming**: Files named based on version/date

### 🔍 Advanced Filtering
- **Work Item Type**: Filter by Feature or PBI
- **Editing Status**: Filter by documentation completion status
- **State Filtering**: Multi-select state filtering (Released, Done, etc.)
- **Tag Filtering**: Include or exclude by tags
- **Keyword Exclusion**: Exclude SPIKEs, technical items, etc.
- **Disclose to Clients**: Filter based on disclosure settings
- **Descendant Filtering**: Filters apply to child items up to 3 levels deep

### ⚡ Performance Features
- **Parallel Fetching**: Fetch Features and Bugs simultaneously
- **Configurable Depth**: Choose hierarchy depth based on needs
- **Progress Tracking**: Real-time progress indicators
- **Error Handling**: Comprehensive error messages and logging

## Prerequisites

- **Web Server**: Apache with PHP support (XAMPP, WAMP, or similar)
- **PHP**: Version 7.0 or higher
- **cURL Extension**: For TFS API communication
- **Modern Browser**: Chrome, Firefox, Edge, or Safari
- **Azure DevOps Access**: Valid PAT token with read permissions

## Installation

1. **Clone or Download** the repository to your web server directory:
   ```bash
   git clone <repository-url> /path/to/webroot/tfs-releasenotes
   ```

2. **Configure TFS Connection**:
   Create a `tfs_config.json` file in the root directory:
   ```json
   {
     "pat": "your-personal-access-token",
     "organization": "your-org-name",
     "project": "your-project-name",
     "apiVersion": "7.0"
   }
   ```

3. **Set Permissions**:
   ```bash
   chmod 644 tfs_config.json
   chmod 755 exports/
   ```

4. **Access the Application**:
   Navigate to `http://localhost/tfs-releasenotes/` in your browser

## Usage Guide

### Step 1: Fetch Work Items

1. Enter your **Features Query ID** (GUID from Azure DevOps)
2. Enter your **Bugs Query ID** (optional)
3. Select **Hierarchy Depth** (1-3 levels)
4. Click **"Fetch Work Items"**

**Tips:**
- Query IDs can be found in the URL when viewing a query in Azure DevOps
- Lower hierarchy depth = faster fetching
- You can provide one or both query IDs

### Step 2: Review and Filter

#### Features Tab
- View all Features and PBIs returned from the query
- Apply **Advanced Filters** to narrow down items:
  - Work Item Type (Feature vs PBI)
  - Editing Status
  - State (Released, Done, etc.)
  - Tags (include/exclude)
  - Keywords (exclude SPIKE, etc.)
  - Disclose to Clients
- Expand work items to see child PBIs, Tasks, and changesets
- Check/uncheck items to include in export

#### Bugs Tab
- View all Bugs/Issues returned from the query
- Apply bug-specific filters:
  - Work Item Type
  - State
  - Priority
  - Tags
  - Disclose to Clients
- Review Files Deployed information
- Select bugs to include in release notes

### Step 3: Export or Generate

1. **Enter Release Information**:
   - Release Version (e.g., 8.5.0)
   - Release Date

2. **Choose Export Option**:
   - **📝 Generate Release Notes**: Opens WYSIWYG editor
   - **📥 Export for Claude Code**: Downloads JSON for AI processing

### Step 4: Review and Edit (WYSIWYG)

1. Review the generated release notes in the editor
2. Make any necessary edits:
   - Modify text
   - Add/remove sections
   - Adjust formatting
3. Choose export format:
   - **📥 Download Markdown**: `.md` file
   - **📄 Download PDF**: Professional PDF document

## File Structure

```
tfs-releasenotes/
├── index.php                      # Main application file
├── fetch_workitems.php           # Backend API for fetching work items
├── generate_release_notes.php    # Release notes generation logic
├── export_for_claude_slim.php    # JSON export for Claude Code
├── image_proxy.php               # Proxy for TFS images
├── tfs_config.json               # TFS configuration (not in repo)
├── exports/                      # Generated release notes
├── CLAUDE.md                     # Claude Code instructions
├── README.md                     # This file
└── CHANGELOG.md                  # Version history
```

## Advanced Features

### Image Proxy
TFS images require authentication. The tool automatically proxies images through `image_proxy.php` to display them in the editor, then restores original URLs in the final export.

### Changeset Integration
View code changes associated with work items:
- Changeset IDs displayed for child items
- Links to Azure DevOps changesets
- File change summaries

### Descendant Filtering
Filters apply not just to parent items but also to descendants:
- Level 1: Direct children (PBIs, Tasks)
- Level 2: Grandchildren (sub-tasks)
- Level 3: Great-grandchildren
- Items show count of matching descendants

## Troubleshooting

### No Work Items Returned
- Verify query IDs are correct GUIDs
- Check PAT token has read permissions
- Ensure queries return Feature/PBI or Bug work items
- Check browser console (F12) for API errors

### Images Not Displaying
- Verify PAT token is valid
- Check `image_proxy.php` has correct permissions
- Ensure TFS URLs are accessible from server

### PDF Generation Issues
- Allow popups in browser
- Check file downloads aren't blocked
- Large documents may take 30-60 seconds
- Try reducing hierarchy depth if timeout occurs

### Performance Issues
- Reduce hierarchy depth (use Level 1 instead of 3)
- Filter results before generating release notes
- Use more specific queries in Azure DevOps
- Clear browser cache and refresh

## Browser Compatibility

- ✅ Chrome 90+ (Recommended)
- ✅ Firefox 88+
- ✅ Edge 90+
- ✅ Safari 14+

## Security Notes

- **Never commit** `tfs_config.json` to version control
- PAT tokens should have **read-only** permissions
- Use HTTPS in production environments
- Regularly rotate PAT tokens
- Review generated exports before sharing externally

## Contributing

This tool was developed for internal use. If you'd like to suggest improvements:

1. Create a feature request with detailed description
2. Document the use case and expected behavior
3. Include screenshots or examples if applicable

## Support

For issues or questions:
- Check the [Changelog](CHANGELOG.md) for recent fixes
- Review error messages in browser console (F12)
- Contact the development team with specific error details

## Credits

Built with:
- [Tailwind CSS](https://tailwindcss.com/) - UI Framework
- [TinyMCE](https://www.tiny.cloud/) - WYSIWYG Editor
- [html2pdf.js](https://github.com/eKoopmans/html2pdf.js) - PDF Generation
- [Turndown](https://github.com/mixmark-io/turndown) - HTML to Markdown

Inspired by [TailAdmin](https://tailadmin.com/) design system.

## License

Internal use only. All rights reserved.

---

**Version**: 1.0.0
**Last Updated**: February 2026
**Maintained by**: Development Team

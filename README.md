# TFS Release Notes Generator

A web-based tool for fetching work items from Azure DevOps (TFS) and generating release notes using Claude Code.

## Features

- Fetch work items from TFS using query IDs
- Filter features and bugs with advanced criteria
- **Generate release notes directly in the application** (Markdown format)
- Export data in a format optimized for Claude Code processing
- Generate comprehensive release notes following the Cobra format

## Files

- **index.php** - Main web interface for fetching and filtering work items
- **fetch_workitems.php** - Backend script for fetching work items from TFS
- **generate_release_notes.php** - Backend script for generating Markdown release notes
- **export_for_claude_slim.php** - Backend script for exporting filtered work items
- **styles.css** - Stylesheet for the web interface
- **CLAUDE.md** - Instructions for Claude Code to generate release notes
- **tfs_config.json.template** - Template for TFS configuration

## Setup

1. Copy `tfs_config.json.template` to `tfs_config.json`
2. Edit `tfs_config.json` and add your TFS credentials:
   ```json
   {
     "base_url": "https://tfs.deltek.com/tfs/Deltek",
     "pat": "YOUR_PERSONAL_ACCESS_TOKEN",
     "default_organization": "Deltek",
     "default_project": "Cobra"
   }
   ```
3. Deploy to a PHP-enabled web server (e.g., Apache, XAMPP)
4. Access `index.php` in your browser

## Usage

### Step 1: Fetch Work Items
1. Enter TFS Query GUIDs for Features and/or Bugs
2. Click "Fetch Work Items" to retrieve data from TFS

### Step 2: Filter Work Items
1. Switch between Features and Bugs tabs
2. Use Advanced Filters to refine the work items:
   - Editing Status
   - State
   - Tags (include/exclude)
   - Keywords (exclude)
   - Disclose to Clients flag
   - Priority (for bugs)

### Step 3: Export or Generate Release Notes

You have two options for generating release notes:

#### Option A: Generate Release Notes Directly (Recommended)
1. Enter Release Version (e.g., "8.5.0")
2. Select Release Date (defaults to today)
3. Select the work items you want to include
4. Click "📝 Generate Release Notes"
5. A Markdown (.md) file will be downloaded with:
   - New Features and Enhancements
   - Software Issues Resolved
   - Security Enhancements
   - Database Changes
   - Data Changes

#### Option B: Export for Claude Code
1. Select the work items you want to include
2. Click "📥 Export for Claude Code"
3. A JSON file will be downloaded
4. Open the exported JSON file in Claude Code
5. Claude will use the instructions in `CLAUDE.md` to generate properly formatted release notes

## Requirements

- PHP 7.4 or higher
- cURL extension enabled
- Access to Azure DevOps/TFS with a valid Personal Access Token

## Security Notes

- Never commit `tfs_config.json` to version control (it contains your PAT)
- The `.gitignore` file is configured to exclude sensitive files
- Keep your Personal Access Token secure and rotate it regularly

## License

Internal use only - Deltek Cobra project

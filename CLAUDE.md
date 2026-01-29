# CLAUDE.md — Release Notes Generation Guide
**Cobra RN Format with Code References, Feature Formatting, and Bug Files Deployed**

This guide instructs Claude Code how to generate Release Notes for Cobra using the release‑context data.
This version adds **changeset support**, **feature code references**, **bug files deployed**,
and **proper formatting for New Features**.

**OUTPUT FORMAT: MIXED (Markdown + HTML)**
- Generate release notes as a `.md` file
- Use **Markdown** for structure: headings, lists, separators
- Keep source content from JSON in **HTML format** (DO NOT convert):
  - `relNotesDescription` → keep HTML as-is
  - `acceptanceCriteria` → keep HTML as-is
  - `description` → keep HTML as-is
- Preserve ALL HTML formatting exactly as provided in the JSON data

------------------------------------------------------------------------

# 1. INPUT CONTEXT

Claude must use ONLY the structured data inside a `.json` file that the user will provide.

Important fields include:

- Work item metadata  
- Acceptance Criteria  
- Linked items  
- Tag checks (Must Have, SPIKE, ENG, etc.)  
- DiscloseToClients flags  
- EditingStatus  
- Database‑related fields  
- changesets  
- filesChanged  
- aggregatedChangesets  
- aggregatedFilesChanged  
- codeReferences  

Claude must NOT invent data.

------------------------------------------------------------------------

# 2. HARD FILTERS

Claude must NOT include any work item that meets:

- `DiscloseToClients = No`
- or `DiscloseToClients = Yes` AND `hasSpawnedTag = true`

------------------------------------------------------------------------

# 3. SOFT FILTERS

These items may appear but Claude must apply documentation judgment:

- Prefer items with `editStatus = "Final Review Complete"`
- SPIKE, ENG, Performance Testing, Inheritance → internal items unless user asks
- Must Have items appear first under New Features

------------------------------------------------------------------------

# 4. ACCEPTANCE CRITERIA PROCESSING

AC must be kept in original HTML format:

- DO NOT convert HTML to Markdown
- Keep HTML tables as-is
- Preserve all HTML formatting (bullets, numbered lists, tables, etc.)
- Output exactly as provided in the `acceptanceCriteria` field
- Only clean up malformed HTML if absolutely necessary (empty tags, unclosed elements)  

------------------------------------------------------------------------

# 5. NEW FEATURES AND ENHANCEMENTS  

For each Feature-like work item:

Use the heading EXACTLY as supplied in `relNotesTitle`.
Format:

```markdown
### {relNotesTitle}
```

DO NOT rewrite titles.
DO NOT change capitalization.
DO NOT apply stylistic edits.

------------------------------------------------------------------------

### 5.2 Summary paragraph
Write a short, user-facing summary describing the feature.  
Do NOT collapse paragraphs into one block.

------------------------------------------------------------------------

### 5.3 Details Section

Output the `relNotesDescription` field EXACTLY as-is in HTML format:

- DO NOT rewrite or convert to Markdown
- Preserve ALL HTML tags and formatting
- Keep paragraph breaks (`<div>`, `<p>`, `<br>`)
- Keep bulleted/numbered lists (`<ul>`, `<ol>`, `<li>`)
- Keep bold/italic emphasis (`<b>`, `<i>`, `<strong>`, `<em>`)
- Keep all images (`<img>` tags) exactly as-is
- Keep all tables (`<table>`, `<tr>`, `<td>`, etc.) exactly as-is

------------------------------------------------------------------------

### 5.4 Database Structure (Feature Only)

Include full SQL Server and Oracle definitions **only in Features**, not in Database Changes.

- Use Markdown tables
- Include column metadata
- Include index metadata
- If the JSON has HTML tables, convert them to Markdown tables  

------------------------------------------------------------------------

### 5.5 Linked Items

If child PBIs/Tasks contain AC or useful descriptions, include summaries or AC tables.

------------------------------------------------------------------------

### 5.7 Code References (from aggregatedFilesChanged / codeReferences)

**Aggregation Logic:**
- If the feature has `aggregatedFilesChanged` or `codeReferences` fields, use those directly
- If NOT present, manually aggregate changesets from child items:
  - Collect all `changesets` arrays from child PBIs and Tasks
  - For each changeset, fetch file information to build the file list
  - Deduplicate changesets by ID
  - Map files to their corresponding changeset IDs

If the Feature has file references (direct or aggregated):

```
<div class="feature-files">
  <h4>Code References</h4>
  <p><b>Generated Description From Code:</b> [generated_description]</p>
  <h4>Files Changed</h4>
  <ul>
    <li>relative/path/to/file1 (edit) — <a href="https://dev.azure.com/{organization}/{project}/_versionControl/changeset/{changesetId}">Changeset {changesetId}</a></li>
    <li>relative/path/to/file2 (add) — <a href="https://dev.azure.com/{organization}/{project}/_versionControl/changeset/{changesetId}">Changeset {changesetId}</a></li>
  </ul>
</div>
```

Rules:
- [generated_description] - read all files from changeset list and generate an active voice, user-facing description
- Use `<div class="feature-files">` as the wrapper element.
- Use `<h4>Code References</h4>` inside the div.
- Use an unordered list (`<ul><li>…</li></ul>`).
- Strip the TFVC root prefix from file paths when displaying (e.g. `$/Cobra/Main/src/...` → `src/...`).
- When a `changesetId` is known for a file (from `codeReferences` or similar mapping), append a hyperlink to the Azure DevOps changeset.

  Base URL format (Option A):

  ```text
  https://dev.azure.com/{organization}/{project}/_versionControl/changeset/{changesetId}
  ```

- Replace `{organization}` and `{project}` with Cobra’s Azure DevOps organization and project, or use a configured base URL if provided elsewhere in the context.
- If multiple files share the same changeset, reuse the same link.
- If `changesetId` is not available for a file, list the file without the changeset link.
- Limit the visible list to ~10 files if there are many, then summarize the rest (for example: “…and 14 more files modified in this feature.”).
- Only Features may use this HTML block. Do NOT use it for Bugs/Defects.

------------------------------------------------------------------------

# 6. SOFTWARE ISSUES RESOLVED
Remove the work item title entirely.
Format each defect like:

```markdown
**Defect {ID}**
```

### 6.1 If `relNotesDescription` exists:
- Output exactly the text from `relNotesDescription`
- **Preserve ALL formatting**
  - Line breaks  
  - Bullets  
  - Bold/italic  
  - Indentation  
  - Markdown tables  
  - Images  

No rewriting. No collapsing.

### 6.2 If no relNotesDescription:
Produce a brief, user-facing summary.

------------------------------------------------------------------------

### 6.3 Files Deployed (if filesChanged exists)

```markdown
#### Files Deployed

Files changed:
- relative/path/file1 (edit)
- relative/path/file2 (add)
```

Rules:

- Use Markdown format
- Strip `$/Cobra/Main/` prefix from file paths
- Include change type: (add), (edit), (delete), or (rename)  

------------------------------------------------------------------------

# 7. DATABASE CHANGES
STRICT MINIMAL Cobra format (Markdown tables only).

### 7.1 Tables
```markdown
### Tables
#### New Tables
| Table Name |
|-----------|
| NARRANALYSIS |
```

### 7.2 Columns
```markdown
### Columns
#### New Columns
| Table Name | Column Name | Data Type |
|------------|-------------|-----------|
| NARRANALYSIS | PROJ | nvarchar(10) |
```

SQL Server types only.

### 7.3 Indexes
```markdown
### Indexes
#### New Indexes
| Table Name | Index Name | Index Fields |
|------------|------------|--------------|
```

------------------------------------------------------------------------

# 8. DATA CHANGES

If none:

```
No data changes are included in this release.
```

------------------------------------------------------------------------

# 9. INTERNAL CHANGES  
Include only if user requests them.

------------------------------------------------------------------------

# 10. STYLE RULES

- **Output format:** Generate as `.md` file (Markdown)
- **Structure:** Use Markdown for headings, lists, tables, separators
- **Source content:** Keep HTML content from JSON fields as-is (DO NOT convert):
  - `relNotesDescription` → preserve HTML exactly
  - `acceptanceCriteria` → preserve HTML exactly
  - `description` → preserve HTML exactly
- **Database tables:** Use Markdown tables
- **Code References:** Use HTML (as specified in section 5.7)
- **No hallucination:** Only use data from the JSON file
- **No rewriting:** Use exact titles from relNotesTitle/title fields
- **CSS Header:** Add this at the very top of the generated .md file:
  ```html
  <link href="https://education.deltek.com/web/dlh/deltek-styles.css" rel="stylesheet" crossorigin="anonymous">
  <style>.feature-files{ background: #fff9e6; padding: 20px; border-radius: 9px; }</style>
  ```

------------------------------------------------------------------------

# END OF CLAUDE.md

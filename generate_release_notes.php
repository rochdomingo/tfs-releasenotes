<?php
/**
 * Release Notes Generator
 * Generates Markdown release notes from TFS work items
 * Following CLAUDE.md format specifications
 */

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load TFS config for work item links
$config = json_decode(file_get_contents('tfs_config.json'), true);
$TFS_BASE_URL = $config['base_url'] ?? 'https://dev.azure.com/yourorg';
$TFS_PROJECT = $config['default_project'] ?? 'YourProject';

// Get POST data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data || !isset($data['features']) || !isset($data['bugs'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request. Features and bugs arrays are required.']);
    exit;
}

$features = $data['features'];
$bugs = $data['bugs'];
$releaseVersion = $data['releaseVersion'] ?? 'Version TBD';
$releaseDate = $data['releaseDate'] ?? date('F j, Y');

// Create exports directory if it doesn't exist
$exportsDir = __DIR__ . '/exports';
if (!file_exists($exportsDir)) {
    mkdir($exportsDir, 0755, true);
}

// Generate filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$filename = "release_notes_{$timestamp}.md";
$filepath = $exportsDir . '/' . $filename;

// Start building the release notes
$markdown = generateReleaseNotes($features, $bugs, $releaseVersion, $releaseDate);

// Write to file
file_put_contents($filepath, $markdown);

// Return success response
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'filepath' => $filepath,
    'message' => 'Release notes generated successfully'
]);

/**
 * Helper function to extract title from HTML heading tags
 */
function extractTitleFromHtml($html) {
    if (empty($html)) {
        return ['title' => '', 'content' => $html];
    }

    // Try to find h2, h3, or h4 tags at the beginning
    if (preg_match('/^\s*<h[234][^>]*>(.*?)<\/h[234]>/is', $html, $matches)) {
        $title = strip_tags($matches[1]);
        // Remove the heading from content
        $content = preg_replace('/^\s*<h[234][^>]*>.*?<\/h[234]>/is', '', $html, 1);
        return ['title' => trim($title), 'content' => $content];
    }

    return ['title' => '', 'content' => $html];
}

/**
 * Replace TFS image URLs with proxy URLs
 * TFS attachments require authentication, so we proxy them through our server
 */
function proxyTfsImages($html) {
    if (empty($html)) {
        return $html;
    }

    // Find all img tags with TFS/Azure DevOps URLs
    $pattern = '/<img([^>]*?)src=["\']([^"\']*(?:tfs\.deltek\.com|dev\.azure\.com)[^"\']*)["\']([^>]*?)>/i';

    $html = preg_replace_callback($pattern, function($matches) {
        $beforeSrc = $matches[1];
        $originalUrl = $matches[2];
        $afterSrc = $matches[3];

        // Encode the URL for the proxy
        $proxyUrl = 'image_proxy.php?url=' . urlencode($originalUrl);

        // Rebuild the img tag with proxy URL
        return '<img' . $beforeSrc . 'src="' . $proxyUrl . '"' . $afterSrc . '>';
    }, $html);

    return $html;
}

/**
 * Main function to generate release notes
 */
function generateReleaseNotes($features, $bugs, $releaseVersion, $releaseDate) {
    $md = '';

    // Add CSS header (CLAUDE.md requirement)
    $md .= '<link href="https://education.deltek.com/web/dlh/deltek-styles.css" rel="stylesheet" crossorigin="anonymous">' . "\n";
    $md .= '<style>.feature-files{ background: #fff9e6; padding: 20px; border-radius: 9px; }</style>' . "\n\n";

    // Title and metadata (HTML format for WYSIWYG editor)
    $md .= "<h1>Cobra Release Notes</h1>\n\n";
    $md .= "<p><strong>Version:</strong> {$releaseVersion}</p>\n\n";
    $md .= "<p><strong>Release Date:</strong> {$releaseDate}</p>\n\n";
    $md .= "<hr>\n\n";

    // Table of Contents (HTML format)
    $md .= "<h2>Table of Contents</h2>\n\n";
    $md .= "<ul>\n";
    $md .= "<li><a href=\"#new-features-and-enhancements\">New Features and Enhancements</a></li>\n";
    $md .= "<li><a href=\"#software-issues-resolved\">Software Issues Resolved</a></li>\n";
    $md .= "<li><a href=\"#security-enhancements\">Security Enhancements</a></li>\n";
    $md .= "<li><a href=\"#database-changes\">Database Changes</a></li>\n";
    $md .= "<li><a href=\"#data-changes\">Data Changes</a></li>\n";
    $md .= "</ul>\n\n";
    $md .= "<hr>\n\n";

    // No filtering needed - user has already selected items in the UI
    // Use features and bugs directly as provided by the frontend

    // Section 1: New Features and Enhancements
    $md .= generateFeaturesSection($features);

    // Section 2: Software Issues Resolved
    $md .= generateBugsSection($bugs);

    // Section 3: Security Enhancements
    $md .= generateSecuritySection($features, $bugs);

    // Section 4: Database Changes
    $md .= generateDatabaseChangesSection($features);

    // Section 5: Data Changes
    $md .= "<h2 id=\"data-changes\">Data Changes</h2>\n\n";
    $md .= "<p>No data changes are included in this release.</p>\n\n";

    return $md;
}

/**
 * Apply hard filters from CLAUDE.md
 * Exclude items with DiscloseToClients = No OR (DiscloseToClients = Yes AND hasSpawnedTag = true)
 */
// Note: Hard filters removed - user selection in UI is the filter
// This function kept for backwards compatibility with export_for_claude_slim.php workflow
function applyHardFilters($items) {
    return array_filter($items, function($item) {
        $disclose = $item['DiscloseToClients'] ?? 'No';
        $hasSpawned = $item['hasSpawnedTag'] ?? false;

        // Exclude if DiscloseToClients = No
        if ($disclose === 'No') {
            return false;
        }

        // Exclude if DiscloseToClients = Yes AND hasSpawnedTag = true
        if ($disclose === 'Yes' && $hasSpawned === true) {
            return false;
        }

        return true;
    });
}

/**
 * Sort features with Must Have first
 */
function sortFeatures($features) {
    usort($features, function($a, $b) {
        $aMustHave = ($a['isMustHave'] ?? false) === true;
        $bMustHave = ($b['isMustHave'] ?? false) === true;

        if ($aMustHave && !$bMustHave) return -1;
        if (!$aMustHave && $bMustHave) return 1;
        return 0;
    });
    return $features;
}

/**
 * Generate New Features and Enhancements section
 */
function generateFeaturesSection($features) {
    $md = "<h2 id=\"new-features-and-enhancements\">New Features and Enhancements</h2>\n\n";

    if (empty($features)) {
        $md .= "<p>No new features in this release.</p>\n\n";
        return $md;
    }

    // Sort features (Must Have first)
    $features = sortFeatures($features);

    foreach ($features as $feature) {
        $md .= generateFeature($feature);
    }

    return $md;
}

/**
 * Generate a single feature following CLAUDE.md format
 */
function generateFeature($feature) {
    global $TFS_BASE_URL, $TFS_PROJECT;

    $md = '';
    $id = $feature['id'] ?? '';

    // Add work item ID with link for verification (HTML format)
    if ($id) {
        $workItemUrl = "{$TFS_BASE_URL}/{$TFS_PROJECT}/_workitems/edit/{$id}";
        $md .= "<p><em>Work Item: <a href=\"{$workItemUrl}\">#{$id}</a></em></p>\n\n";
    }

    if (!empty($feature['relNotesDescription'])) {
        // Proxy TFS images through our server (for authentication)
        $content = proxyTfsImages($feature['relNotesDescription']);

        // Extract title from HTML if present
        $extracted = extractTitleFromHtml($content);

        if (!empty($extracted['title'])) {
            // Found a title in HTML - use it as HTML h3
            $md .= "<h3>{$extracted['title']}</h3>\n\n";
            // Keep remaining HTML content as-is (with proxied images)
            $md .= trim($extracted['content']) . "\n\n";
        } else {
            // No title found, use relNotesTitle or title field
            $title = $feature['relNotesTitle'] ?? $feature['title'] ?? 'Untitled Feature';
            $md .= "<h3>{$title}</h3>\n\n";
            // Keep HTML content as-is (with proxied images)
            $md .= $content . "\n\n";
        }
    } else {
        // Fallback: if no relNotesDescription, show basic info
        $title = $feature['relNotesTitle'] ?? $feature['title'] ?? 'Untitled Feature';
        $md .= "<h3>{$title}</h3>\n\n";
        $summary = $feature['description'] ?? "This feature enhances the Cobra system.";
        $md .= "<p>{$summary}</p>\n\n";
    }

    // Database Structure (if available)
    if (!empty($feature['databaseChanges'])) {
        $md .= generateFeatureDatabaseStructure($feature['databaseChanges']);
    }

    // Code References (from aggregatedFilesChanged or filesChanged)
    if (!empty($feature['filesChanged']) || !empty($feature['aggregatedFilesChanged'])) {
        $md .= generateCodeReferences($feature);
    }

    $md .= "<hr>\n\n";

    return $md;
}

/**
 * Generate code references section for features
 */
function generateCodeReferences($feature) {
    $files = $feature['aggregatedFilesChanged'] ?? $feature['filesChanged'] ?? [];

    if (empty($files)) {
        return '';
    }

    $html = '<div class="feature-files">' . "\n";
    $html .= '  <h4>Code References</h4>' . "\n";

    // Generate description from code
    $description = generateCodeDescription($files);
    $html .= '  <p><b>Generated Description From Code:</b> ' . $description . '</p>' . "\n";

    $html .= '  <h4>Files Changed</h4>' . "\n";
    $html .= '  <ul>' . "\n";

    // Limit to 10 files
    $displayFiles = array_slice($files, 0, 10);
    $remainingCount = count($files) - count($displayFiles);

    foreach ($displayFiles as $file) {
        // Check if this is a full file entry or just a changeset reference
        if (isset($file['path'])) {
            // Full file entry with path
            $path = $file['path'];
            $changeType = $file['changeType'] ?? 'edit';
            $changesetId = $file['changesetId'] ?? '';

            // Strip TFVC prefix
            $displayPath = stripTfvcPrefix($path);

            $html .= '    <li>' . htmlspecialchars($displayPath) . ' (' . htmlspecialchars($changeType) . ')';

            // Add changeset link if available
            if ($changesetId) {
                $changesetUrl = "https://dev.azure.com/Deltek/Cobra/_versionControl/changeset/{$changesetId}";
                $html .= ' — <a href="' . $changesetUrl . '">Changeset ' . $changesetId . '</a>';
            }

            $html .= '</li>' . "\n";
        } elseif (isset($file['changesetId'])) {
            // Just a changeset reference without file details
            $changesetId = $file['changesetId'];
            $comment = !empty($file['comment']) ? htmlspecialchars($file['comment']) : 'Code changes';
            $changesetUrl = "https://dev.azure.com/Deltek/Cobra/_versionControl/changeset/{$changesetId}";

            $html .= '    <li><a href="' . $changesetUrl . '">Changeset ' . $changesetId . '</a> — ' . $comment . '</li>' . "\n";
        }
    }

    if ($remainingCount > 0) {
        $html .= '    <li><em>…and ' . $remainingCount . ' more files modified in this feature.</em></li>' . "\n";
    }

    $html .= '  </ul>' . "\n";
    $html .= '</div>' . "\n\n";

    return $html;
}

/**
 * Generate active voice description from file changes
 */
function generateCodeDescription($files) {
    if (empty($files)) {
        return 'No code changes detected.';
    }

    // Check if we have file paths or just changesets
    $hasFilePaths = false;
    foreach ($files as $file) {
        if (isset($file['path'])) {
            $hasFilePaths = true;
            break;
        }
    }

    if (!$hasFilePaths) {
        // Just have changeset references
        $changesetCount = count($files);
        return "This feature includes {$changesetCount} changeset" . ($changesetCount > 1 ? 's' : '') . " with code modifications.";
    }

    // Have detailed file information
    $fileCount = count($files);
    $addCount = 0;
    $editCount = 0;
    $deleteCount = 0;

    foreach ($files as $file) {
        $changeType = strtolower($file['changeType'] ?? 'edit');
        if ($changeType === 'add') $addCount++;
        elseif ($changeType === 'delete') $deleteCount++;
        else $editCount++;
    }

    $description = "This feature modifies {$fileCount} file(s)";

    $parts = [];
    if ($addCount > 0) $parts[] = "{$addCount} added";
    if ($editCount > 0) $parts[] = "{$editCount} modified";
    if ($deleteCount > 0) $parts[] = "{$deleteCount} deleted";

    if (!empty($parts)) {
        $description .= " (" . implode(", ", $parts) . ")";
    }

    $description .= " across the codebase.";

    return $description;
}

/**
 * Strip TFVC prefix from file paths
 */
function stripTfvcPrefix($path) {
    // Remove $/Cobra/Main/ prefix
    $prefixes = ['$/Cobra/Main/', '$/Cobra/', '$/'];
    foreach ($prefixes as $prefix) {
        if (strpos($path, $prefix) === 0) {
            return substr($path, strlen($prefix));
        }
    }
    return $path;
}

/**
 * Generate database structure for features (full SQL Server definitions)
 */
function generateFeatureDatabaseStructure($dbChanges) {
    $md = "<p><strong>Database Structure:</strong></p>\n\n";

    // Tables
    if (!empty($dbChanges['tables'])) {
        $md .= "<p><strong>Tables:</strong></p>\n\n";
        $md .= "<ul>\n";
        foreach ($dbChanges['tables'] as $table) {
            $md .= "<li>" . $table['name'] . "</li>\n";
        }
        $md .= "</ul>\n\n";
    }

    // Columns
    if (!empty($dbChanges['columns'])) {
        $md .= "<p><strong>Columns:</strong></p>\n\n";
        $md .= "| Table | Column | Data Type | Nullable |\n";
        $md .= "|-------|--------|-----------|----------|\n";
        foreach ($dbChanges['columns'] as $col) {
            $table = $col['table'] ?? '';
            $name = $col['name'] ?? '';
            $type = $col['dataType'] ?? '';
            $nullable = ($col['nullable'] ?? true) ? 'Yes' : 'No';
            $md .= "| {$table} | {$name} | {$type} | {$nullable} |\n";
        }
        $md .= "\n";
    }

    return $md;
}

/**
 * Generate Software Issues Resolved section
 */
function generateBugsSection($bugs) {
    $md = "<h2 id=\"software-issues-resolved\">Software Issues Resolved</h2>\n\n";

    if (empty($bugs)) {
        $md .= "<p>No software issues resolved in this release.</p>\n\n";
        return $md;
    }

    foreach ($bugs as $bug) {
        $md .= generateBug($bug);
    }

    return $md;
}

/**
 * Generate a single bug/defect
 */
function generateBug($bug) {
    global $TFS_BASE_URL, $TFS_PROJECT;

    $md = '';

    $id = $bug['id'] ?? 'Unknown';

    // Add work item ID with link for verification (HTML format)
    if ($id && $id !== 'Unknown') {
        $workItemUrl = "{$TFS_BASE_URL}/{$TFS_PROJECT}/_workitems/edit/{$id}";
        $md .= "<p><em>Work Item: <a href=\"{$workItemUrl}\">#{$id}</a></em></p>\n\n";
    }

    // Defect heading (just ID) - HTML format
    $md .= "<p><strong>Defect {$id}</strong></p>\n\n";

    // If relNotesDescription exists, proxy images and keep HTML as-is
    if (!empty($bug['relNotesDescription'])) {
        $md .= proxyTfsImages($bug['relNotesDescription']) . "\n\n";
    } else {
        // Create brief summary from description or title
        $description = $bug['description'] ?? $bug['title'] ?? 'Issue resolved.';
        $md .= "<p>" . proxyTfsImages($description) . "</p>\n\n";
    }

    // Files Updated section
    // Check for both filesDeployed text field and filesChanged array
    $hasFiles = false;

    // Option 1: Use Deltek.FilesDeployed text field (preferred - from frontend display)
    if (!empty($bug['filesDeployed'])) {
        $md .= "<h4>Files Updated</h4>\n\n";
        $md .= proxyTfsImages($bug['filesDeployed']) . "\n\n";
        $hasFiles = true;
    }

    // Option 2: Use filesChanged array (from changesets)
    if (!$hasFiles && !empty($bug['filesChanged'])) {
        $md .= generateFilesUpdatedFromChangesets($bug['filesChanged']);
    }

    return $md;
}

/**
 * Generate Files Updated section for bugs (from changesets)
 */
function generateFilesUpdatedFromChangesets($files) {
    $md = "<h4>Files Updated</h4>\n\n";

    // Check if we have file paths or just changesets
    $hasFilePaths = false;
    foreach ($files as $file) {
        if (isset($file['path'])) {
            $hasFilePaths = true;
            break;
        }
    }

    if ($hasFilePaths) {
        $md .= "<p>Files changed:</p>\n";
        $md .= "<ul>\n";
        foreach ($files as $file) {
            $path = $file['path'] ?? '';
            $changeType = $file['changeType'] ?? 'edit';

            // Strip TFVC prefix
            $displayPath = stripTfvcPrefix($path);

            $md .= "<li>{$displayPath} ({$changeType})</li>\n";
        }
        $md .= "</ul>\n";
    } else {
        // Just have changeset references
        $md .= "<p>Changesets:</p>\n";
        $md .= "<ul>\n";
        foreach ($files as $file) {
            if (isset($file['changesetId'])) {
                $changesetId = $file['changesetId'];
                $comment = !empty($file['comment']) ? $file['comment'] : 'Code changes';
                $md .= "<li><a href=\"https://dev.azure.com/Deltek/Cobra/_versionControl/changeset/{$changesetId}\">Changeset {$changesetId}</a> — {$comment}</li>\n";
            }
        }
        $md .= "</ul>\n";
    }

    $md .= "\n";

    return $md;
}

/**
 * Generate Security Enhancements section
 */
function generateSecuritySection($features, $bugs) {
    $md = "<h2 id=\"security-enhancements\">Security Enhancements</h2>\n\n";

    // Look for security-related items (based on tags or titles)
    $securityItems = [];

    foreach ($features as $feature) {
        if (isSecurityRelated($feature)) {
            $securityItems[] = $feature;
        }
    }

    foreach ($bugs as $bug) {
        if (isSecurityRelated($bug)) {
            $securityItems[] = $bug;
        }
    }

    if (empty($securityItems)) {
        $md .= "<p>No security enhancements in this release.</p>\n\n";
        return $md;
    }

    foreach ($securityItems as $item) {
        $title = $item['relNotesTitle'] ?? $item['title'] ?? 'Security Enhancement';
        $id = $item['id'] ?? '';
        $description = $item['relNotesDescription'] ?? $item['description'] ?? '';

        $md .= "<h3>{$title}</h3>\n\n";
        if ($id) {
            $md .= "<p><em>Work Item ID: {$id}</em></p>\n\n";
        }
        if ($description) {
            $md .= proxyTfsImages($description) . "\n\n";
        }
    }

    return $md;
}

/**
 * Check if work item is security-related
 */
function isSecurityRelated($item) {
    $tags = $item['tags'] ?? [];
    $title = strtolower($item['title'] ?? '');
    $description = strtolower($item['description'] ?? '');

    // Check tags
    foreach ($tags as $tag) {
        if (stripos($tag, 'security') !== false || stripos($tag, 'vulnerability') !== false) {
            return true;
        }
    }

    // Check title and description
    $securityKeywords = ['security', 'vulnerability', 'authentication', 'authorization', 'encryption', 'xss', 'sql injection', 'csrf'];
    foreach ($securityKeywords as $keyword) {
        if (stripos($title, $keyword) !== false || stripos($description, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Generate Database Changes section
 */
function generateDatabaseChangesSection($features) {
    $md = "<h2 id=\"database-changes\">Database Changes</h2>\n\n";

    // Aggregate all database changes from features
    $allTables = [];
    $allColumns = [];
    $allIndexes = [];

    foreach ($features as $feature) {
        if (!empty($feature['databaseChanges'])) {
            $dbChanges = $feature['databaseChanges'];

            if (!empty($dbChanges['tables'])) {
                $allTables = array_merge($allTables, $dbChanges['tables']);
            }
            if (!empty($dbChanges['columns'])) {
                $allColumns = array_merge($allColumns, $dbChanges['columns']);
            }
            if (!empty($dbChanges['indexes'])) {
                $allIndexes = array_merge($allIndexes, $dbChanges['indexes']);
            }
        }
    }

    // Remove duplicates
    $allTables = array_unique($allTables, SORT_REGULAR);
    $allColumns = array_unique($allColumns, SORT_REGULAR);
    $allIndexes = array_unique($allIndexes, SORT_REGULAR);

    if (empty($allTables) && empty($allColumns) && empty($allIndexes)) {
        $md .= "<p>No database changes in this release.</p>\n\n";
        return $md;
    }

    // Tables
    if (!empty($allTables)) {
        $md .= "<h3>Tables</h3>\n\n";
        $md .= "<h4>New Tables</h4>\n\n";
        $md .= "| Table Name |\n";
        $md .= "|-----------|\n";
        foreach ($allTables as $table) {
            $tableName = is_array($table) ? ($table['name'] ?? '') : $table;
            $md .= "| {$tableName} |\n";
        }
        $md .= "\n";
    }

    // Columns
    if (!empty($allColumns)) {
        $md .= "<h3>Columns</h3>\n\n";
        $md .= "<h4>New Columns</h4>\n\n";
        $md .= "| Table Name | Column Name | Data Type |\n";
        $md .= "|------------|-------------|----------|\n";
        foreach ($allColumns as $col) {
            $table = $col['table'] ?? '';
            $name = $col['name'] ?? '';
            $type = $col['dataType'] ?? '';
            $md .= "| {$table} | {$name} | {$type} |\n";
        }
        $md .= "\n";
    }

    // Indexes
    if (!empty($allIndexes)) {
        $md .= "<h3>Indexes</h3>\n\n";
        $md .= "<h4>New Indexes</h4>\n\n";
        $md .= "| Table Name | Index Name | Index Fields |\n";
        $md .= "|------------|------------|-------------|\n";
        foreach ($allIndexes as $idx) {
            $table = $idx['table'] ?? '';
            $name = $idx['name'] ?? '';
            $fields = $idx['fields'] ?? '';
            $md .= "| {$table} | {$name} | {$fields} |\n";
        }
        $md .= "\n";
    }

    return $md;
}

?>

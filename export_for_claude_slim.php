<?php
header('Content-Type: application/json');

// Get the work items from the request
$input = json_decode(file_get_contents('php://input'), true);
$features = $input['features'] ?? [];
$bugs = $input['bugs'] ?? [];

if (empty($features) && empty($bugs)) {
    echo json_encode(['success' => false, 'error' => 'No work items provided']);
    exit;
}

try {
    // Helper function to extract only essential fields for release notes
    function extractEssentialFields($item) {
        $essential = [
            'id' => $item['id'],
            'type' => $item['type'],
            'title' => $item['title'],
            'state' => $item['state'],
            'priority' => $item['priority'] ?? null,
            'tags' => $item['tags'] ?? null,
            'relNotesDescription' => $item['relNotesDescription'] ?? null,
            'acceptanceCriteria' => $item['acceptanceCriteria'] ?? null,
            'description' => $item['description'] ?? null,  // Fallback if relNotesDescription is empty
            'querySource' => $item['querySource'] ?? 'unknown'  // Indicates which query this came from
        ];

        // Include deployment information (primarily for bugs)
        if (isset($item['filesDeployed']) && !empty($item['filesDeployed'])) {
            $essential['filesDeployed'] = $item['filesDeployed'];
        }

        // Include all descendants (up to 3 levels) for all work item types
        if (isset($item['childItems']) && !empty($item['childItems'])) {
            $essential['childItems'] = $item['childItems'];
        }

        return $essential;
    }

    // Process and slim down the work items
    $slimFeatures = array_map('extractEssentialFields', $features);
    $slimBugs = array_map('extractEssentialFields', $bugs);

    // Build slim export structure
    $export = [
        '_instructions' => [
            'purpose' => 'Curated work items for Cobra release notes generation',
            'workflow' => [
                'Features → "New Features and Enhancements" section',
                'Bugs → "Software Issues Resolved" section'
            ],
            'content_rules' => [
                'Use relNotesDescription as primary content (fallback to description if empty)',
                'Include acceptanceCriteria as "Implementation Details" if >50 chars',
                'Keep images inline, convert tables to markdown',
                'Clean malformed HTML (empty tags, split words, etc.)',
                'Child items may include changesets array with linked source code changes'
            ]
        ],

        'metadata' => [
            'exportDate' => date('Y-m-d H:i:s'),
            'totalFeatures' => count($slimFeatures),
            'totalBugs' => count($slimBugs),
            'note' => 'Items marked with querySource field indicating origin (features/bugs query)'
        ],

        'features' => $slimFeatures,
        'bugs' => $slimBugs,

        '_fields' => [
            'id' => 'TFS Work Item ID',
            'type' => 'Feature or Bug',
            'title' => 'Work Item Title',
            'state' => 'Current State',
            'priority' => 'Priority (for bugs)',
            'tags' => 'Tags from TFS',
            'relNotesDescription' => 'PRIMARY: Main release notes content (HTML)',
            'acceptanceCriteria' => 'OPTIONAL: Implementation details (HTML)',
            'description' => 'FALLBACK: Use if relNotesDescription is empty',
            'filesDeployed' => 'OPTIONAL: List of files deployed (HTML, primarily for bugs)',
            'childItems' => 'OPTIONAL: Flattened array of ALL descendants up to 3 levels (includes id, type, title, state, level, changesets)',
            'childItems.changesets' => 'OPTIONAL: Array of linked changesets for each child item (includes id, url, comment)',
            'querySource' => 'SOURCE: Indicates which query this item came from (features/bugs/unknown)'
        ]
    ];

    // Create filename
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "release_notes_{$timestamp}.json";

    // Save to file
    if (!file_exists('exports')) {
        mkdir('exports', 0777, true);
    }
    $savedPath = "exports/{$filename}";

    $jsonContent = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    file_put_contents($savedPath, $jsonContent);

    // Return response
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'savedPath' => $savedPath,
        'content' => $jsonContent,
        'counts' => [
            'features' => count($slimFeatures),
            'bugs' => count($slimBugs),
            'total' => count($slimFeatures) + count($slimBugs)
        ],
        'sizeSaved' => 'Reduced to essential fields only (much smaller file)'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

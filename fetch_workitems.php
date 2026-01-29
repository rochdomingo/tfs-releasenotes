<?php
// Increase execution time limit to 10 minutes for large queries with deep hierarchies
set_time_limit(600);
ini_set('max_execution_time', 600);

header('Content-Type: application/json');

// Load TFS configuration
$config = json_decode(file_get_contents('tfs_config.json'), true);

// Get the query ID and type from the request
$input = json_decode(file_get_contents('php://input'), true);
$queryId = $input['queryId'] ?? '';
$queryType = $input['queryType'] ?? 'unknown'; // 'features' or 'bugs'

if (empty($queryId)) {
    echo json_encode(['success' => false, 'error' => 'Query ID is required']);
    exit;
}

// Create workitems directory if it doesn't exist
if (!file_exists('workitems')) {
    mkdir('workitems', 0777, true);
}

try {
    // Step 1: Get work item IDs from the query
    $queryUrl = $config['base_url'] . "/{$config['default_project']}/_apis/wit/wiql/{$queryId}?api-version=5.0";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $queryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode(':' . $config['pat']),
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $queryResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Failed to fetch query results. HTTP Code: {$httpCode}. Response: {$queryResponse}");
    }

    $queryData = json_decode($queryResponse, true);

    // Handle different TFS response structures
    $workItemIds = [];

    // Check if response is directly an array of work items
    if (isset($queryData[0]) && isset($queryData[0]['id'])) {
        // Response is directly an array
        $workItemIds = array_map(function($item) {
            return $item['id'];
        }, $queryData);
    } elseif (isset($queryData['workItems']) && !empty($queryData['workItems'])) {
        // Standard format: workItems property
        $workItemIds = array_map(function($item) {
            return $item['id'];
        }, $queryData['workItems']);
    } elseif (isset($queryData['workItemRelations']) && !empty($queryData['workItemRelations'])) {
        // Hierarchical format: workItemRelations array (tree queries)
        foreach ($queryData['workItemRelations'] as $relation) {
            if (isset($relation['target']['id'])) {
                $workItemIds[] = $relation['target']['id'];
            }
            if (isset($relation['source']['id'])) {
                $workItemIds[] = $relation['source']['id'];
            }
        }
        // Remove duplicates
        $workItemIds = array_unique($workItemIds);
        $workItemIds = array_values($workItemIds); // Re-index array
    }

    if (empty($workItemIds)) {
        echo json_encode(['success' => true, 'workitems' => [], 'debug' => 'No work items found']);
        exit;
    }

    // Step 2: Get detailed work item information
    $batchSize = 200; // TFS API limit
    $allWorkItems = [];

    for ($i = 0; $i < count($workItemIds); $i += $batchSize) {
        $batchIds = array_slice($workItemIds, $i, $batchSize);
        $idsParam = implode(',', $batchIds);

        $workItemsUrl = $config['base_url'] . "/_apis/wit/workitems?ids={$idsParam}&\$expand=all&api-version=5.0";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $workItemsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode(':' . $config['pat']),
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $workItemsResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Failed to fetch work items. HTTP Code: {$httpCode}");
        }

        $workItemsData = json_decode($workItemsResponse, true);

        if (isset($workItemsData['value'])) {
            $allWorkItems = array_merge($allWorkItems, $workItemsData['value']);
        }
    }

    // Step 3: Batch function to fetch all descendants up to 3 levels deep
    // This uses a breadth-first approach with batching to minimize API calls
    function fetchAllDescendantsBatch($workItemId, $config, $maxDepth = 3) {
        $allDescendants = [];
        $currentLevelIds = [$workItemId];

        for ($level = 1; $level <= $maxDepth; $level++) {
            if (empty($currentLevelIds)) {
                break;
            }

            // Step 1: Fetch relations for all items at current level (with expand=relations)
            // TFS supports fetching multiple items at once with relations
            $idsParam = implode(',', $currentLevelIds);
            $relationsUrl = $config['base_url'] . "/_apis/wit/workitems?ids={$idsParam}&\$expand=relations&api-version=5.0";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $relationsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . base64_encode(':' . $config['pat']),
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            $relationsResponse = curl_exec($ch);

            if (curl_errno($ch)) {
                error_log("CURL Error fetching relations at level {$level}: " . curl_error($ch));
                curl_close($ch);
                break;
            }

            curl_close($ch);

            $relationsData = json_decode($relationsResponse, true);

            // Step 2: Collect all child IDs from relations
            $nextLevelIds = [];

            if (isset($relationsData['value'])) {
                foreach ($relationsData['value'] as $item) {
                    if (isset($item['relations'])) {
                        foreach ($item['relations'] as $relation) {
                            // Check for child relationship
                            if (isset($relation['rel']) && $relation['rel'] === 'System.LinkTypes.Hierarchy-Forward') {
                                if (isset($relation['url'])) {
                                    $childId = basename($relation['url']);
                                    $nextLevelIds[] = $childId;
                                }
                            }
                        }
                    }
                }
            }

            // Remove duplicates
            $nextLevelIds = array_unique($nextLevelIds);

            // Step 3: Batch fetch details for all children at this level
            if (!empty($nextLevelIds)) {
                // TFS API supports up to 200 IDs per request, so batch if needed
                $batches = array_chunk($nextLevelIds, 200);

                foreach ($batches as $batchIds) {
                    $batchIdsParam = implode(',', $batchIds);
                    // Include relations to capture changesets
                    $childrenUrl = $config['base_url'] . "/_apis/wit/workitems?ids={$batchIdsParam}&\$expand=relations&api-version=5.0";

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $childrenUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Basic ' . base64_encode(':' . $config['pat']),
                        'Content-Type: application/json'
                    ]);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

                    $childrenResponse = curl_exec($ch);

                    if (curl_errno($ch)) {
                        error_log("CURL Error fetching children batch at level {$level}: " . curl_error($ch));
                        curl_close($ch);
                        continue;
                    }

                    curl_close($ch);

                    $childrenData = json_decode($childrenResponse, true);

                    // Step 4: Add all children to flattened list
                    if (isset($childrenData['value'])) {
                        foreach ($childrenData['value'] as $child) {
                            $childFields = $child['fields'];

                            $childItem = [
                                'id' => $child['id'],
                                'type' => $childFields['System.WorkItemType'] ?? 'Unknown',
                                'title' => $childFields['System.Title'] ?? 'No Title',
                                'state' => $childFields['System.State'] ?? 'Unknown',
                                'level' => $level
                            ];

                            // Extract changesets from relations
                            $changesets = [];
                            if (isset($child['relations'])) {
                                foreach ($child['relations'] as $relation) {
                                    // Check for changeset links (ArtifactLink to VersionControl/Changeset)
                                    if (isset($relation['rel']) && $relation['rel'] === 'ArtifactLink' &&
                                        isset($relation['url']) && strpos($relation['url'], 'VersionControl/Changeset') !== false) {

                                        // Extract changeset ID from URL (e.g., vstfs:///VersionControl/Changeset/123456)
                                        $changesetId = basename($relation['url']);

                                        $changesetInfo = [
                                            'id' => $changesetId,
                                            'url' => $relation['url']
                                        ];

                                        // Include comment if available
                                        if (isset($relation['attributes']['comment'])) {
                                            $changesetInfo['comment'] = $relation['attributes']['comment'];
                                        }

                                        $changesets[] = $changesetInfo;
                                    }
                                }
                            }

                            // Add changesets to child item if any found
                            if (!empty($changesets)) {
                                $childItem['changesets'] = $changesets;
                            }

                            $allDescendants[] = $childItem;
                        }
                    }
                }
            }

            // Move to next level
            $currentLevelIds = $nextLevelIds;
        }

        return $allDescendants;
    }

    // Fetch all descendants for Features and Bugs
    // Features may have deep hierarchies (PBIs, Tasks, etc.)
    // Bugs may have linked Cases or other work items
    $childWorkItemsMap = [];

    foreach ($allWorkItems as $item) {
        $workItemType = $item['fields']['System.WorkItemType'] ?? '';

        // Fetch descendants for Features (up to 3 levels - full hierarchy)
        if ($workItemType === 'Feature') {
            $workItemId = $item['id'];

            // Fetch all descendants using batch approach (up to 3 levels)
            $descendants = fetchAllDescendantsBatch($workItemId, $config, 3);

            if (!empty($descendants)) {
                $childWorkItemsMap[$workItemId] = $descendants;
            }
        }
        // Fetch descendants for Bugs (up to 1 level - just direct children like Cases)
        elseif ($workItemType === 'Bug' || $workItemType === 'Issue' || $workItemType === 'Defect') {
            $workItemId = $item['id'];

            // Fetch only direct children (level 1) - typically Cases linked to Bugs
            $descendants = fetchAllDescendantsBatch($workItemId, $config, 1);

            if (!empty($descendants)) {
                $childWorkItemsMap[$workItemId] = $descendants;
            }
        }
    }

    // Step 4: Format work items for frontend
    $formattedWorkItems = [];

    foreach ($allWorkItems as $item) {
        $fields = $item['fields'];

        // Extract all fields for maximum flexibility
        $workItem = [
            'id' => $item['id'],
            'type' => $fields['System.WorkItemType'] ?? 'Unknown',
            'title' => $fields['System.Title'] ?? 'No Title',
            'state' => $fields['System.State'] ?? 'Unknown',
            'assignedTo' => isset($fields['System.AssignedTo']) ? $fields['System.AssignedTo']['displayName'] : null,
            'createdDate' => $fields['System.CreatedDate'] ?? null,
            'changedDate' => $fields['System.ChangedDate'] ?? null,
            'description' => $fields['System.Description'] ?? '',
            'areaPath' => $fields['System.AreaPath'] ?? '',
            'iterationPath' => $fields['System.IterationPath'] ?? '',
            'priority' => $fields['Microsoft.VSTS.Common.Priority'] ?? null,
            'severity' => $fields['Microsoft.VSTS.Common.Severity'] ?? null,
            'tags' => $fields['System.Tags'] ?? '',
            'url' => $item['url'] ?? '',
            // Custom fields for advanced filtering (update field names as needed)
            'editingStatus' => $fields['Deltek.EditStatus'] ?? null,
            'isMustHave' => $fields['Deltek.IsMustHave'] ?? null,
            'disclose' => $fields['Deltek.DiscloseToClients'] ?? null,
            // Fields for release notes generation
            'relNotesDescription' => $fields['Deltek.RelNotesDescription'] ?? null,
            'acceptanceCriteria' => $fields['Microsoft.VSTS.Common.AcceptanceCriteria'] ?? null,
            // Deployment fields (primarily for bugs)
            'filesDeployed' => $fields['Deltek.FilesDeployed'] ?? null,
            // Store all raw fields for custom field access
            'allFields' => $fields,
            // Query source metadata to indicate which query this came from
            'querySource' => $queryType // 'features' or 'bugs'
        ];

        // Add all descendant work items (flattened up to 3 levels)
        if (isset($childWorkItemsMap[$item['id']])) {
            $workItem['childItems'] = $childWorkItemsMap[$item['id']];
        } else {
            $workItem['childItems'] = [];
        }

        $formattedWorkItems[] = $workItem;
    }

    // Step 5: Save to JSON file
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "workitems/{$queryType}_query_{$queryId}_{$timestamp}.json";

    file_put_contents($filename, json_encode($formattedWorkItems, JSON_PRETTY_PRINT));

    // Step 6: Return results
    echo json_encode([
        'success' => true,
        'workitems' => $formattedWorkItems,
        'savedFile' => $filename,
        'count' => count($formattedWorkItems),
        'queryType' => $queryType
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

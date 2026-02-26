<?php
// Increase execution time limit to 10 minutes for large queries with deep hierarchies
set_time_limit(600);
ini_set('max_execution_time', 600);

// Enable output buffering for progress updates
header('Content-Type: application/json');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Progress tracking array
$progressLog = [];

// Load TFS configuration
$config = json_decode(file_get_contents('tfs_config.json'), true);

// Get the query ID and type from the request
$input = json_decode(file_get_contents('php://input'), true);
$queryId = $input['queryId'] ?? '';
$queryType = $input['queryType'] ?? 'unknown'; // 'features' or 'bugs'
$hierarchyDepth = $input['hierarchyDepth'] ?? 2; // Default to 2 levels if not specified

if (empty($queryId)) {
    echo json_encode(['success' => false, 'error' => 'Query ID is required']);
    exit;
}

// Create workitems directory if it doesn't exist
if (!file_exists('workitems')) {
    mkdir('workitems', 0777, true);
}

try {
    $startTime = microtime(true);
    $progressLog[] = ['step' => 'start', 'message' => 'Starting fetch process', 'time' => 0];

    // Step 1: Get work item IDs from the query
    $progressLog[] = ['step' => 'query', 'message' => 'Fetching work item IDs from query', 'time' => round(microtime(true) - $startTime, 2)];
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
        $progressLog[] = ['step' => 'error', 'message' => "Query failed with HTTP {$httpCode}", 'time' => round(microtime(true) - $startTime, 2)];
        throw new Exception("Failed to fetch query results. HTTP Code: {$httpCode}. Response: {$queryResponse}");
    }

    $queryData = json_decode($queryResponse, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Failed to parse query response: " . json_last_error_msg());
    }

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
        $progressLog[] = ['step' => 'no_items', 'message' => 'Query returned no work item IDs', 'time' => round(microtime(true) - $startTime, 2), 'queryData' => $queryData];
        echo json_encode(['success' => true, 'workitems' => [], 'debug' => 'No work items found in query results', 'progress' => $progressLog, 'queryResponse' => $queryData]);
        exit;
    }

    $progressLog[] = ['step' => 'query_complete', 'message' => 'Found ' . count($workItemIds) . ' work items', 'time' => round(microtime(true) - $startTime, 2), 'ids' => array_slice($workItemIds, 0, 10)];

    // Step 2: Get detailed work item information
    $progressLog[] = ['step' => 'details', 'message' => 'Fetching detailed information for ' . count($workItemIds) . ' work items', 'time' => round(microtime(true) - $startTime, 2)];
    $batchSize = 200; // TFS API limit
    $allWorkItems = [];

    for ($i = 0; $i < count($workItemIds); $i += $batchSize) {
        $batchIds = array_slice($workItemIds, $i, $batchSize);
        $idsParam = implode(',', $batchIds);

        // Optimize: Fetch only relations instead of all fields (skips history, comments, attachments)
        // This significantly reduces response size and processing time
        // Note: Using $expand=relations only (without field filter) to avoid URL length issues
        $workItemsUrl = $config['base_url'] . "/_apis/wit/workitems?ids={$idsParam}&\$expand=relations&api-version=5.0";

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
            $progressLog[] = ['step' => 'error', 'message' => "Work items fetch failed with HTTP {$httpCode}", 'time' => round(microtime(true) - $startTime, 2), 'response' => substr($workItemsResponse, 0, 500)];
            throw new Exception("Failed to fetch work items. HTTP Code: {$httpCode}. Response: " . substr($workItemsResponse, 0, 500));
        }

        $workItemsData = json_decode($workItemsResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse work items response: " . json_last_error_msg());
        }

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
            // Optimize: Only fetch minimal fields needed for relation traversal
            $idsParam = implode(',', $currentLevelIds);
            $relationsUrl = $config['base_url'] . "/_apis/wit/workitems?ids={$idsParam}&\$expand=relations&fields=System.Id&api-version=5.0";

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
                    // Include relations to capture changesets, but only fetch essential fields
                    // This reduces payload size for child items significantly
                    $childrenUrl = $config['base_url'] . "/_apis/wit/workitems?ids={$batchIdsParam}&\$expand=relations&fields=System.Id,System.WorkItemType,System.Title,System.State&api-version=5.0";

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

    /**
     * Fetch descendants for multiple work items in parallel using curl_multi
     * This dramatically improves performance by making concurrent API calls
     */
    function fetchAllDescendantsParallel($itemsToFetch, $config, &$progressLog, $startTime) {
        $childWorkItemsMap = [];
        $parallelBatchSize = 5; // Process 5 work items in parallel at a time

        // Process items in batches for parallel fetching
        $batches = array_chunk($itemsToFetch, $parallelBatchSize);

        foreach ($batches as $batchIndex => $batch) {
            $progressLog[] = ['step' => 'parallel_batch', 'message' => "Processing parallel batch " . ($batchIndex + 1) . "/" . count($batches) . " (" . count($batch) . " items)", 'time' => round(microtime(true) - $startTime, 2)];

            // Create curl multi handle
            $multiHandle = curl_multi_init();
            $curlHandles = [];
            $handleMap = [];

            // Initialize curl handles for this batch
            foreach ($batch as $itemInfo) {
                $workItemId = $itemInfo['id'];
                $depth = $itemInfo['depth'];

                // Fetch first level relations
                $relationsUrl = $config['base_url'] . "/_apis/wit/workitems?ids={$workItemId}&\$expand=relations&fields=System.Id&api-version=5.0";

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

                curl_multi_add_handle($multiHandle, $ch);
                $handleMap[(int)$ch] = ['workItemId' => $workItemId, 'depth' => $depth];
                $curlHandles[] = $ch;
            }

            // Execute all handles in parallel
            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while ($running > 0);

            // Process results for each handle
            foreach ($curlHandles as $ch) {
                $response = curl_multi_getcontent($ch);
                $workItemId = $handleMap[(int)$ch]['workItemId'];
                $depth = $handleMap[(int)$ch]['depth'];

                // Now fetch full descendants using the standard function
                // (This maintains the multi-level hierarchy logic)
                $descendants = fetchAllDescendantsBatch($workItemId, $config, $depth);

                if (!empty($descendants)) {
                    $childWorkItemsMap[$workItemId] = $descendants;
                }

                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }

            curl_multi_close($multiHandle);
        }

        return $childWorkItemsMap;
    }

    $progressLog[] = ['step' => 'details_complete', 'message' => 'Retrieved details for ' . count($allWorkItems) . ' work items', 'time' => round(microtime(true) - $startTime, 2)];

    // Fetch all descendants for Features and Bugs (OPTIMIZED with parallel fetching)
    // Features may have deep hierarchies (PBIs, Tasks, etc.)
    // Bugs may have linked Cases or other work items
    $progressLog[] = ['step' => 'descendants', 'message' => 'Fetching child work items in parallel (optimized)', 'time' => round(microtime(true) - $startTime, 2)];

    // Collect all work items that need descendant fetching
    $itemsToFetch = [];
    foreach ($allWorkItems as $item) {
        $workItemType = $item['fields']['System.WorkItemType'] ?? '';
        $workItemId = $item['id'];

        if ($workItemType === 'Feature') {
            $itemsToFetch[] = ['id' => $workItemId, 'depth' => $hierarchyDepth, 'type' => 'Feature'];
        } elseif ($workItemType === 'Bug' || $workItemType === 'Issue' || $workItemType === 'Defect') {
            $itemsToFetch[] = ['id' => $workItemId, 'depth' => 1, 'type' => $workItemType];
        }
    }

    $progressLog[] = ['step' => 'descendants_parallel', 'message' => 'Fetching descendants for ' . count($itemsToFetch) . ' items using parallel requests', 'time' => round(microtime(true) - $startTime, 2)];

    // Fetch all descendants in parallel using curl_multi
    $childWorkItemsMap = fetchAllDescendantsParallel($itemsToFetch, $config, $progressLog, $startTime);

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
            'tags' => !empty($fields['System.Tags']) ? explode('; ', $fields['System.Tags']) : [],
            'url' => $item['url'] ?? '',
            // Custom fields for advanced filtering (update field names as needed)
            'editingStatus' => $fields['Deltek.EditStatus'] ?? null,
            'isMustHave' => ($fields['Deltek.IsMustHave'] ?? null) === 'Yes' ? true : false,
            'disclose' => $fields['Deltek.DiscloseToClients'] ?? null,
            // Fields for release notes generation (match generate_release_notes.php expectations)
            'DiscloseToClients' => $fields['Deltek.DiscloseToClients'] ?? 'No',
            'hasSpawnedTag' => !empty($fields['System.Tags']) && (stripos($fields['System.Tags'], 'Spawned') !== false || stripos($fields['System.Tags'], 'Spawns') !== false),
            'relNotesTitle' => $fields['Deltek.RelNotesTitle'] ?? $fields['System.Title'] ?? 'No Title',
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

        // Extract changesets from relations for filesChanged
        $filesChanged = [];
        if (isset($item['relations'])) {
            foreach ($item['relations'] as $relation) {
                if (isset($relation['rel']) && $relation['rel'] === 'ArtifactLink' &&
                    isset($relation['url']) && strpos($relation['url'], 'VersionControl/Changeset') !== false) {

                    $changesetId = basename($relation['url']);

                    // For now, create placeholder file entries
                    // In a real implementation, you'd fetch actual file details from the changeset
                    $filesChanged[] = [
                        'changesetId' => $changesetId,
                        'url' => $relation['url'],
                        'comment' => $relation['attributes']['comment'] ?? ''
                    ];
                }
            }
        }

        if (!empty($filesChanged)) {
            $workItem['filesChanged'] = $filesChanged;
            $workItem['changesets'] = $filesChanged; // For backwards compatibility
        }

        $formattedWorkItems[] = $workItem;
    }

    $progressLog[] = ['step' => 'formatting', 'message' => 'Formatting work items for display', 'time' => round(microtime(true) - $startTime, 2)];

    // Step 5: Save to JSON file
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "workitems/{$queryType}_query_{$queryId}_{$timestamp}.json";

    file_put_contents($filename, json_encode($formattedWorkItems, JSON_PRETTY_PRINT));

    $totalTime = round(microtime(true) - $startTime, 2);
    $progressLog[] = ['step' => 'complete', 'message' => "Completed in {$totalTime} seconds", 'time' => $totalTime];

    // Step 6: Return results
    echo json_encode([
        'success' => true,
        'workitems' => $formattedWorkItems,
        'savedFile' => $filename,
        'count' => count($formattedWorkItems),
        'queryType' => $queryType,
        'progress' => $progressLog,
        'totalTime' => $totalTime
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'progress' => $progressLog ?? []
    ]);
}
?>

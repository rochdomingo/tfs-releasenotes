<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TFS Release Notes Generator</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
        }
        .tab.active {
            background: white;
            border-bottom: 2px solid white;
            margin-bottom: -2px;
            font-weight: bold;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .count-badge {
            background: #070D63;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        .filter-preset {
            background: #e8f4f8;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>TFS Release Notes Generator</h1>

        <div class="card">
            <h2>Step 1: Fetch Work Items from Queries</h2>
            <form id="fetchForm">
                <div class="form-group">
                    <label for="featuresQueryId">Features Query ID:</label>
                    <input type="text" id="featuresQueryId" name="featuresQueryId" placeholder="Enter TFS Query GUID for Features">
                    <small style="color: #666; display: block; margin-top: 5px;">Query should return Feature or Product Backlog Item work items</small>
                </div>
                <div class="form-group">
                    <label for="bugsQueryId">Bugs Query ID:</label>
                    <input type="text" id="bugsQueryId" name="bugsQueryId" placeholder="Enter TFS Query GUID for Bugs">
                    <small style="color: #666; display: block; margin-top: 5px;">Query should return Bug/Issue work items</small>
                </div>
                <div class="form-group">
                    <label for="hierarchyDepth">Hierarchy Depth for Features:</label>
                    <select id="hierarchyDepth" name="hierarchyDepth" style="width: 300px;">
                        <option value="1">1 Level (Direct children only - fastest)</option>
                        <option value="2" selected>2 Levels (Recommended)</option>
                        <option value="3">3 Levels (Full hierarchy - slowest)</option>
                    </select>
                    <small style="color: #666; display: block; margin-top: 5px;">Controls how many levels of child items to fetch for features. Lower = faster.</small>
                </div>
                <div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; border: 1px solid #ffeaa7;">
                    <strong>Note:</strong> You can provide one or both query IDs. At least one is required.
                </div>
                <button type="submit" class="btn btn-primary">Fetch Work Items</button>
            </form>
            <div id="fetchStatus" class="status-message"></div>
        </div>

        <div class="card" id="workitemsSection" style="display: none;">
            <h2>Step 2: Review and Filter Work Items</h2>

            <div class="tabs">
                <div class="tab active" onclick="switchTab('features')">
                    🎯 Features <span id="featuresCount" class="count-badge">0</span>
                </div>
                <div class="tab" onclick="switchTab('bugs')">
                    🐛 Bugs <span id="bugsCount" class="count-badge">0</span>
                </div>
            </div>

            <!-- FEATURES TAB -->
            <div id="featuresTab" class="tab-content active">
                <div class="section-header">
                    <h3>New Features and Enhancements</h3>
                </div>

                <div class="filter-preset">
                    <strong>Default Filters:</strong> Work Item Type = Feature OR Product Backlog Item
                </div>

                <button onclick="toggleAdvancedFilters('features')" class="btn btn-secondary">Advanced Filters</button>

                <div id="advancedFiltersFeatures" class="advanced-filters" style="display: none;">
                    <h3>Advanced Filters for Features</h3>

                    <div style="background: #e8f4f8; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px;">
                        <strong>💡 Tip:</strong> All filters (except Work Item Type) apply to descendant items up to 3 levels deep. Items with descendants will show a count of matching items.
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableTypeFeatures" onchange="toggleFilterInput('typeFeatures')">
                        <label for="enableTypeFeatures">Work Item Type:</label>
                        <select id="filterTypeFeatures" disabled>
                            <option value="all">All (Feature & PBI)</option>
                            <option value="Feature">Feature only</option>
                            <option value="Product Backlog Item">Product Backlog Item only</option>
                        </select>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableEditingStatusFeatures" onchange="toggleFilterInput('editingStatusFeatures')">
                        <label for="enableEditingStatusFeatures">Editing Status:</label>
                        <input type="text" id="filterEditingStatusFeatures" placeholder="e.g., Final Review Complete" disabled>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableStateFeatures" onchange="toggleFilterInput('stateFeatures')">
                        <label for="enableStateFeatures">State (multi-select):</label>
                        <input type="text" id="filterStateFeatures" placeholder="e.g., Released,Done" disabled>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableIncludeTagsFeatures" onchange="toggleFilterInput('includeTagsFeatures')">
                        <label for="enableIncludeTagsFeatures">Include Tags (has ANY of these):</label>
                        <input type="text" id="filterIncludeTagsFeatures" placeholder="e.g., Must Have, Critical (comma-separated)" disabled>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableExcludeTagsFeatures" onchange="toggleFilterInput('excludeTagsFeatures')">
                        <label for="enableExcludeTagsFeatures">Exclude Tags:</label>
                        <input type="text" id="filterExcludeTagsFeatures" placeholder="e.g., Spawned, Not Ready (comma-separated)" disabled>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableExcludeKeywordsFeatures" onchange="toggleFilterInput('excludeKeywordsFeatures')">
                        <label for="enableExcludeKeywordsFeatures">Exclude Keywords (Title/Notes):</label>
                        <input type="text" id="filterExcludeKeywordsFeatures" placeholder="e.g., Spike, Technical Analysis" disabled>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableDiscloseFeatures" onchange="toggleFilterInput('discloseFeatures')">
                        <label for="enableDiscloseFeatures">Disclose to Clients:</label>
                        <select id="filterDiscloseFeatures" disabled>
                            <option value="null">Null or not set</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button onclick="applyFilters('features')" class="btn btn-primary">Apply Filters</button>
                        <button onclick="clearAdvancedFilters('features')" class="btn btn-secondary">Clear Filters</button>
                    </div>
                </div>

                <div id="featuresListWrapper" style="margin-top: 20px;">
                    <div id="featuresList"></div>
                </div>
            </div>

            <!-- BUGS TAB -->
            <div id="bugsTab" class="tab-content">
                <div class="section-header">
                    <h3>Software Issues Resolved</h3>
                </div>

                <button onclick="toggleAdvancedFilters('bugs')" class="btn btn-secondary">Show Filters</button>

                <div id="advancedFiltersBugs" class="advanced-filters" style="display: none;">
                    <h3>Filters for Bugs (All filters use AND logic)</h3>

                    <div style="background: #e8f4f8; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px;">
                        <strong>💡 Tip:</strong> All filters (except Work Item Type) apply to descendant items up to 3 levels deep. Items with descendants will show a count of matching items.
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableTypeBugs" onchange="toggleFilterInput('typeBugs')">
                        <label for="enableTypeBugs">Work Item Type:</label>
                        <input type="text" id="filterTypeBugs" placeholder="e.g., Bug, Issue, Defect" disabled>
                        <small style="color: #666; display: block; margin-top: 5px;">Filter by work item type</small>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableStateBugs" onchange="toggleFilterInput('stateBugs')">
                        <label for="enableStateBugs">State (multi-select):</label>
                        <input type="text" id="filterStateBugs" placeholder="e.g., Resolved,Closed" disabled>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableDiscloseBugs" onchange="toggleFilterInput('discloseBugs')">
                        <label for="enableDiscloseBugs">Disclose to Clients:</label>
                        <select id="filterDiscloseBugs" disabled>
                            <option value="">All</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                            <option value="null">Null or not set</option>
                        </select>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableIncludeTagsBugs" onchange="toggleFilterInput('includeTagsBugs')">
                        <label for="enableIncludeTagsBugs">Include Tags (has ANY of these):</label>
                        <input type="text" id="filterIncludeTagsBugs" placeholder="e.g., Critical, Security (comma-separated)" disabled>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enableExcludeTagsBugs" onchange="toggleFilterInput('excludeTagsBugs')">
                        <label for="enableExcludeTagsBugs">Exclude Tags:</label>
                        <input type="text" id="filterExcludeTagsBugs" placeholder="e.g., Spawned, Wont Fix (comma-separated)" disabled>
                    </div>

                    <div class="filter-row">
                        <input type="checkbox" id="enablePriorityBugs" onchange="toggleFilterInput('priorityBugs')">
                        <label for="enablePriorityBugs">Priority:</label>
                        <select id="filterPriorityBugs" disabled>
                            <option value="">All Priorities</option>
                            <option value="1">1 - Critical</option>
                            <option value="2">2 - High</option>
                            <option value="3">3 - Medium</option>
                            <option value="4">4 - Low</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button onclick="applyFilters('bugs')" class="btn btn-primary">Apply Filters</button>
                        <button onclick="clearAdvancedFilters('bugs')" class="btn btn-secondary">Clear Filters</button>
                    </div>
                </div>

                <div id="bugsListWrapper" style="margin-top: 20px;">
                    <div id="bugsList"></div>
                </div>
            </div>

            <div class="action-buttons" style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;">
                <h3>Step 3: Export or Generate Release Notes</h3>

                <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
                    <h4 style="margin-top: 0;">Release Information</h4>
                    <div class="form-group">
                        <label for="releaseVersion">Release Version:</label>
                        <input type="text" id="releaseVersion" placeholder="e.g., 8.5.0" style="width: 300px;">
                    </div>
                    <div class="form-group">
                        <label for="releaseDate">Release Date:</label>
                        <input type="date" id="releaseDate" style="width: 300px;">
                    </div>
                </div>

                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button onclick="generateReleaseNotes()" class="btn btn-primary">📝 Generate Release Notes</button>
                    <button onclick="exportForClaude()" class="btn btn-success">📥 Export for Claude Code</button>
                </div>
            </div>
            <div id="exportStatus" style="margin-top: 15px;"></div>

            <!-- WYSIWYG Editor Section -->
            <div id="editorSection" style="display: none; margin-top: 30px; padding-top: 20px; border-top: 2px solid #ddd;">
                <h3>Step 4: Review and Edit Release Notes</h3>
                <p>Edit the content below, then click "Convert & Download Markdown" to export.</p>

                <div style="margin: 20px 0;">
                    <textarea id="releaseNotesEditor" style="width: 100%; height: 600px;"></textarea>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 15px;">
                    <button onclick="convertAndDownload()" class="btn btn-primary">📥 Convert & Download Markdown</button>
                    <button onclick="hideEditor()" class="btn">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- TinyMCE WYSIWYG Editor -->
    <script src="https://cdn.tiny.cloud/1/g9n8w7viyagtdgg9dlo0qwv3e8qagg53k3onnow7vll0mclo/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

    <!-- Turndown for HTML to Markdown conversion -->
    <script src="https://unpkg.com/turndown/dist/turndown.js"></script>

    <script>
        let workitemsData = [];
        let filteredFeatures = [];
        let filteredBugs = [];
        let currentTab = 'features';

        document.getElementById('fetchForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const featuresQueryId = document.getElementById('featuresQueryId').value.trim();
            const bugsQueryId = document.getElementById('bugsQueryId').value.trim();
            const hierarchyDepth = parseInt(document.getElementById('hierarchyDepth').value) || 2;
            const statusDiv = document.getElementById('fetchStatus');

            // Validate that at least one query ID is provided
            if (!featuresQueryId && !bugsQueryId) {
                statusDiv.innerHTML = '<div class="error">Please provide at least one Query ID (Features or Bugs).</div>';
                return;
            }

            statusDiv.innerHTML = '<div class="loading">Fetching work items from TFS...</div>';

            try {
                let allWorkItems = [];
                let featuresCount = 0;
                let bugsCount = 0;
                let featuresResult;
                let bugsResult;

                // Fetch from Features query if provided
                if (featuresQueryId) {
                    statusDiv.innerHTML = `<div class="loading">Fetching Features from TFS (${hierarchyDepth} level${hierarchyDepth > 1 ? 's' : ''} deep)...</div>`;
                    const featuresResponse = await fetch('fetch_workitems.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ queryId: featuresQueryId, queryType: 'features', hierarchyDepth: hierarchyDepth })
                    });

                    featuresResult = await featuresResponse.json();

                    if (featuresResult.success) {
                        allWorkItems = allWorkItems.concat(featuresResult.workitems);
                        featuresCount = featuresResult.workitems.length;

                        // Display progress information
                        if (featuresResult.progress) {
                            const lastProgress = featuresResult.progress[featuresResult.progress.length - 1];
                            statusDiv.innerHTML = `<div class="loading">✅ Features: ${featuresCount} items (${featuresResult.totalTime}s)</div>`;
                        }
                    } else {
                        statusDiv.innerHTML += `<div class="error">Features Query Error: ${featuresResult.error}</div>`;
                        if (featuresResult.progress) {
                            console.error('Features fetch progress before error:', featuresResult.progress);
                        }
                    }
                }

                // Fetch from Bugs query if provided
                if (bugsQueryId) {
                    statusDiv.innerHTML = '<div class="loading">Fetching Bugs from TFS (1 level deep)...</div>';
                    const bugsResponse = await fetch('fetch_workitems.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ queryId: bugsQueryId, queryType: 'bugs', hierarchyDepth: 1 })
                    });

                    bugsResult = await bugsResponse.json();

                    if (bugsResult.success) {
                        allWorkItems = allWorkItems.concat(bugsResult.workitems);
                        bugsCount = bugsResult.workitems.length;

                        // Display progress information
                        if (bugsResult.progress) {
                            statusDiv.innerHTML += `<div class="loading">✅ Bugs: ${bugsCount} items (${bugsResult.totalTime}s)</div>`;
                        }
                    } else {
                        statusDiv.innerHTML += `<div class="error">Bugs Query Error: ${bugsResult.error}</div>`;
                        if (bugsResult.progress) {
                            console.error('Bugs fetch progress before error:', bugsResult.progress);
                        }
                    }
                }

                if (allWorkItems.length > 0) {
                    workitemsData = allWorkItems;

                    // Apply initial filters
                    applyFilters('features');
                    applyFilters('bugs');

                    // Calculate total time
                    let timingInfo = '';
                    if (featuresResult && featuresResult.totalTime) {
                        timingInfo += `Features: ${featuresResult.totalTime}s`;
                    }
                    if (bugsResult && bugsResult.totalTime) {
                        if (timingInfo) timingInfo += ' | ';
                        timingInfo += `Bugs: ${bugsResult.totalTime}s`;
                    }

                    statusDiv.innerHTML = `<div class="success">✅ Successfully fetched ${allWorkItems.length} work items!<br>📊 Features: ${featuresCount} | Bugs: ${bugsCount}${timingInfo ? '<br>⏱️ ' + timingInfo : ''}</div>`;

                    // Log detailed progress to console for debugging
                    if (window.console) {
                        if (featuresResult && featuresResult.progress) {
                            console.log('Features fetch progress:', featuresResult.progress);
                        }
                        if (bugsResult && bugsResult.progress) {
                            console.log('Bugs fetch progress:', bugsResult.progress);
                        }
                    }

                    document.getElementById('workitemsSection').style.display = 'block';
                } else {
                    statusDiv.innerHTML = '<div class="error">No work items were fetched. Please check your Query IDs.<br>Check the browser console (F12) for detailed error information.</div>';

                    // Log debug information
                    console.error('No work items fetched. Debug info:');
                    if (featuresQueryId && featuresResult) {
                        console.error('Features result:', featuresResult);
                    }
                    if (bugsQueryId && bugsResult) {
                        console.error('Bugs result:', bugsResult);
                    }
                }
            } catch (error) {
                statusDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
            }
        });

        function switchTab(tab) {
            // Update active tab
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));

            if (tab === 'features') {
                document.querySelector('.tab:nth-child(1)').classList.add('active');
                document.getElementById('featuresTab').classList.add('active');
            } else {
                document.querySelector('.tab:nth-child(2)').classList.add('active');
                document.getElementById('bugsTab').classList.add('active');
            }

            currentTab = tab;
        }

        function applyFilters(section) {
            if (section === 'features') {
                filteredFeatures = workitemsData.filter(item => {
                    // Must be a Feature or Product Backlog Item
                    if (item.type !== 'Feature' && item.type !== 'Product Backlog Item') return false;

                    // Apply advanced filters if enabled
                    // Work Item Type filter
                    if (document.getElementById('enableTypeFeatures')?.checked) {
                        const typeValue = document.getElementById('filterTypeFeatures').value;
                        if (typeValue !== 'all' && item.type !== typeValue) return false;
                    }

                    if (document.getElementById('enableEditingStatusFeatures')?.checked) {
                        const value = document.getElementById('filterEditingStatusFeatures').value.trim();
                        if (value && item.editingStatus !== value) return false;
                    }

                    if (document.getElementById('enableStateFeatures')?.checked) {
                        const states = document.getElementById('filterStateFeatures').value.split(',').map(s => s.trim());
                        if (!states.includes(item.state)) return false;
                    }

                    if (document.getElementById('enableIncludeTagsFeatures')?.checked) {
                        const tags = document.getElementById('filterIncludeTagsFeatures').value.split(',').map(t => t.trim());
                        const hasTags = tags.some(tag => item.tags && item.tags.includes(tag));
                        if (!hasTags) return false;
                    }

                    if (document.getElementById('enableExcludeTagsFeatures')?.checked) {
                        const excludeTags = document.getElementById('filterExcludeTagsFeatures').value.split(',').map(t => t.trim());
                        const hasExcludedTag = excludeTags.some(tag => item.tags && item.tags.includes(tag));
                        if (hasExcludedTag) return false;
                    }

                    if (document.getElementById('enableExcludeKeywordsFeatures')?.checked) {
                        const keywords = document.getElementById('filterExcludeKeywordsFeatures').value.split(',').map(k => k.trim().toLowerCase());
                        const hasKeyword = keywords.some(keyword =>
                            item.title.toLowerCase().includes(keyword) ||
                            (item.description && item.description.toLowerCase().includes(keyword))
                        );
                        if (hasKeyword) return false;
                    }

                    if (document.getElementById('enableDiscloseFeatures')?.checked) {
                        const discloseValue = document.getElementById('filterDiscloseFeatures').value;
                        if (discloseValue === 'null' && item.disclose !== null) return false;
                        if (discloseValue === 'yes' && item.disclose !== 'Yes') return false;
                        if (discloseValue === 'no' && item.disclose !== 'No') return false;
                    }

                    return true;
                });

                displayWorkitems(filteredFeatures, 'featuresList');
                document.getElementById('featuresCount').textContent = filteredFeatures.length;

            } else if (section === 'bugs') {
                filteredBugs = workitemsData.filter(item => {
                    // Must be from bugs query
                    if (item.querySource !== 'bugs') return false;

                    // Optional: Work Item Type filter
                    if (document.getElementById('enableTypeBugs')?.checked) {
                        const typeValue = document.getElementById('filterTypeBugs').value.trim();
                        if (typeValue && item.type !== typeValue) return false;
                    }

                    // Optional: State filter
                    if (document.getElementById('enableStateBugs')?.checked) {
                        const states = document.getElementById('filterStateBugs').value.split(',').map(s => s.trim());
                        if (!states.includes(item.state)) return false;
                    }

                    // Optional: Disclose filter
                    if (document.getElementById('enableDiscloseBugs')?.checked) {
                        const discloseValue = document.getElementById('filterDiscloseBugs').value;
                        if (discloseValue === 'null' && item.disclose !== null) return false;
                        if (discloseValue === 'yes' && item.disclose !== 'Yes') return false;
                        if (discloseValue === 'no' && item.disclose !== 'No') return false;
                    }

                    if (document.getElementById('enableIncludeTagsBugs')?.checked) {
                        const tags = document.getElementById('filterIncludeTagsBugs').value.split(',').map(t => t.trim());
                        const hasTags = tags.some(tag => item.tags && item.tags.includes(tag));
                        if (!hasTags) return false;
                    }

                    if (document.getElementById('enableExcludeTagsBugs')?.checked) {
                        const excludeTags = document.getElementById('filterExcludeTagsBugs').value.split(',').map(t => t.trim());
                        const hasExcludedTag = excludeTags.some(tag => item.tags && item.tags.includes(tag));
                        if (hasExcludedTag) return false;
                    }

                    if (document.getElementById('enablePriorityBugs')?.checked) {
                        const priority = document.getElementById('filterPriorityBugs').value;
                        if (priority && item.priority !== priority) return false;
                    }

                    return true;
                });

                displayWorkitems(filteredBugs, 'bugsList');
                document.getElementById('bugsCount').textContent = filteredBugs.length;
            }
        }

        function displayWorkitems(items, containerId) {
            const list = document.getElementById(containerId);

            if (items.length === 0) {
                list.innerHTML = '<p>No work items match the current filters.</p>';
                return;
            }

            let html = `<div class="workitems-count">Showing ${items.length} work item(s)</div>`;
            html += '<table class="workitems-table"><thead><tr>';
            html += `<th><input type="checkbox" onchange="toggleSelectAll(this, '${containerId}')" checked></th>`;
            html += '<th>ID</th><th>Type</th><th>Title</th><th>State</th><th>Priority</th><th>Disclose</th><th>Source</th><th>Descendants</th>';
            html += '</tr></thead><tbody>';

            items.forEach((item, index) => {
                // Apply filters to child items (ALL filters except Work Item Type)
                let filteredChildren = [];
                if (item.childItems && item.childItems.length > 0) {
                    filteredChildren = item.childItems.filter(child => {
                        const section = containerId === 'featuresList' ? 'features' : 'bugs';

                        if (section === 'features') {
                            // Apply State filter
                            if (document.getElementById('enableStateFeatures')?.checked) {
                                const states = document.getElementById('filterStateFeatures').value.split(',').map(s => s.trim());
                                if (!states.includes(child.state)) return false;
                            }

                            // Apply Exclude Keywords filter to child title
                            if (document.getElementById('enableExcludeKeywordsFeatures')?.checked) {
                                const keywords = document.getElementById('filterExcludeKeywordsFeatures').value.split(',').map(k => k.trim().toLowerCase());
                                const hasKeyword = keywords.some(keyword =>
                                    child.title.toLowerCase().includes(keyword)
                                );
                                if (hasKeyword) return false;
                            }

                            // Apply Include Tags filter (if child has tags)
                            if (document.getElementById('enableIncludeTagsFeatures')?.checked) {
                                const tags = document.getElementById('filterIncludeTagsFeatures').value.split(',').map(t => t.trim());
                                const hasTags = tags.some(tag => child.tags && child.tags.includes(tag));
                                if (!hasTags) return false;
                            }

                            // Apply Exclude Tags filter (if child has tags)
                            if (document.getElementById('enableExcludeTagsFeatures')?.checked) {
                                const excludeTags = document.getElementById('filterExcludeTagsFeatures').value.split(',').map(t => t.trim());
                                const hasExcludedTag = excludeTags.some(tag => child.tags && child.tags.includes(tag));
                                if (hasExcludedTag) return false;
                            }

                            // Apply Disclose filter (if child has disclose)
                            if (document.getElementById('enableDiscloseFeatures')?.checked) {
                                const discloseValue = document.getElementById('filterDiscloseFeatures').value;
                                if (discloseValue === 'null' && child.disclose !== null && child.disclose !== undefined) return false;
                                if (discloseValue === 'yes' && child.disclose !== 'Yes') return false;
                                if (discloseValue === 'no' && child.disclose !== 'No') return false;
                            }

                        } else if (section === 'bugs') {
                            // Apply State filter
                            if (document.getElementById('enableStateBugs')?.checked) {
                                const states = document.getElementById('filterStateBugs').value.split(',').map(s => s.trim());
                                if (!states.includes(child.state)) return false;
                            }

                            // Apply Disclose filter (if child has disclose)
                            if (document.getElementById('enableDiscloseBugs')?.checked) {
                                const discloseValue = document.getElementById('filterDiscloseBugs').value;
                                if (discloseValue === 'null' && child.disclose !== null && child.disclose !== undefined) return false;
                                if (discloseValue === 'yes' && child.disclose !== 'Yes') return false;
                                if (discloseValue === 'no' && child.disclose !== 'No') return false;
                            }

                            // Apply Include Tags filter (if child has tags)
                            if (document.getElementById('enableIncludeTagsBugs')?.checked) {
                                const tags = document.getElementById('filterIncludeTagsBugs').value.split(',').map(t => t.trim());
                                const hasTags = tags.some(tag => child.tags && child.tags.includes(tag));
                                if (!hasTags) return false;
                            }

                            // Apply Exclude Tags filter (if child has tags)
                            if (document.getElementById('enableExcludeTagsBugs')?.checked) {
                                const excludeTags = document.getElementById('filterExcludeTagsBugs').value.split(',').map(t => t.trim());
                                const hasExcludedTag = excludeTags.some(tag => child.tags && child.tags.includes(tag));
                                if (hasExcludedTag) return false;
                            }

                            // Apply Priority filter (if child has priority)
                            if (document.getElementById('enablePriorityBugs')?.checked) {
                                const priority = document.getElementById('filterPriorityBugs').value;
                                if (priority && child.priority !== priority) return false;
                            }
                        }

                        return true;
                    });
                }

                // Main row for parent item
                html += '<tr>';
                html += `<td><input type="checkbox" class="workitem-checkbox ${containerId}" data-id="${item.id}" checked></td>`;
                html += `<td><a href="https://tfs.deltek.com/tfs/Deltek/Cobra/_workitems/edit/${item.id}" target="_blank" style="color: #0066cc; text-decoration: none;">${item.id}</a></td>`;
                html += `<td><span class="badge badge-${item.type.toLowerCase().replace(' ', '-')}">${item.type}</span></td>`;
                html += `<td>${item.title}</td>`;
                html += `<td>${item.state}</td>`;
                html += `<td>${item.priority || '-'}</td>`;
                html += `<td>${item.disclose || '-'}</td>`;

                // Source column - show which query this came from
                const sourceLabel = item.querySource === 'features' ? 'Features Query' : item.querySource === 'bugs' ? 'Bugs Query' : 'Unknown';
                const sourceColor = item.querySource === 'features' ? '#070D63' : item.querySource === 'bugs' ? '#dc3545' : '#6c757d';
                html += `<td><span style="background: ${sourceColor}; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">${sourceLabel}</span></td>`;

                // Children column with expand/collapse button
                if (filteredChildren.length > 0) {
                    html += `<td><button onclick="toggleChildren(${item.id})" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;">▼ ${filteredChildren.length}</button></td>`;
                } else if (item.childItems && item.childItems.length > 0) {
                    html += `<td style="color: #999;">${item.childItems.length} (filtered out)</td>`;
                } else {
                    html += `<td>-</td>`;
                }
                html += '</tr>';

                // Files Deployed row (for bugs) - shown immediately after main row
                if (item.filesDeployed && item.filesDeployed.trim() !== '') {
                    html += '<tr style="background: #fff9e6;">';
                    html += '<td colspan="9" style="padding: 8px 20px; font-size: 12px;">';
                    html += '<strong style="color: #856404;">📦 Files Deployed:</strong> ';
                    html += '<div style="margin-left: 20px; margin-top: 5px;">' + item.filesDeployed + '</div>';
                    html += '</td>';
                    html += '</tr>';
                }

                // Child items row (hidden by default)
                if (filteredChildren.length > 0) {
                    html += `<tr id="children-${item.id}" style="display: none;">`;
                    html += `<td colspan="8" style="background: #f8f9fa; padding: 10px 20px;">`;
                    html += `<div style="margin-left: 20px;">`;
                    html += '<table style="width: 100%; border-collapse: collapse;">';
                    html += '<thead><tr style="background: #e9ecef;">';
                    html += '<th style="padding: 8px; text-align: left; font-size: 12px;">ID</th>';
                    html += '<th style="padding: 8px; text-align: left; font-size: 12px;">Type</th>';
                    html += '<th style="padding: 8px; text-align: left; font-size: 12px;">Title</th>';
                    html += '<th style="padding: 8px; text-align: left; font-size: 12px;">State</th>';
                    html += '</tr></thead><tbody>';

                    filteredChildren.forEach(child => {
                        // Calculate indentation based on level (Level 1 = 10px, Level 2 = 25px, Level 3 = 40px)
                        const indent = child.level ? (child.level * 15 - 5) : 10;
                        const levelIndicator = child.level ? `L${child.level}: ` : '';

                        html += '<tr>';
                        html += `<td style="padding: 8px;"><a href="https://tfs.deltek.com/tfs/Deltek/Cobra/_workitems/edit/${child.id}" target="_blank" style="color: #0066cc; text-decoration: none; font-size: 12px;">${child.id}</a></td>`;
                        html += `<td style="padding: 8px; font-size: 12px;">${child.type}</td>`;

                        // Add title with changesets indicator
                        let titleHtml = `<span style="color: #999; font-size: 10px;">${levelIndicator}</span>${child.title}`;
                        if (child.changesets && child.changesets.length > 0) {
                            titleHtml += ` <span style="background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;" title="Has ${child.changesets.length} changeset(s)">📦 ${child.changesets.length}</span>`;
                        }
                        html += `<td style="padding: 8px 8px 8px ${indent}px; font-size: 12px;">${titleHtml}</td>`;

                        html += `<td style="padding: 8px; font-size: 12px;">${child.state}</td>`;
                        html += '</tr>';

                        // Add changeset details row if available
                        if (child.changesets && child.changesets.length > 0) {
                            html += '<tr style="background: #f0f8f0;">';
                            html += '<td colspan="4" style="padding: 4px 8px 4px 40px; font-size: 11px; color: #555;">';
                            html += '<strong>Changesets:</strong> ';
                            child.changesets.forEach((cs, idx) => {
                                if (idx > 0) html += ', ';
                                html += `<a href="https://tfs.deltek.com/tfs/Deltek/Cobra/_versionControl/changeset/${cs.id}" target="_blank" style="color: #28a745; text-decoration: none;">#${cs.id}</a>`;
                                if (cs.comment) {
                                    html += ` <span style="color: #888;">(${cs.comment})</span>`;
                                }
                            });
                            html += '</td>';
                            html += '</tr>';
                        }
                    });

                    html += '</tbody></table>';
                    html += '</div>';
                    html += '</td>';
                    html += '</tr>';
                }
            });

            html += '</tbody></table>';
            list.innerHTML = html;
        }

        function toggleChildren(itemId) {
            const childRow = document.getElementById(`children-${itemId}`);
            if (childRow) {
                childRow.style.display = childRow.style.display === 'none' ? 'table-row' : 'none';
            }
        }

        function toggleSelectAll(checkbox, containerId) {
            document.querySelectorAll(`.workitem-checkbox.${containerId}`).forEach(cb => {
                cb.checked = checkbox.checked;
            });
        }

        function toggleAdvancedFilters(section) {
            const panel = document.getElementById(`advancedFilters${section.charAt(0).toUpperCase() + section.slice(1)}`);
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        }

        function toggleFilterInput(filterName) {
            const checkbox = document.getElementById(`enable${filterName.charAt(0).toUpperCase() + filterName.slice(1)}`);
            const input = document.getElementById(`filter${filterName.charAt(0).toUpperCase() + filterName.slice(1)}`);
            input.disabled = !checkbox.checked;
        }

        function clearAdvancedFilters(section) {
            const prefix = section === 'features' ? 'Features' : 'Bugs';

            document.querySelectorAll(`#advancedFilters${prefix} input[type="checkbox"]`).forEach(cb => {
                cb.checked = false;
            });
            document.querySelectorAll(`#advancedFilters${prefix} input[type="text"]`).forEach(input => {
                input.value = '';
                input.disabled = true;
            });
            document.querySelectorAll(`#advancedFilters${prefix} select`).forEach(select => {
                select.selectedIndex = 0;
                select.disabled = true;
            });

            applyFilters(section);
        }

        async function exportForClaude() {
            const statusDiv = document.getElementById('exportStatus');
            statusDiv.innerHTML = '<div class="loading">Preparing export...</div>';

            // Get selected features
            const selectedFeatureIds = Array.from(document.querySelectorAll('.workitem-checkbox.featuresList:checked'))
                .map(cb => cb.dataset.id);
            const selectedFeatures = filteredFeatures.filter(item => selectedFeatureIds.includes(String(item.id)));

            // Get selected bugs
            const selectedBugIds = Array.from(document.querySelectorAll('.workitem-checkbox.bugsList:checked'))
                .map(cb => cb.dataset.id);
            const selectedBugs = filteredBugs.filter(item => selectedBugIds.includes(String(item.id)));

            if (selectedFeatures.length === 0 && selectedBugs.length === 0) {
                statusDiv.innerHTML = '<div class="error">Please select at least one work item to export.</div>';
                return;
            }

            try {
                const response = await fetch('export_for_claude_slim.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        features: selectedFeatures,
                        bugs: selectedBugs
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Trigger download
                    const blob = new Blob([result.content], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = result.filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    statusDiv.innerHTML = `<div class="success">✅ Exported ${selectedFeatures.length} features and ${selectedBugs.length} bugs!<br>📄 File: ${result.filename}</div>`;
                } else {
                    statusDiv.innerHTML = `<div class="error">Error: ${result.error}</div>`;
                }
            } catch (error) {
                statusDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
            }
        }

        async function generateReleaseNotes() {
            const statusDiv = document.getElementById('exportStatus');
            statusDiv.innerHTML = '<div class="loading">Generating release notes...</div>';

            // Get release information
            const releaseVersion = document.getElementById('releaseVersion').value.trim() || 'Version TBD';
            const releaseDateInput = document.getElementById('releaseDate').value;
            const releaseDate = releaseDateInput ? new Date(releaseDateInput).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            // Get selected features
            const selectedFeatureIds = Array.from(document.querySelectorAll('.workitem-checkbox.featuresList:checked'))
                .map(cb => cb.dataset.id);
            const selectedFeatures = filteredFeatures.filter(item => selectedFeatureIds.includes(String(item.id)));

            // Get selected bugs
            const selectedBugIds = Array.from(document.querySelectorAll('.workitem-checkbox.bugsList:checked'))
                .map(cb => cb.dataset.id);
            const selectedBugs = filteredBugs.filter(item => selectedBugIds.includes(String(item.id)));

            if (selectedFeatures.length === 0 && selectedBugs.length === 0) {
                statusDiv.innerHTML = '<div class="error">Please select at least one work item to generate release notes.</div>';
                return;
            }

            try {
                const response = await fetch('generate_release_notes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        features: selectedFeatures,
                        bugs: selectedBugs,
                        releaseVersion: releaseVersion,
                        releaseDate: releaseDate
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Fetch the markdown file content
                    const fileResponse = await fetch('exports/' + result.filename);
                    const fileContent = await fileResponse.text();

                    // Store filename for later download
                    window.currentReleaseFilename = result.filename;

                    // Load content into TinyMCE editor
                    tinymce.get('releaseNotesEditor').setContent(fileContent);

                    // Show editor section
                    document.getElementById('editorSection').style.display = 'block';

                    // Scroll to editor
                    document.getElementById('editorSection').scrollIntoView({ behavior: 'smooth' });

                    statusDiv.innerHTML = `<div class="success">✅ Release notes loaded into editor!<br>📊 Features: ${selectedFeatures.length} | Bugs: ${selectedBugs.length}<br><small>Review and edit the content below, then click "Convert & Download Markdown"</small></div>`;
                } else {
                    statusDiv.innerHTML = `<div class="error">Error: ${result.error}</div>`;
                }
            } catch (error) {
                statusDiv.innerHTML = `<div class="error">Error: ${error.message}</div>`;
            }
        }

        // Initialize: Set today's date as default
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('releaseDate').value = today;

            // Initialize TinyMCE
            tinymce.init({
                selector: '#releaseNotesEditor',
                height: 600,
                menubar: true,
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount'
                ],
                toolbar: 'undo redo | blocks | ' +
                    'bold italic forecolor | alignleft aligncenter ' +
                    'alignright alignjustify | bullist numlist outdent indent | ' +
                    'removeformat | code | help',
                content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; font-size: 14px; } img { max-width: 100%; height: auto; }',

                // Allow all HTML elements and attributes (preserves images from TFS)
                valid_elements: '*[*]',
                extended_valid_elements: '*[*]',

                // Don't convert URLs - keep them as-is
                relative_urls: false,
                remove_script_host: false,
                convert_urls: false,

                // Allow pasting images
                paste_data_images: true
            });
        });

        // Hide the editor section
        function hideEditor() {
            document.getElementById('editorSection').style.display = 'none';
            document.getElementById('exportStatus').innerHTML = '';
        }

        // Convert HTML to Markdown and download
        function convertAndDownload() {
            try {
                // Get content from TinyMCE
                const htmlContent = tinymce.get('releaseNotesEditor').getContent();

                // Initialize Turndown
                const turndownService = new TurndownService({
                    headingStyle: 'atx',
                    hr: '---',
                    bulletListMarker: '-',
                    codeBlockStyle: 'fenced',
                    emDelimiter: '_'
                });

                // Custom rule to preserve work item links
                turndownService.addRule('preserveWorkItemLinks', {
                    filter: function (node) {
                        return node.nodeName === 'EM' && node.textContent.includes('Work Item:');
                    },
                    replacement: function (content) {
                        return content; // Keep as-is
                    }
                });

                // Convert HTML to Markdown
                let markdown = turndownService.turndown(htmlContent);

                // Replace proxy image URLs with original TFS URLs
                // Pattern 1: In markdown syntax ![alt](image_proxy.php?url=ENCODED_URL)
                markdown = markdown.replace(/image_proxy\.php\?url=([^)\s&]+)/g, function(match, encodedUrl) {
                    try {
                        return decodeURIComponent(encodedUrl);
                    } catch (e) {
                        return encodedUrl; // If decode fails, return as-is
                    }
                });

                // Pattern 2: In HTML img tags (if any remain)
                markdown = markdown.replace(/<img([^>]*?)src=["']image_proxy\.php\?url=([^"']+)["']([^>]*?)>/gi, function(match, before, encodedUrl, after) {
                    try {
                        const originalUrl = decodeURIComponent(encodedUrl);
                        return '<img' + before + 'src="' + originalUrl + '"' + after + '>';
                    } catch (e) {
                        return match; // If decode fails, return original
                    }
                });

                // Create filename with timestamp
                const timestamp = new Date().toISOString().split('T')[0].replace(/-/g, '-');
                const filename = window.currentReleaseFilename || `release_notes_${timestamp}.md`;

                // Download the file
                const blob = new Blob([markdown], { type: 'text/markdown' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                // Show success message
                document.getElementById('exportStatus').innerHTML = '<div class="success">✅ Markdown file downloaded successfully!</div>';

                // Hide editor after a short delay
                setTimeout(() => {
                    hideEditor();
                }, 2000);

            } catch (error) {
                document.getElementById('exportStatus').innerHTML = `<div class="error">Error converting to Markdown: ${error.message}</div>`;
                console.error('Conversion error:', error);
            }
        }
    </script>
</body>
</html>

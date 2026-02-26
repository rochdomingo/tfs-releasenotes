<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TFS Release Notes Generator</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3C50E0',
                        secondary: '#80CAEE',
                        success: '#10B981',
                        warning: '#F59E0B',
                        'warning-dark': '#856404',
                        danger: '#EF4444',
                        dark: '#1C2434',
                        'body-bg': '#F1F5F9'
                    }
                }
            }
        }
    </script>

    <style>
        /* TailAdmin-inspired custom styles */
        .tabs {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-bottom: 0;
            border-radius: 0.5rem 0.5rem 0 0;
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.2s;
        }
        .tab:hover {
            background-color: #f3f4f6;
        }
        .tab.active {
            background-color: white;
            border-color: #3C50E0;
            color: #3C50E0;
            margin-bottom: -2px;
            font-weight: 600;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
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
            margin-bottom: 1rem;
        }
        .count-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            background-color: #3C50E0;
            color: white;
        }
        .filter-preset {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #1e3a8a;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select {
            width: 100%;
            padding: 0.625rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3C50E0;
            box-shadow: 0 0 0 3px rgba(60, 80, 224, 0.1);
        }
        .form-group small {
            display: block;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: #3C50E0;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2a3eb8;
        }
        .btn-success {
            background-color: #10B981;
            color: white;
        }
        .btn-success:hover {
            background-color: #059669;
        }
        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
        }
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }
        .status-message {
            margin-top: 1rem;
        }
        .loading {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
        }
        .success {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
        }
        .error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
        }
        .advanced-filters {
            background-color: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
            margin-top: 1rem;
        }
        .filter-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .filter-row input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            color: #3C50E0;
            border-color: #d1d5db;
            border-radius: 0.25rem;
        }
        .filter-row label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            min-width: 180px;
        }
        .filter-row input[type="text"],
        .filter-row select {
            flex: 1;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
        }
        .filter-row input[type="text"]:focus,
        .filter-row select:focus {
            outline: none;
            border-color: #3C50E0;
            box-shadow: 0 0 0 3px rgba(60, 80, 224, 0.1);
        }
        .filter-actions {
            display: flex;
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            margin-top: 1rem;
        }
        .workitems-count {
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 0.5rem;
            display: inline-block;
        }
        .workitems-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .workitems-table thead {
            background-color: #f3f4f6;
            border-bottom: 2px solid #d1d5db;
        }
        .workitems-table th {
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
        }
        .workitems-table td {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        .workitems-table tbody tr:hover {
            background-color: #f9fafb;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-feature {
            background-color: rgba(60, 80, 224, 0.1);
            color: #3C50E0;
        }
        .badge-product-backlog-item {
            background-color: rgba(128, 202, 238, 0.2);
            color: #1d4ed8;
        }
        .badge-bug {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .badge-issue {
            background-color: #fed7aa;
            color: #9a3412;
        }
        .badge-task {
            background-color: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body class="bg-body-bg">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-dark">TFS Release Notes Generator</h1>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary/10 text-primary">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Cobra
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Step 1 Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-xl font-semibold text-dark flex items-center">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold mr-3">1</span>
                    Fetch Work Items from Queries
                </h2>
            </div>
            <div class="p-6">
                    <form id="fetchForm">
                        <div class="form-group">
                            <label for="featuresQueryId">Features Query ID:</label>
                            <input type="text" id="featuresQueryId" name="featuresQueryId" placeholder="Enter TFS Query GUID for Features">
                            <small>Query should return Feature or Product Backlog Item work items</small>
                        </div>
                        <div class="form-group">
                            <label for="bugsQueryId">Bugs Query ID:</label>
                            <input type="text" id="bugsQueryId" name="bugsQueryId" placeholder="Enter TFS Query GUID for Bugs">
                            <small>Query should return Bug/Issue work items</small>
                        </div>
                        <div class="form-group">
                            <label for="hierarchyDepth">Hierarchy Depth for Features:</label>
                            <select id="hierarchyDepth" name="hierarchyDepth">
                                <option value="1">1 Level (Direct children only - fastest)</option>
                                <option value="2" selected>2 Levels (Recommended)</option>
                                <option value="3">3 Levels (Full hierarchy - slowest)</option>
                            </select>
                            <small>Controls how many levels of child items to fetch for features. Lower = faster.</small>
                        </div>
                        <div class="bg-warning/10 border border-warning/30 text-warning-dark px-4 py-3 rounded-lg mb-6">
                            <strong>Note:</strong> You can provide one or both query IDs. At least one is required.
                        </div>
                        <button type="submit" class="btn btn-primary">Fetch Work Items</button>
                    </form>
                    <div id="fetchStatus" class="status-message"></div>
            </div>
        </div>

        <!-- Step 2 Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8" id="workitemsSection" style="display: none;">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-xl font-semibold text-dark flex items-center">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold mr-3">2</span>
                    Review and Filter Work Items
                </h2>
            </div>
            <div class="p-6">
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
                        <h3 class="text-lg font-semibold text-dark mb-4">Advanced Filters for Features</h3>

                        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg mb-4 text-sm">
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
                        <h3 class="text-lg font-semibold text-dark mb-4">Filters for Bugs (All filters use AND logic)</h3>

                        <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg mb-4 text-sm">
                            <strong>💡 Tip:</strong> All filters (except Work Item Type) apply to descendant items up to 3 levels deep. Items with descendants will show a count of matching items.
                        </div>

                        <div class="filter-row">
                            <input type="checkbox" id="enableTypeBugs" onchange="toggleFilterInput('typeBugs')">
                            <label for="enableTypeBugs">Work Item Type:</label>
                            <input type="text" id="filterTypeBugs" placeholder="e.g., Bug, Issue, Defect" disabled>
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

                    <div id="bugsListWrapper" class="mt-5">
                        <div id="bugsList"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3 Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8" id="step3Section" style="display: none;">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-xl font-semibold text-dark flex items-center">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold mr-3">3</span>
                    Export or Generate Release Notes
                </h2>
            </div>
            <div class="p-6">
                <div class="bg-gray-50 border border-gray-200 p-6 rounded-lg mb-6">
                    <h4 class="text-base font-semibold text-dark mb-4">Release Information</h4>
                    <div class="form-group">
                        <label for="releaseVersion">Release Version:</label>
                        <input type="text" id="releaseVersion" placeholder="e.g., 8.5.0" class="max-w-xs">
                    </div>
                    <div class="form-group">
                        <label for="releaseDate">Release Date:</label>
                        <input type="date" id="releaseDate" class="max-w-xs">
                    </div>
                </div>

                <div class="flex gap-4 flex-wrap">
                    <button onclick="generateReleaseNotes()" class="btn btn-primary">📝 Generate Release Notes</button>
                    <button onclick="exportForClaude()" class="btn btn-success">📥 Export for Claude Code</button>
                </div>
                <div id="exportStatus" class="mt-4"></div>
            </div>
        </div>

        <!-- Step 4 Card - WYSIWYG Editor -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8" id="editorSection" style="display: none;">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-xl font-semibold text-dark flex items-center">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold mr-3">4</span>
                    Review and Edit Release Notes
                </h2>
                <p class="text-sm text-gray-600 mt-2">Edit the content below, then download as Markdown or PDF.</p>
            </div>
            <div class="p-6">
                <div class="mb-6">
                    <textarea id="releaseNotesEditor" class="w-full" style="height: 600px;"></textarea>
                </div>

                <div class="flex gap-4 flex-wrap">
                    <button onclick="convertAndDownload()" class="btn btn-primary">📥 Download Markdown</button>
                    <button onclick="convertAndDownloadPDF()" class="btn btn-success">📄 Download PDF</button>
                    <button onclick="hideEditor()" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- TinyMCE WYSIWYG Editor -->
    <script src="https://cdn.tiny.cloud/1/g9n8w7viyagtdgg9dlo0qwv3e8qagg53k3onnow7vll0mclo/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

    <!-- Turndown for HTML to Markdown conversion -->
    <script src="https://unpkg.com/turndown/dist/turndown.js"></script>

    <!-- html2pdf.js for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

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
                    document.getElementById('step3Section').style.display = 'block';
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

        // Convert HTML to PDF and download
        function convertAndDownloadPDF() {
            try {
                // Get content from TinyMCE
                const htmlContent = tinymce.get('releaseNotesEditor').getContent();

                // Create a clean wrapper for PDF
                const wrapper = document.createElement('div');
                wrapper.style.cssText = `
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 40px;
                    font-family: 'Arial', 'Helvetica', sans-serif;
                    font-size: 12px;
                    line-height: 1.6;
                    color: #000;
                    background: #fff;
                `;

                // Add content with some style cleanup
                wrapper.innerHTML = htmlContent;

                // Create filename with timestamp
                const timestamp = new Date().toISOString().split('T')[0];
                const filename = window.currentReleaseFilename
                    ? window.currentReleaseFilename.replace('.md', '.pdf')
                    : `release_notes_${timestamp}.pdf`;

                // Configure html2pdf options - simplified for better compatibility
                const opt = {
                    margin: [10, 10, 10, 10],
                    filename: filename,
                    image: {
                        type: 'jpeg',
                        quality: 0.95
                    },
                    html2canvas: {
                        scale: 2,
                        useCORS: true,
                        letterRendering: true,
                        scrollY: 0,
                        scrollX: 0
                    },
                    jsPDF: {
                        unit: 'mm',
                        format: 'a4',
                        orientation: 'portrait'
                    },
                    pagebreak: {
                        mode: ['avoid-all', 'css', 'legacy'],
                        avoid: ['img', 'table', 'tr']
                    }
                };

                // Show loading message
                document.getElementById('exportStatus').innerHTML = '<div class="loading">⏳ Generating PDF... This may take a moment.</div>';

                // Generate PDF
                html2pdf().set(opt).from(wrapper).save().then(() => {
                    // Show success message
                    document.getElementById('exportStatus').innerHTML = '<div class="success">✅ PDF file downloaded successfully!</div>';

                    // Hide editor after a short delay
                    setTimeout(() => {
                        hideEditor();
                    }, 2000);
                }).catch(error => {
                    document.getElementById('exportStatus').innerHTML = `<div class="error">Error generating PDF: ${error.message}</div>`;
                    console.error('PDF generation error:', error);
                });

            } catch (error) {
                document.getElementById('exportStatus').innerHTML = `<div class="error">Error generating PDF: ${error.message}</div>`;
                console.error('PDF generation error:', error);
            }
        }
    </script>
</body>
</html>

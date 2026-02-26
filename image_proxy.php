<?php
/**
 * Image Proxy for TFS Attachments
 * Fetches images from TFS API with authentication and serves them to the browser
 */

// Get the image URL from query parameter
$imageUrl = $_GET['url'] ?? '';

if (empty($imageUrl)) {
    http_response_code(400);
    echo 'Missing image URL';
    exit;
}

// Validate that it's a TFS URL
if (strpos($imageUrl, 'tfs.deltek.com') === false && strpos($imageUrl, 'dev.azure.com') === false) {
    http_response_code(403);
    echo 'Invalid image source';
    exit;
}

// Load TFS config for authentication
$config = json_decode(file_get_contents('tfs_config.json'), true);

if (!$config || !isset($config['pat'])) {
    http_response_code(500);
    echo 'Configuration error';
    exit;
}

// Fetch the image with authentication
$ch = curl_init($imageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . base64_encode(':' . $config['pat']),
    'User-Agent: TFS-Release-Notes-Generator'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For internal TFS servers
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$imageData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200 || !$imageData) {
    http_response_code(404);
    echo 'Image not found';
    exit;
}

// Serve the image with appropriate headers
header('Content-Type: ' . $contentType);
header('Content-Length: ' . strlen($imageData));
header('Cache-Control: public, max-age=86400'); // Cache for 1 day
echo $imageData;
?>

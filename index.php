<?php
// Target URL
$url = "https://goldbroker.com/widget/live-table/XAG/OMR";

// Initialize cURL instead of file_get_contents for better control
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Be cautious with this in production
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

// Execute request
$html = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(["error" => "Failed to fetch data: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if request was successful
if ($httpCode !== 200) {
    echo json_encode(["error" => "HTTP error: " . $httpCode]);
    exit;
}

// Use DOMDocument to parse HTML
$dom = new DOMDocument();
libxml_use_internal_errors(true); // suppress invalid HTML warnings

// Load HTML with proper encoding
if (!$dom->loadHTML('<?xml encoding="UTF-8">' . $html)) {
    echo json_encode(["error" => "Failed to parse HTML"]);
    exit;
}

libxml_clear_errors();

$xpath = new DOMXPath($dom);

// Try multiple possible XPaths as the structure might change
$xpaths = [
    "/html/body/div/div[5]/span[2]",
    "//span[contains(@class, 'rate')]",
    "//div[contains(text(), 'OMR')]",
    "//*[@id='rate']",
    "//div[@class='rate']"
];

$rate = null;
foreach ($xpaths as $path) {
    $nodes = $xpath->query($path);
    if ($nodes->length > 0) {
        $text = trim($nodes->item(0)->textContent);
        // Look for OMR rate specifically
        if (strpos($text, 'OMR') !== false) {
            $rate = preg_replace('/[^\d.]/', '', $text);
            break;
        }
    }
}

if ($rate) {
    echo json_encode(["rate" => $rate]);
} else {
    // Fallback: search for any numeric rate in the page
    preg_match('/[\d]+[.,]\d+/', $html, $matches);
    if (!empty($matches)) {
        echo json_encode(["rate" => str_replace(',', '.', $matches[0])]);
    } else {
        echo json_encode(["error" => "Rate not found", "debug" => substr($html, 0, 500)]);
    }
}
?>
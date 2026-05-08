<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['student_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

$allowed_domains = [
    'wsu.ac.za', 'www.wsu.ac.za', 'wiseup.wsu.ac.za', 'ie.wsu.ac.za',
    'status.wsu.ac.za', 'students.wsu.ac.za', 'library.wsu.ac.za',
    'print.wsu.ac.za', 'mysafespace.wsu.ac.za', 'qualityvoice.wsu.ac.za',
    'wsulib.summon.serialssolutions.com', 'wsuacza.sharepoint.com',
    'www.nsfas.org.za', 'nsfas.org.za',
    'outlook.office.com', 'www.office.com', 'office.com',
    'aka.ms', 'forms.office.com', 'mail.google.com',
];

$url = isset($_GET['url']) ? trim($_GET['url']) : '';
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400); exit('Bad request');
}

$parsed = parse_url($url);
$scheme = isset($parsed['scheme']) ? strtolower($parsed['scheme']) : '';
if (!in_array($scheme, ['http', 'https'])) {
    http_response_code(400); exit('Bad scheme');
}

$host = isset($parsed['host']) ? strtolower($parsed['host']) : '';

// SSRF protection — block private/reserved IP ranges
if (filter_var($host, FILTER_VALIDATE_IP)) {
    if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        http_response_code(403); exit('Forbidden');
    }
}

$is_allowed = false;
foreach ($allowed_domains as $d) {
    if ($host === $d || (strlen($host) > strlen($d) && substr($host, -(strlen($d) + 1)) === '.' . $d)) {
        $is_allowed = true;
        break;
    }
}
if (!$is_allowed) {
    http_response_code(403); exit('Domain not allowed');
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    CURLOPT_HEADER         => true,
    CURLOPT_ENCODING       => '',
]);
$raw         = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false) {
    http_response_code(502); exit('Fetch failed');
}

$resp_headers = substr($raw, 0, $header_size);
$body         = substr($raw, $header_size);

// Extract Content-Type, skip all frame/security headers
$ct = 'text/html; charset=utf-8';
foreach (explode("\r\n", $resp_headers) as $line) {
    if (preg_match('/^Content-Type:\s*(.+)/i', $line, $m)) {
        $ct = trim($m[1]);
    }
}

header('Content-Type: ' . $ct);
http_response_code($http_code);

// Inject <base> so relative URLs resolve correctly
if (stripos($ct, 'text/html') !== false) {
    $dir  = isset($parsed['path']) ? dirname($parsed['path']) : '/';
    if ($dir === '.') $dir = '/';
    if (substr($dir, -1) !== '/') $dir .= '/';
    $base = htmlspecialchars($scheme . '://' . $host . $dir, ENT_QUOTES);

    if (preg_match('/<head[^>]*>/i', $body)) {
        $body = preg_replace('/(<head[^>]*>)/i', '$1<base href="' . $base . '">', $body, 1);
    } else {
        $body = '<base href="' . $base . '">' . $body;
    }
}

echo $body;

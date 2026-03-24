<?php
// Test XML validation for phieuyeucau export
require_once 'config/database.php';
require_once 'models/PhieuYeuCau.php';
require_once 'controllers/PhieuYeuCauController.php';

// Capture output
ob_start();

$_GET['phieu'] = '1997';

$controller = new PhieuYeuCauController();
$detail = $controller->model->getPhieuDetail('1997');

if (!$detail) {
    die("No data found for phieu 1997");
}

$summary = $detail['summary'];
$devices = $detail['devices'];

$sohoso = $summary['phieu'];
$ngay = date('d/m/Y', strtotime($summary['ngayyc']));
$khachhang = $summary['ngyeucau'];
$donvi = $summary['tendv'];
$dienthoai = $summary['dienthoai'];
$nhanvien = $summary['ngnhyeucau'];
$ycthemkh = $summary['ycthemkh'];
$cv = $summary['cv'];

$thietbi = [];
$model = [];
$somay = [];
$tinhtrang = [];
$yeucau = [];
$vitri = [];

$solan = count($devices);

for ($i = 1; $i <= $solan; $i++) {
    if (isset($devices[$i-1])) {
        $device = $devices[$i-1];
        $thietbi[$i] = $device['tenvt'] ?? $device['mavt'];
        $model[$i] = $device['model'] ?? '';
        $somay[$i] = $device['somay'] ?? '';
        $tinhtrang[$i] = $device['honghoc'] ?? '';
        $yeucau[$i] = $device['cv'] ?? '';
        $vitri[$i] = $device['vitrimaybd'] ?? '';
    }
}

$lo = $devices[0]['lo'] ?? '';
$mo = $devices[0]['mo'] ?? '';
$gieng = $devices[0]['gieng'] ?? '';
$xemxetxuong = $devices[0]['xemxetxuong'] ?? '';

require 'views/phieuyeucau/export_word.php';

$output = ob_get_clean();

// Save to file for testing
file_put_contents('test_word_output.doc', $output);

echo "File created: test_word_output.doc\n";
echo "File size: " . strlen($output) . " bytes\n";

// Try to validate XML structure
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$loaded = $doc->loadHTML($output);

if (!$loaded) {
    echo "\n=== XML ERRORS ===\n";
    foreach (libxml_get_errors() as $error) {
        echo "Line {$error->line}: {$error->message}\n";
    }
    libxml_clear_errors();
} else {
    echo "\nXML structure is valid!\n";
}

// Show first 500 chars
echo "\n=== FIRST 500 CHARS ===\n";
echo substr($output, 0, 500) . "\n";

<?php
// Simple export test
require_once 'config/database.php';
require_once 'models/PhieuYeuCau.php';

$model = new PhieuYeuCau();
$detail = $model->getPhieuDetail('1997');

if (!$detail) {
    echo "No data\n";
    exit;
}

echo "Data loaded: " . count($detail['devices']) . " devices\n";

// Now test the export
ob_start();

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

file_put_contents('test_output.doc', $output);

echo "Output size: " . strlen($output) . " bytes\n";
echo "File saved to test_output.doc\n";

// Check for common XML issues
if (strpos($output, '<u1:') !== false) {
    echo "ERROR: Contains u1: namespace tags\n";
}
if (strpos($output, ' <br>') !== false || strpos($output, '<br> ') !== false) {
    echo "ERROR: Contains unclosed <br> tags\n";
}
if (strpos($output, '</br>') !== false) {
    echo "ERROR: Contains </br> closing tags\n";
}
if (substr_count($output, '<div') != substr_count($output, '</div>')) {
    echo "WARNING: Mismatched div tags\n";
}

echo "Test complete\n";

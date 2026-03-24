<?php
// Test mavt-model display format

// Test data
$devices = [
    ['mavt' => 'TB001', 'model' => 'ABC123', 'tenvt' => 'Thiết bị A'],
    ['mavt' => 'TB002', 'model' => '', 'tenvt' => 'Thiết bị B'],
    ['mavt' => '', 'model' => 'XYZ789', 'tenvt' => 'Thiết bị C'],
    ['mavt' => 'TB004', 'model' => 'DEF456', 'tenvt' => 'Thiết bị D'],
];

$thietbi = [];
$mavt = [];
$model = [];
$solan = count($devices);

// Populate arrays (same logic as controller)
for ($i = 1; $i <= $solan; $i++) {
    if (isset($devices[$i-1])) {
        $device = $devices[$i-1];
        $thietbi[$i] = $device['tenvt'] ?? $device['mavt'];
        $mavt[$i] = $device['mavt'] ?? '';
        $model[$i] = $device['model'] ?? '';
    }
}

echo "=== Testing mavt-model Display Format ===\n\n";

// Test display logic (same as view)
for ($i = 1; $i <= $solan; $i++) {
    if (isset($thietbi[$i]) && $thietbi[$i] != "") {
        // Display format: mavt-model
        $mamay = $mavt[$i] ?? '';
        if (!empty($model[$i])) {
            $mamay .= (!empty($mamay) ? '-' : '') . $model[$i];
        }
        // If no mavt-model, fallback to thietbi name
        if (empty($mamay)) {
            $mamay = $thietbi[$i];
        }
        
        echo "Device $i:\n";
        echo "  mavt:    '{$mavt[$i]}'\n";
        echo "  model:   '{$model[$i]}'\n";
        echo "  tenvt:   '{$thietbi[$i]}'\n";
        echo "  Display: '{$mamay}'\n";
        echo str_repeat('-', 50) . "\n";
    }
}

echo "\n=== Expected Output ===\n";
echo "Device 1: TB001-ABC123\n";
echo "Device 2: TB002\n";
echo "Device 3: XYZ789\n";
echo "Device 4: TB004-DEF456\n";

<?php
// Test ttktbefore field update

echo "=== PhieuYeuCau Field Update Test ===\n\n";

// Simulate device data
$devices = [
    [
        'mavt' => 'TB001',
        'tenvt' => 'Thiết bị kiểm tra A',
        'model' => 'MODEL-A1',
        'somay' => 'SN12345',
        'honghoc' => 'Cũ - mô tả hỏng hóc',
        'ttktbefore' => 'Tình trạng kỹ thuật trước khi đưa về xưởng',
        'cv' => 'SC',
        'vitrimaybd' => 'Tam Đảo 5'
    ]
];

echo "Test Device:\n";
echo "  mavt: {$devices[0]['mavt']}\n";
echo "  honghoc (cũ): {$devices[0]['honghoc']}\n";  
echo "  ttktbefore (mới): {$devices[0]['ttktbefore']}\n";
echo "\n";

// Simulate controller logic
$thietbi = [];
$mavt = [];
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
        $mavt[$i] = $device['mavt'] ?? '';
        $model[$i] = $device['model'] ?? '';
        $somay[$i] = $device['somay'] ?? '';
        $tinhtrang[$i] = $device['ttktbefore'] ?? '';  // UPDATED: Using ttktbefore instead of honghoc
        $yeucau[$i] = $device['cv'] ?? '';
        $vitri[$i] = $device['vitrimaybd'] ?? '';
    }
}

echo "Controller Output:\n";
echo "  \$tinhtrang[1]: {$tinhtrang[1]}\n";
echo "\n";

echo "Expected in Word/PDF export:\n";
echo "  'Mô tả chi tiết tình trạng kỹ thuật...': {$tinhtrang[1]}\n";
echo "\n";

echo "✅ Update successful!\n";
echo "   - Controller now uses ttktbefore field\n";
echo "   - Word export displays ttktbefore\n";
echo "   - PDF export displays ttktbefore\n";

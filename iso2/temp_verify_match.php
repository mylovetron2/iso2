<?php
require 'models/ThietBiKpiBaoDuong.php';

class TestMatcher extends ThietBiKpiBaoDuong
{
    public function __construct()
    {
    }

    public function run(string $deviceCode, string $kpiValue): bool
    {
        $method = new ReflectionMethod($this, 'namesMatch');
        $method->setAccessible(true);
        return $method->invoke($this, $deviceCode, $kpiValue);
    }
}

$matcher = new TestMatcher();
$cases = [
    ['AK', 'AK-73, AK-76, AK-A-90'],
    ['AK73', 'AK-73, AK-76, AK-A-90'],
    ['AK76', 'AK-73, AK-76, AK-A-90'],
    ['AKA90', 'AK-73, AK-76, AK-A-90'],
];

foreach ($cases as [$deviceCode, $kpiValue]) {
    $result = $matcher->run($deviceCode, $kpiValue) ? 'match' : 'no-match';
    echo $deviceCode . ' <-> ' . $kpiValue . ' => ' . $result . PHP_EOL;
}

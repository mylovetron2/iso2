<?php
declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();
if (!hasPermission('admin') && !hasPermission('hososcbd.view')) {
    http_response_code(403);
    exit('Không có quyền truy cập.');
}

require_once __DIR__ . '/models/BaseModel.php';

class IndexHelper extends BaseModel {
    public function __construct() { parent::__construct('hososcbd_iso'); }
    public function getDb(): PDO { return $this->db; }

    public function getIndexes(string $table): array {
        $stmt = $this->db->query("SHOW INDEX FROM `$table`");
        $indexes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $indexes[$row['Key_name']][] = $row['Column_name'];
        }
        return $indexes;
    }

    public function addIndexIfMissing(string $table, string $indexName, string $colDef): array {
        $existing = $this->getIndexes($table);
        if (isset($existing[$indexName])) {
            return ['status' => 'skip', 'msg' => "Index <b>$indexName</b> trên <b>$table</b> đã tồn tại, bỏ qua."];
        }
        try {
            $this->db->exec("ALTER TABLE `$table` ADD INDEX `$indexName` ($colDef)");
            return ['status' => 'ok', 'msg' => "Đã thêm index <b>$indexName</b> trên <b>$table</b> ($colDef)."];
        } catch (PDOException $e) {
            return ['status' => 'error', 'msg' => "Lỗi thêm index <b>$indexName</b> trên <b>$table</b>: " . htmlspecialchars($e->getMessage())];
        }
    }
}

$helper = new IndexHelper();

// Danh sách index cần thêm: [table, index_name, columns]
$indexes = [
    // hososcbd_iso - các cột filter thường dùng
    ['hososcbd_iso',     'idx_madv',      '`madv`'],
    ['hososcbd_iso',     'idx_nhomsc',    '`nhomsc`'],
    ['hososcbd_iso',     'idx_ngayyc',    '`ngayyc`'],
    ['hososcbd_iso',     'idx_hoso',      '`hoso`'],
    ['hososcbd_iso',     'idx_phieu',     '`phieu`'],
    ['hososcbd_iso',     'idx_ngayth',    '`ngayth`'],
    ['hososcbd_iso',     'idx_ngaykt',    '`ngaykt`'],
    ['hososcbd_iso',     'idx_bg',        '`bg`'],
    // hososcbd_tamdung - tăng tốc correlated subquery MAX(id) GROUP BY hoso
    ['hososcbd_tamdung', 'idx_hoso_id',   '`hoso`, `id`'],
    // thietbi_iso - tăng tốc JOIN h.mavt = t.mavt AND h.somay = t.somay
    ['thietbi_iso',      'idx_mavt_somay', '`mavt`, `somay`'],
    // hososcbd_iso - tăng tốc JOIN với thietbi_iso qua mavt+somay
    ['hososcbd_iso',     'idx_mavt_somay', '`mavt`, `somay`'],
];

$results = [];
foreach ($indexes as [$table, $name, $cols]) {
    $results[] = $helper->addIndexIfMissing($table, $name, $cols);
}

// Hiển thị kết quả
$title = 'Thêm Index - HoSoSCBD';
require_once __DIR__ . '/views/layouts/header.php';
?>
<div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Thêm Index tối ưu hiệu năng - hososcbd_iso</h1>
    <div class="space-y-2">
        <?php foreach ($results as $r): ?>
            <?php
            $color = match($r['status']) {
                'ok'    => 'bg-green-50 border-green-400 text-green-800',
                'skip'  => 'bg-gray-50 border-gray-300 text-gray-600',
                'error' => 'bg-red-50 border-red-400 text-red-800',
                default => ''
            };
            $icon = match($r['status']) {
                'ok'    => '✅',
                'skip'  => '⏭️',
                'error' => '❌',
                default => ''
            };
            ?>
            <div class="border-l-4 p-3 rounded <?= $color ?>">
                <?= $icon ?> <?= $r['msg'] ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-6">
        <a href="hososcbd.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded font-semibold">
            ← Quay lại Hồ sơ SCBĐ
        </a>
    </div>
</div>
<?php
require_once __DIR__ . '/views/layouts/footer.php';

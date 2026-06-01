<?php
/**
 * Fix hồ sơ HC bị dùng chung cho nhiều máy có cùng mavattu.
 *
 * Cách làm ĐÚNG:
 *   - KHÔNG thay đổi mavattu trong thietbihckd_iso (stt mới là PK)
 *   - SET hosohckd_iso.thietbi_stt = <stt của máy vật lý đúng>
 *     cho từng hồ sơ HC bị ảnh hưởng
 *
 * Yêu cầu: Đã chạy migration add_thietbi_stt_to_hosohckd.sql trước.
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

requireAuth();

$db = getDBConnection();

// Kiểm tra cột thietbi_stt đã tồn tại
$colExists = !empty($db->query("SHOW COLUMNS FROM hosohckd_iso LIKE 'thietbi_stt'")->fetchAll());

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $colExists) {
    $action = $_POST['action'] ?? '';
    if ($action === 'assign_batch') {
        $assignments = $_POST['assignments'] ?? [];
        $count = 0;
        $stmt = $db->prepare('UPDATE hosohckd_iso SET thietbi_stt = :tb WHERE stt = :hs');
        foreach ($assignments as $hosoSttRaw => $tbSttRaw) {
            $hosoStt = (int)$hosoSttRaw;
            $tbStt   = (int)$tbSttRaw;
            if ($hosoStt > 0 && $tbStt > 0) {
                $stmt->execute([':tb' => $tbStt, ':hs' => $hosoStt]);
                $count++;
            }
        }
        $message     = "Đã gán $count hồ sơ.";
        $messageType = 'success';
    }
}

// Nhóm mavattu trùng
$dupGroups = $db->query("
    SELECT mavattu, COUNT(*) AS cnt
    FROM thietbihckd_iso
    GROUP BY mavattu HAVING COUNT(*) > 1
    ORDER BY mavattu
")->fetchAll(PDO::FETCH_ASSOC);

$groups = [];
foreach ($dupGroups as $g) {
    $mavattu = $g['mavattu'];
    $stmtM = $db->prepare("SELECT stt, mavattu, tenviettat, somay, bophansh
                            FROM thietbihckd_iso
                            WHERE mavattu = ? ORDER BY stt");
    $stmtM->execute([$mavattu]);
    $machines = $stmtM->fetchAll(PDO::FETCH_ASSOC);

    $stmtH = $db->prepare("SELECT stt, sohs, ngayhc, ttkt, thietbi_stt
                            FROM hosohckd_iso WHERE tenmay = ?
                            ORDER BY ngayhc DESC, stt DESC");
    $stmtH->execute([$mavattu]);
    $hosoList = $stmtH->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($hosoList)) {
        $groups[] = ['mavattu' => $mavattu, 'machines' => $machines, 'hoso' => $hosoList];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Gán thietbi_stt cho hồ sơ HC bị dùng chung</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-2">Fix hồ sơ HC bị dùng chung (mavattu trùng)</h1>
    <p class="text-sm text-gray-600 mb-4">
        Trang này <strong>không thay đổi mavattu</strong>.
        Chỉ gán <code>thietbi_stt</code> trong <code>hosohckd_iso</code>
        để liên kết hồ sơ đúng với máy vật lý.
    </p>

    <?php if (!$colExists): ?>
        <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded mb-6">
            <strong>Chưa chạy migration!</strong>
            Cần chạy <code>migrations/add_thietbi_stt_to_hosohckd.sql</code> trước.
        </div>
    <?php elseif ($message): ?>
        <div class="<?php echo $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-800' : 'bg-red-100 border-red-400 text-red-800'; ?> border px-4 py-3 rounded mb-6">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($groups)): ?>
        <div class="bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded">
            Không có hồ sơ HC nào cần xử lý.
        </div>
    <?php else: ?>
        <?php foreach ($groups as $g): ?>
        <div class="bg-white rounded shadow mb-8 p-5">
            <h2 class="text-lg font-semibold mb-3">
                mavattu: <code class="bg-yellow-100 px-2 py-0.5 rounded"><?php echo htmlspecialchars($g['mavattu']); ?></code>
                &mdash; <?php echo count($g['machines']); ?> máy, <?php echo count($g['hoso']); ?> hồ sơ HC
            </h2>
            <!-- Danh sách máy -->
            <table class="text-sm border border-gray-200 w-full mb-4">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="border px-3 py-1 text-left">STT (PK)</th>
                        <th class="border px-3 py-1 text-left">tenviettat</th>
                        <th class="border px-3 py-1 text-left">Số máy</th>
                        <th class="border px-3 py-1 text-left">Bộ phận</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($g['machines'] as $m): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border px-3 py-1 font-mono font-bold"><?php echo (int)$m['stt']; ?></td>
                        <td class="border px-3 py-1"><?php echo htmlspecialchars($m['tenviettat']); ?></td>
                        <td class="border px-3 py-1"><?php echo htmlspecialchars($m['somay']); ?></td>
                        <td class="border px-3 py-1 text-xs"><?php echo htmlspecialchars($m['bophansh'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <!-- Form gán batch -->
            <form method="post">
                <input type="hidden" name="action" value="assign_batch">
                <p class="text-sm font-medium text-gray-700 mb-2">Chọn máy đúng cho từng hồ sơ HC:</p>
                <table class="text-sm border border-gray-200 w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border px-3 py-1">Hồ sơ #STT</th>
                            <th class="border px-3 py-1">Số HS</th>
                            <th class="border px-3 py-1">Ngày HC</th>
                            <th class="border px-3 py-1">KQ</th>
                            <th class="border px-3 py-1">thietbi_stt hiện tại</th>
                            <th class="border px-3 py-1">Gán cho máy (STT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($g['hoso'] as $h): ?>
                        <tr class="<?php echo $h['thietbi_stt'] ? 'bg-green-50' : 'bg-white'; ?> hover:bg-yellow-50">
                            <td class="border px-3 py-1 font-mono text-center"><?php echo (int)$h['stt']; ?></td>
                            <td class="border px-3 py-1"><?php echo htmlspecialchars($h['sohs']); ?></td>
                            <td class="border px-3 py-1"><?php echo htmlspecialchars($h['ngayhc']); ?></td>
                            <td class="border px-3 py-1"><?php echo htmlspecialchars($h['ttkt']); ?></td>
                            <td class="border px-3 py-1 text-center font-mono">
                                <?php echo $h['thietbi_stt'] ? (int)$h['thietbi_stt'] : '<span class="text-red-400">NULL</span>'; ?>
                            </td>
                            <td class="border px-3 py-1">
                                <select name="assignments[<?php echo (int)$h['stt']; ?>]" class="border rounded px-2 py-1 text-sm w-full">
                                    <option value="0">-- không đổi --</option>
                                    <?php foreach ($g['machines'] as $m): ?>
                                    <option value="<?php echo (int)$m['stt']; ?>"
                                        <?php echo ((int)$h['thietbi_stt'] === (int)$m['stt']) ? 'selected' : ''; ?>>
                                        STT <?php echo (int)$m['stt']; ?> — [<?php echo htmlspecialchars($m['somay']); ?>]
                                        <?php echo htmlspecialchars($m['tenviettat']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="mt-3 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium">
                    Lưu phân công nhóm này
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>

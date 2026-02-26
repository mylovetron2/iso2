<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

// Display info page
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm Tra Hệ Thống KPI Sửa Chữa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    
    <div class="container mx-auto max-w-4xl">
        
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4">
                <i class="fas fa-check-circle text-green-600"></i> 
                Kiểm Tra Hệ Thống Quản Lý Công Việc KPI
            </h1>
            <p class="text-gray-600 mb-6">
                Trang này kiểm tra các bảng, view, trigger của hệ thống KPI sửa chữa.
            </p>
        </div>

        <?php
        require_once __DIR__ . '/config/database.php';
        $db = getDBConnection();

        // 1. Kiểm tra bảng
        echo "<div class='bg-white rounded-lg shadow-md p-6 mb-6'>";
        echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-database'></i> 1. Kiểm Tra Bảng</h2>";
        
        $requiredTables = [
            'capdo_baocuong_iso' => 'Cấp độ bảo dưỡng',
            'thietbi_capdo_kpi_iso' => 'KPI thiết bị',
            'congviec_suachua_iso' => 'Công việc sửa chữa'
        ];
        
        foreach ($requiredTables as $table => $desc) {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            
            if ($exists) {
                // Đếm số records
                $count = $db->query("SELECT COUNT(*) as cnt FROM $table")->fetch()['cnt'];
                echo "<p class='mb-2'>✅ <strong>$table</strong> ($desc) - $count records</p>";
            } else {
                echo "<p class='mb-2'>❌ <strong>$table</strong> ($desc) - CHƯA TỒN TẠI</p>";
            }
        }
        echo "</div>";

        // 2. Kiểm tra View
        echo "<div class='bg-white rounded-lg shadow-md p-6 mb-6'>";
        echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-eye'></i> 2. Kiểm Tra View</h2>";
        
        $views = [
            'view_congviec_nhanvien_thongke',
            'view_kpi_thietbi_thongke',
            'view_thongke_theo_capdo'
        ];
        
        foreach ($views as $view) {
            $stmt = $db->query("SHOW FULL TABLES WHERE Table_Type = 'VIEW' AND Tables_in_diavatly_db = '$view'");
            $exists = $stmt->rowCount() > 0;
            
            if ($exists) {
                echo "<p class='mb-2'>✅ <strong>$view</strong></p>";
            } else {
                echo "<p class='mb-2'>❌ <strong>$view</strong> - CHƯA TỒN TẠI</p>";
            }
        }
        echo "</div>";

        // 3. Kiểm tra Trigger
        echo "<div class='bg-white rounded-lg shadow-md p-6 mb-6'>";
        echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-bolt'></i> 3. Kiểm Tra Trigger</h2>";
        
        $triggers = [
            'before_insert_congviec_check_gio',
            'before_update_congviec_check_gio'
        ];
        
        foreach ($triggers as $trigger) {
            $stmt = $db->query("SHOW TRIGGERS WHERE `Trigger` = '$trigger'");
            $exists = $stmt->rowCount() > 0;
            
            if ($exists) {
                echo "<p class='mb-2'>✅ <strong>$trigger</strong></p>";
            } else {
                echo "<p class='mb-2'>❌ <strong>$trigger</strong> - CHƯA TỒN TẠI</p>";
            }
        }
        echo "</div>";

        // 4. Kiểm tra dữ liệu cấp độ
        echo "<div class='bg-white rounded-lg shadow-md p-6 mb-6'>";
        echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-layer-group'></i> 4. Dữ Liệu Cấp Độ</h2>";
        
        $stmt = $db->query("SELECT * FROM capdo_baocuong_iso ORDER BY thu_tu");
        $capdos = $stmt->fetchAll();
        
        if (count($capdos) > 0) {
            echo "<table class='min-w-full border'>";
            echo "<thead class='bg-gray-200'>";
            echo "<tr>";
            echo "<th class='border px-4 py-2'>Mã</th>";
            echo "<th class='border px-4 py-2'>Tên</th>";
            echo "<th class='border px-4 py-2'>KPI (giờ)</th>";
            echo "<th class='border px-4 py-2'>Trạng thái</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            foreach ($capdos as $cd) {
                $status = $cd['trang_thai'] == 1 ? '<span class="text-green-600">Kích hoạt</span>' : '<span class="text-red-600">Vô hiệu</span>';
                echo "<tr>";
                echo "<td class='border px-4 py-2'>{$cd['ma_capdo']}</td>";
                echo "<td class='border px-4 py-2'>{$cd['ten_capdo']}</td>";
                echo "<td class='border px-4 py-2 text-center'>{$cd['kpi_gio_chuan']}</td>";
                echo "<td class='border px-4 py-2 text-center'>$status</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p class='text-yellow-600'>⚠️ Chưa có dữ liệu cấp độ. Vui lòng chạy migration.</p>";
        }
        echo "</div>";

        // 5. Kiểm tra models
        echo "<div class='bg-white rounded-lg shadow-md p-6 mb-6'>";
        echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-code'></i> 5. Kiểm Tra Model Files</h2>";
        
        $modelFiles = [
            'models/CapDoBaoCuong.php',
            'models/ThietBiCapDoKPI.php',
            'models/CongViecSuaChua.php'
        ];
        
        foreach ($modelFiles as $file) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                echo "<p class='mb-2'>✅ <strong>$file</strong></p>";
            } else {
                echo "<p class='mb-2'>❌ <strong>$file</strong> - CHƯA TỒN TẠI</p>";
            }
        }
        echo "</div>";

        // 6. Kiểm tra controller
        echo "<div class='bg-white rounded-lg shadow-md p-6 mb-6'>";
        echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-cogs'></i> 6. Kiểm Tra Controller</h2>";
        
        $controllerFile = 'controllers/CongViecSuaChuaController.php';
        $fullPath = __DIR__ . '/' . $controllerFile;
        
        if (file_exists($fullPath)) {
            echo "<p>✅ <strong>$controllerFile</strong></p>";
            
            require_once $fullPath;
            $controller = new CongViecSuaChuaController();
            echo "<p class='mt-2 text-green-600'>✅ Controller khởi tạo thành công</p>";
        } else {
            echo "<p>❌ <strong>$controllerFile</strong> - CHƯA TỒN TẠI</p>";
        }
        echo "</div>";

        // 7. Kiểm tra views
        echo "<div class='bg-white rounded-lg shadow-md p-6 mb-6'>";
        echo "<h2 class='text-xl font-bold mb-4'><i class='fas fa-desktop'></i> 7. Kiểm Tra View Files</h2>";
        
        $viewFiles = [
            'views/congviec/index.php' => 'View nhập công việc',
            'congviec_suachua.php' => 'Route chính',
            'baocao_kpi.php' => 'Báo cáo KPI'
        ];
        
        foreach ($viewFiles as $file => $desc) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                echo "<p class='mb-2'>✅ <strong>$file</strong> ($desc)</p>";
            } else {
                echo "<p class='mb-2'>❌ <strong>$file</strong> ($desc) - CHƯA TỒN TẠI</p>";
            }
        }
        echo "</div>";
        ?>

        <!-- Links -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded">
            <h3 class="font-bold mb-3"><i class="fas fa-link"></i> Truy Cập Chức Năng</h3>
            <div class="space-y-2">
                <a href="congviec_suachua.php" class="text-blue-600 hover:underline block">
                    <i class="fas fa-arrow-right"></i> Nhập công việc sửa chữa hàng ngày
                </a>
                <a href="baocao_kpi.php" class="text-blue-600 hover:underline block">
                    <i class="fas fa-arrow-right"></i> Xem báo cáo KPI
                </a>
                <a href="CONGVIEC_KPI_README.md" class="text-blue-600 hover:underline block" target="_blank">
                    <i class="fas fa-arrow-right"></i> Đọc tài liệu hướng dẫn
                </a>
            </div>
        </div>

        <!-- Hướng dẫn chạy migration -->
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded mt-6">
            <h3 class="font-bold mb-3"><i class="fas fa-exclamation-triangle"></i> Nếu Thiếu Bảng/View</h3>
            <p class="mb-2">Chạy lệnh sau trong terminal hoặc phpMyAdmin:</p>
            <pre class="bg-gray-800 text-white p-4 rounded overflow-x-auto">mysql -u root -p diavatly_db < migrations/20260224_create_kpi_suachua_system.sql</pre>
            <p class="mt-2 text-sm text-gray-600">Hoặc import file SQL trong phpMyAdmin</p>
        </div>

    </div>
</body>
</html>

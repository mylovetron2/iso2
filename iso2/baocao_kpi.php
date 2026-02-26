<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

require_once __DIR__ . '/controllers/CongViecSuaChuaController.php';

$controller = new CongViecSuaChuaController();

// Xuất Excel nếu có request
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $controller->exportExcel();
    exit;
}

// Lấy dữ liệu báo cáo
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');

$_GET['from'] = $from;
$_GET['to'] = $to;

$baoCao = $controller->getBaoCaoTongQuan();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo KPI Sửa Chữa</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body class="bg-gray-100">
    
    <!-- Header -->
    <div class="bg-purple-600 text-white p-4 shadow-md">
        <div class="container mx-auto">
            <h1 class="text-2xl font-bold">
                <i class="fas fa-chart-bar"></i> Báo Cáo KPI Sửa Chữa
            </h1>
            <p class="text-sm mt-1">Thống kê hiệu suất công việc theo nhân viên và cấp độ</p>
        </div>
    </div>

    <div class="container mx-auto p-6">
        
        <!-- Filter -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4"><i class="fas fa-calendar-alt"></i> Chọn Khoảng Thời Gian</h2>
            
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Từ ngày</label>
                    <input type="date" name="from" value="<?= $from ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Đến ngày</label>
                    <input type="date" name="to" value="<?= $to ?>" 
                           class="w-full border rounded px-3 py-2">
                </div>
                
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        <i class="fas fa-search"></i> Xem
                    </button>
                    <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" 
                       class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        Tháng này
                    </a>
                </div>
                
                <div class="flex items-end">
                    <a href="?from=<?= $from ?>&to=<?= $to ?>&export=excel" 
                       class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600 w-full text-center">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </a>
                </div>
            </form>
        </div>

        <!-- Báo cáo tổng quan theo nhân viên -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">
                <i class="fas fa-users"></i> Báo Cáo Theo Nhân Viên
            </h2>
            
            <?php if (empty($baoCao['bao_cao_nhan_vien'])): ?>
                <p class="text-gray-500 text-center py-8">
                    <i class="fas fa-inbox text-4xl mb-2"></i><br>
                    Chưa có dữ liệu trong khoảng thời gian này
                </p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full border">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="border px-4 py-2">STT</th>
                                <th class="border px-4 py-2">Nhân viên</th>
                                <th class="border px-4 py-2">Số công việc</th>
                                <th class="border px-4 py-2">Tổng giờ</th>
                                <th class="border px-4 py-2">Giờ TB/công việc</th>
                                <th class="border px-4 py-2">Số ngày làm</th>
                                <th class="border px-4 py-2">TB giờ/ngày</th>
                                <th class="border px-4 py-2">Số thiết bị sửa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalCongViec = 0;
                            $totalGio = 0;
                            $stt = 1;
                            foreach ($baoCao['bao_cao_nhan_vien'] as $row): 
                                $totalCongViec += $row['so_cong_viec'];
                                $totalGio += $row['tong_gio'];
                                $avgGioPerDay = $row['so_ngay_lam'] > 0 ? $row['tong_gio'] / $row['so_ngay_lam'] : 0;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="border px-4 py-2 text-center"><?= $stt++ ?></td>
                                <td class="border px-4 py-2">
                                    <strong><?= htmlspecialchars($row['nhanvien_ten']) ?></strong>
                                </td>
                                <td class="border px-4 py-2 text-center"><?= $row['so_cong_viec'] ?></td>
                                <td class="border px-4 py-2 text-center font-bold text-blue-600">
                                    <?= number_format($row['tong_gio'], 2) ?>h
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <?= number_format($row['gio_trung_binh'], 2) ?>h
                                </td>
                                <td class="border px-4 py-2 text-center"><?= $row['so_ngay_lam'] ?> ngày</td>
                                <td class="border px-4 py-2 text-center">
                                    <?= number_format($avgGioPerDay, 2) ?>h
                                </td>
                                <td class="border px-4 py-2 text-center"><?= $row['so_thietbi_sua'] ?> TB</td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Tổng cộng -->
                            <tr class="bg-blue-50 font-bold">
                                <td colspan="2" class="border px-4 py-2 text-right">TỔNG CỘNG:</td>
                                <td class="border px-4 py-2 text-center"><?= $totalCongViec ?></td>
                                <td class="border px-4 py-2 text-center text-blue-600">
                                    <?= number_format($totalGio, 2) ?>h
                                </td>
                                <td colspan="4" class="border px-4 py-2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Biểu đồ -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <canvas id="chartNhanVienGio"></canvas>
                    </div>
                    <div>
                        <canvas id="chartNhanVienCongViec"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Thống kê theo cấp độ -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold mb-4">
                <i class="fas fa-layer-group"></i> Thống Kê Theo Cấp Độ Bảo Dưỡng
            </h2>
            
            <?php if (empty($baoCao['thong_ke_cap_do'])): ?>
                <p class="text-gray-500 text-center py-8">Chưa có dữ liệu</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full border">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="border px-4 py-2">Cấp độ</th>
                                <th class="border px-4 py-2">KPI Chuẩn</th>
                                <th class="border px-4 py-2">Số công việc</th>
                                <th class="border px-4 py-2">Tổng giờ làm</th>
                                <th class="border px-4 py-2">Giờ TB/công việc</th>
                                <th class="border px-4 py-2">Hiệu suất (%)</th>
                                <th class="border px-4 py-2">Đánh giá</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($baoCao['thong_ke_cap_do'] as $cd): 
                                $hieuSuat = $cd['hieu_suat_percent'] ?? 0;
                                $danhGia = '';
                                $colorClass = '';
                                
                                if ($hieuSuat >= 100) {
                                    $danhGia = 'Đạt KPI';
                                    $colorClass = 'text-green-600';
                                } elseif ($hieuSuat >= 80) {
                                    $danhGia = 'Gần đạt';
                                    $colorClass = 'text-orange-600';
                                } else {
                                    $danhGia = 'Chưa đạt';
                                    $colorClass = 'text-red-600';
                                }
                            ?>
                            <tr>
                                <td class="border px-4 py-2">
                                    <strong><?= htmlspecialchars($cd['ten_capdo']) ?></strong>
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <?= number_format($cd['kpi_gio_chuan'], 2) ?>h
                                </td>
                                <td class="border px-4 py-2 text-center"><?= $cd['so_cong_viec'] ?: 0 ?></td>
                                <td class="border px-4 py-2 text-center">
                                    <?= number_format($cd['tong_gio_lam'] ?? 0, 2) ?>h
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <?= number_format($cd['gio_trung_binh'] ?? 0, 2) ?>h
                                </td>
                                <td class="border px-4 py-2 text-center font-bold <?= $colorClass ?>">
                                    <?= number_format($hieuSuat, 1) ?>%
                                </td>
                                <td class="border px-4 py-2 text-center">
                                    <span class="<?= $colorClass ?> font-semibold"><?= $danhGia ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Biểu đồ cấp độ -->
                <div class="mt-6">
                    <canvas id="chartCapDo"></canvas>
                </div>
            <?php endif; ?>
        </div>

        <!-- Nút quay lại -->
        <div>
            <a href="congviec_suachua.php" class="bg-gray-500 text-white px-6 py-3 rounded hover:bg-gray-600 inline-block">
                <i class="fas fa-arrow-left"></i> Quay lại Nhập công việc
            </a>
        </div>
    </div>

    <script>
        // Dữ liệu cho biểu đồ
        const nhanvienData = <?= json_encode($baoCao['bao_cao_nhan_vien']) ?>;
        const capdoData = <?= json_encode($baoCao['thong_ke_cap_do']) ?>;

        // Biểu đồ tổng giờ theo nhân viên
        if (nhanvienData.length > 0) {
            const ctxGio = document.getElementById('chartNhanVienGio').getContext('2d');
            new Chart(ctxGio, {
                type: 'bar',
                data: {
                    labels: nhanvienData.map(d => d.nhanvien_ten),
                    datasets: [{
                        label: 'Tổng giờ làm việc',
                        data: nhanvienData.map(d => parseFloat(d.tong_gio)),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Tổng Giờ Làm Việc Theo Nhân Viên'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Giờ'
                            }
                        }
                    }
                }
            });

            // Biểu đồ số công việc
            const ctxCV = document.getElementById('chartNhanVienCongViec').getContext('2d');
            new Chart(ctxCV, {
                type: 'pie',
                data: {
                    labels: nhanvienData.map(d => d.nhanvien_ten),
                    datasets: [{
                        label: 'Số công việc',
                        data: nhanvienData.map(d => parseInt(d.so_cong_viec)),
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)',
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Phân Bổ Công Việc Theo Nhân Viên'
                        }
                    }
                }
            });
        }

        // Biểu đồ cấp độ
        if (capdoData.length > 0) {
            const ctxCD = document.getElementById('chartCapDo').getContext('2d');
            new Chart(ctxCD, {
                type: 'bar',
                data: {
                    labels: capdoData.map(d => d.ten_capdo),
                    datasets: [
                        {
                            label: 'KPI Chuẩn (h)',
                            data: capdoData.map(d => parseFloat(d.kpi_gio_chuan)),
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Giờ TB Thực Tế (h)',
                            data: capdoData.map(d => parseFloat(d.gio_trung_binh || 0)),
                            backgroundColor: 'rgba(255, 159, 64, 0.6)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'So Sánh KPI Chuẩn vs Thực Tế Theo Cấp Độ'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Giờ'
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>

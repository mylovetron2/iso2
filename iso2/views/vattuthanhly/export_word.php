<!DOCTYPE html>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <title>Thống kê Vật tư Thanh lý</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 2cm 1.5cm 2cm 1.5cm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13pt;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 15px 0;
        }
        .subtitle {
            font-size: 13pt;
            font-style: italic;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11pt;
        }
        th, td {
            border: 1px solid black;
            padding: 6px;
        }
        th {
            background-color: #dbeafe;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        td {
            vertical-align: middle;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .bilingual-header {
            font-size: 11pt;
            line-height: 1.2;
        }
        .header-vn {
            font-weight: bold;
        }
        .header-ru {
            font-style: italic;
            font-size: 10pt;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f9ff;
        }
        .col-stt {
            width: 3%;
        }
        .col-mavattu {
            width: 10%;
        }
        .col-tenvattu {
            width: 25%;
        }
        .col-donvi {
            width: 7%;
        }
        .col-namsd {
            width: 7%;
        }
        .col-soluong {
            width: 7%;
        }
        .col-dongia {
            width: 10%;
        }
        .col-thanhtien {
            width: 12%;
        }
        .col-nguyennhan {
            width: 19%;
        }
        .header-container {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
            font-size: 11pt;
            line-height: 1.4;
        }
        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
            font-size: 11pt;
        }
        .approval-signature {
            margin-top: 50px;
        }
        .act-title {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin: 20px 0 10px 0;
        }
        .description {
            font-size: 11pt;
            text-align: justify;
            margin: 10px 0 10px 0;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <div class="header-left">
            <div>LD Việt-Nga Vietsovpetro/СП «Вьетсовпетро»</div>
            <div>XN Dia vật lý GK/ КПП</div>
            <div>Xưởng Sửa chữa thiết bị dia vật lý / ЦРГО</div>
        </div>
        <div class="header-right">
            <div><strong>Phụ lục 2/Приложение 2</strong></div>
            <div style="margin-top: 15px;"><strong>"Phê duyệt/Утверждаю"</strong></div>
            <div>Chủ tịch Hội đồng thanh lý/</div>
            <div>Председатель комиссии</div>
            <div class="approval-signature">
                <div>Phạm Hồng Khanh</div>
                <div>«____» ____ 2025 г.</div>
            </div>
        </div>
    </div>
    
    <?php 
    // Extract month and year from the date range
    $month = isset($denngay) ? date('m', strtotime($denngay)) : date('m');
    $year = isset($denngay) ? date('Y', strtotime($denngay)) : date('Y');
    $monthName = isset($denngay) ? date('n', strtotime($denngay)) : date('n'); // Without leading zero
    ?>
    
    <div class="act-title">
        BIÊN BẢN/АКТ №____<br/>
        V/v SỬ DỤNG VẬT TƯ TIÊU HAO, PHỤ TÙNG THÁNG <?php echo $monthName; ?> NĂM <?php echo $year; ?><br/>
        ОБ ИСПОЛЬЗОВАНИИ РАСХОДНЫХ МАТЕРИАЛОВ, ЗАПЧАСТЕЙ<br/>
        ЗА <?php echo $month . '/' . $year; ?>Г.
    </div>
    
    <div class="description">
        Hội đồng thanh lý công cụ, dụng cụ, vật tư tiêu hao được bổ nhiệm bằng quyết định số 131/QĐ-ĐVL ngày 08 tháng 08 năm 2019, xác nhận rằng trong tháng đã sử dụng tại Xưởng SCTBĐVL những vật tư tiêu hao, phụ tùng dưới đây:
    </div>
    
    <div class="description" style="font-style: italic;">
        Комиссия по списанию инструментов, расходных материалов, назначенная приказом № 131/QĐ-ĐVL от 08/08/2019г, подтверждает, что за текущий месяц в/на ЦРГО  израсходованы нижеперечисленные материалы и запчасти:
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-stt bilingual-header">
                    <div class="header-vn">TT</div>
                    <div class="header-ru">ПП</div>
                </th>
                <th class="col-mavattu bilingual-header">
                    <div class="header-vn">Mã vật tư</div>
                    <div class="header-ru">Номенкла-турный код</div>
                </th>
                <th class="col-tenvattu bilingual-header">
                    <div class="header-vn">Tên vật tư</div>
                    <div class="header-ru">Наименование материалов</div>
                </th>
                <th class="col-donvi bilingual-header">
                    <div class="header-vn">Đơn vị</div>
                    <div class="header-ru">Ед. изм</div>
                </th>
                <th class="col-namsd bilingual-header">
                    <div class="header-vn">Năm SD</div>
                    <div class="header-ru">Срок эксплуа-тации (лет)</div>
                </th>
                <th class="col-soluong bilingual-header">
                    <div class="header-vn">Số lượng</div>
                    <div class="header-ru">Кол-во</div>
                </th>
                <th class="col-dongia bilingual-header">
                    <div class="header-vn">Đơn giá</div>
                    <div class="header-ru">Цена</div>
                </th>
                <th class="col-thanhtien bilingual-header">
                    <div class="header-vn">Thành tiền</div>
                    <div class="header-ru">Сумма</div>
                </th>
                <th class="col-nguyennhan bilingual-header">
                    <div class="header-vn">Nguyên nhân</div>
                    <div class="header-ru">Причина списания</div>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['mavattu']); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($item['ten_tiengviet']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['donvi'] ?? ''); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($item['namsd'] ?? ''); ?></td>
                    <td class="text-center"><?php echo number_format($item['soluong_thaydoi']); ?></td>
                    <td class="text-right"><?php echo number_format($item['dongia'], 2, ',', '.'); ?></td>
                    <td class="text-right"><?php echo number_format($item['thanhtien'], 2, ',', '.'); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($item['nguyennhan'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
                
                <!-- Total row -->
                <tr class="total-row">
                    <td colspan="5" class="text-center">
                        <strong>Tổng cộng / Всего:</strong>
                    </td>
                    <td class="text-center">
                        <strong><?php echo number_format($totalQuantity); ?></strong>
                    </td>
                    <td></td>
                    <td class="text-right">
                        <strong><?php echo number_format($totalAmount, 2, ',', '.'); ?> đ</strong>
                    </td>
                    <td></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">
                        <em>Không có dữ liệu trong khoảng thời gian này</em>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px; font-size: 12pt;">
        <p><strong>Tổng số hiện vật (ghi bằng chữ):</strong> <?php echo ucfirst($totalInWords); ?> mục.</p>
        <p><strong>Общее количество предметов (прописью):</strong></p>
    </div>

    <div style="margin-top: 20px; font-size: 12pt;">
        <p><strong>Những vật phế liệu dưới đây cần được nhập kho:</strong></p>
        <p style="font-style: italic;"><strong>Списанные предметы, подлежащие оприходованию в качестве утиля:</strong></p>
    </div>

    <div style="margin-top: 20px; font-size: 12pt;">
        <p><strong>Những vật phế thải không nhập kho sẽ được hủy bỏ.</strong></p>
        <p style="font-style: italic;"><strong>Неоприходованные отходы, подлежащие утилизации.</strong></p>
    </div>

    <div style="margin-top: 40px; font-size: 11pt;">
        <div style="margin-bottom: 20px;">
            <p><strong>Phó chủ tịch hội đồng / Зам. председателя:</strong></p>
            <table style="border: none; width: 100%;">
                <tr style="border: none;">
                    <td style="border: none; width: 60%; vertical-align: bottom;">
                        <strong>Chánh kế toán/ Глав. бухгалтер</strong>
                    </td>
                    <td style="border: none; width: 40%; vertical-align: bottom; text-align: right;">
                        _________________Đinh Thủy Việt
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 20px;">
            <p><strong>Thành viên/Члены:</strong></p>
            <table style="border: none; width: 100%;">
                <tr style="border: none;">
                    <td style="border: none; width: 60%; vertical-align: bottom;">
                        Trưởng Ban VTHC /<br/>
                        Начальник СМТОиЛ
                    </td>
                    <td style="border: none; width: 40%; vertical-align: bottom; text-align: right;">
                        _________________Phan Văn Hòa
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 20px;">
            <table style="border: none; width: 100%;">
                <tr style="border: none;">
                    <td style="border: none; width: 60%; vertical-align: bottom;">
                        Trưởng phòng kỹ thuật sản xuất/<br/>
                        Начальник профильного технического отдела
                    </td>
                    <td style="border: none; width: 40%; vertical-align: bottom; text-align: right;">
                        _________________Nguyễn Đình Hưởng
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 20px;">
            <table style="border: none; width: 100%;">
                <tr style="border: none;">
                    <td style="border: none; width: 60%; vertical-align: bottom;">
                        Xưởng trưởng Xưởng SCTBĐVL<br/>
                        Начальник ЦРГО<br/>
                        Người chịu trách nhiệm vật chất/<br/>
                        Материально-ответственное лицо
                    </td>
                    <td style="border: none; width: 40%; vertical-align: bottom; text-align: right;">
                        _________________Đặng Văn Tuệ
                    </td>
                </tr>
            </table>
        </div>

        <div style="margin-bottom: 10px;">
            <p><strong>Viza:</strong></p>
            <p>Trưởng phòng KTKH-TMDV/</p>
        </div>

        <div style="margin-top: 20px; font-size: 10pt;">
            <table style="border: none; width: 100%;">
                <tr style="border: none;">
                    <td style="border: none; width: 33%; text-align: left;">
                        VSP- 000 -ТСКТ-244
                    </td>
                    <td style="border: none; width: 34%; text-align: center;">
                        Phiên bản/Версия: 00
                    </td>
                    <td style="border: none; width: 33%; text-align: right;">
                        Trang/Стр. 2/3
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>

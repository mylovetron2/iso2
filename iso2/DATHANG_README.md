# CHỨC NĂNG PHIẾU ĐẶT HÀNG - VẬT TƯ THANH LÝ

## 📋 Tổng Quan

Đã thêm chức năng **Tạo Phiếu Đặt Hàng** vào module Vật Tư Thanh Lý với giao diện 3 ngôn ngữ (Việt - Anh - Nga).

## 🎯 Các File Đã Tạo/Sửa

### 1. Router & Controller
- ✅ `vattuthanhly.php` - Thêm 2 actions mới: `taophieudathang`, `xuatphieudathang`
- ✅ `controllers/VatTuThanhLyController.php` - Thêm 2 methods:
  - `taophieudathang()` - Chọn vật tư và hiển thị phiếu
  - `xuatphieudathang()` - Xuất file Excel

### 2. Views (Tailwind CSS)
- ✅ `views/vattuthanhly/chon_dathang.php` - Trang chọn vật tư (có tìm kiếm, lọc)
- ✅ `views/vattuthanhly/phieu_dathang.php` - Form phiếu đặt hàng
- ✅ `views/vattuthanhly/index.php` - Thêm nút "Tạo phiếu đặt hàng"

### 3. Test File
- ✅ `test_dathang.php` - File kiểm tra chức năng

## 📊 Cấu Trúc Bảng (Theo Mẫu)

| П/п (Stt) | Tên Tiếng Anh | Tên Tiếng Nga | Tên Tiếng Việt | Đặc tính KT | Đơn vị | Số lượng | Ghi chú |
|-----------|---------------|---------------|----------------|-------------|---------|----------|---------|
| 1 | CAP 0.1UF 100V... | Конденсатор 0.1мкФ... | Tụ điện 0.1UF... | Xem YCKT | Cái | 20 | |
| 2 | CAP CER 0.47UF... | Конденсатор 0.47мкФ... | Tụ điện CER... | Xem YCKT | Cái | 15 | |

## 🚀 Hướng Dẫn Sử Dụng

### Bước 1: Vào trang Vật Tư Thanh Lý
```
https://diavatly.cloud/iso2/vattuthanhly.php
```
Hoặc local:
```
http://localhost/iso2/vattuthanhly.php
```

### Bước 2: Click nút "Tạo phiếu đặt hàng"
- Nút màu cam ở phía trên bên phải
- Icon: 🛒 (shopping cart)

### Bước 3: Chọn vật tư cần đặt
- ☑️ Check vào checkbox các vật tư cần đặt
- 🔍 Có thể tìm kiếm theo tên, mã vật tư
- 📂 Có thể lọc theo phân loại
- ✅ Click "Chọn tất cả" để chọn toàn bộ

### Bước 4: Tạo phiếu
- Click nút **"Tạo Phiếu Đặt Hàng (n)"** (n = số vật tư đã chọn)
- Hiển thị form phiếu đặt hàng

### Bước 5: Nhập thông tin
- ✏️ Nhập **số lượng** cho từng vật tư (mặc định = số tồn kho)
- ✏️ Thêm **ghi chú** nếu cần
- 🗑️ Có thể xóa vật tư không cần

### Bước 6: Xuất file hoặc in
- **📗 Xuất Excel**: Tải file .xls (mở bằng Excel/LibreOffice)
- **🖨️ In Phiếu**: In trực tiếp từ trình duyệt
- **Chọn lại**: Quay lại chọn vật tư khác
- **Hủy**: Quay về trang danh sách

## 🌐 Đa Ngôn Ngữ

### Header Bảng:
- **Tiếng Việt**: STT, Tên hàng hóa, Đặc tính kỹ thuật, Đơn vị tính, Số lượng, Ghi chú
- **Tiếng Anh**: Stt, Name, Technical Specs, Unit, Quantity, Note
- **Tiếng Nga**: П/п, Наименование, Тех. Характеристики, Ед.изм, Объем, Примечание

### Chữ ký:
- **Người lập phiếu** / Prepared by / Подготовлено
- **Phê duyệt** / Approved by / Утверждено

## 🎨 Giao Diện (Tailwind CSS)

### Trang Chọn Vật Tư
- **Header**: Màu xanh dương (`bg-blue-600`)
- **Bộ lọc**: 
  - Input tìm kiếm
  - Dropdown phân loại
  - Nút "Tạo Phiếu" (màu xanh lá)
  - Nút "Chọn tất cả" (màu xám)
- **Bảng**: 
  - Checkbox mỗi dòng
  - Badge màu hiển thị tồn kho
  - Badge màu hiển thị phân loại

### Phiếu Đặt Hàng
- **Header**: Màu xanh dương
- **Bảng**: Định dạng 3 header rows (giống mẫu)
- **Nút thao tác**:
  - Xuất Excel: Xanh lá (`bg-green-600`)
  - In Phiếu: Xanh dương (`bg-blue-600`)
  - Chọn lại: Vàng (`bg-yellow-600`)
  - Hủy: Xám (`bg-gray-600`)
- **Chữ ký**: 2 cột, viền trên đen

## 🧪 Test

### Test thủ công:
```
http://localhost/iso2/test_dathang.php
```

### Checklist:
- [ ] Files tồn tại
- [ ] Controller methods hoạt động
- [ ] Database connection OK
- [ ] Trang chọn vật tư hiển thị đúng
- [ ] Checkbox chọn vật tư hoạt động
- [ ] Bộ lọc/tìm kiếm hoạt động
- [ ] Phiếu đặt hàng hiển thị đúng 3 ngôn ngữ
- [ ] Nhập số lượng hoạt động
- [ ] Xuất Excel ra file đúng format
- [ ] In phiếu hiển thị đúng (ẩn nút thao tác)

## 📝 Ví Dụ Dữ Liệu

### Input (từ database):
```sql
SELECT 
    stt, mavattu,
    ten_tienganh, ten_tiengnga, ten_tiengviet,
    dactinhkt_tiengnga, dactinhkt_tiengviet,
    dvt_tiengnga, dvt_tiengviet,
    soluong_conlai
FROM vattu_thanh_ly_iso
WHERE stt IN (1,2,3,4)
```

### Output (Excel):
| 1 | CAP 0.1UF 100V X7R 0805 | Конденсатор 0.1мкФ 100В | Tụ điện 0.1UF 100V | Xem YCKT | Cái | 20 | |
| 2 | CAP CER 0.47UF 630V | Конденсатор 0.47мкФ 630В | Tụ điện CER 0.47UF | Xem YCKT | Cái | 15 | |

## 🐛 Troubleshooting

### Lỗi: "No permission"
- Kiểm tra user có quyền `vattu.view`
- File: `vattuthanhly.php` line 26-30

### Lỗi: "no_items"
- Không có vật tư nào được chọn
- Quay lại chọn ít nhất 1 vật tư

### Lỗi: Hiển thị sai font/layout
- Kiểm tra `views/layouts/header.php` có load Tailwind CSS
- Kiểm tra `views/layouts/footer.php` có load jQuery

### Lỗi: Xuất Excel bị lỗi font
- File đã có UTF-8 BOM (`\xEF\xBB\xBF`)
- Mở bằng Excel > Save As > chọn encoding UTF-8

## 📞 Support

Nếu có vấn đề:
1. Chạy `test_dathang.php` để kiểm tra
2. Xem error log: `check_php_error_log.php`
3. Kiểm tra database connection
4. Kiểm tra permissions

## 🎉 Hoàn Thành!

Tính năng đã sẵn sàng sử dụng. Chúc bạn làm việc hiệu quả! 🚀

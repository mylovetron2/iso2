# Tài liệu Chức năng In Phiếu SC (Sửa Chữa)

## Mục lục
1. [Tổng quan](#tổng-quan)
2. [Luồng xử lý](#luồng-xử-lý)
3. [Cấu trúc Database](#cấu-trúc-database)
4. [Giao diện và Form](#giao-diện-và-form)
5. [Code Implementation](#code-implementation)
6. [Cấu trúc File Output](#cấu-trúc-file-output)
7. [Hướng dẫn Tái sử dụng](#hướng-dẫn-tái-sử-dụng)

---

## Tổng quan

### Mục đích
Chức năng "In phiếu SC" cho phép xuất phiếu thực hiện công việc sửa chữa (SC), bảo dưỡng (BD), hoặc kiểm tra (KT) thiết bị dưới dạng file HTML định dạng Microsoft Word để in ấn.

### Vị trí trong hệ thống
- **File chính**: `formsc.php`
- **Dòng code**: 7040-9300+
- **Kích hoạt**: Khi người dùng click nút "In SC" (hình ảnh `upload/Insc.jpg`)

### Thông tin xuất ra
- Thông tin thiết bị (tên máy, số máy, model, họ máy)
- Ngày bắt đầu và kết thúc công việc
- Danh sách người thực hiện và số giờ công
- Thiết bị/phần mềm phụ trợ sử dụng
- Loại công việc (KT/BD/SC)
- Nội dung kiểm tra, hỏng hóc, cách khắc phục, kết luận

---

## Luồng xử lý

### 1. Người dùng click nút "In phiếu SC"
```
Người dùng -> Click button "savefilesc" -> Submit form với POST data
```

### 2. Server nhận request
```php
if($savefilesc=="savesc") {
    // Xử lý in phiếu
}
```

### 3. Lấy dữ liệu từ Database
- Truy vấn bảng `hososcbd_iso` để lấy thông tin hồ sơ
- Truy vấn bảng `ngthuchien_iso` để lấy danh sách người thực hiện

### 4. Xử lý và format dữ liệu
- Chuyển đổi định dạng ngày từ database
- Parse ngày tháng từ format YYYY-MM-DD hoặc DD/MM/YYYY
- Chuẩn bị dữ liệu thiết bị phụ trợ

### 5. Xuất HTML
- Generate HTML với CSS tương thích Microsoft Word
- Sử dụng namespace Office XML
- Format theo chuẩn `.doc` để có thể mở bằng MS Word

### 6. Hiển thị cho người dùng
- Trình duyệt render HTML
- Người dùng có thể Ctrl+P để in hoặc Save as .doc

---

## Cấu trúc Database

### Bảng 1: `hososcbd_iso`
Lưu thông tin hồ sơ sửa chữa/bảo dưỡng

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `hoso` | VARCHAR | Số hồ sơ (Primary Key) |
| `maql` | VARCHAR | Mã quản lý |
| `mavt` | VARCHAR | Mã vật tư/thiết bị |
| `somay` | VARCHAR | Số máy |
| `model` | VARCHAR | Model thiết bị |
| `ngayth` | DATE | Ngày thực hiện |
| `ngaykt` | DATE | Ngày kết thúc |
| `cv` | VARCHAR | Công việc (KT/BD/SC) |
| `noidung` | TEXT | Nội dung kiểm tra |
| `honghoc` | TEXT | Hỏng hóc |
| `khacphuc` | TEXT | Cách khắc phục |
| `ketluan` | TEXT | Kết luận |
| `ttktbefore` | TEXT | Tình trạng kỹ thuật trước |
| `ttktafter` | TEXT | Tình trạng kỹ thuật sau |
| `ghichu` | VARCHAR | Ghi chú |
| `ghichufinal` | TEXT | Ghi chú cuối cùng |
| `tbdosc` | VARCHAR | Thiết bị đo/SC 1 |
| `serialtbdosc` | VARCHAR | Serial thiết bị 1 |
| `tbdosc1` | VARCHAR | Thiết bị đo/SC 2 |
| `serialtbdosc1` | VARCHAR | Serial thiết bị 2 |
| `tbdosc2` | VARCHAR | Thiết bị đo/SC 3 |
| `serialtbdosc2` | VARCHAR | Serial thiết bị 3 |
| `tbdosc3` | VARCHAR | Thiết bị đo/SC 4 |
| `serialtbdosc3` | VARCHAR | Serial thiết bị 4 |
| `tbdosc4` | VARCHAR | Thiết bị đo/SC 5 |
| `serialtbdosc4` | VARCHAR | Serial thiết bị 5 |

### Bảng 2: `ngthuchien_iso`
Lưu danh sách người thực hiện công việc

| Trường | Kiểu | Mô tả |
|--------|------|-------|
| `id` | INT | ID (Auto increment) |
| `mahoso` | VARCHAR | Mã hồ sơ (Foreign Key) |
| `hoten` | VARCHAR | Họ tên người thực hiện |
| `giolv` | FLOAT | Số giờ làm việc |
| `stt` | INT | Số thứ tự |

### Quan hệ giữa các bảng
```
hososcbd_iso (1) --- (n) ngthuchien_iso
    hoso           =      mahoso
```

---

## Giao diện và Form

### HTML Form Structure
```html
<form action="formsc.php" method="post">
    <!-- Hidden fields -->
    <input type="hidden" name="savefilesc" value="">
    <input type="hidden" name="hosomay" value="<?php echo $hosomay; ?>">
    <input type="hidden" name="maquanly" value="<?php echo $maql; ?>">
    <input type="hidden" name="username" value="<?php echo $username; ?>">
    <input type="hidden" name="password" value="<?php echo $password; ?>">
    
    <!-- Print button -->
    <input type="image" 
           name="savefilesc" 
           value="savesc" 
           src="upload/Insc.jpg" 
           alt="In phiếu SC" 
           onclick="this.form.savefilesc.value = this.value"/>
</form>
```

### Parameters được truyền
- `savefilesc`: Giá trị "savesc" khi click button
- `hosomay`: Số hồ sơ cần in
- `maquanly`: Mã quản lý
- `username`, `password`: Thông tin đăng nhập

---

## Code Implementation

### 1. Khởi tạo và kiểm tra điều kiện

```php
// Nhận POST data
$savefilesc = isset($_POST['savefilesc']) ? $_POST['savefilesc'] : '';
$hosomay = isset($_POST['hosomay']) ? $_POST['hosomay'] : '';
$maquanly = isset($_POST['maquanly']) ? $_POST['maquanly'] : '';

// Kiểm tra điều kiện
if($savefilesc == "savesc") {
    // Xử lý in phiếu
}
```

### 2. Khởi tạo mảng dữ liệu

```php
// Reset mảng thiết bị phụ trợ
for($i=1; $i<6; $i++) {
    $tbdosc[$i] = "";
    $serialtbdosc[$i] = "";
}

// Reset mảng người thực hiện
for($i=1; $i<9; $i++) {
    $hoten[$i] = "";
    $giolv[$i] = "";
}
```

### 3. Lấy dữ liệu từ Database

```php
// Lấy thông tin hồ sơ
$sql = "SELECT * FROM hososcbd_iso 
        WHERE maql='$maquanly' AND hoso='$hosomay'";
$result = mysqli_query($link, $sql);

while($row = mysqli_fetch_array($result)) {
    $ngayth = $row['ngayth'];
    $ngaykt = $row['ngaykt'];
    $ttktbefore = $row['ttktbefore'];
    $khacphuc = $row['khacphuc'];
    $ghichu = $row['ghichu'];
    $honghoc = $row['honghoc'];
    $ttktafter = $row['ttktafter'];
    
    // Thiết bị phụ trợ (1-5)
    $tbdosc[1] = $row['tbdosc'];
    $serialtbdosc[1] = $row['serialtbdosc'];
    $tbdosc[2] = $row['tbdosc1'];
    
    $serialtbdosc[2] = $row['serialtbdosc1'];
    // ... tương tự cho tbdosc3, tbdosc4, tbdosc5
    
    $somay = $row['somay'];
    $mavtu = $row['mavt'];
    $model = $row['model'];
    $cv = $row['cv'];
    $noidung = $row['noidung'];
    $ketluan = $row['ketluan'];
    $ghichufinal = $row['ghichufinal'];
}

// Lấy danh sách người thực hiện
$sql2 = "SELECT * FROM ngthuchien_iso WHERE mahoso='$hosomay'";
$result2 = mysqli_query($link, $sql2);

$k = 1;
while($row = mysqli_fetch_array($result2)) {
    $hoten[$k] = $row['hoten'];
    $giolv[$k] = $row['giolv'];
    $k++;
}
```

### 4. Xử lý ngày tháng

```php
function parseDate($dateString) {
    $datetype = 0; // 0 = DD/MM/YYYY, 1 = YYYY-MM-DD
    
    // Detect format
    if (strpos($dateString, '/') !== false) {
        $datetype = 0;
    } elseif (strpos($dateString, '-') !== false) {
        $datetype = 1;
    }
    
    if ($datetype == 0) {
        // Format DD/MM/YYYY
        $parts = explode('/', $dateString);
        $day = $parts[0];
        $month = $parts[1];
        $year = $parts[2];
    } else {
        // Format YYYY-MM-DD
        $parts = explode('-', $dateString);
        $year = $parts[0];
        $month = $parts[1];
        $day = $parts[2];
    }
    
    return array(
        'day' => $day,
        'month' => $month,
        'year' => $year
    );
}

// Parse ngày bắt đầu
$dateStart = parseDate($ngayth);
$ngays = $dateStart['day'];
$thangs = $dateStart['month'];
$nams = $dateStart['year'];

// Parse ngày kết thúc
$dateEnd = parseDate($ngaykt);
$ngayt = $dateEnd['day'];
$thangt = $dateEnd['month'];
$namt = $dateEnd['year'];
```

### 5. Generate HTML Output

```php
echo "
<html xmlns:v=\"urn:schemas-microsoft-com:vml\"
xmlns:o=\"urn:schemas-microsoft-com:office:office\"
xmlns:w=\"urn:schemas-microsoft-com:office:word\"
xmlns:m=\"http://schemas.microsoft.com/office/2004/12/omml\"
xmlns=\"http://www.w3.org/TR/REC-html40\">

<head>
<meta http-equiv=Content-Type content=\"text/html; charset=windows-1252\">
<meta name=ProgId content=Word.Document>
<meta name=Generator content=\"Microsoft Word 12\">
<title>PHIẾU THỰC HIỆN CÔNG VIỆC</title>

<!--[if gte mso 9]><xml>
 <o:DocumentProperties>
  <o:Author>System</o:Author>
  <o:Created>".date('Y-m-d\TH:i:s\Z')."</o:Created>
  <o:Pages>1</o:Pages>
 </o:DocumentProperties>
</xml><![endif]-->

<style>
/* CSS cho Microsoft Word */
@font-face {
    font-family: 'Times New Roman';
}

body {
    font-family: 'Times New Roman', serif;
    font-size: 12pt;
}

table.MsoNormalTable {
    border-collapse: collapse;
    border: solid windowtext 1.0pt;
}

table.MsoNormalTable td {
    border: solid windowtext 1.0pt;
    padding: 0in 5.4pt 0in 5.4pt;
}

p.MsoNormal {
    margin: 0in;
    margin-bottom: .0001pt;
    font-size: 12.0pt;
    font-family: 'Times New Roman', serif;
}
</style>
</head>

<body>
<div class=Section1>
";
```

### 6. Nội dung phiếu - Header

```php
echo "
<p class=MsoNormal style='line-height:115%'>
    <span style='font-size:12.0pt'>
        &nbsp;&nbsp;&nbsp;&nbsp;XN Địa vật lý GK
        <span style='mso-tab-count:2'>&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <b>PHIẾU THỰC HIỆN CÔNG VIỆC</b>
    </span>
</p>

<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        &nbsp;&nbsp;&nbsp;Xưởng SCTBĐVL
        <span style='mso-tab-count:3'>&nbsp;&nbsp;&nbsp;&nbsp;</span>
        Ngày bắt đầu: &nbsp;$ngays/$thangs/$nams
    </span>
</p>

<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Số hồ sơ: $hosomay
        <span style='mso-tab-count:2'>&nbsp;&nbsp;&nbsp;&nbsp;</span>
        Ngày kết thúc: $ngayt/$thangt/$namt
    </span>
</p>
";
```

### 7. Thông tin thiết bị

```php
echo "
<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        1. Tên thiết bị: $mavtu-$model
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        Số máy: &nbsp;&nbsp;&nbsp;&nbsp;$somay
    </span>
</p>
";
```

### 8. Bảng người thực hiện

```php
echo "
<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        2. Người tham gia thực hiện công việc:
    </span>
</p>

<table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0>
    <tr>
        <td style='width:173.15pt'>
            <p class=MsoNormal align=center>Họ và tên</p>
        </td>
        <td style='width:85.05pt'>
            <p class=MsoNormal align=center>Số giờ tham gia</p>
        </td>
        <td style='width:173.15pt'>
            <p class=MsoNormal align=center>Họ và tên</p>
        </td>
        <td style='width:89.05pt'>
            <p class=MsoNormal align=center>Số giờ tham gia</p>
        </td>
    </tr>
";

// Xuất 4 dòng, mỗi dòng 2 người
for($i=1; $i<=4; $i++) {
    $index1 = ($i-1) * 2 + 1;
    $index2 = ($i-1) * 2 + 2;
    
    echo "
    <tr>
        <td><p class=MsoNormal>$index1.$hoten[$index1]</p></td>
        <td><p class=MsoNormal>$giolv[$index1]</p></td>
        <td><p class=MsoNormal>$index2.$hoten[$index2]</p></td>
        <td><p class=MsoNormal>$giolv[$index2]</p></td>
    </tr>
    ";
}

echo "</table>";
```

### 9. Bảng thiết bị phụ trợ

```php
echo "
<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        3. Các thiết bị và phần mềm phụ trợ:
    </span>
</p>

<table class=MsoNormalTable border=1 cellspacing=0 cellpadding=0>
    <tr>
        <td style='width:40.0pt'>
            <p class=MsoNormal align=center>STT</p>
        </td>
        <td style='width:239.0pt'>
            <p class=MsoNormal align=center>Tên viết tắt</p>
        </td>
        <td style='width:230.95pt'>
            <p class=MsoNormal align=center>Số serial</p>
        </td>
    </tr>
";

// Xuất tối đa 5 thiết bị
for($i=1; $i<6; $i++) {
    if($tbdosc[$i] != "") {
        echo "
        <tr>
            <td><p class=MsoNormal>$i</p></td>
            <td><p class=MsoNormal>$tbdosc[$i]</p></td>
            <td><p class=MsoNormal>$serialtbdosc[$i]</p></td>
        </tr>
        ";
    }
}

echo "</table>";
```

### 10. Loại công việc (Checkbox)

```php
echo "
<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        4. Nội dung công việc: 
";

// Hiển thị checkbox tương ứng với loại công việc
if($cv == "KT") {
    echo "KT <input type='checkbox' checked> &nbsp;&nbsp;
          BD <input type='checkbox'> &nbsp;&nbsp;
          SC <input type='checkbox'>";
} elseif($cv == "BD") {
    echo "KT <input type='checkbox'> &nbsp;&nbsp;
          BD <input type='checkbox' checked> &nbsp;&nbsp;
          SC <input type='checkbox'>";
} elseif($cv == "SC") {
    echo "KT <input type='checkbox'> &nbsp;&nbsp;
          BD <input type='checkbox'> &nbsp;&nbsp;
          SC <input type='checkbox' checked>";
} else {
    echo "KT <input type='checkbox'> &nbsp;&nbsp;
          BD <input type='checkbox'> &nbsp;&nbsp;
          SC <input type='checkbox'>";
}

echo "
    </span>
</p>
";
```

### 11. Nội dung chi tiết

```php
echo "
<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        5. Nội dung:<br>
        $noidung
    </span>
</p>

<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        6. Hiện tượng hỏng hóc:<br>
        $honghoc
    </span>
</p>

<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        7. Cách khắc phục:<br>
        $khacphuc
    </span>
</p>

<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        8. Kết luận:<br>
        $ketluan
    </span>
</p>

<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        9. Tình trạng kỹ thuật trước SC/BD:<br>
        $ttktbefore
    </span>
</p>

<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        10. Tình trạng kỹ thuật sau SC/BD:<br>
        $ttktafter
    </span>
</p>

<p class=MsoNormal>
    <span style='font-size:12.0pt'>
        11. Ghi chú:<br>
        $ghichufinal
    </span>
</p>
";
```

### 12. Đóng HTML

```php
echo "
</div>
</body>
</html>
";
```

---

## Cấu trúc File Output

### Định dạng
- **MIME Type**: `text/html` với charset `windows-1252`
- **Namespace**: Office XML (VML, Office, Word)
- **Tương thích**: Microsoft Word 2007+

### Đặc điểm
1. **XML Metadata**: Chứa thông tin document properties
2. **CSS Inline**: Style được nhúng trực tiếp trong HTML
3. **Font**: Sử dụng Times New Roman là font chính
4. **Border**: Bảng có border kiểu windowtext
5. **Encoding**: windows-1252 để tương thích MS Word

### Cách mở file
- **Trình duyệt**: Mở trực tiếp, có thể in (Ctrl+P)
- **Microsoft Word**: File->Open, chọn file HTML
- **Save as**: Có thể save thành .doc hoặc .docx

---

## Hướng dẫn Tái sử dụng

### Bước 1: Chuẩn bị Database

```sql
-- Tạo bảng hososcbd_iso
CREATE TABLE hososcbd_iso (
    hoso VARCHAR(50) PRIMARY KEY,
    maql VARCHAR(50),
    mavt VARCHAR(100),
    somay VARCHAR(50),
    model VARCHAR(50),
    ngayth DATE,
    ngaykt DATE,
    cv VARCHAR(10),
    noidung TEXT,
    honghoc TEXT,
    khacphuc TEXT,
    ketluan TEXT,
    ttktbefore TEXT,
    ttktafter TEXT,
    ghichu VARCHAR(255),
    ghichufinal TEXT,
    tbdosc VARCHAR(100),
    serialtbdosc VARCHAR(100),
    tbdosc1 VARCHAR(100),
    serialtbdosc1 VARCHAR(100),
    tbdosc2 VARCHAR(100),
    serialtbdosc2 VARCHAR(100),
    tbdosc3 VARCHAR(100),
    serialtbdosc3 VARCHAR(100),
    tbdosc4 VARCHAR(100),
    serialtbdosc4 VARCHAR(100),
    INDEX idx_maql (maql),
    INDEX idx_ngayth (ngayth)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tạo bảng ngthuchien_iso
CREATE TABLE ngthuchien_iso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mahoso VARCHAR(50),
    hoten VARCHAR(100),
    giolv FLOAT,
    stt INT,
    FOREIGN KEY (mahoso) REFERENCES hososcbd_iso(hoso) ON DELETE CASCADE,
    INDEX idx_mahoso (mahoso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Bước 2: Tạo Form HTML

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>In phiếu SC</title>
</head>
<body>
    <h2>In Phiếu Thực Hiện Công Việc</h2>
    
    <form action="print_phieu.php" method="post">
        <!-- Chọn hồ sơ -->
        <label>Chọn hồ sơ:</label>
        <select name="hosomay" required>
            <?php
            // Load danh sách hồ sơ từ DB
            $sql = "SELECT hoso, mavt, somay FROM hososcbd_iso ORDER BY hoso DESC";
            $result = mysqli_query($link, $sql);
            while($row = mysqli_fetch_array($result)) {
                echo "<option value='{$row['hoso']}'>";
                echo "{$row['hoso']} - {$row['mavt']} - {$row['somay']}";
                echo "</option>";
            }
            ?>
        </select>
        <br><br>
        
        <!-- Hidden fields -->
        <input type="hidden" name="savefilesc" value="savesc">
        <input type="hidden" name="username" value="<?php echo $_SESSION['username']; ?>">
        
        <!-- Submit button -->
        <button type="submit">
            <img src="upload/Insc.jpg" alt="In phiếu">
            In Phiếu SC
        </button>
    </form>
</body>
</html>
```

### Bước 3: Tạo File Xử Lý (print_phieu.php)

```php
<?php
// Kết nối database
include("connect_db.php");

// Nhận parameters
$savefilesc = isset($_POST['savefilesc']) ? $_POST['savefilesc'] : '';
$hosomay = isset($_POST['hosomay']) ? $_POST['hosomay'] : '';

if($savefilesc == "savesc" && !empty($hosomay)) {
    
    // 1. Lấy dữ liệu từ database
    $sql = "SELECT * FROM hososcbd_iso WHERE hoso = ?";
    $stmt = mysqli_prepare($link, $sql);
    mysqli_stmt_bind_param($stmt, "s", $hosomay);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($row = mysqli_fetch_array($result)) {
        // Extract data
        $ngayth = $row['ngayth'];
        $ngaykt = $row['ngaykt'];
        $mavt = $row['mavt'];
        $somay = $row['somay'];
        $model = $row['model'];
        $cv = $row['cv'];
        $noidung = $row['noidung'];
        $honghoc = $row['honghoc'];
        $khacphuc = $row['khacphuc'];
        $ketluan = $row['ketluan'];
        $ttktbefore = $row['ttktbefore'];
        $ttktafter = $row['ttktafter'];
        $ghichufinal = $row['ghichufinal'];
        
        // Thiết bị phụ trợ
        $tbdosc = array(
            1 => $row['tbdosc'],
            2 => $row['tbdosc1'],
            3 => $row['tbdosc2'],
            4 => $row['tbdosc3'],
            5 => $row['tbdosc4']
        );
        $serialtbdosc = array(
            1 => $row['serialtbdosc'],
            2 => $row['serialtbdosc1'],
            3 => $row['serialtbdosc2'],
            4 => $row['serialtbdosc3'],
            5 => $row['serialtbdosc4']
        );
    }
    
    // 2. Lấy người thực hiện
    $sql2 = "SELECT * FROM ngthuchien_iso WHERE mahoso = ? ORDER BY stt";
    $stmt2 = mysqli_prepare($link, $sql2);
    mysqli_stmt_bind_param($stmt2, "s", $hosomay);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    
    $hoten = array();
    $giolv = array();
    $i = 1;
    while($row = mysqli_fetch_array($result2)) {
        $hoten[$i] = $row['hoten'];
        $giolv[$i] = $row['giolv'];
        $i++;
    }
    
    // 3. Parse ngày tháng
    $dateStart = date_parse_from_format('Y-m-d', $ngayth);
    $ngays = $dateStart['day'];
    $thangs = $dateStart['month'];
    $nams = $dateStart['year'];
    
    $dateEnd = date_parse_from_format('Y-m-d', $ngaykt);
    $ngayt = $dateEnd['day'];
    $thangt = $dateEnd['month'];
    $namt = $dateEnd['year'];
    
    // 4. Include template
    include("template_phieu_sc.php");
    
} else {
    echo "Lỗi: Thiếu thông tin hồ sơ!";
}
?>
```

### Bước 4: Tạo Template File (template_phieu_sc.php)

```php
<!DOCTYPE html>
<html xmlns:v="urn:schemas-microsoft-com:vml"
xmlns:o="urn:schemas-microsoft-com:office:office"
xmlns:w="urn:schemas-microsoft-com:office:word"
xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252">
<meta name="ProgId" content="Word.Document">
<title>PHIẾU THỰC HIỆN CÔNG VIỆC</title>
<style>
body {
    font-family: 'Times New Roman', serif;
    font-size: 12pt;
}
table {
    border-collapse: collapse;
    width: 100%;
    margin: 10px 0;
}
table, td, th {
    border: 1px solid black;
}
td, th {
    padding: 5px;
}
.header {
    text-align: center;
    font-weight: bold;
    font-size: 14pt;
}
.section {
    margin: 10px 0;
}
</style>
</head>
<body>

<div class="header">
    XN Địa vật lý GK<br>
    PHIẾU THỰC HIỆN CÔNG VIỆC
</div>

<div class="section">
    Xưởng SCTBĐVL &nbsp;&nbsp;&nbsp;&nbsp; Ngày bắt đầu: <?php echo "$ngays/$thangs/$nams"; ?>
</div>

<div class="section">
    Số hồ sơ: <?php echo $hosomay; ?> &nbsp;&nbsp;&nbsp;&nbsp; Ngày kết thúc: <?php echo "$ngayt/$thangt/$namt"; ?>
</div>

<div class="section">
    <strong>1. Tên thiết bị:</strong> <?php echo "$mavt-$model"; ?> 
    &nbsp;&nbsp;&nbsp;&nbsp; 
    <strong>Số máy:</strong> <?php echo $somay; ?>
</div>

<div class="section">
    <strong>2. Người tham gia thực hiện công việc:</strong>
    <table>
        <tr>
            <th>Họ và tên</th>
            <th>Số giờ</th>
            <th>Họ và tên</th>
            <th>Số giờ</th>
        </tr>
        <?php for($i=1; $i<=4; $i++): 
            $idx1 = ($i-1)*2 + 1;
            $idx2 = ($i-1)*2 + 2;
        ?>
        <tr>
            <td><?php echo $idx1.". ".($hoten[$idx1] ?? ''); ?></td>
            <td><?php echo $giolv[$idx1] ?? ''; ?></td>
            <td><?php echo $idx2.". ".($hoten[$idx2] ?? ''); ?></td>
            <td><?php echo $giolv[$idx2] ?? ''; ?></td>
        </tr>
        <?php endfor; ?>
    </table>
</div>

<div class="section">
    <strong>3. Các thiết bị và phần mềm phụ trợ:</strong>
    <table>
        <tr>
            <th width="50">STT</th>
            <th>Tên viết tắt</th>
            <th>Số serial</th>
        </tr>
        <?php for($i=1; $i<=5; $i++): 
            if(!empty($tbdosc[$i])):
        ?>
        <tr>
            <td><?php echo $i; ?></td>
            <td><?php echo $tbdosc[$i]; ?></td>
            <td><?php echo $serialtbdosc[$i]; ?></td>
        </tr>
        <?php 
            endif;
        endfor; 
        ?>
    </table>
</div>

<div class="section">
    <strong>4. Nội dung công việc:</strong>
    KT <input type="checkbox" <?php echo ($cv=='KT')?'checked':''; ?>>
    BD <input type="checkbox" <?php echo ($cv=='BD')?'checked':''; ?>>
    SC <input type="checkbox" <?php echo ($cv=='SC')?'checked':''; ?>>
</div>

<div class="section">
    <strong>5. Nội dung:</strong><br>
    <?php echo nl2br($noidung); ?>
</div>

<div class="section">
    <strong>6. Hiện tượng hỏng hóc:</strong><br>
    <?php echo nl2br($honghoc); ?>
</div>

<div class="section">
    <strong>7. Cách khắc phục:</strong><br>
    <?php echo nl2br($khacphuc); ?>
</div>

<div class="section">
    <strong>8. Kết luận:</strong><br>
    <?php echo nl2br($ketluan); ?>
</div>

<div class="section">
    <strong>9. Tình trạng kỹ thuật trước SC/BD:</strong><br>
    <?php echo nl2br($ttktbefore); ?>
</div>

<div class="section">
    <strong>10. Tình trạng kỹ thuật sau SC/BD:</strong><br>
    <?php echo nl2br($ttktafter); ?>
</div>

<div class="section">
    <strong>11. Ghi chú:</strong><br>
    <?php echo nl2br($ghichufinal); ?>
</div>

</body>
</html>
```

### Bước 5: Test và Sử Dụng

```php
// Test script
<?php
// 1. Kết nối DB
include("connect_db.php");

// 2. Insert test data
$sql = "INSERT INTO hososcbd_iso 
        (hoso, mavt, somay, model, ngayth, ngaykt, cv, noidung) 
        VALUES ('TEST001', 'MAY-001', 'SM-001', 'MODEL-X', 
                '2026-03-10', '2026-03-12', 'SC', 'Test nội dung')";
mysqli_query($link, $sql);

// 3. Insert người thực hiện
$sql2 = "INSERT INTO ngthuchien_iso 
         (mahoso, hoten, giolv, stt) 
         VALUES 
         ('TEST001', 'Nguyễn Văn A', 8, 1),
         ('TEST001', 'Trần Văn B', 6, 2)";
mysqli_query($link, $sql2);

// 4. Test in phiếu
echo "<form method='post' action='print_phieu.php'>
        <input type='hidden' name='savefilesc' value='savesc'>
        <input type='hidden' name='hosomay' value='TEST001'>
        <button type='submit'>Test In Phiếu</button>
      </form>";
?>
```

---

## Best Practices

### 1. Security
- **SQL Injection**: Sử dụng Prepared Statements
- **XSS**: Escape output với `htmlspecialchars()`
- **Authentication**: Kiểm tra quyền trước khi in

```php
// Good
$stmt = mysqli_prepare($link, "SELECT * FROM hososcbd_iso WHERE hoso = ?");
mysqli_stmt_bind_param($stmt, "s", $hosomay);
mysqli_stmt_execute($stmt);

// Output escaping
echo htmlspecialchars($noidung, ENT_QUOTES, 'UTF-8');
```

### 2. Performance
- **Index**: Tạo index cho các trường tìm kiếm thường xuyên
- **Caching**: Cache template HTML
- **Pagination**: Giới hạn số bản ghi khi load danh sách

### 3. Maintainability
- **Separation of Concerns**: Tách logic và presentation
- **Template Engine**: Sử dụng template engine (Twig, Blade)
- **Error Handling**: Log errors, hiển thị thông báo thân thiện

### 4. Compatibility
- **Browser**: Test trên Chrome, Firefox, Edge
- **MS Word**: Test với Word 2007, 2010, 2013, 2016+
- **Encoding**: Đảm bảo charset phù hợp

---

## Troubleshooting

### Lỗi thường gặp

#### 1. "Warning: mysqli_fetch_array() expects parameter 1 to be mysqli_result"
**Nguyên nhân**: Query lỗi hoặc không có kết quả

**Giải pháp**:
```php
$result = mysqli_query($link, $sql);
if(!$result) {
    die("Query error: " . mysqli_error($link));
}
```

#### 2. Font không hiển thị đúng
**Nguyên nhân**: Thiếu font hoặc encoding sai

**Giải pháp**:
```php
header('Content-Type: text/html; charset=windows-1252');
```

#### 3. Bảng không có border khi mở bằng Word
**Nguyên nhân**: CSS không tương thích

**Giải pháp**: Sử dụng inline style và border attributes
```html
<table border="1" style="border-collapse:collapse">
```

#### 4. Ngày tháng hiển thị sai format
**Nguyên nhân**: Database lưu format khác

**Giải pháp**:
```php
// Convert từ YYYY-MM-DD sang DD/MM/YYYY
$date = date('d/m/Y', strtotime($ngayth));
```

---

## Extensions và Cải tiến

### 1. Xuất PDF
```php
// Sử dụng DOMPDF
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->loadHtml($html_content);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("phieu_sc_$hosomay.pdf");
```

### 2. Gửi Email
```php
// Sử dụng PHPMailer
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$mail = new PHPMailer();
$mail->setFrom('system@company.com');
$mail->addAddress('user@example.com');
$mail->Subject = "Phiếu SC - $hosomay";
$mail->Body = $html_content;
$mail->isHTML(true);
$mail->send();
```

### 3. Lưu lại lịch sử in
```sql
CREATE TABLE print_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hoso VARCHAR(50),
    username VARCHAR(50),
    print_date DATETIME,
    ip_address VARCHAR(50)
);
```

```php
$sql = "INSERT INTO print_history (hoso, username, print_date, ip_address) 
        VALUES (?, ?, NOW(), ?)";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "sss", $hosomay, $username, $_SERVER['REMOTE_ADDR']);
mysqli_stmt_execute($stmt);
```

### 4. Thêm chữ ký điện tử
```html
<div style="margin-top: 50px">
    <table width="100%" border="0">
        <tr>
            <td width="50%" align="center">
                <strong>Người thực hiện</strong><br><br><br>
                (Ký, ghi rõ họ tên)
            </td>
            <td width="50%" align="center">
                <strong>Trưởng phòng</strong><br><br><br>
                (Ký, ghi rõ họ tên)
            </td>
        </tr>
    </table>
</div>
```

### 5. Template động
```php
// Cho phép customize template theo từng đơn vị
$template_file = "templates/phieu_sc_{$department}.php";
if(file_exists($template_file)) {
    include($template_file);
} else {
    include("templates/phieu_sc_default.php");
}
```

---

## Tài liệu tham khảo

1. **Microsoft Office XML**: https://docs.microsoft.com/en-us/office/
2. **PHP MySQLi**: https://www.php.net/manual/en/book.mysqli.php
3. **HTML to Word**: https://docs.microsoft.com/en-us/office/vba/word/
4. **DOMPDF**: https://github.com/dompdf/dompdf
5. **PHPMailer**: https://github.com/PHPMailer/PHPMailer

---

## License và Bản quyền

Tài liệu này được tạo ra cho mục đích học tập và tham khảo. 
Code có thể được sử dụng tự do với điều kiện ghi rõ nguồn.

---

**Phiên bản**: 1.0  
**Ngày cập nhật**: 13/03/2026  
**Tác giả**: ISO System Development Team  
**Liên hệ**: Xưởng SCTBĐVL - XN Địa vật lý GK

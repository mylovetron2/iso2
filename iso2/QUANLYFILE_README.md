# QUAN LY FILE - TAI LIEU CHI TIET DE TAI SU DUNG

## 1. Muc tieu
Tai lieu nay mo ta day du phan Quan ly file dang co trong project `iso2` de co the sao chep sang project khac mot cach co he thong.

Pham vi gom 2 lop:
- Lop 1: Menu "Quan ly file" tren thanh dieu huong (mo he thong quan ly file ben ngoai).
- Lop 2: Quan ly file dinh kem noi bo trong module `thietbihotro` (upload, luu, hien thi, mo file).

---

## 2. Kien truc hien tai trong project

### 2.1. Menu Quan ly file (external)
- Vi tri: `views/layouts/header.php`
- Dieu kien hien thi: user da dang nhap (`isLoggedIn()`).
- Hanh vi: mo tab moi den URL ben ngoai:
  - `https://diavatly.cloud/gdrive-manager`

Muc dich: tach rieng he thong quan ly file tong (Google Drive manager) khoi ung dung chinh.

### 2.2. Upload file noi bo (module Thiet Bi Ho Tro)
- Entry route: `thietbihotro.php`
- Controller: `controllers/ThietBiHoTroController.php`
- Model: `models/ThietBiHoTro.php`
- Views:
  - `views/thietbihotro/create.php`
  - `views/thietbihotro/edit.php`
  - `views/thietbihotro/view.php`
  - `views/thietbihotro/index.php`

Muc dich: luu file dinh kem theo tung thiet bi (ho so ky thuat + tai lieu ky thuat).

---

## 3. Luong nghiep vu chi tiet

### 3.1. Luong upload file
1. User vao form Tao/Sua thiet bi (`create`, `edit`).
2. User chon file cho 2 truong:
   - `hosomay`
   - `tlkt`
3. Form submit `multipart/form-data`.
4. Controller goi:
   - `handleFileUpload('hosomay', 'hosomay')`
   - `handleFileUpload('tlkt', 'tlkt')`
5. Ham upload thuc hien validate va luu file vao:
   - `uploads/hosomay/`
   - `uploads/tlkt/`
6. Database chi luu ten file (`filename`) trong cot tuong ung.

### 3.2. Luong sua ban ghi
- Neu edit ma khong upload file moi:
  - giu lai ten file cu (`?: $device['hosomay']`, `?: $device['tlkt']`).
- Neu upload file moi:
  - cap nhat ten file moi trong DB.

### 3.3. Luong xem/mo file
- View `edit.php` va `view.php` render link truc tiep:
  - `/iso2/uploads/hosomay/<filename>`
  - `/iso2/uploads/tlkt/<filename>`
- User click de mo file tab moi.

---

## 4. Rule xu ly file dang ap dung

Trong `ThietBiHoTroController::handleFileUpload()`:

### 4.1. Dieu kien nhan file
- Co file trong `$_FILES[fieldName]`.
- Khong phai `UPLOAD_ERR_NO_FILE`.
- Trang thai upload phai `UPLOAD_ERR_OK`.

### 4.2. Gioi han kich thuoc
- Toi da `5MB`:
  - `5 * 1024 * 1024`

### 4.3. Kiem tra MIME type
- Dung `finfo_file()` de lay MIME that.
- Danh sach cho phep:
  - `application/pdf`
  - `application/msword`
  - `application/vnd.openxmlformats-officedocument.wordprocessingml.document`
  - `application/vnd.ms-excel`
  - `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
  - `image/jpeg`
  - `image/jpg`
  - `image/png`

### 4.4. Dat ten file
- Cong thuc hien tai:
  - `uniqid() . '_' . time() . '.' . extension`
- Muc tieu:
  - tranh trung ten
  - khong dung truc tiep ten file goc

### 4.5. Luu file
- Tao folder neu chua co (`mkdir(..., 0755, true)`).
- Doi cho file tu temp sang dich den (`move_uploaded_file`).
- Neu that bai => tra ve chuoi rong, khong crash request.

---

## 5. Cac file quan trong va vai tro

- `views/layouts/header.php`
  - Khai bao menu "Quan ly file" (external link).
- `thietbihotro.php`
  - Router action (`index/create/edit/delete/view/exportpdf`).
- `controllers/ThietBiHoTroController.php`
  - Toan bo logic upload + validate + create/update.
- `models/ThietBiHoTro.php`
  - Query danh sach, thong ke, filter, phan trang.
- `views/thietbihotro/create.php`
  - Form upload file khi tao moi.
- `views/thietbihotro/edit.php`
  - Form upload file khi cap nhat + link file hien tai.
- `views/thietbihotro/view.php`
  - Trang chi tiet + link mo file.

---

## 6. Permission va bao mat

### 6.1. Permission hien tai
Module `thietbihotro` dang dung bo permission chung cua project:
- `project.view`
- `project.create`
- `project.edit`
- `project.delete`

Kiem tra tai:
- `config/constants.php`
- `includes/permissions.php`
- `controllers/ThietBiHoTroController.php`

### 6.2. Bao mat dang co
- Co auth gate o route (`requireAuth()`).
- Co permission gate theo action (`requirePermission(...)`).
- Validate MIME + size truoc khi luu.
- Khong luu ten file goc truc tiep.

### 6.3. Rủi ro can luu y khi nhan ban
- Link file dang public theo path tinh (`/uploads/...`), chua co signed URL.
- Chua co co che xoa file vat ly khi ban ghi bi xoa/doi file.
- Chua normalize extension ve lowercase truoc khi luu.

---

## 7. Checklist nhan ban sang project khac

## 7.1. Buoc A - Chuan bi cau truc
1. Tao router cho module file attachment.
2. Tao controller co ham upload dung chung.
3. Tao 2 thu muc luu file:
   - `uploads/hosomay`
   - `uploads/tlkt`
4. Cap quyen ghi cho web server vao thu muc `uploads`.

## 7.2. Buoc B - Chuan bi DB
1. Dam bao bang nghiep vu co 2 cot luu ten file:
   - `hosomay` (varchar)
   - `tlkt` (varchar)
2. Neu module moi, tao migration bo sung 2 cot tren.
3. Chi luu `filename`, khong luu full URL.

## 7.3. Buoc C - Form va UX
1. Form phai co `enctype="multipart/form-data"`.
2. Input file:
   - `name="hosomay"`
   - `name="tlkt"`
3. `accept` o frontend chi de goi y, KHONG thay the backend validation.
4. O trang edit/view: hien link file neu ton tai.

## 7.4. Buoc D - Backend upload
1. Kiem tra upload error.
2. Kiem tra size.
3. Kiem tra MIME that bang `finfo`.
4. Tao filename unique.
5. Tao thu muc neu chua ton tai.
6. `move_uploaded_file` vao dung folder.
7. Return filename de luu DB.

## 7.5. Buoc E - Security hardening (khuyen nghi)
1. Doi ten file theo UUID thay vi `uniqid()`.
2. Them whitelist extension dong bo voi MIME.
3. Chan upload file co noi dung script nguy hiem.
4. Tach storage ra ngoai webroot va phuc vu qua endpoint co auth.
5. Co job don dep file mo coi (orphan files).
6. Log audit: ai upload, luc nao, file nao, record nao.

---

## 8. Mau pseudocode de tai su dung

```php
function handleFileUpload(string $fieldName, string $folder): string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return '';
    }

    $allowedMime = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/png',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowedMime, true)) {
        return '';
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(16)) . '_' . time() . '.' . $extension;

    $uploadDir = __DIR__ . '/../uploads/' . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destination = $uploadDir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return '';
    }

    return $filename;
}
```

---

## 9. Cach tich hop menu "Quan ly file" cho project moi

Neu dung he thong file manager ben ngoai:
1. Them menu trong layout header.
2. Chi hien thi khi user da login.
3. Dat `target="_blank"` de mo tab moi.
4. Neu can, bo sung token SSO hoac signed query de dong bo dang nhap.

Mau:

```php
<?php if (isLoggedIn()): ?>
<li>
    <a href="https://your-file-manager-domain" target="_blank">
        Quan ly file
    </a>
</li>
<?php endif; ?>
```

---

## 10. Ke hoach nang cap de dung dai han (de xuat)
1. Chuyen ve `storage private + download endpoint` co auth.
2. Luu metadata file vao bang rieng:
   - `id`, `module`, `record_id`, `field_name`, `stored_name`, `original_name`, `mime`, `size`, `uploaded_by`, `uploaded_at`.
3. Ho tro nhieu file/1 truong thay vi 1 file/1 cot.
4. Them virus scan (neu ha tang cho phep).
5. Them policy retention va xoa file theo vong doi nghiep vu.

---

## 11. Tom tat
Phan "Quan ly file" cua project hien tai gom:
- 1 diem vao file manager ben ngoai (menu global).
- 1 co che upload noi bo gan voi module nghiep vu (`thietbihotro`).

Neu sao chep sang project khac, co the ap dung nguyen bo theo checklist Muc 7, sau do bo sung hardening Muc 10 de dat muc do san sang production cao hon.

---

## 12. Input bat buoc de Copilot co the trien khai dung

Khi mang sang project khac, hay chuan bi truoc cac bien sau (chi can doi gia tri):

1. `MODULE_NAME`: ten module nghiep vu (vi du: `thietbihotro`, `maydo`, `hosokiemdinh`).
2. `TABLE_NAME`: bang DB cua module (vi du: `thietbihotro_iso`).
3. `PRIMARY_KEY`: khoa chinh (vi du: `id` hoac `stt`).
4. `UPLOAD_FIELDS`: danh sach cot file (vi du: `hosomay`, `tlkt`).
5. `UPLOAD_DIR_PREFIX`: duong dan luu file (vi du: `uploads`).
6. `MAX_FILE_SIZE_MB`: gioi han dung luong (vi du: `5`).
7. `ALLOWED_MIME`: danh sach MIME cho phep.
8. `ROUTE_ENTRY_FILE`: file route vao module (vi du: `thietbihotro.php`).
9. `PERMISSIONS`: bo quyen can check (`project.view/create/edit/delete` hoac bo rieng).
10. `EXTERNAL_FILE_MANAGER_URL` (neu co): link menu quan ly file ngoai.

Neu thieu 1 trong cac bien tren, Copilot rat de sinh code sai ten truong, sai route, hoac sai quyen.

---

## 13. Prompt copy-paste cho Copilot (trien khai end-to-end)

Ban co the copy nguyen prompt duoi day cho Copilot trong project moi:

```text
Toi muon ban trien khai module Quan ly file dinh kem theo pattern giong iso2.

Thong tin dau vao:
- MODULE_NAME = <dien gia tri>
- TABLE_NAME = <dien gia tri>
- PRIMARY_KEY = <dien gia tri>
- UPLOAD_FIELDS = <vi du: hosomay,tlkt>
- UPLOAD_DIR_PREFIX = uploads
- MAX_FILE_SIZE_MB = 5
- ALLOWED_MIME = application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,image/jpeg,image/jpg,image/png
- ROUTE_ENTRY_FILE = <dien gia tri>
- PERMISSIONS = <dien gia tri>
- EXTERNAL_FILE_MANAGER_URL = <neu co>

Yeu cau thuc hien:
1) Tao/bo sung migration de them cac cot file trong TABLE_NAME neu chua co.
2) Tao cap nhat controller voi ham upload dung chung:
  - validate upload error
  - validate max size theo MAX_FILE_SIZE_MB
  - validate MIME that bang finfo
  - tao ten file unique
  - tao thu muc neu chua co
  - move_uploaded_file
3) Cap nhat create/edit view:
  - form multipart/form-data
  - input file theo UPLOAD_FIELDS
  - o edit: giu file cu neu khong upload moi
  - hien link mo file hien tai
4) Cap nhat view chi tiet de mo file dinh kem.
5) Cap nhat route entry file cho action index/create/edit/view/delete.
6) Them check permission cho tung action theo PERMISSIONS.
7) Neu EXTERNAL_FILE_MANAGER_URL co gia tri, them menu "Quan ly file" o header, chi hien thi khi da login, mo tab moi.
8) Khong doi ten API/cu phap cac module khac, chi thay doi cac file lien quan.
9) Sau khi code xong, liet ke danh sach file da sua va test cases can chay thu.

Tieu chi hoan thanh:
- Upload thanh cong va luu dung folder
- DB luu filename
- Edit khong upload moi thi giu file cu
- Co the mo file tu trang chi tiet
- Action bi chan neu thieu permission
```

---

## 14. Tieu chi nghiem thu sau khi mang sang project khac

Checklist test nhanh (manual):

1. Tao moi ban ghi + upload du file trong `UPLOAD_FIELDS` -> DB co filename, file ton tai trong thu muc.
2. Upload file > gioi han -> bi tu choi.
3. Upload MIME khong hop le -> bi tu choi.
4. Edit ban ghi, khong chon file moi -> filename trong DB khong doi.
5. Edit ban ghi, co file moi -> filename trong DB thay doi, link mo file dung file moi.
6. User khong co quyen `create/edit/delete` -> khong thuc hien duoc action.
7. Neu co menu external -> menu chi hien thi khi login, click mo tab moi dung URL.

Checklist test SQL:

1. Kiem tra cot file ton tai:
  - `SHOW COLUMNS FROM <TABLE_NAME> LIKE 'hosomay';`
  - `SHOW COLUMNS FROM <TABLE_NAME> LIKE 'tlkt';`
2. Kiem tra data mau vua tao:
  - `SELECT <PRIMARY_KEY>, hosomay, tlkt FROM <TABLE_NAME> ORDER BY <PRIMARY_KEY> DESC LIMIT 5;`

Neu 7 buoc manual + 2 buoc SQL deu pass thi co the xem la da "mang qua project khac" thanh cong va Copilot co the tiep tuc mo rong.

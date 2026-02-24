# Lưu đồ - Hệ thống Quản lý Công việc KPI

> Tài liệu mô tả chi tiết chức năng quản lý công việc sửa chữa theo KPI  
> Ngày tạo: 24/02/2026

---

## 📊 1. Lưu đồ Tổng quan Hệ thống

Mô tả luồng hoạt động chính từ đăng nhập đến nhập công việc, xem báo cáo và xuất Excel.

```mermaid
flowchart TD
    Start([Bắt đầu]) --> Login[Đăng nhập hệ thống]
    Login --> Menu{Chọn chức năng}
    
    Menu -->|Nhập công việc| CheckAuth1[Kiểm tra quyền truy cập]
    Menu -->|Xem báo cáo| CheckAuth2[Kiểm tra quyền truy cập]
    
    CheckAuth1 --> InputWork[Trang nhập công việc]
    InputWork --> SelectEmp[Chọn nhân viên]
    SelectEmp --> GetTotalHours[Lấy tổng giờ đã làm trong ngày]
    
    GetTotalHours --> CheckLimit{Tổng giờ < 8h?}
    CheckLimit -->|Không| ShowWarning[Hiển thị cảnh báo: Đã đủ 8h]
    ShowWarning --> DisableAdd[Vô hiệu hóa nút Thêm]
    
    CheckLimit -->|Có| ShowRemaining[Hiển thị: X/8h, còn Y giờ]
    ShowRemaining --> FillForm[Nhập thông tin công việc]
    
    FillForm --> InputDetails[- Ngày làm việc<br/>- Mã vật tư thiết bị<br/>- Số máy<br/>- Cấp độ bảo dưỡng<br/>- Số giờ làm<br/>- Ghi chú]
    
    InputDetails --> ValidateForm{Kiểm tra dữ liệu}
    ValidateForm -->|Thiếu trường| ShowError1[Hiển thị lỗi validation]
    ShowError1 --> FillForm
    
    ValidateForm -->|Hợp lệ| CheckHourLimit{Số giờ + Tổng giờ <= 8h?}
    CheckHourLimit -->|Vượt quá| ShowError2[Lỗi: Vượt quá 8h/ngày]
    ShowError2 --> FillForm
    
    CheckHourLimit -->|OK| GetKPI[Lấy KPI chuẩn từ cấp độ]
    GetKPI --> SaveWork[(Lưu vào DB:<br/>congviec_suachua_iso)]
    
    SaveWork --> Trigger[Trigger kiểm tra 8h]
    Trigger --> TriggerCheck{Tổng giờ <= 8?}
    TriggerCheck -->|Không| Rollback[ROLLBACK giao dịch]
    Rollback --> ShowError3[Lỗi: Trigger chặn]
    ShowError3 --> FillForm
    
    TriggerCheck -->|Có| Commit[COMMIT giao dịch]
    Commit --> CalcEfficiency[Tính hiệu suất:<br/>KPI/Giờ thực tế × 100%]
    CalcEfficiency --> UpdateTable[Cập nhật bảng danh sách]
    UpdateTable --> ShowSuccess[Thông báo thành công]
    ShowSuccess --> InputWork
    
    CheckAuth2 --> ReportPage[Trang báo cáo KPI]
    ReportPage --> SelectDateRange[Chọn khoảng thời gian]
    SelectDateRange --> FetchData[Truy vấn dữ liệu từ VIEWs]
    
    FetchData --> QueryViews[(- view_congviec_nhanvien_thongke<br/>- view_thongke_theo_capdo<br/>- view_kpi_thietbi_thongke)]
    
    QueryViews --> ShowStats[Hiển thị thống kê]
    ShowStats --> StatsDetail[- Số công việc/nhân viên<br/>- Tổng giờ, TB giờ<br/>- Số ngày làm việc<br/>- Số thiết bị sửa<br/>- Hiệu suất theo cấp độ]
    
    StatsDetail --> ShowCharts[Vẽ biểu đồ Chart.js]
    ShowCharts --> Charts[- Bar: Giờ theo nhân viên<br/>- Pie: Phân bố công việc<br/>- Bar: KPI vs Thực tế]
    
    Charts --> ExportOption{Xuất Excel?}
    ExportOption -->|Có| ExportExcel[Controller exportExcel]
    ExportExcel --> DownloadFile[Tải file HTML/Excel]
    
    ExportOption -->|Không| End([Kết thúc])
    DownloadFile --> End
    DisableAdd --> End
    
    style Start fill:#90EE90
    style End fill:#FFB6C1
    style ShowError1 fill:#FFD700
    style ShowError2 fill:#FFD700
    style ShowError3 fill:#FFD700
    style Rollback fill:#FF6B6B
    style Commit fill:#98FB98
    style SaveWork fill:#87CEEB
    style QueryViews fill:#87CEEB
    style ShowCharts fill:#DDA0DD
```

---

## 🔄 2. Sequence Diagram - Quy trình Nhập Công việc

Chi tiết tương tác giữa các thành phần khi nhập công việc mới.

```mermaid
sequenceDiagram
    actor User as Người dùng
    participant UI as Giao diện Web
    participant Controller as CongViecSuaChuaController
    participant Model as CongViecSuaChua Model
    participant DB as Database MySQL
    participant Trigger as DB Trigger
    
    User->>UI: Chọn nhân viên từ dropdown
    activate UI
    UI->>Controller: AJAX: checkGioConLai(nhanvien_stt, ngay)
    activate Controller
    Controller->>Model: getTongGioTrongNgay()
    activate Model
    Model->>DB: SELECT SUM(so_gio_lam) WHERE...
    activate DB
    DB-->>Model: Tổng giờ (VD: 6.5h)
    deactivate DB
    Model-->>Controller: 6.5
    deactivate Model
    Controller->>Controller: Tính: 8 - 6.5 = 1.5h
    Controller-->>UI: JSON {tong_gio: 6.5, gio_con_lai: 1.5}
    deactivate Controller
    UI->>UI: Hiển thị: "6.5/8h, còn 1.5 giờ"
    UI->>UI: Tô màu xanh (< 7h)
    deactivate UI
    
    User->>UI: Nhập thông tin công việc
    UI->>UI: Nhập: mavt, somay, capdo, 2.5 giờ
    User->>UI: Click "Thêm công việc"
    activate UI
    
    UI->>UI: Validate: Kiểm tra trường bắt buộc
    alt Thiếu dữ liệu
        UI-->>User: Alert: "Vui lòng nhập đầy đủ"
    else Dữ liệu đầy đủ
        UI->>UI: Kiểm tra: 6.5 + 2.5 = 9 > 8?
        alt Vượt quá 8h
            UI-->>User: Alert: "Vượt quá 8h/ngày!"
        else OK (≤ 8h)
            UI->>Controller: POST create(form data)
            activate Controller
            Controller->>Controller: Validate dữ liệu đầu vào
            Controller->>Model: canAddGio(nhanvien_stt, ngay, 2.5)
            activate Model
            Model->>DB: SELECT SUM(so_gio_lam)...
            DB-->>Model: 6.5h
            Model->>Model: Validate: 6.5 + 2.5 = 9 > 8?
            Model-->>Controller: {can_add: false, vuot_gio: 1}
            deactivate Model
            Controller-->>UI: Error: "Vượt quá giới hạn 8h"
            deactivate Controller
            UI-->>User: Hiển thị lỗi
        end
    end
    deactivate UI
    
    Note over User,Trigger: Trường hợp nhập hợp lệ (VD: 1 giờ)
    
    User->>UI: Nhập 1 giờ, Click "Thêm"
    activate UI
    UI->>Controller: POST create(1 giờ)
    activate Controller
    Controller->>Model: canAddGio(nhanvien_stt, ngay, 1)
    activate Model
    Model->>DB: SELECT SUM(so_gio_lam)
    DB-->>Model: 6.5h
    Model->>Model: 6.5 + 1 = 7.5 ≤ 8 ✓
    Model-->>Controller: {can_add: true, gio_con_lai: 0.5}
    deactivate Model
    
    Controller->>Model: createWithValidation(data)
    activate Model
    Model->>DB: BEGIN TRANSACTION
    activate DB
    Model->>DB: INSERT INTO congviec_suachua_iso...
    DB->>Trigger: BEFORE INSERT Trigger
    activate Trigger
    Trigger->>DB: SELECT SUM(so_gio_lam) + NEW.so_gio_lam
    DB-->>Trigger: 7.5h
    Trigger->>Trigger: 7.5 ≤ 8? ✓
    Trigger-->>DB: OK, tiếp tục INSERT
    deactivate Trigger
    DB-->>Model: INSERT OK, lastInsertId = 123
    Model->>DB: COMMIT
    DB-->>Model: SUCCESS
    deactivate DB
    Model-->>Controller: ['success' => true, 'stt' => 123]
    deactivate Model
    
    Controller->>Controller: Tính hiệu suất: (KPI/1) × 100%
    Controller-->>UI: JSON success + dữ liệu mới
    deactivate Controller
    
    UI->>UI: Thêm row vào bảng
    UI->>UI: Cập nhật: "7.5/8h, còn 0.5 giờ"
    UI->>UI: Đổi màu sang vàng (≥ 7h)
    UI->>UI: Tính lại hiệu suất trung bình
    UI-->>User: "✓ Thêm công việc thành công"
    deactivate UI
```

---

## 🗂️ 3. ER Diagram - Cấu trúc Database

Mô tả quan hệ giữa các bảng, VIEWs và khóa ngoại.

```mermaid
erDiagram
    capdo_baocuong_iso ||--o{ congviec_suachua_iso : has
    capdo_baocuong_iso ||--o{ thietbi_capdo_kpi_iso : defines
    resume ||--o{ congviec_suachua_iso : performs
    hososcbd_iso ||--o| congviec_suachua_iso : "links to"
    thietbi_iso ||--o| congviec_suachua_iso : "links to"
    
    capdo_baocuong_iso {
        int stt PK "AUTO_INCREMENT"
        varchar ma_capdo UK "CAP1,CAP2,CAP3"
        varchar ten_capdo "Cấp 1,2,3 Bảo dưỡng"
        decimal kpi_gio_chuan "2.0, 4.0, 8.0"
        text mo_ta "Mô tả chi tiết"
        int thu_tu "Thứ tự hiển thị"
        tinyint trang_thai "1=active,0=inactive"
        datetime created_at
        datetime updated_at
    }
    
    thietbi_capdo_kpi_iso {
        int stt PK "AUTO_INCREMENT"
        varchar mavt
        varchar somay
        int capdo_stt FK "→capdo_baocuong_iso"
        decimal kpi_gio_du_kien "KPI riêng cho TB"
        text ghi_chu
        datetime created_at
        datetime updated_at
    }
    
    congviec_suachua_iso {
        int stt PK "AUTO_INCREMENT"
        int nhanvien_stt FK "→resume.stt"
        date ngay_lam_viec "Ngày thực hiện"
        varchar mavt_thietbi
        varchar somay_thietbi
        int capdo_stt FK "→capdo_baocuong_iso"
        decimal so_gio_lam "Số giờ thực tế"
        int hososcbd_stt FK "nullable"
        int thietbi_stt FK "nullable"
        text ghi_chu
        datetime created_at
        datetime updated_at
        varchar created_by
        varchar updated_by
    }
    
    resume {
        int stt PK
        varchar HOTEN
        varchar USERNAME
        int TRANGTHAI "1=active"
    }
    
    hososcbd_iso {
        int stt PK
        varchar MA_HS
    }
    
    thietbi_iso {
        int stt PK
        varchar MAVT
        varchar SOMAY
    }
    
    view_congviec_nhanvien_thongke {
        int nhanvien_stt PK
        varchar ten_nhanvien
        int so_cong_viec "COUNT"
        decimal tong_gio "SUM"
        decimal gio_trung_binh "AVG"
        int so_ngay_lam "DISTINCT dates"
        int so_thietbi_sua "DISTINCT equip"
    }
    
    view_thongke_theo_capdo {
        int capdo_stt PK
        varchar ma_capdo
        varchar ten_capdo
        decimal kpi_chuan
        int so_cong_viec "COUNT"
        decimal tong_gio "SUM"
        decimal gio_trung_binh "AVG"
        decimal hieu_suat_percent "KPI/AVG×100"
    }
    
    view_kpi_thietbi_thongke {
        varchar mavt_thietbi PK
        varchar somay_thietbi PK
        int so_lan_sua "COUNT"
        decimal tong_gio "SUM"
        int so_capdo_khac_nhau "DISTINCT"
    }
    
    congviec_suachua_iso }o--|| view_congviec_nhanvien_thongke : aggregates
    congviec_suachua_iso }o--|| view_thongke_theo_capdo : aggregates
    congviec_suachua_iso }o--|| view_kpi_thietbi_thongke : aggregates
```

---

## 🔀 4. State Diagram - Trạng thái Hệ thống

Mô tả các trạng thái và chuyển đổi trong quá trình làm việc.

```mermaid
stateDiagram-v2
    [*] --> ChonNhanVien: Truy cập trang
    
    ChonNhanVien --> KiemTraGio: Chọn nhân viên + ngày
    
    state KiemTraGio {
        [*] --> LayTongGio
        LayTongGio --> TinhGioConLai
        TinhGioConLai --> HienThiTrangThai
        
        state HienThiTrangThai <<choice>>
        HienThiTrangThai --> ConGio: < 8h
        HienThiTrangThai --> DayGio: = 8h
        HienThiTrangThai --> VuotGio: > 8h (lỗi DB)
        
        ConGio --> MauXanh: < 7h
        ConGio --> MauVang: 7h ≤ x < 8h
        
        MauXanh --> [*]: Hiển thị X/8h
        MauVang --> [*]: Hiển thị X/8h
        DayGio --> MauDo: Đủ 8h
        MauDo --> VoHieuHoaNut: Disable thêm
        VoHieuHoaNut --> [*]
    }
    
    KiemTraGio --> NhapThongTin: Còn giờ
    KiemTraGio --> [*]: Đã đủ 8h
    
    NhapThongTin --> ValidationUI: Submit form
    
    state ValidationUI {
        [*] --> KiemTraBatBuoc
        KiemTraBatBuoc --> ThieuDuLieu: Missing fields
        KiemTraBatBuoc --> KiemTraGioNhap: OK
        
        ThieuDuLieu --> [*]: Alert lỗi
        
        KiemTraGioNhap --> VuotQuota: Tổng > 8h
        KiemTraGioNhap --> GuiServer: Tổng ≤ 8h
        
        VuotQuota --> [*]: Alert vượt quá
    }
    
    ValidationUI --> GuiDenController: Validation OK
    ValidationUI --> NhapThongTin: Validation Failed
    
    state GuiDenController {
        [*] --> ControllerValidation
        ControllerValidation --> ModelCanAddGio
        ModelCanAddGio --> CannotAdd: vuot_gio = true
        ModelCanAddGio --> CreateRecord: can_add = true
        
        CannotAdd --> [*]: Return error JSON
        
        state CreateRecord {
            [*] --> BeginTransaction
            BeginTransaction --> InsertSQL
            InsertSQL --> TriggerCheck
            
            state TriggerCheck <<choice>>
            TriggerCheck --> TriggerOK: ≤ 8h
            TriggerCheck --> TriggerFail: > 8h
            
            TriggerFail --> Rollback
            Rollback --> [*]: Lỗi trigger
            
            TriggerOK --> Commit
            Commit --> [*]: Success
        }
        
        CreateRecord --> [*]
    }
    
    GuiDenController --> ThanhCong: Insert OK
    GuiDenController --> ThatBai: Insert Failed
    
    ThanhCong --> CapNhatUI: Return JSON success
    
    state CapNhatUI {
        [*] --> ThemRowMoi
        ThemRowMoi --> TinhHieuSuat
        TinhHieuSuat --> CapNhatTongGio
        CapNhatTongGio --> DoiMauStatus
        DoiMauStatus --> [*]
    }
    
    CapNhatUI --> ChonNhanVien: Tiếp tục nhập
    ThatBai --> NhapThongTin: Hiển thị lỗi
    
    ChonNhanVien --> XemBaoCao: Click "Báo cáo KPI"
    
    state XemBaoCao {
        [*] --> ChonKhoangTG
        ChonKhoangTG --> QueryVIEWs
        
        state QueryVIEWs {
            [*] --> ViewNhanVien
            [*] --> ViewCapDo
            [*] --> ViewThietBi
            
            ViewNhanVien --> [*]
            ViewCapDo --> [*]
            ViewThietBi --> [*]
        }
        
        QueryVIEWs --> TinhToanThongKe
        TinhToanThongKe --> VeBieuDo
        
        state VeBieuDo {
            [*] --> BarChartNhanVien
            [*] --> PieChartCongViec
            [*] --> BarChartKPI
            
            BarChartNhanVien --> [*]
            PieChartCongViec --> [*]
            BarChartKPI --> [*]
        }
        
        VeBieuDo --> HienThiBaoCao
        HienThiBaoCao --> ExportExcel: Click xuất
        HienThiBaoCao --> [*]: Xem tiếp
        
        ExportExcel --> TaiFile
        TaiFile --> [*]
    }
    
    XemBaoCao --> [*]: Kết thúc
    
    note right of KiemTraGio
        Real-time validation:
        - AJAX mỗi khi chọn nhân viên
        - Cập nhật ngay tổng giờ
        - Màu sắc cảnh báo
    end note
    
    note right of GuiDenController
        3 lớp validation:
        1. UI (JavaScript)
        2. Controller (PHP)
        3. Database Trigger
    end note
    
    note right of XemBaoCao
        Thống kê từ VIEWs:
        - Hiệu suất nhân viên
        - So sánh với KPI
        - Phân tích theo cấp độ
    end note
```

---

## 🏗️ 5. Class Diagram - Kiến trúc OOP

Mô tả cấu trúc các class, inheritance và dependencies.

```mermaid
classDiagram
    class BaseModel {
        #PDO $db
        #string $table
        #string $primaryKey
        #array $fillable
        #array $guarded
        __construct(PDO $db)
        +getAll() array
        +find(int $id) array|null
        +create(array $data) string
        +update(int $id, array $data) int
        +delete(int $id) int
        #logActivity(string $action, string $details)
        #filterData(array $data) array
        #executeQuery(string $sql, array $params) PDOStatement
    }
    
    class CongViecSuaChua {
        #string $table "congviec_suachua_iso"
        #string $primaryKey "stt"
        #array $fillable
        +createWithValidation(array $data) array
        +canAddGio(int $nhanvienStt, string $ngay, float $gioMoi) array
        +getTongGioTrongNgay(int $nhanvienStt, string $ngay) float
        +getByNhanVien(int $nhanvienStt, string $ngayBd, string $ngayKt) array
        +getByDateRange(string $ngayBd, string $ngayKt) array
        +updateSoGio(int $stt, float $soGio) bool
        +delete(int $id) int
        -validateRequiredFields(array $data) array
    }
    
    class CapDoBaoCuong {
        #string $table "capdo_baocuong_iso"
        #string $primaryKey "stt"
        #array $fillable
        +getActiveLevels() array
        +getByCode(string $maCapdo) array|null
        +getKPIChuan(int $capdoStt) float
        +getStatistics(string $ngayBd, string $ngayKt) array
        +updateKPI(int $stt, float $kpiGioChuan) bool
        +toggleStatus(int $stt) bool
    }
    
    class ThietBiCapDoKPI {
        #string $table "thietbi_capdo_kpi_iso"
        #string $primaryKey "stt"
        #array $fillable
        +getByThietBi(string $mavt, string $somay) array
        +getByCapDo(int $capdoStt) array
        +createOrUpdate(array $data) array
        +exists(string $mavt, string $somay, int $capdoStt) bool
        +delete(int $id) int
        +getKPIDuKien(string $mavt, string $somay, int $capdoStt) float|null
    }
    
    class Resume {
        #string $table "resume"
        #string $primaryKey "stt"
        +getActiveEmployees() array
        +findByUsername(string $username) array|null
        +getEmployeeInfo(int $stt) array|null
    }
    
    class ThietBi {
        #string $table "thietbi_iso"
        #string $primaryKey "stt"
        +findByMaVtAndSoMay(string $mavt, string $somay) array|null
        +getEquipmentInfo(string $mavt, string $somay) array|null
    }
    
    class CongViecSuaChuaController {
        -PDO $db
        -CongViecSuaChua $congviecModel
        -CapDoBaoCuong $capdoModel
        -ThietBiCapDoKPI $kpiModel
        -Resume $resumeModel
        -ThietBi $thietbiModel
        __construct(PDO $db)
        +index() void
        +create() void
        +update() void
        +delete() void
        +checkGioConLai() void
        +getBaoCaoTongQuan(string $ngayBd, string $ngayKt) array
        +exportExcel(string $ngayBd, string $ngayKt) void
        -validateInput(array $data) array
        -formatResponse(bool $success, string $message, array $data) array
    }
    
    class ViewCongViecNhanVienThongKe {
        <<VIEW>>
        +nhanvien_stt int
        +ten_nhanvien varchar
        +so_cong_viec int
        +tong_gio decimal
        +gio_trung_binh decimal
        +so_ngay_lam int
        +so_thietbi_sua int
    }
    
    class ViewThongKeoCapDo {
        <<VIEW>>
        +capdo_stt int
        +ma_capdo varchar
        +ten_capdo varchar
        +kpi_chuan decimal
        +so_cong_viec int
        +tong_gio decimal
        +gio_trung_binh decimal
        +hieu_suat_percent decimal
    }
    
    class ViewKPIThietBiThongKe {
        <<VIEW>>
        +mavt_thietbi varchar
        +somay_thietbi varchar
        +so_lan_sua int
        +tong_gio decimal
        +so_capdo_khac_nhau int
    }
    
    BaseModel <|-- CongViecSuaChua : extends
    BaseModel <|-- CapDoBaoCuong : extends
    BaseModel <|-- ThietBiCapDoKPI : extends
    BaseModel <|-- Resume : extends
    BaseModel <|-- ThietBi : extends
    
    CongViecSuaChuaController --> CongViecSuaChua : uses
    CongViecSuaChuaController --> CapDoBaoCuong : uses
    CongViecSuaChuaController --> ThietBiCapDoKPI : uses
    CongViecSuaChuaController --> Resume : uses
    CongViecSuaChuaController --> ThietBi : uses
    
    CongViecSuaChua --> CapDoBaoCuong : FK capdo_stt
    CongViecSuaChua --> Resume : FK nhanvien_stt
    ThietBiCapDoKPI --> CapDoBaoCuong : FK capdo_stt
    
    CongViecSuaChua ..> ViewCongViecNhanVienThongKe : aggregates
    CongViecSuaChua ..> ViewThongKeoCapDo : aggregates
    CongViecSuaChua ..> ViewKPIThietBiThongKe : aggregates
    
    note for CongViecSuaChua "Validation rules:\n- nhanvien_stt required\n- ngay_lam_viec required\n- capdo_stt required\n- so_gio_lam > 0\n- Total daily hours ≤ 8"
    
    note for CapDoBaoCuong "KPI Standards:\nCAP1: 2h\nCAP2: 4h\nCAP3: 8h"
    
    note for BaseModel "Base CRUD operations:\n- Auto logging\n- Fillable/guarded protection\n- Return types:\n  create() → string (ID)\n  update() → int (rows)\n  delete() → int (rows)"
```

---

## 🔧 6. Component Diagram - Kiến trúc Tổng thể

Mô tả các lớp (layers) và sự tương tác giữa chúng.

```mermaid
graph TB
    subgraph "Client Layer - Trình duyệt"
        UI[fa:fa-desktop Giao diện Web]
        JS[fa:fa-code JavaScript AJAX]
        Chart[fa:fa-chart-bar Chart.js]
        CSS[fa:fa-paint-brush Tailwind CSS]
    end
    
    subgraph "Presentation Layer - PHP Views"
        ViewIndex[views/congviec/index.php<br/>Form nhập công việc]
        ViewBaoCao[baocao_kpi.php<br/>Dashboard KPI]
        ViewExport[Excel Export View]
    end
    
    subgraph "Application Layer - PHP Controller"
        Router[congviec_suachua.php<br/>Action Router]
        Controller[CongViecSuaChuaController<br/>Business Logic]
        
        Router --> Controller
        
        subgraph "Controller Actions"
            ActIndex[index: Hiển thị form]
            ActCreate[create: Thêm CV]
            ActUpdate[update: Sửa CV]
            ActDelete[delete: Xóa CV]
            ActCheck[checkGioConLai: Validation]
            ActReport[getBaoCaoTongQuan]
            ActExport[exportExcel]
        end
        
        Controller --> ActIndex
        Controller --> ActCreate
        Controller --> ActUpdate
        Controller --> ActDelete
        Controller --> ActCheck
        Controller --> ActReport
        Controller --> ActExport
    end
    
    subgraph "Domain Layer - PHP Models"
        ModelCV[CongViecSuaChua.php]
        ModelCD[CapDoBaoCuong.php]
        ModelKPI[ThietBiCapDoKPI.php]
        ModelNV[Resume.php]
        ModelTB[ThietBi.php]
        ModelBase[BaseModel.php<br/>Abstract Base]
        
        ModelCV -.extends.-> ModelBase
        ModelCD -.extends.-> ModelBase
        ModelKPI -.extends.-> ModelBase
        ModelNV -.extends.-> ModelBase
        ModelTB -.extends.-> ModelBase
    end
    
    subgraph "Data Layer - MySQL Database"
        subgraph "Tables"
            TblCV[(congviec_suachua_iso)]
            TblCD[(capdo_baocuong_iso)]
            TblKPI[(thietbi_capdo_kpi_iso)]
            TblNV[(resume)]
            TblTB[(thietbi_iso)]
        end
        
        subgraph "Views"
            ViewNV[(view_congviec_nhanvien_thongke)]
            ViewCapDo[(view_thongke_theo_capdo)]
            ViewTBKPI[(view_kpi_thietbi_thongke)]
        end
        
        subgraph "Triggers"
            TrigInsert[before_insert_congviec_check_gio]
            TrigUpdate[before_update_congviec_check_gio]
        end
        
        TblCV --> ViewNV
        TblCV --> ViewCapDo
        TblCV --> ViewTBKPI
        
        TblCV -.trigger.-> TrigInsert
        TblCV -.trigger.-> TrigUpdate
        
        TblCV -->|FK capdo_stt| TblCD
        TblCV -->|FK nhanvien_stt| TblNV
        TblKPI -->|FK capdo_stt| TblCD
    end
    
    subgraph "Infrastructure"
        DB[fa:fa-database PDO Connection]
        Auth[requireAuth.php<br/>Session Auth]
        Config[config.php<br/>DB Config]
    end
    
    UI --> JS
    UI --> CSS
    JS --> ViewIndex
    JS --> ViewBaoCao
    
    Chart --> ViewBaoCao
    
    ViewIndex --> Router
    ViewBaoCao --> Router
    
    Router --> Auth
    Auth --> Config
    Config --> DB
    
    Controller --> ModelCV
    Controller --> ModelCD
    Controller --> ModelKPI
    Controller --> ModelNV
    Controller --> ModelTB
    
    ModelBase --> DB
    
    ModelCV --> TblCV
    ModelCD --> TblCD
    ModelKPI --> TblKPI
    ModelNV --> TblNV
    ModelTB --> TblTB
    
    ActReport --> ViewNV
    ActReport --> ViewCapDo
    ActReport --> ViewTBKPI
    
    ActExport --> ViewExport
    
    style UI fill:#E3F2FD
    style ViewIndex fill:#FFF9C4
    style ViewBaoCao fill:#FFF9C4
    style Controller fill:#C8E6C9
    style ModelBase fill:#FFCCBC
    style TblCV fill:#B2DFDB
    style ViewNV fill:#D1C4E9
    style TrigInsert fill:#FFAB91
    style TrigUpdate fill:#FFAB91
    style DB fill:#CFD8DC
    
    classDef controllerAction fill:#A5D6A7,stroke:#4CAF50,stroke-width:2px
    class ActIndex,ActCreate,ActUpdate,ActDelete,ActCheck,ActReport,ActExport controllerAction
```

---

## 📋 Tóm tắt Chức năng

### Quy tắc Nghiệp vụ
- ✅ Mỗi nhân viên tối đa **8 giờ/ngày**
- ✅ 3 cấp độ bảo dưỡng với KPI chuẩn:
  - **CAP1**: 2 giờ
  - **CAP2**: 4 giờ  
  - **CAP3**: 8 giờ
- ✅ Hiệu suất = (KPI chuẩn / Giờ thực tế) × 100%

### Validation 3 Lớp
1. **JavaScript** (Client-side): Validation form trước khi submit
2. **PHP Controller**: Kiểm tra logic nghiệp vụ
3. **Database Trigger**: Đảm bảo tính toàn vẹn dữ liệu

### Màu Cảnh báo
- 🟢 **Xanh**: < 7 giờ (an toàn)
- 🟡 **Vàng**: 7-8 giờ (gần đủ)
- 🔴 **Đỏ**: = 8 giờ (đã đủ, disable thêm)

### Báo cáo Thống kê
- Số công việc, tổng giờ, giờ trung bình theo nhân viên
- So sánh KPI chuẩn vs thực tế theo cấp độ
- Thống kê thiết bị: số lần sửa, tổng giờ
- Export Excel với UTF-8 BOM

---

## 📂 Files Liên quan

```
iso2/
├── migrations/
│   └── 20260224_create_kpi_suachua_system.sql
├── models/
│   ├── BaseModel.php
│   ├── CongViecSuaChua.php
│   ├── CapDoBaoCuong.php
│   ├── ThietBiCapDoKPI.php
│   ├── Resume.php
│   └── ThietBi.php
├── controllers/
│   └── CongViecSuaChuaController.php
├── views/
│   └── congviec/
│       └── index.php
├── congviec_suachua.php (Router)
├── baocao_kpi.php (Dashboard)
├── CONGVIEC_KPI_README.md
└── LUUDO_CONGVIEC_KPI.md (file này)
```

---

**Lưu ý**: Để xem các biểu đồ Mermaid, mở file này bằng:
- VS Code với extension **Markdown Preview Mermaid Support**
- GitHub / GitLab (hỗ trợ Mermaid native)
- Các editor hỗ trợ Mermaid khác

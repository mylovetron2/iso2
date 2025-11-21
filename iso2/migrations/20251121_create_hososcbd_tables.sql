-- Migration: Create tables for hososcbd (Service Request & Repair Management)
-- Date: 2025-11-21

-- Bảng: hososcbd_iso - Hồ sơ sửa chữa/bảo dưỡng
CREATE TABLE IF NOT EXISTS `hososcbd_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `maql` VARCHAR(80) NOT NULL COMMENT 'Mã quản lý',
    `hoso` VARCHAR(80) NOT NULL COMMENT 'Mã hồ sơ',
    `phieu` CHAR(10) NOT NULL COMMENT 'Số phiếu',
    
    -- Thông tin thiết bị
    `mavt` VARCHAR(80) NOT NULL COMMENT 'Mã vật tư/thiết bị',
    `somay` VARCHAR(80) NOT NULL COMMENT 'Số máy/Serial',
    `model` VARCHAR(100) DEFAULT '' COMMENT 'Model thiết bị',
    `solg` INT(30) DEFAULT 1 COMMENT 'Số lượng',
    `vitrimaybd` VARCHAR(200) DEFAULT '' COMMENT 'Vị trí thiết bị',
    
    -- Thời gian
    `ngayyc` DATE NOT NULL COMMENT 'Ngày yêu cầu',
    `ngayth` DATE DEFAULT NULL COMMENT 'Ngày thực hiện',
    `ngaykt` DATE DEFAULT NULL COMMENT 'Ngày kết thúc',
    `ngaybdtt` DATE DEFAULT NULL COMMENT 'Ngày BD tiếp theo',
    
    -- Khách hàng & Yêu cầu
    `madv` VARCHAR(80) NOT NULL COMMENT 'Mã đơn vị',
    `ngyeucau` VARCHAR(80) DEFAULT '' COMMENT 'Người yêu cầu',
    `ngnhyeucau` VARCHAR(80) DEFAULT '' COMMENT 'Người nhận yêu cầu',
    `dienthoai` VARCHAR(20) DEFAULT '' COMMENT 'Điện thoại',
    `cv` TEXT COMMENT 'Công việc yêu cầu',
    `ycthemkh` TEXT COMMENT 'Yêu cầu thêm từ khách hàng',
    
    -- Chuẩn đoán & Xử lý
    `ttktbefore` TEXT COMMENT 'Tình trạng KT trước',
    `honghoc` TEXT COMMENT 'Mô tả hỏng hóc',
    `khacphuc` TEXT COMMENT 'Cách khắc phục',
    `ttktafter` TEXT COMMENT 'Tình trạng KT sau',
    `xemxetxuong` TEXT COMMENT 'Xem xét của xưởng',
    
    -- Thiết bị hỗ trợ (max 5 thiết bị)
    `tbdosc` VARCHAR(80) DEFAULT '' COMMENT 'Thiết bị đo/SC #1',
    `serialtbdosc` VARCHAR(80) DEFAULT '' COMMENT 'Serial TB #1',
    `tbdosc1` VARCHAR(80) DEFAULT '' COMMENT 'Thiết bị đo/SC #2',
    `serialtbdosc1` VARCHAR(80) DEFAULT '' COMMENT 'Serial TB #2',
    `tbdosc2` VARCHAR(80) DEFAULT '' COMMENT 'Thiết bị đo/SC #3',
    `serialtbdosc2` VARCHAR(80) DEFAULT '' COMMENT 'Serial TB #3',
    `tbdosc3` VARCHAR(80) DEFAULT '' COMMENT 'Thiết bị đo/SC #4',
    `serialtbdosc3` VARCHAR(80) DEFAULT '' COMMENT 'Serial TB #4',
    `tbdosc4` VARCHAR(80) DEFAULT '' COMMENT 'Thiết bị đo/SC #5',
    `serialtbdosc4` VARCHAR(80) DEFAULT '' COMMENT 'Serial TB #5',
    
    -- Bàn giao & Hoàn thành
    `bg` INT(2) DEFAULT 0 COMMENT 'Trạng thái bàn giao: 0=Chưa, 1=Đã',
    `slbg` INT(3) DEFAULT NULL COMMENT 'Số lần bàn giao',
    `ghichu` TEXT COMMENT 'Ghi chú chung',
    `ghichufinal` TEXT COMMENT 'Ghi chú khi hoàn thành',
    
    -- Phân loại
    `nhomsc` VARCHAR(100) DEFAULT '' COMMENT 'Nhóm sửa chữa: CNC, KTKT...',
    `dong` VARCHAR(80) DEFAULT '' COMMENT 'Dòng thiết bị',
    
    -- Vị trí giếng khoan (đặc thù dầu khí)
    `lo` VARCHAR(200) DEFAULT '' COMMENT 'Lô khai thác',
    `gieng` VARCHAR(200) DEFAULT '' COMMENT 'Giếng khoan',
    `mo` VARCHAR(250) DEFAULT '' COMMENT 'Mỏ dầu khí',
    
    -- Báo cáo
    `noidung` TEXT COMMENT 'Nội dung công việc chi tiết',
    `ketluan` TEXT COMMENT 'Kết luận sau khi hoàn thành',
    
    INDEX `idx_phieu` (`phieu`),
    INDEX `idx_maql` (`maql`),
    INDEX `idx_hoso` (`hoso`),
    INDEX `idx_mavt` (`mavt`),
    INDEX `idx_madv` (`madv`),
    INDEX `idx_bg` (`bg`),
    INDEX `idx_ngayyc` (`ngayyc`),
    INDEX `idx_nhomsc` (`nhomsc`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Hồ sơ sửa chữa/bảo dưỡng thiết bị';

-- Bảng: donvi_iso - Đơn vị khách hàng
CREATE TABLE IF NOT EXISTS `donvi_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `madv` VARCHAR(80) NOT NULL UNIQUE COMMENT 'Mã đơn vị',
    `tendv` VARCHAR(255) NOT NULL COMMENT 'Tên đơn vị',
    `diachi` VARCHAR(500) DEFAULT '' COMMENT 'Địa chỉ',
    `dienthoai` VARCHAR(20) DEFAULT '' COMMENT 'Điện thoại',
    `email` VARCHAR(100) DEFAULT '' COMMENT 'Email',
    `nguoidaidien` VARCHAR(100) DEFAULT '' COMMENT 'Người đại diện',
    `ghichu` TEXT COMMENT 'Ghi chú',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_madv` (`madv`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Đơn vị khách hàng';

-- Bảng: thietbi_iso - Thiết bị
CREATE TABLE IF NOT EXISTS `thietbi_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `mavt` VARCHAR(80) NOT NULL COMMENT 'Mã vật tư',
    `mamay` VARCHAR(100) DEFAULT '' COMMENT 'Mã máy đầy đủ',
    `tenvt` VARCHAR(255) NOT NULL COMMENT 'Tên thiết bị',
    `somay` VARCHAR(80) NOT NULL COMMENT 'Serial number',
    `model` VARCHAR(100) DEFAULT '' COMMENT 'Model',
    `madv` VARCHAR(80) DEFAULT '' COMMENT 'Mã đơn vị sở hữu',
    `hangsx` VARCHAR(100) DEFAULT '' COMMENT 'Hãng sản xuất',
    `nuocsx` VARCHAR(100) DEFAULT '' COMMENT 'Nước sản xuất',
    `namsx` INT(4) DEFAULT NULL COMMENT 'Năm sản xuất',
    `tinhtrang` VARCHAR(50) DEFAULT 'Hoạt động' COMMENT 'Tình trạng hiện tại',
    `vitri` VARCHAR(200) DEFAULT '' COMMENT 'Vị trí lắp đặt',
    `ghichu` TEXT COMMENT 'Ghi chú',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_mavt` (`mavt`),
    INDEX `idx_somay` (`somay`),
    INDEX `idx_madv` (`madv`),
    UNIQUE KEY `unique_equipment` (`mavt`, `somay`, `model`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Danh mục thiết bị';

-- Bảng: vitri_iso - Vị trí thiết bị
CREATE TABLE IF NOT EXISTS `vitri_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `mavitri` VARCHAR(80) NOT NULL UNIQUE COMMENT 'Mã vị trí',
    `tenvitri` VARCHAR(255) NOT NULL COMMENT 'Tên vị trí',
    `mota` TEXT COMMENT 'Mô tả',
    INDEX `idx_mavitri` (`mavitri`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Vị trí lắp đặt thiết bị';

-- Bảng: lo_iso - Lô khai thác
CREATE TABLE IF NOT EXISTS `lo_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `malo` VARCHAR(80) NOT NULL UNIQUE COMMENT 'Mã lô',
    `tenlo` VARCHAR(255) NOT NULL COMMENT 'Tên lô',
    `mota` TEXT COMMENT 'Mô tả',
    INDEX `idx_malo` (`malo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Lô khai thác dầu khí';

-- Bảng: mo_iso - Mỏ dầu khí
CREATE TABLE IF NOT EXISTS `mo_iso` (
    `stt` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `mamo` VARCHAR(80) NOT NULL UNIQUE COMMENT 'Mã mỏ',
    `tenmo` VARCHAR(255) NOT NULL COMMENT 'Tên mỏ',
    `mota` TEXT COMMENT 'Mô tả',
    INDEX `idx_mamo` (`mamo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Mỏ dầu khí';

@echo off
echo ========================================
echo FIX LỖI ĐĂNG NHẬP - COMMENT TẠM GIỎ HÀNG
echo ========================================
echo.

set "header_file=views\layouts\header.php"
set "backup_file=views\layouts\header.php.backup_%date:~-4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%%time:~6,2%"
set "backup_file=%backup_file: =0%"

echo 1. Backup header.php...
copy "%header_file%" "%backup_file%" >nul 2>&1
if %errorlevel% equ 0 (
    echo    ✓ Đã backup: %backup_file%
) else (
    echo    ✗ Lỗi backup!
    pause
    exit /b 1
)

echo.
echo 2. Comment tạm phần giỏ hàng trong header.php...
echo    (Dùng PowerShell để replace)

powershell -Command "$content = Get-Content '%header_file%' -Raw -Encoding UTF8; $content = $content -replace '(?s)(\s*<\?php if \(hasPermission\(''giohang\.view''\)\): \?>.*?<\?php endif; \?>)', '<!--$1-->'; $content = $content -replace '(?s)(\s*<\?php if \(hasPermission\(''phieudathang\.view''\)\): \?>.*?<\?php endif; \?>)', '<!--$1-->'; [System.IO.File]::WriteAllText('%header_file%', $content, [System.Text.Encoding]::UTF8)"

if %errorlevel% equ 0 (
    echo    ✓ Đã comment thành công!
) else (
    echo    ✗ Lỗi khi comment!
    echo    Khôi phục từ backup...
    copy "%backup_file%" "%header_file%" >nul 2>&1
    pause
    exit /b 1
)

echo.
echo ========================================
echo ✓ HOÀN TẤT!
echo ========================================
echo.
echo Các thay đổi:
echo - Đã backup header.php
echo - Đã comment tạm phần giỏ hàng ^& phiếu đặt hàng
echo.
echo Bây giờ thử:
echo 1. Mở trình duyệt: http://localhost/iso2/views/auth/login.php
echo 2. Thử đăng nhập
echo.
echo Nếu vào được:
echo - Chạy: setup_giohang_phieudathang.sql
echo - Chạy: grant_giohang_phieudathang_permissions.php
echo - Chạy: restore_header.bat (để bật lại giỏ hàng)
echo.
pause

@echo off
echo ========================================
echo KHÔI PHỤC HEADER.PHP
echo ========================================
echo.

set "header_file=views\layouts\header.php"
set "backup_file="

echo Tìm backup file mới nhất...
for /f "delims=" %%i in ('dir /b /o-d "views\layouts\header.php.backup_*" 2^>nul') do (
    set "backup_file=views\layouts\%%i"
    goto :found
)

:found
if "%backup_file%"=="" (
    echo ✗ Không tìm thấy backup file!
    echo.
    pause
    exit /b 1
)

echo Backup file: %backup_file%
echo.
echo Bạn muốn khôi phục từ backup này? (Y/N)
choice /c YN /n
if %errorlevel% equ 2 (
    echo Đã hủy.
    pause
    exit /b 0
)

echo.
echo Đang khôi phục...
copy "%backup_file%" "%header_file%" >nul 2>&1
if %errorlevel% equ 0 (
    echo ✓ Đã khôi phục header.php thành công!
) else (
    echo ✗ Lỗi khi khôi phục!
    pause
    exit /b 1
)

echo.
echo ========================================
echo ✓ HOÀN TẤT!
echo ========================================
echo.
echo Header.php đã được khôi phục về trạng thái có giỏ hàng.
echo.
echo Bây giờ:
echo 1. Clear cache trình duyệt (Ctrl+Shift+R)
echo 2. Vào trang vật tư để xem giỏ hàng
echo.
pause

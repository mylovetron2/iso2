@echo off
REM 🔧 SCRIPT TỰ ĐỘNG UNCOMMENT PERMISSIONS SAU KHI CHẠY MIGRATION (Windows)
REM Chạy script này sau khi execute_add_congviec_permissions.php thành công

echo 🔄 Đang uncomment permission checks...

REM Sử dụng PowerShell để thay thế text
powershell -Command "(Get-Content views\hososcbd\components\congviec_widget.php) -replace 'if \(true \|\| hasPermission', 'if (hasPermission' | Set-Content views\hososcbd\components\congviec_widget.php"
powershell -Command "(Get-Content views\hososcbd\components\congviec_widget.php) -replace '// TODO: Uncomment sau khi chạy migration', '' | Set-Content views\hososcbd\components\congviec_widget.php"

powershell -Command "(Get-Content views\layouts\header.php) -replace 'if \(false\)', 'if (isLoggedIn() && hasPermission(''congviec_suachua.view''))' | Set-Content views\layouts\header.php"
powershell -Command "(Get-Content views\layouts\header.php) -replace '// IMPORTANT:.*', '' | Set-Content views\layouts\header.php"

REM Uncomment đầu file widget (dòng 16-24)
powershell -Command "$content = Get-Content views\hososcbd\components\congviec_widget.php; $content[15] = $content[15] -replace '/\*', ''; $content[23] = $content[23] -replace '\*/', ''; $content | Set-Content views\hososcbd\components\congviec_widget.php"

echo ✅ Hoàn tất! Permissions đã được kích hoạt.
echo 🔄 Đăng xuất và đăng nhập lại để load permissions mới.
pause

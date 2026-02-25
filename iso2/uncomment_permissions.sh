#!/bin/bash
# 🔧 SCRIPT TỰ ĐỘNG UNCOMMENT PERMISSIONS SAU KHI CHẠY MIGRATION
# Chạy script này sau khi execute_add_congviec_permissions.php thành công

echo "🔄 Đang uncomment permission checks..."

# File 1: congviec_widget.php
sed -i 's/if (true || hasPermission/if (hasPermission/g' views/hososcbd/components/congviec_widget.php
sed -i '/TODO: Uncomment sau khi chạy migration/d' views/hososcbd/components/congviec_widget.php

# Uncomment permission check ở đầu file
FILE="views/hososcbd/components/congviec_widget.php"
sed -i '16,23s/^\/\*//' $FILE
sed -i '16,23s/^\*\///' $FILE

# File 2: header.php
sed -i 's/if (false)/if (isLoggedIn() \&\& hasPermission('\''congviec_suachua.view'\''))/' views/layouts/header.php
sed -i '/IMPORTANT: Chạy execute_add_congviec_permissions.php/d' views/layouts/header.php

echo "✅ Hoàn tất! Permissions đã được kích hoạt."
echo "🔄 Đăng xuất và đăng nhập lại để load permissions mới."

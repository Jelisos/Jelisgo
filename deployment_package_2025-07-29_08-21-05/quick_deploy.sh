#!/bin/bash
# 快速部署脚本
# 使用方法：chmod +x quick_deploy.sh && ./quick_deploy.sh

echo "开始部署线上修复包..."

# 检查是否为root用户
if [ "$EUID" -ne 0 ]; then
    echo "请使用root用户或sudo运行此脚本"
    exit 1
fi

# 设置网站根目录（请根据实际情况修改）
WEB_ROOT="/www/wwwroot/jelisgo.cn"

if [ ! -d "$WEB_ROOT" ]; then
    echo "错误：网站根目录不存在 $WEB_ROOT"
    echo "请修改脚本中的WEB_ROOT变量"
    exit 1
fi

echo "网站根目录: $WEB_ROOT"

# 备份原文件
echo "备份原文件..."
cp "$WEB_ROOT/config.php" "$WEB_ROOT/config.php.backup.$(date +%Y%m%d_%H%M%S)" 2>/dev/null
cp "$WEB_ROOT/api/vip/membership_status.php" "$WEB_ROOT/api/vip/membership_status.php.backup.$(date +%Y%m%d_%H%M%S)" 2>/dev/null

# 复制新文件
echo "复制修复文件..."
cp config.php "$WEB_ROOT/config.php"
cp membership_status.php "$WEB_ROOT/api/vip/membership_status.php"

# 创建日志目录
echo "创建日志目录..."
mkdir -p "$WEB_ROOT/logs"

# 设置权限
echo "设置文件权限..."
chmod 644 "$WEB_ROOT/config.php"
chmod 644 "$WEB_ROOT/api/vip/membership_status.php"
chmod 755 "$WEB_ROOT/logs"

# 重启服务
echo "重启PHP-FPM和Nginx..."
systemctl restart php-fpm 2>/dev/null || service php-fpm restart 2>/dev/null
systemctl restart nginx 2>/dev/null || service nginx restart 2>/dev/null

echo "部署完成！"
echo ""
echo "请按照以下步骤完成配置："
echo "1. 编辑 $WEB_ROOT/config.php 文件，更新数据库配置信息"
echo "2. 访问 https://www.jelisgo.cn/config.php 测试数据库连接"
echo "3. 访问 https://www.jelisgo.cn/api/vip/membership_status.php 测试API"
echo "4. 检查前端功能是否正常"
echo ""
echo "如有问题，请查看日志文件：$WEB_ROOT/logs/"

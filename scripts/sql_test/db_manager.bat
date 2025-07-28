@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

REM 数据库管理批处理脚本
REM 文件位置：/scripts/sql_test/db_manager.bat
REM 提供Windows环境下的数据库快速管理功能

echo ========================================
echo           数据库管理工具
echo ========================================
echo.

REM 设置PHP路径（根据XAMPP安装路径调整）
set PHP_PATH=C:\xampp\php\php.exe
set SCRIPT_DIR=%~dp0

REM 检查PHP是否可用
"%PHP_PATH%" -v >nul 2>&1
if errorlevel 1 (
    echo [错误] 无法找到PHP，请检查XAMPP是否正确安装
    echo 当前PHP路径: %PHP_PATH%
    echo.
    echo 请修改此脚本中的PHP_PATH变量为正确的路径
    pause
    exit /b 1
)

REM 检查MySQL服务状态
echo [信息] 检查MySQL服务状态...
netstat -an | findstr :3306 >nul
if errorlevel 1 (
    echo [警告] MySQL服务可能未启动（端口3306未监听）
    echo [建议] 请启动XAMPP控制面板并启动MySQL服务
    echo.
) else (
    echo [成功] MySQL服务正在运行
    echo.
)

REM 显示菜单
:menu
echo 请选择操作：
echo.
echo 1. 测试数据库连接
echo 2. 创建数据库和表结构
echo 3. 验证数据库结构
echo 4. 查看数据库状态
echo 5. 优化数据库性能
echo 6. 备份数据库结构
echo 7. 快速重建数据库
echo 8. 查看帮助信息
echo 9. 退出
echo.
set /p choice=请输入选项 (1-9): 

if "%choice%"=="1" goto test_connection
if "%choice%"=="2" goto create_database
if "%choice%"=="3" goto verify_database
if "%choice%"=="4" goto show_status
if "%choice%"=="5" goto optimize_database
if "%choice%"=="6" goto backup_structure
if "%choice%"=="7" goto rebuild_database
if "%choice%"=="8" goto show_help
if "%choice%"=="9" goto exit

echo [错误] 无效选项，请重新选择
echo.
goto menu

:test_connection
echo.
echo [执行] 测试数据库连接...
echo ========================================
"%PHP_PATH%" "%SCRIPT_DIR%db_connection_test.php"
echo ========================================
echo.
goto continue

:create_database
echo.
echo [执行] 创建数据库和表结构...
echo ========================================
"%PHP_PATH%" "%SCRIPT_DIR%db_quick_tools.php" create
echo ========================================
echo.
goto continue

:verify_database
echo.
echo [执行] 验证数据库结构...
echo ========================================
"%PHP_PATH%" "%SCRIPT_DIR%db_quick_tools.php" verify
echo ========================================
echo.
goto continue

:show_status
echo.
echo [执行] 查看数据库状态...
echo ========================================
"%PHP_PATH%" "%SCRIPT_DIR%db_quick_tools.php" status
echo ========================================
echo.
goto continue

:optimize_database
echo.
echo [警告] 数据库优化可能需要较长时间，确定要继续吗？
set /p confirm=输入 Y 确认，其他键取消: 
if /i not "%confirm%"=="Y" (
    echo [取消] 操作已取消
    echo.
    goto menu
)
echo.
echo [执行] 优化数据库性能...
echo ========================================
"%PHP_PATH%" "%SCRIPT_DIR%db_quick_tools.php" optimize
echo ========================================
echo.
goto continue

:backup_structure
echo.
echo [执行] 备份数据库结构...
echo ========================================
"%PHP_PATH%" "%SCRIPT_DIR%db_quick_tools.php" backup
echo ========================================
echo.
goto continue

:rebuild_database
echo.
echo [警告] 此操作将重建整个数据库，所有数据将丢失！
echo [警告] 请确保已备份重要数据！
echo.
set /p confirm=输入 REBUILD 确认重建，其他内容取消: 
if not "%confirm%"=="REBUILD" (
    echo [取消] 操作已取消
    echo.
    goto menu
)
echo.
echo [执行] 重建数据库...
echo ========================================
echo [步骤1] 删除现有数据库...
"%PHP_PATH%" -r "try { $pdo = new PDO('mysql:host=localhost', 'root', ''); $pdo->exec('DROP DATABASE IF EXISTS wallpaper_db'); echo '数据库删除成功'; } catch(Exception $e) { echo '删除失败: ' . $e->getMessage(); }"
echo.
echo [步骤2] 重新创建数据库和表结构...
"%PHP_PATH%" "%SCRIPT_DIR%db_quick_tools.php" create
echo.
echo [步骤3] 验证重建结果...
"%PHP_PATH%" "%SCRIPT_DIR%db_quick_tools.php" verify
echo ========================================
echo.
goto continue

:show_help
echo.
echo ========================================
echo               帮助信息
echo ========================================
echo.
echo 数据库管理工具使用说明：
echo.
echo 1. 测试数据库连接
echo    - 检查PHP扩展是否正确加载
echo    - 测试MySQL连接是否正常
echo    - 验证数据库配置
echo.
echo 2. 创建数据库和表结构
echo    - 创建wallpaper_db数据库
echo    - 创建所有必需的表结构
echo    - 设置外键约束和索引
echo.
echo 3. 验证数据库结构
echo    - 检查数据库是否存在
echo    - 验证表结构完整性
echo    - 检查索引和外键约束
echo.
echo 4. 查看数据库状态
echo    - 显示MySQL版本信息
echo    - 查看连接数和查询统计
echo    - 显示数据库大小信息
echo.
echo 5. 优化数据库性能
echo    - 分析表结构
echo    - 优化表存储
echo    - 重建索引统计
echo.
echo 6. 备份数据库结构
echo    - 导出表结构定义
echo    - 生成SQL备份文件
echo    - 保存到scripts目录
echo.
echo 7. 快速重建数据库
echo    - 删除现有数据库
echo    - 重新创建完整结构
echo    - 验证重建结果
echo.
echo 常见问题解决：
echo.
echo Q: 提示"无法找到PHP"
echo A: 请检查XAMPP是否正确安装，或修改脚本中的PHP_PATH
echo.
echo Q: 提示"MySQL连接失败"
echo A: 请启动XAMPP控制面板中的MySQL服务
echo.
echo Q: 提示"数据库不存在"
echo A: 请先执行"创建数据库和表结构"操作
echo.
echo Q: 性能优化失败
echo A: 请确保没有其他程序正在使用数据库
echo.
echo 技术支持：
echo - 配置文件位置：f:\XAMPP\htdocs\.trae\rules\xampp_debug_rules.md
echo - 脚本位置：f:\XAMPP\htdocs\scripts\
echo - 日志位置：f:\XAMPP\htdocs\logs\
echo.
echo ========================================
echo.
goto continue

:continue
echo 按任意键继续...
pause >nul
echo.
goto menu

:exit
echo.
echo [信息] 感谢使用数据库管理工具！
echo [提示] 如需帮助，请查看 xampp_debug_rules.md 文档
echo.
pause
exit /b 0

REM 错误处理
:error
echo.
echo [错误] 执行过程中发生错误
echo [建议] 请检查错误信息并重试
echo.
goto continue
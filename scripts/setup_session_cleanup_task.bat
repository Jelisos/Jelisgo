@echo off
chcp 65001 >nul
echo ========================================
echo     Session清理定时任务设置工具
echo ========================================
echo.

set "SCRIPT_DIR=%~dp0"
set "PHP_SCRIPT=%SCRIPT_DIR%cron_session_cleanup.php"
set "PHP_PATH=f:\XAMPP\php\php.exe"
set "TASK_NAME=SessionCleanup"

echo 检查PHP路径...
if not exist "%PHP_PATH%" (
    echo 错误: 找不到PHP可执行文件: %PHP_PATH%
    echo 请修改此批处理文件中的PHP_PATH变量
    pause
    exit /b 1
)

echo 检查清理脚本...
if not exist "%PHP_SCRIPT%" (
    echo 错误: 找不到清理脚本: %PHP_SCRIPT%
    pause
    exit /b 1
)

echo.
echo 当前配置:
echo PHP路径: %PHP_PATH%
echo 脚本路径: %PHP_SCRIPT%
echo 任务名称: %TASK_NAME%
echo.

echo 请选择操作:
echo 1. 创建定时任务 (每小时执行一次)
echo 2. 创建定时任务 (每天凌晨2点执行)
echo 3. 创建定时任务 (每周日凌晨3点执行)
echo 4. 删除现有定时任务
echo 5. 查看任务状态
echo 6. 手动执行一次清理
echo 7. 退出
echo.
set /p choice=请输入选择 (1-7): 

if "%choice%"=="1" goto create_hourly
if "%choice%"=="2" goto create_daily
if "%choice%"=="3" goto create_weekly
if "%choice%"=="4" goto delete_task
if "%choice%"=="5" goto check_status
if "%choice%"=="6" goto manual_run
if "%choice%"=="7" goto exit

echo 无效选择，请重新运行脚本
pause
exit /b 1

:create_hourly
echo.
echo 创建每小时执行的定时任务...
schtasks /create /tn "%TASK_NAME%" /tr "\"%PHP_PATH%\" \"%PHP_SCRIPT%\"\"\ /sc hourly /ru SYSTEM /f
if %errorlevel%==0 (
    echo 成功创建每小时执行的定时任务
) else (
    echo 创建任务失败，错误代码: %errorlevel%
)
goto end

:create_daily
echo.
echo 创建每天凌晨2点执行的定时任务...
schtasks /create /tn "%TASK_NAME%" /tr "\"%PHP_PATH%\" \"%PHP_SCRIPT%\"\"\ /sc daily /st 02:00 /ru SYSTEM /f
if %errorlevel%==0 (
    echo 成功创建每天凌晨2点执行的定时任务
) else (
    echo 创建任务失败，错误代码: %errorlevel%
)
goto end

:create_weekly
echo.
echo 创建每周日凌晨3点执行的定时任务...
schtasks /create /tn "%TASK_NAME%" /tr "\"%PHP_PATH%\" \"%PHP_SCRIPT%\"\"\ /sc weekly /d SUN /st 03:00 /ru SYSTEM /f
if %errorlevel%==0 (
    echo 成功创建每周日凌晨3点执行的定时任务
) else (
    echo 创建任务失败，错误代码: %errorlevel%
)
goto end

:delete_task
echo.
echo 删除现有定时任务...
schtasks /delete /tn "%TASK_NAME%" /f
if %errorlevel%==0 (
    echo 成功删除定时任务
) else (
    echo 删除任务失败或任务不存在，错误代码: %errorlevel%
)
goto end

:check_status
echo.
echo 检查任务状态...
schtasks /query /tn "%TASK_NAME%" /fo table /v
if %errorlevel%==0 (
    echo.
    echo 任务存在并显示详细信息
) else (
    echo 任务不存在或查询失败
)
goto end

:manual_run
echo.
echo 手动执行Session清理...
echo 执行命令: "%PHP_PATH%" "%PHP_SCRIPT%"
echo.
"%PHP_PATH%" "%PHP_SCRIPT%"
echo.
echo 清理执行完成，请查看上方输出信息
goto end

:end
echo.
echo ========================================
echo 操作完成
echo.
echo 注意事项:
echo 1. 定时任务以SYSTEM用户身份运行
echo 2. 确保PHP和MySQL服务正常运行
echo 3. 日志文件位置: %SCRIPT_DIR%..\logs\session_cleanup_cron.log
echo 4. 可以通过Windows任务计划程序查看和管理任务
echo ========================================
echo.
pause

:exit
exit /b 0
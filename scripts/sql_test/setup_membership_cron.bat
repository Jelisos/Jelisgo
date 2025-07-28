@echo off
REM Windows定时任务设置脚本
REM 用于设置会员系统的定时任务
REM 
REM @author AI Assistant
REM @date 2024-01-27

echo ========================================
echo 会员系统定时任务设置脚本
echo ========================================
echo.

REM 检查管理员权限
net session >nul 2>&1
if %errorLevel% == 0 (
    echo [INFO] 检测到管理员权限，继续执行...
) else (
    echo [ERROR] 需要管理员权限才能设置定时任务！
    echo [INFO] 请右键点击此脚本，选择"以管理员身份运行"
    pause
    exit /b 1
)

echo.
echo [INFO] 开始设置定时任务...
echo.

REM 设置变量
set SCRIPT_DIR=%~dp0
set PHP_PATH=F:\XAMPP\php\php.exe
set SCRIPT_PATH=%SCRIPT_DIR%process_expired_memberships.php
set TASK_NAME=WallpaperMembershipProcessor
set LOG_DIR=%SCRIPT_DIR%..\logs

REM 检查PHP路径
if not exist "%PHP_PATH%" (
    echo [ERROR] PHP可执行文件不存在: %PHP_PATH%
    echo [INFO] 请检查XAMPP安装路径，或修改此脚本中的PHP_PATH变量
    pause
    exit /b 1
)

REM 检查脚本文件
if not exist "%SCRIPT_PATH%" (
    echo [ERROR] 处理脚本不存在: %SCRIPT_PATH%
    pause
    exit /b 1
)

REM 创建日志目录
if not exist "%LOG_DIR%" (
    echo [INFO] 创建日志目录: %LOG_DIR%
    mkdir "%LOG_DIR%"
)

REM 删除已存在的任务（如果有）
echo [INFO] 检查并删除已存在的定时任务...
schtasks /query /tn "%TASK_NAME%" >nul 2>&1
if %errorLevel% == 0 (
    echo [INFO] 发现已存在的任务，正在删除...
    schtasks /delete /tn "%TASK_NAME%" /f
    if %errorLevel% == 0 (
        echo [SUCCESS] 已删除旧的定时任务
    ) else (
        echo [ERROR] 删除旧任务失败
    )
)

REM 创建新的定时任务
echo [INFO] 创建新的定时任务...
echo [INFO] 任务名称: %TASK_NAME%
echo [INFO] 执行时间: 每天凌晨2点
echo [INFO] 脚本路径: %SCRIPT_PATH%
echo.

schtasks /create /tn "%TASK_NAME%" /tr "\"%PHP_PATH%\" \"%SCRIPT_PATH%\"\"\ /sc daily /st 02:00 /ru SYSTEM /rl HIGHEST /f

if %errorLevel% == 0 (
    echo.
    echo [SUCCESS] 定时任务创建成功！
    echo.
    echo 任务详情:
    echo - 任务名称: %TASK_NAME%
    echo - 执行时间: 每天凌晨2:00
    echo - 执行用户: SYSTEM
    echo - 脚本路径: %SCRIPT_PATH%
    echo - 日志目录: %LOG_DIR%
    echo.
    echo [INFO] 您可以通过以下方式管理此任务:
    echo 1. 打开"任务计划程序"(taskschd.msc)
    echo 2. 在"任务计划程序库"中找到"%TASK_NAME%"
    echo 3. 右键点击可以运行、禁用或删除任务
    echo.
    
    REM 询问是否立即测试运行
    set /p test_run="是否立即测试运行一次？(y/n): "
    if /i "%test_run%"=="y" (
        echo.
        echo [INFO] 正在测试运行...
        schtasks /run /tn "%TASK_NAME%"
        if %errorLevel% == 0 (
            echo [SUCCESS] 测试运行已启动，请检查日志文件查看结果
        ) else (
            echo [ERROR] 测试运行失败
        )
    )
    
) else (
    echo.
    echo [ERROR] 定时任务创建失败！
    echo [INFO] 请检查:
    echo 1. 是否以管理员身份运行此脚本
    echo 2. PHP路径是否正确: %PHP_PATH%
    echo 3. 脚本路径是否正确: %SCRIPT_PATH%
)

echo.
echo ========================================
echo 脚本执行完成
echo ========================================
pause
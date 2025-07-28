@echo off
chcp 65001 > nul

echo 正在启动浏览器缓存清理工具...

:: 获取当前脚本所在目录
set SCRIPT_DIR=%~dp0

:: 构建HTML文件的完整路径
set HTML_FILE=%SCRIPT_DIR%clear_browser_cache.php

:: 检查文件是否存在
if not exist "%HTML_FILE%" (
    echo 错误：找不到缓存清理工具文件！
    echo 请确保 clear_browser_cache.php 文件位于同一目录。
    pause
    exit /b 1
)

:: 使用默认浏览器打开HTML文件
start "" "%HTML_FILE%"

echo 浏览器缓存清理工具已启动。
exit /b 0
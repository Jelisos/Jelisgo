<?php
/**
 * 文件: scripts/fix_membership_status.php
 * 描述: 修复membership_status.php接口的问题
 * 创建时间: 2025-06-27
 * 维护: AI助手
 */

// 设置错误报告
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 创建日志目录
$logDir = __DIR__ . '/../LOGS';
if (!file_exists($logDir)) {
    mkdir($logDir, 0777, true);
}

// 日志文件路径
$logFile = $logDir . '/fix_membership_status_' . date('Y-m-d_H-i-s') . '.log';

// 日志函数
function logMessage($message, $type = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry;
}

// 检查文件是否存在
function checkFileExists($filePath) {
    if (file_exists($filePath)) {
        logMessage("文件存在: $filePath");
        return true;
    } else {
        logMessage("文件不存在: $filePath", "ERROR");
        return false;
    }
}

// 检查并修复membership_status.php文件
function fixMembershipStatusFile() {
    $filePath = __DIR__ . '/../api/vip/membership_status.php';
    
    if (!checkFileExists($filePath)) {
        logMessage("无法修复不存在的文件", "ERROR");
        return false;
    }
    
    // 读取文件内容
    $content = file_get_contents($filePath);
    if ($content === false) {
        logMessage("无法读取文件内容", "ERROR");
        return false;
    }
    
    // 备份原文件
    $backupPath = $filePath . '.bak.' . date('YmdHis');
    if (file_put_contents($backupPath, $content) === false) {
        logMessage("无法创建备份文件", "ERROR");
        return false;
    }
    logMessage("已创建备份文件: $backupPath");
    
    // 修复1: 添加错误处理
    if (strpos($content, 'ini_set(') === false) {
        $errorHandlingCode = "<?php\n// 设置错误报告\nini_set('display_errors', 0);\nini_set('log_errors', 1);\nini_set('error_log', __DIR__ . '/../../LOGS/php_errors.log');\n\n";
        $content = preg_replace('/^<\?php\s*/i', $errorHandlingCode, $content, 1, $count);
        if ($count > 0) {
            logMessage("已添加错误处理代码");
        } else {
            logMessage("无法添加错误处理代码", "WARNING");
        }
    } else {
        logMessage("文件已包含错误处理代码");
    }
    
    // 修复2: 检查并修复数据库连接函数
    if (strpos($content, 'function getDbConnection') !== false) {
        // 提取数据库连接函数
        if (preg_match('/function\s+getDbConnection\s*\(\)\s*\{([^\}]+)\}/s', $content, $matches)) {
            $dbConnectionFunction = $matches[1];
            
            // 检查是否包含try-catch
            if (strpos($dbConnectionFunction, 'try') === false) {
                $newDbConnectionFunction = "\n    try {\n        $dbConnectionFunction\n    } catch (PDOException \$e) {\n        // 记录错误日志\n        error_log('数据库连接失败: ' . \$e->getMessage());\n        return null;\n    }\n";
                
                $content = str_replace($matches[1], $newDbConnectionFunction, $content);
                logMessage("已修复数据库连接函数，添加try-catch");
            } else {
                logMessage("数据库连接函数已包含try-catch");
            }
        } else {
            logMessage("无法提取数据库连接函数", "WARNING");
        }
    } else {
        logMessage("未找到数据库连接函数", "WARNING");
    }
    
    // 修复3: 检查并修复getMembershipStatus函数
    if (strpos($content, 'function getMembershipStatus') !== false) {
        // 确保函数包含错误处理
        if (strpos($content, 'getMembershipStatus') !== false && strpos($content, 'try') === false) {
            // 在函数开始添加try-catch
            $content = preg_replace(
                '/(function\s+getMembershipStatus\s*\([^\)]*\)\s*\{)/',
                '\1\n    try {',
                $content
            );
            
            // 在函数结束前添加catch
            $content = preg_replace(
                '/(\s*\}\s*\/\/\s*end\s+of\s+getMembershipStatus|\s*\}\s*\/\/\s*getMembershipStatus)/',
                '\n    } catch (Exception $e) {\n        // 记录错误日志\n        error_log(\'获取会员状态失败: \' . $e->getMessage());\n        header(\'Content-Type: application/json\');\n        echo json_encode([\n            \'status\' => \'error\',\n            \'message\' => \'服务器内部错误\',\n            \'error_code\' => 500\n        ]);\n        exit;\n    }\1',
                $content
            );
            
            logMessage("已修复getMembershipStatus函数，添加try-catch");
        } else {
            logMessage("getMembershipStatus函数已包含try-catch或无法修复");
        }
    } else {
        logMessage("未找到getMembershipStatus函数", "WARNING");
    }
    
    // 修复4: 检查并修复表结构检查
    if (strpos($content, 'DESCRIBE users') === false) {
        // 在getDbConnection函数后添加表结构检查函数
        $tableCheckFunction = "\n/**\n * 检查必要的表和字段是否存在\n */\nfunction checkRequiredTables(\$pdo) {\n    try {\n        // 检查users表是否存在\n        \$stmt = \$pdo->query(\"SHOW TABLES LIKE 'users'\");\n        if (\$stmt->rowCount() === 0) {\n            error_log('users表不存在');\n            return false;\n        }\n        \n        // 检查downloads表是否存在\n        \$stmt = \$pdo->query(\"SHOW TABLES LIKE 'downloads'\");\n        if (\$stmt->rowCount() === 0) {\n            error_log('downloads表不存在');\n            return false;\n        }\n        \n        // 检查users表必要字段\n        \$requiredFields = ['id', 'membership_type', 'membership_expiry'];\n        foreach (\$requiredFields as \$field) {\n            \$stmt = \$pdo->query(\"SHOW COLUMNS FROM users LIKE '\$field'\");\n            if (\$stmt->rowCount() === 0) {\n                error_log('users表缺少字段: ' . \$field);\n                return false;\n            }\n        }\n        \n        return true;\n    } catch (PDOException \$e) {\n        error_log('检查表结构失败: ' . \$e->getMessage());\n        return false;\n    }\n}";
        
        // 在getDbConnection函数后插入表结构检查函数
        $content = preg_replace(
            '/(function\s+getDbConnection.*?\})(\s*\/\/\s*end\s+of\s+getDbConnection|\s*\/\/\s*getDbConnection)?/s',
            '\1\2' . $tableCheckFunction,
            $content
        );
        
        // 在getMembershipStatus函数开始处添加表结构检查
        $content = preg_replace(
            '/(function\s+getMembershipStatus\s*\([^\)]*\)\s*\{)(\s*try\s*\{)?/s',
            '\1\2\n    // 检查必要的表和字段是否存在\n    if (!checkRequiredTables($pdo)) {\n        header(\'Content-Type: application/json\');\n        echo json_encode([\n            \'status\' => \'error\',\n            \'message\' => \'数据库结构不完整\',\n            \'error_code\' => 500\n        ]);\n        exit;\n    }\n',
            $content
        );
        
        logMessage("已添加表结构检查函数和调用");
    } else {
        logMessage("文件已包含表结构检查");
    }
    
    // 保存修改后的文件
    if (file_put_contents($filePath, $content) === false) {
        logMessage("无法保存修改后的文件", "ERROR");
        return false;
    }
    
    logMessage("已成功修复文件: $filePath");
    return true;
}

// 检查并修复image_proxy.php文件中的防盗链问题
function fixImageProxyFile() {
    $filePath = __DIR__ . '/../image_proxy.php';
    
    if (!checkFileExists($filePath)) {
        logMessage("无法修复不存在的文件", "ERROR");
        return false;
    }
    
    // 读取文件内容
    $content = file_get_contents($filePath);
    if ($content === false) {
        logMessage("无法读取文件内容", "ERROR");
        return false;
    }
    
    // 备份原文件
    $backupPath = $filePath . '.bak.' . date('YmdHis');
    if (file_put_contents($backupPath, $content) === false) {
        logMessage("无法创建备份文件", "ERROR");
        return false;
    }
    logMessage("已创建备份文件: $backupPath");
    
    // 检查并修复checkReferer函数
    if (strpos($content, 'function checkReferer') !== false) {
        // 提取checkReferer函数
        if (preg_match('/function\s+checkReferer\s*\(\)\s*\{([^\}]+)\}/s', $content, $matches)) {
            $refererFunction = $matches[1];
            
            // 检查是否需要修复
            if (strpos($refererFunction, 'preg_replace') === false) {
                // 添加域名匹配优化
                $newRefererFunction = "\n    // 基本允许的域名\n    \$allowedDomains = [\n        'localhost',\n        '127.0.0.1'\n    ];\n    \n    \$referer = \$_SERVER['HTTP_REFERER'] ?? '';\n    \$host = \$_SERVER['HTTP_HOST'] ?? '';\n    \n    // 如果没有referer，允许直接访问（某些浏览器或应用可能不发送referer）\n    if (empty(\$referer)) {\n        return true;\n    }\n    \n    \$refererHost = parse_url(\$referer, PHP_URL_HOST);\n    if (empty(\$refererHost)) {\n        return true; // 无法解析的referer视为允许\n    }\n    \n    // 检查基本允许域名\n    if (in_array(\$refererHost, \$allowedDomains)) {\n        return true;\n    }\n    \n    // 添加当前主机到允许列表\n    if (!empty(\$host) && \$refererHost === \$host) {\n        return true;\n    }\n    \n    // 提取主域名（去除www前缀）\n    \$mainHost = preg_replace('/^www\\./i', '', \$host);\n    \$mainRefererHost = preg_replace('/^www\\./i', '', \$refererHost);\n    \n    // 检查主域名是否匹配\n    if (\$mainRefererHost === \$mainHost) {\n        return true;\n    }\n    \n    // 检查是否为子域名\n    if (preg_match('/\\.' . preg_quote(\$mainHost, '/') . '\$/', \$refererHost)) {\n        return true;\n    }\n    \n    return false;";
                
                $content = str_replace($matches[1], $newRefererFunction, $content);
                logMessage("已修复checkReferer函数，优化域名匹配");
            } else {
                logMessage("checkReferer函数已包含域名匹配优化");
            }
        } else {
            logMessage("无法提取checkReferer函数", "WARNING");
        }
    } else {
        logMessage("未找到checkReferer函数", "WARNING");
    }
    
    // 保存修改后的文件
    if (file_put_contents($filePath, $content) === false) {
        logMessage("无法保存修改后的文件", "ERROR");
        return false;
    }
    
    logMessage("已成功修复文件: $filePath");
    return true;
}

// 主函数
function main() {
    logMessage("开始修复过程");
    
    // 修复membership_status.php文件
    logMessage("===== 修复membership_status.php文件 =====");
    $result1 = fixMembershipStatusFile();
    
    // 修复image_proxy.php文件
    logMessage("===== 修复image_proxy.php文件 =====");
    $result2 = fixImageProxyFile();
    
    // 输出总结
    logMessage("===== 修复过程完成 =====");
    if ($result1 && $result2) {
        logMessage("所有文件修复成功");
    } else {
        logMessage("部分文件修复失败，请查看日志了解详情", "WARNING");
    }
    
    logMessage("日志文件: $logFile");
}

// 执行主函数
main();

// 输出HTML页面
echo "<!DOCTYPE html>";
echo "<html lang='zh-CN'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>修复脚本执行结果</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; margin: 20px; }";
echo "h1 { color: #333; }";
echo "pre { background-color: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }";
echo ".info { color: #0066cc; }";
echo ".warning { color: #ff9900; }";
echo ".error { color: #cc0000; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>修复脚本执行结果</h1>";
echo "<p>修复日志已保存到: $logFile</p>";

echo "<h2>执行日志</h2>";
echo "<pre>";
if (file_exists($logFile)) {
    $log = file_get_contents($logFile);
    $log = preg_replace('/\[(INFO)\]/', '[<span class="info">INFO</span>]', $log);
    $log = preg_replace('/\[(WARNING)\]/', '[<span class="warning">WARNING</span>]', $log);
    $log = preg_replace('/\[(ERROR)\]/', '[<span class="error">ERROR</span>]', $log);
    echo $log;
} else {
    echo "日志文件不存在";
}
echo "</pre>";

echo "<h2>部署说明</h2>";
echo "<p>请将以下文件上传到线上环境：</p>";
echo "<ol>";
echo "<li>api/vip/membership_status.php（已修复）</li>";
echo "<li>image_proxy.php（已修复）</li>";
echo "</ol>";

echo "<p>同时，请确保线上环境满足以下条件：</p>";
echo "<ol>";
echo "<li>数据库连接参数正确</li>";
echo "<li>数据库包含必要的表和字段</li>";
echo "<li>PHP版本兼容（建议PHP 7.4+）</li>";
echo "<li>创建LOGS目录并确保有写入权限</li>";
echo "</ol>";

echo "</body>";
echo "</html>";
?>
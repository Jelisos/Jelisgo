<?php
// smtp_mailer.php - SMTP邮件发送类（不依赖PHPMailer）

class SMTPMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $secure;
    
    public function __construct($host, $port, $username, $password, $secure = 'ssl') {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->secure = $secure;
    }
    
    public function sendMail($to, $subject, $body, $fromName = '') {
        try {
            // 创建socket连接
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            
            $socket = stream_socket_client(
                ($this->secure === 'ssl' ? 'ssl://' : '') . $this->host . ':' . $this->port,
                $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context
            );
            
            if (!$socket) {
                throw new Exception("无法连接到SMTP服务器: $errstr ($errno)");
            }
            
            // 读取服务器响应
            $this->readResponse($socket);
            
            // EHLO命令
            fwrite($socket, "EHLO localhost\r\n");
            $this->readResponse($socket);
            
            // 认证
            fwrite($socket, "AUTH LOGIN\r\n");
            $this->readResponse($socket);
            
            fwrite($socket, base64_encode($this->username) . "\r\n");
            $this->readResponse($socket);
            
            fwrite($socket, base64_encode($this->password) . "\r\n");
            $this->readResponse($socket);
            
            // 发件人
            fwrite($socket, "MAIL FROM: <{$this->username}>\r\n");
            $this->readResponse($socket);
            
            // 收件人
            fwrite($socket, "RCPT TO: <$to>\r\n");
            $this->readResponse($socket);
            
            // 邮件内容
            fwrite($socket, "DATA\r\n");
            $this->readResponse($socket);
            
            $fromDisplay = $fromName ? $fromName : $this->username;
            $headers = "From: $fromDisplay <{$this->username}>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "\r\n";
            
            fwrite($socket, $headers . $body . "\r\n.\r\n");
            $this->readResponse($socket);
            
            // 退出
            fwrite($socket, "QUIT\r\n");
            $this->readResponse($socket);
            
            fclose($socket);
            return true;
            
        } catch (Exception $e) {
            error_log("SMTP邮件发送失败: " . $e->getMessage());
            return false;
        }
    }
    
    private function readResponse($socket) {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }
}

?>
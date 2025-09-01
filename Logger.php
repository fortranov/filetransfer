<?php
/**
 * Класс для логирования операций загрузки файлов
 */
require_once 'config.php';

class Logger {
    private $logsDir;
    private $maxFileSize;
    private $currentLogFile = null;
    
    public function __construct() {
        $this->logsDir = LOGS_DIR;
        $this->maxFileSize = MAX_LOG_FILE_SIZE;
        
        // Создаем папку для логов если её нет
        if (!is_dir($this->logsDir)) {
            mkdir($this->logsDir, 0755, true);
        }
        
        // Определяем текущий файл лога
        $this->currentLogFile = $this->getCurrentLogFile();
    }
    
    /**
     * Получает путь к текущему файлу лога
     */
    private function getCurrentLogFile() {
        $files = glob($this->logsDir . '*.log');
        
        if (empty($files)) {
            // Если файлов нет, создаем новый
            return $this->logsDir . date('Y-m-d_H-i-s') . '.log';
        }
        
        // Берем последний файл
        $latestFile = end($files);
        
        // Проверяем размер файла
        if (file_exists($latestFile) && filesize($latestFile) >= $this->maxFileSize) {
            // Если файл достиг максимального размера, создаем новый
            return $this->logsDir . date('Y-m-d_H-i-s') . '.log';
        }
        
        return $latestFile;
    }
    
    /**
     * Логирует загрузку файла
     */
    public function logFileUpload($fileName, $fileSize, $folder, $ip = null) {
        if ($ip === null) {
            $ip = $this->getClientIP();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $fileSizeFormatted = $this->formatFileSize($fileSize);
        
        $logEntry = sprintf(
            "[%s] IP: %s | Файл: %s | Размер: %s | Папка: %s\n",
            $timestamp,
            $ip,
            $fileName,
            $fileSizeFormatted,
            $folder
        );
        
        // Проверяем, не нужно ли создать новый файл
        if (file_exists($this->currentLogFile) && filesize($this->currentLogFile) >= $this->maxFileSize) {
            $this->currentLogFile = $this->logsDir . date('Y-m-d_H-i-s') . '.log';
        }
        
        // Записываем в лог
        file_put_contents($this->currentLogFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Логирует удаление файла
     */
    public function logFileDelete($fileName, $folder, $ip = null) {
        if ($ip === null) {
            $ip = $this->getClientIP();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = sprintf(
            "[%s] IP: %s | УДАЛЕНИЕ | Файл: %s | Папка: %s\n",
            $timestamp,
            $ip,
            $fileName,
            $folder
        );
        
        // Проверяем, не нужно ли создать новый файл
        if (file_exists($this->currentLogFile) && filesize($this->currentLogFile) >= $this->maxFileSize) {
            $this->currentLogFile = $this->logsDir . date('Y-m-d_H-i-s') . '.log';
        }
        
        // Записываем в лог
        file_put_contents($this->currentLogFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Логирует ошибки
     */
    public function logError($error, $ip = null) {
        if ($ip === null) {
            $ip = $this->getClientIP();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = sprintf(
            "[%s] IP: %s | ОШИБКА | %s\n",
            $timestamp,
            $ip,
            $error
        );
        
        // Проверяем, не нужно ли создать новый файл
        if (file_exists($this->currentLogFile) && filesize($this->currentLogFile) >= $this->maxFileSize) {
            $this->currentLogFile = $this->logsDir . date('Y-m-d_H-i-s') . '.log';
        }
        
        // Записываем в лог
        file_put_contents($this->currentLogFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Получает IP адрес клиента
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
    
    /**
     * Форматирует размер файла
     */
    private function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Получает список файлов логов
     */
    public function getLogFiles() {
        $files = glob($this->logsDir . '*.log');
        $logFiles = [];
        
        foreach ($files as $file) {
            $logFiles[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file),
                'path' => $file
            ];
        }
        
        // Сортируем по дате изменения (новые сначала)
        usort($logFiles, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $logFiles;
    }
    
    /**
     * Читает содержимое лог файла
     */
    public function readLogFile($filename) {
        $filepath = $this->logsDir . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        return file_get_contents($filepath);
    }
}
?>

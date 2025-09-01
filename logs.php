<?php
session_start();

// Подключаем конфигурацию и класс логирования
require_once 'config.php';
require_once 'Logger.php';
$logger = new Logger();

// Обработка просмотра конкретного лог файла
$selectedLog = null;
if (isset($_GET['view']) && !empty($_GET['view'])) {
    $selectedLog = $logger->readLogFile($_GET['view']);
}

// Получаем список файлов логов
$logFiles = $logger->getLogFiles();

// Функция для форматирования размера файла
function formatFileSize($bytes) {
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр логов - Система загрузки файлов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            padding: 2rem;
        }
        
        .log-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
            margin-bottom: 1rem;
        }
        
        .log-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .log-header {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(52, 152, 219, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(149, 165, 166, 0.3);
        }
        
        .log-content {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .empty-logs {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="main-container">
                    <!-- Заголовок -->
                    <div class="header text-center">
                        <h1 class="mb-3">
                            <i class="fas fa-file-alt me-3"></i>
                            Просмотр логов системы
                        </h1>
                        <p class="mb-0">Мониторинг операций загрузки и удаления файлов</p>
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-outline-light me-2">
                                <i class="fas fa-upload me-2"></i>
                                Загрузка файлов
                            </a>
                            <a href="files.php" class="btn btn-outline-light">
                                <i class="fas fa-folder-open me-2"></i>
                                Просмотр файлов
                            </a>
                        </div>
                    </div>
                    
                    <!-- Основной контент -->
                    <div class="p-4">
                        <!-- Статистика -->
                        <div class="stats-card">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <h3><?php echo count($logFiles); ?></h3>
                                    <p class="mb-0">Файлов логов</p>
                                </div>
                                <div class="col-md-4">
                                    <h3><?php 
                                        $totalSize = 0;
                                        foreach ($logFiles as $logFile) {
                                            $totalSize += $logFile['size'];
                                        }
                                        echo formatFileSize($totalSize);
                                    ?></h3>
                                    <p class="mb-0">Общий размер</p>
                                </div>
                                <div class="col-md-4">
                                    <h3><?php 
                                        $latestLog = reset($logFiles);
                                        echo $latestLog ? date('d.m.Y H:i', $latestLog['modified']) : 'Нет';
                                    ?></h3>
                                    <p class="mb-0">Последнее обновление</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (empty($logFiles)): ?>
                            <div class="empty-logs">
                                <i class="fas fa-file-alt fa-4x mb-3"></i>
                                <h4>Логи отсутствуют</h4>
                                <p>Файлы логов появятся после выполнения операций с файлами</p>
                            </div>
                        <?php else: ?>
                            <!-- Список файлов логов -->
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="mb-3">
                                        <i class="fas fa-list me-2"></i>
                                        Файлы логов
                                    </h5>
                                    <?php foreach ($logFiles as $logFile): ?>
                                        <div class="log-card">
                                            <div class="p-3">
                                                <h6 class="mb-2"><?php echo htmlspecialchars($logFile['name']); ?></h6>
                                                <small class="text-muted">
                                                    Размер: <?php echo formatFileSize($logFile['size']); ?><br>
                                                    Изменен: <?php echo date('d.m.Y H:i', $logFile['modified']); ?>
                                                </small>
                                                <div class="mt-2">
                                                    <a href="logs.php?view=<?php echo urlencode($logFile['name']); ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye me-1"></i>
                                                        Просмотр
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="col-md-8">
                                    <h5 class="mb-3">
                                        <i class="fas fa-eye me-2"></i>
                                        Содержимое лога
                                    </h5>
                                    
                                    <?php if ($selectedLog !== false): ?>
                                        <div class="log-card">
                                            <div class="log-header">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-file-alt me-2"></i>
                                                    <?php echo htmlspecialchars($_GET['view'] ?? 'Выберите файл для просмотра'); ?>
                                                </h6>
                                            </div>
                                            <div class="p-3">
                                                <?php if ($selectedLog !== null): ?>
                                                    <div class="log-content">
                                                        <?php echo htmlspecialchars($selectedLog); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center py-4">
                                                        <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                                                        <p class="text-muted">Выберите файл лога для просмотра</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Файл лога не найден или недоступен для чтения
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

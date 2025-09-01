<?php
session_start();

// Подключаем конфигурацию и класс логирования
require_once 'config.php';
require_once 'Logger.php';

$logger = new Logger();

// Обработка удаления файла
if (isset($_GET['delete']) && isset($_GET['folder'])) {
    $file = $_GET['delete'];
    $folder = $_GET['folder'];
    $filePath = getFullPath($folder) . $file;
    
    if (file_exists($filePath) && unlink($filePath)) {
        $_SESSION['message'] = "Файл '$file' успешно удален";
        $_SESSION['message_type'] = 'success';
        
        // Логируем удаление файла
        $logger->logFileDelete($file, $folder);
    } else {
        $_SESSION['message'] = "Ошибка при удалении файла";
        $_SESSION['message_type'] = 'error';
        
        // Логируем ошибку удаления
        $logger->logError("Ошибка при удалении файла: $file из папки $folder");
    }
    
    header('Location: files.php');
    exit;
}

// Получаем список папок и файлов из сетевого каталога с подпапками
$folders = [];
$basePath = getNetworkPath() . OUTPUT_FOLDER_NAME;

if (is_dir($basePath)) {
    $items = scandir($basePath);
    foreach ($items as $item) {
        if ($item !== '.' && $item !== '..' && is_dir($basePath . DIRECTORY_SEPARATOR . $item)) {
            $folderPath = $basePath . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR;
            
            // Основная папка - ищем файлы в подпапке files
            $folders[$item] = [];
            $uploadSubfolderPath = $folderPath . DIRECTORY_SEPARATOR . UPLOAD_SUBFOLDER;
            if (is_dir($uploadSubfolderPath)) {
                $files = scandir($uploadSubfolderPath);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && is_file($uploadSubfolderPath . DIRECTORY_SEPARATOR . $file)) {
                        $fileInfo = [
                            'name' => $file,
                            'size' => filesize($uploadSubfolderPath . DIRECTORY_SEPARATOR . $file),
                            'modified' => filemtime($uploadSubfolderPath . DIRECTORY_SEPARATOR . $file),
                            'path' => $uploadSubfolderPath . DIRECTORY_SEPARATOR . $file
                        ];
                        $folders[$item][] = $fileInfo;
                    }
                }
            }
            
            // Подпапки - ищем файлы в подпапке files
            $files = scandir($folderPath);
            foreach ($files as $subItem) {
                if ($subItem !== '.' && $subItem !== '..' && is_dir($folderPath . $subItem)) {
                    $subFolderPath = $folderPath . $subItem . DIRECTORY_SEPARATOR;
                    $subFolderKey = $item . DIRECTORY_SEPARATOR . $subItem;
                    $folders[$subFolderKey] = [];
                    
                    $uploadSubfolderPath = $subFolderPath . UPLOAD_SUBFOLDER;
                    if (is_dir($uploadSubfolderPath)) {
                        $subFiles = scandir($uploadSubfolderPath);
                        foreach ($subFiles as $file) {
                            if ($file !== '.' && $file !== '..' && is_file($uploadSubfolderPath . DIRECTORY_SEPARATOR . $file)) {
                                $fileInfo = [
                                    'name' => $file,
                                    'size' => filesize($uploadSubfolderPath . DIRECTORY_SEPARATOR . $file),
                                    'modified' => filemtime($uploadSubfolderPath . DIRECTORY_SEPARATOR . $file),
                                    'path' => $uploadSubfolderPath . DIRECTORY_SEPARATOR . $file
                                ];
                                $folders[$subFolderKey][] = $fileInfo;
                            }
                        }
                    }
                }
            }
        }
    }
}

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

// Функция для получения иконки файла
function getFileIcon($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'pdf':
            return 'fas fa-file-pdf text-danger';
        case 'doc':
        case 'docx':
            return 'fas fa-file-word text-primary';
        case 'xls':
        case 'xlsx':
            return 'fas fa-file-excel text-success';
        case 'txt':
            return 'fas fa-file-alt text-secondary';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return 'fas fa-file-image text-info';
        default:
            return 'fas fa-file text-muted';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр файлов - Система загрузки файлов</title>
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
        
        .folder-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .folder-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .folder-header {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem;
        }
        
        .file-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .file-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.9rem;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
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
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .empty-folder {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
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
                            <i class="fas fa-folder-open me-3"></i>
                            Просмотр загруженных файлов
                        </h1>
                        <p class="mb-0">Управление файлами по папкам</p>
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-primary me-2">
                                <i class="fas fa-arrow-left me-2"></i>
                                Вернуться к загрузке
                            </a>
                            <a href="logs.php" class="btn btn-outline-light">
                                <i class="fas fa-file-alt me-2"></i>
                                Просмотр логов
                            </a>
                        </div>
                    </div>
                    
                    <!-- Основной контент -->
                    <div class="p-4">
                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                                <i class="fas fa-<?php echo $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                                <?php echo $_SESSION['message']; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                        <?php endif; ?>
                        
                        <!-- Статистика -->
                        <div class="stats-card">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <h3><?php echo count($folders); ?></h3>
                                    <p class="mb-0">Папок</p>
                                </div>
                                <div class="col-md-4">
                                    <h3><?php echo array_sum(array_map('count', $folders)); ?></h3>
                                    <p class="mb-0">Всего файлов</p>
                                </div>
                                <div class="col-md-4">
                                    <h3><?php 
                                        $totalSize = 0;
                                        foreach ($folders as $folderFiles) {
                                            foreach ($folderFiles as $file) {
                                                $totalSize += $file['size'];
                                            }
                                        }
                                        echo formatFileSize($totalSize);
                                    ?></h3>
                                    <p class="mb-0">Общий размер</p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (empty($folders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Папки пусты</h4>
                                <p class="text-muted">Загрузите файлы, чтобы они появились здесь</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>
                                    Загрузить файлы
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Список папок -->
                            <?php foreach ($folders as $folderName => $files): ?>
                                <div class="folder-card mb-4">
                                    <div class="folder-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                                                                 <h5 class="mb-0">
                                                     <?php if (strpos($folderName, DIRECTORY_SEPARATOR) !== false): ?>
                                                         <i class="fas fa-folder-open me-2"></i>
                                                         <i class="fas fa-level-down-alt me-1 text-muted"></i>
                                                         <?php 
                                                         $parts = explode(DIRECTORY_SEPARATOR, $folderName);
                                                         echo htmlspecialchars($parts[0]) . ' / ' . htmlspecialchars($parts[1]) . ' / ' . UPLOAD_SUBFOLDER;
                                                         ?>
                                                     <?php else: ?>
                                                         <i class="fas fa-folder me-2"></i>
                                                         <?php echo htmlspecialchars($folderName) . ' / ' . UPLOAD_SUBFOLDER; ?>
                                                     <?php endif; ?>
                                                 </h5>
                                                <small>
                                                    <?php echo count($files); ?> файлов
                                                    <?php 
                                                    $folderSize = 0;
                                                    foreach ($files as $file) {
                                                        $folderSize += $file['size'];
                                                    }
                                                    echo ' • ' . formatFileSize($folderSize);
                                                    ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="p-3">
                                        <?php if (empty($files)): ?>
                                            <div class="empty-folder">
                                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                                <p class="mb-0">Папка пуста</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($files as $file): ?>
                                                <div class="file-item">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center">
                                                                <i class="<?php echo getFileIcon($file['name']); ?> fa-2x me-3"></i>
                                                                <div>
                                                                    <h6 class="mb-1"><?php echo htmlspecialchars($file['name']); ?></h6>
                                                                    <small class="text-muted">
                                                                        <?php echo formatFileSize($file['size']); ?> • 
                                                                        Загружен: <?php echo date('d.m.Y H:i', $file['modified']); ?>
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6 text-end">
                                                            <a href="<?php echo $file['path']; ?>" class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                                                <i class="fas fa-download me-1"></i>
                                                                Скачать
                                                            </a>
                                                            <a href="files.php?delete=<?php echo urlencode($file['name']); ?>&folder=<?php echo urlencode($folderName); ?>" 
                                                               class="btn btn-sm btn-danger"
                                                               onclick="return confirm('Вы уверены, что хотите удалить файл \'<?php echo htmlspecialchars($file['name']); ?>\'?')">
                                                                <i class="fas fa-trash me-1"></i>
                                                                Удалить
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

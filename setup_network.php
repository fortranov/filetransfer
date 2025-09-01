<?php
session_start();

// Подключаем конфигурацию
require_once 'config.php';

// Обработка изменения сетевого пути
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['network_path'])) {
    $newPath = trim($_POST['network_path']);
    
    // Проверяем корректность пути
    if (!empty($newPath)) {
        // Создаем временный конфигурационный файл
        $configContent = "<?php\n";
        $configContent .= "/**\n";
        $configContent .= " * Конфигурационный файл системы загрузки файлов\n";
        $configContent .= " */\n\n";
        $configContent .= "// Настройки сетевого пути\n";
        $configContent .= "define('NETWORK_PATH', '" . addslashes($newPath) . "');\n\n";
        $configContent .= "// Имя папки для хранения файлов в сетевом каталоге\n";
        $configContent .= "define('OUTPUT_FOLDER_NAME', 'filetransfer');\n\n";
        $configContent .= "// Дополнительная подпапка для загрузки файлов (одинаковая для всех папок)\n";
        $configContent .= "define('UPLOAD_SUBFOLDER', 'files');\n\n";
        $configContent .= "// Полный путь к папке для загрузки файлов\n";
        $configContent .= "define('UPLOAD_DIR', NETWORK_PATH . OUTPUT_FOLDER_NAME . '\\\\');\n\n";
        $configContent .= "// Локальная папка для логов (остается на сервере)\n";
        $configContent .= "define('LOGS_DIR', 'logs/');\n\n";
        $configContent .= "// Максимальный размер лог-файла (10MB)\n";
        $configContent .= "define('MAX_LOG_FILE_SIZE', 10485760);\n\n";
        $configContent .= "// Поддерживаемые форматы файлов\n";
        $configContent .= "define('ALLOWED_EXTENSIONS', [\n";
        $configContent .= "    'pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx'\n";
        $configContent .= "]);\n\n";
        $configContent .= "// Максимальный размер загружаемого файла (50MB)\n";
        $configContent .= "define('MAX_UPLOAD_SIZE', 52428800);\n\n";
        $configContent .= "// Настройки безопасности\n";
        $configContent .= "define('ENABLE_IP_LOGGING', true);\n";
        $configContent .= "define('ENABLE_FILE_VALIDATION', true);\n\n";
        $configContent .= "// Функция для проверки доступности сетевого пути\n";
        $configContent .= "function checkNetworkPath() {\n";
        $configContent .= "    \$path = NETWORK_PATH;\n";
        $configContent .= "    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {\n";
        $configContent .= "        return is_dir(\$path) || @mkdir(\$path, 0755, true);\n";
        $configContent .= "    }\n";
        $configContent .= "    return is_dir(\$path) || @mkdir(\$path, 0755, true);\n";
        $configContent .= "}\n\n";
        $configContent .= "// Функция для получения корректного сетевого пути\n";
        $configContent .= "function getNetworkPath() {\n";
        $configContent .= "    \$path = NETWORK_PATH;\n";
        $configContent .= "    return rtrim(\$path, '\\\\/') . DIRECTORY_SEPARATOR;\n";
        $configContent .= "}\n\n";
        $configContent .= "// Функция для создания полного пути к папке\n";
        $configContent .= "function getFullPath(\$folderName) {\n";
        $configContent .= "    return getNetworkPath() . OUTPUT_FOLDER_NAME . DIRECTORY_SEPARATOR . \$folderName . DIRECTORY_SEPARATOR . UPLOAD_SUBFOLDER . DIRECTORY_SEPARATOR;\n";
        $configContent .= "}\n\n";
        $configContent .= "// Функция для проверки и создания необходимых папок\n";
        $configContent .= "function ensureDirectoriesExist() {\n";
        $configContent .= "    \$basePath = getNetworkPath() . OUTPUT_FOLDER_NAME;\n";
        $configContent .= "    if (!is_dir(\$basePath)) {\n";
        $configContent .= "        if (!@mkdir(\$basePath, 0755, true)) {\n";
        $configContent .= "            error_log(\"Не удалось создать сетевую папку: \" . \$basePath);\n";
        $configContent .= "            return false;\n";
        $configContent .= "        }\n";
        $configContent .= "    }\n";
        $configContent .= "    if (!is_dir(LOGS_DIR)) {\n";
        $configContent .= "        if (!@mkdir(LOGS_DIR, 0755, true)) {\n";
        $configContent .= "            error_log(\"Не удалось создать папку для логов: \" . LOGS_DIR);\n";
        $configContent .= "            return false;\n";
        $configContent .= "        }\n";
        $configContent .= "    }\n";
        $configContent .= "    return true;\n";
        $configContent .= "}\n\n";
        $configContent .= "// Функция для получения списка папок из сетевого каталога с подпапками\n";
        $configContent .= "function getNetworkFolders() {\n";
        $configContent .= "    \$basePath = getNetworkPath() . OUTPUT_FOLDER_NAME;\n";
        $configContent .= "    \$folders = [];\n";
        $configContent .= "    if (is_dir(\$basePath)) {\n";
        $configContent .= "        \$items = scandir(\$basePath);\n";
        $configContent .= "        foreach (\$items as \$item) {\n";
        $configContent .= "            if (\$item !== '.' && \$item !== '..' && is_dir(\$basePath . DIRECTORY_SEPARATOR . \$item)) {\n";
        $configContent .= "                \$folderPath = \$basePath . DIRECTORY_SEPARATOR . \$item;\n";
        $configContent .= "                \$folders[\$item] = [\n";
        $configContent .= "                    'name' => \$item,\n";
        $configContent .= "                    'path' => \$item,\n";
        $configContent .= "                    'subfolders' => []\n";
        $configContent .= "                ];\n";
        $configContent .= "                \$subItems = scandir(\$folderPath);\n";
        $configContent .= "                foreach (\$subItems as \$subItem) {\n";
        $configContent .= "                    if (\$subItem !== '.' && \$subItem !== '..' && is_dir(\$folderPath . DIRECTORY_SEPARATOR . \$subItem)) {\n";
        $configContent .= "                        \$folders[\$item]['subfolders'][] = [\n";
        $configContent .= "                            'name' => \$subItem,\n";
        $configContent .= "                            'path' => \$item . DIRECTORY_SEPARATOR . \$subItem\n";
        $configContent .= "                        ];\n";
        $configContent .= "                    }\n";
        $configContent .= "                }\n";
        $configContent .= "            }\n";
        $configContent .= "        }\n";
        $configContent .= "    }\n";
        $configContent .= "    return \$folders;\n";
        $configContent .= "}\n\n";
        $configContent .= "// Функция для получения плоского списка всех папок (для совместимости)\n";
        $configContent .= "function getFlatFolderList() {\n";
        $configContent .= "    \$folders = getNetworkFolders();\n";
        $configContent .= "    \$flatList = [];\n";
        $configContent .= "    foreach (\$folders as \$folder) {\n";
        $configContent .= "        \$flatList[] = \$folder['path'];\n";
        $configContent .= "        foreach (\$folder['subfolders'] as \$subfolder) {\n";
        $configContent .= "            \$flatList[] = \$subfolder['path'];\n";
        $configContent .= "        }\n";
        $configContent .= "    }\n";
        $configContent .= "    return \$flatList;\n";
        $configContent .= "}\n\n";
        $configContent .= "// Функция для проверки прав доступа к сетевой папке\n";
        $configContent .= "function checkNetworkPermissions() {\n";
        $configContent .= "    \$testPath = getNetworkPath() . OUTPUT_FOLDER_NAME . DIRECTORY_SEPARATOR . 'test_' . time();\n";
        $configContent .= "    if (@mkdir(\$testPath, 0755, true)) {\n";
        $configContent .= "        @rmdir(\$testPath);\n";
        $configContent .= "        return true;\n";
        $configContent .= "    }\n";
        $configContent .= "    return false;\n";
        $configContent .= "}\n";
        $configContent .= "?>\n";
        
        // Сохраняем новый конфигурационный файл
        if (file_put_contents('config.php', $configContent)) {
            $_SESSION['message'] = "Сетевой путь успешно обновлен";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = "Ошибка при сохранении конфигурации";
            $_SESSION['message_type'] = 'error';
        }
        
        header('Location: setup_network.php');
        exit;
    }
}

// Проверяем текущее состояние
$networkAvailable = checkNetworkPath();
$networkPermissions = checkNetworkPermissions();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройка сетевого подключения - Система загрузки файлов</title>
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
        
        .status-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="main-container">
                    <!-- Заголовок -->
                    <div class="header text-center">
                        <h1 class="mb-3">
                            <i class="fas fa-network-wired me-3"></i>
                            Настройка сетевого подключения
                        </h1>
                        <p class="mb-0">Конфигурация сетевого пути для хранения файлов</p>
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-outline-light">
                                <i class="fas fa-arrow-left me-2"></i>
                                Вернуться к системе
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
                        
                        <!-- Текущее состояние -->
                        <div class="status-card">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                Текущее состояние
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <strong>Сетевой путь:</strong><br>
                                        <code><?php echo htmlspecialchars(NETWORK_PATH); ?></code>
                                    </p>
                                    <p class="mb-2">
                                        <strong>Доступность:</strong> 
                                        <?php if ($networkAvailable): ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>Доступен
                                            </span>
                                        <?php else: ?>
                                            <span class="text-danger">
                                                <i class="fas fa-times-circle me-1"></i>Недоступен
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Права доступа:</strong> 
                                        <?php if ($networkPermissions): ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>Есть
                                            </span>
                                        <?php else: ?>
                                            <span class="text-danger">
                                                <i class="fas fa-times-circle me-1"></i>Нет
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info">
                                        <h6 class="alert-heading">
                                            <i class="fas fa-lightbulb me-2"></i>
                                            Примеры путей:
                                        </h6>
                                        <small>
                                            <strong>Windows:</strong><br>
                                            <code>\\\\192.168.1.100\\SharedFolder\\</code><br>
                                            <code>\\\\COMPUTER-NAME\\SharedFolder\\</code><br><br>
                                            <strong>Linux/Mac:</strong><br>
                                            <code>/mnt/network/share/</code><br>
                                            <code>/Volumes/NetworkShare/</code>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Форма настройки -->
                        <div class="status-card">
                            <h5 class="mb-3">
                                <i class="fas fa-cog me-2"></i>
                                Изменить сетевой путь
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="network_path" class="form-label">
                                        <i class="fas fa-folder me-2"></i>
                                        Сетевой путь
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="network_path" 
                                           name="network_path" 
                                           value="<?php echo htmlspecialchars(NETWORK_PATH); ?>"
                                           placeholder="\\\\192.168.1.100\\SharedFolder\\"
                                           required>
                                    <div class="form-text">
                                        Укажите полный путь к расшаренной папке в сети
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    Сохранить настройки
                                </button>
                            </form>
                        </div>
                        
                        <!-- Инструкции -->
                        <div class="status-card">
                            <h5 class="mb-3">
                                <i class="fas fa-question-circle me-2"></i>
                                Инструкции по настройке
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Для Windows:</h6>
                                    <ol class="small">
                                        <li>Откройте "Проводник"</li>
                                        <li>В адресной строке введите: <code>\\\\IP-АДРЕС\\ИМЯ-ПАПКИ</code></li>
                                        <li>Скопируйте путь из адресной строки</li>
                                        <li>Добавьте обратный слеш в конце</li>
                                    </ol>
                                </div>
                                <div class="col-md-6">
                                    <h6>Для Linux/Mac:</h6>
                                    <ol class="small">
                                        <li>Смонтируйте сетевую папку</li>
                                        <li>Используйте команду <code>mount</code></li>
                                        <li>Укажите путь в формате: <code>/mnt/network/share/</code></li>
                                        <li>Убедитесь в правах доступа</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Конфигурационный файл системы загрузки файлов
 */

// Настройки сетевого пути
// Примеры форматов:
// Windows: '\\\\192.168.1.100\\SharedFolder\\' или '\\\\COMPUTER-NAME\\SharedFolder\\'
// Linux/Mac: '/mnt/network/share/' или '/Volumes/NetworkShare/'
define('NETWORK_PATH', '\\\\192.168.1.100\\SharedFolder\\');

// Имя папки для хранения файлов в сетевом каталоге
define('OUTPUT_FOLDER_NAME', 'filetransfer');

// Дополнительная подпапка для загрузки файлов (одинаковая для всех папок)
define('UPLOAD_SUBFOLDER', 'files');

// Полный путь к папке для загрузки файлов
define('UPLOAD_DIR', NETWORK_PATH . OUTPUT_FOLDER_NAME . '\\');

// Локальная папка для логов (остается на сервере)
define('LOGS_DIR', 'logs/');

// Максимальный размер лог-файла (10MB)
define('MAX_LOG_FILE_SIZE', 10485760);

// Поддерживаемые форматы файлов
define('ALLOWED_EXTENSIONS', [
    'pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx'
]);

// Максимальный размер загружаемого файла (50MB)
define('MAX_UPLOAD_SIZE', 52428800);

// Настройки безопасности
define('ENABLE_IP_LOGGING', true);
define('ENABLE_FILE_VALIDATION', true);

// Функция для проверки доступности сетевого пути
function checkNetworkPath() {
    $path = NETWORK_PATH;
    
    // Для Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return is_dir($path) || @mkdir($path, 0755, true);
    }
    
    // Для Linux/Mac
    return is_dir($path) || @mkdir($path, 0755, true);
}

// Функция для получения корректного сетевого пути
function getNetworkPath() {
    $path = NETWORK_PATH;
    
    // Убираем лишние слеши в конце
    return rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
}

// Функция для создания полного пути к папке
function getFullPath($folderName) {
    return getNetworkPath() . OUTPUT_FOLDER_NAME . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . UPLOAD_SUBFOLDER . DIRECTORY_SEPARATOR;
}

// Функция для проверки и создания необходимых папок
function ensureDirectoriesExist() {
    $basePath = getNetworkPath() . OUTPUT_FOLDER_NAME;
    
    // Создаем основную папку если её нет
    if (!is_dir($basePath)) {
        if (!@mkdir($basePath, 0755, true)) {
            error_log("Не удалось создать сетевую папку: " . $basePath);
            return false;
        }
    }
    
    // Создаем локальную папку для логов
    if (!is_dir(LOGS_DIR)) {
        if (!@mkdir(LOGS_DIR, 0755, true)) {
            error_log("Не удалось создать папку для логов: " . LOGS_DIR);
            return false;
        }
    }
    
    return true;
}

// Функция для получения списка папок из сетевого каталога с подпапками
function getNetworkFolders() {
    $basePath = getNetworkPath() . OUTPUT_FOLDER_NAME;
    $folders = [];
    
    if (is_dir($basePath)) {
        $items = scandir($basePath);
        foreach ($items as $item) {
            if ($item !== '.' && $item !== '..' && is_dir($basePath . DIRECTORY_SEPARATOR . $item)) {
                $folderPath = $basePath . DIRECTORY_SEPARATOR . $item;
                $folders[$item] = [
                    'name' => $item,
                    'path' => $item,
                    'subfolders' => []
                ];
                
                // Сканируем подпапки на один уровень
                $subItems = scandir($folderPath);
                foreach ($subItems as $subItem) {
                    if ($subItem !== '.' && $subItem !== '..' && is_dir($folderPath . DIRECTORY_SEPARATOR . $subItem)) {
                        $folders[$item]['subfolders'][] = [
                            'name' => $subItem,
                            'path' => $item . DIRECTORY_SEPARATOR . $subItem
                        ];
                    }
                }
            }
        }
    }
    
    return $folders;
}

// Функция для получения плоского списка всех папок (для совместимости)
function getFlatFolderList() {
    $folders = getNetworkFolders();
    $flatList = [];
    
    foreach ($folders as $folder) {
        $flatList[] = $folder['path'];
        foreach ($folder['subfolders'] as $subfolder) {
            $flatList[] = $subfolder['path'];
        }
    }
    
    return $flatList;
}

// Функция для проверки прав доступа к сетевой папке
function checkNetworkPermissions() {
    $testPath = getNetworkPath() . OUTPUT_FOLDER_NAME . DIRECTORY_SEPARATOR . 'test_' . time();
    
    // Пытаемся создать тестовую папку
    if (@mkdir($testPath, 0755, true)) {
        // Удаляем тестовую папку
        @rmdir($testPath);
        return true;
    }
    
    return false;
}
?>

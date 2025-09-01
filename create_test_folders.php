<?php
/**
 * Скрипт для создания тестовых папок с подпапками
 * Запустите этот файл один раз для создания демонстрационной структуры
 */

require_once 'config.php';

echo "<h2>Создание тестовых папок</h2>";

// Создаем основную папку если её нет
$basePath = getNetworkPath() . OUTPUT_FOLDER_NAME;
if (!is_dir($basePath)) {
    if (mkdir($basePath, 0755, true)) {
        echo "✅ Создана основная папка: $basePath<br>";
    } else {
        echo "❌ Ошибка создания основной папки: $basePath<br>";
        exit;
    }
}

// Массив тестовых папок с подпапками
$testFolders = [
    'центр' => ['отдел1', 'отдел2', 'администрация'],
    'деревня' => ['ферма', 'магазин', 'школа'],
    'город' => ['офис', 'склад', 'магазин', 'ресторан']
];

$createdCount = 0;

foreach ($testFolders as $mainFolder => $subFolders) {
    $mainPath = $basePath . DIRECTORY_SEPARATOR . $mainFolder;
    
    // Создаем основную папку
    if (!is_dir($mainPath)) {
        if (mkdir($mainPath, 0755, true)) {
            echo "✅ Создана папка: $mainFolder<br>";
            $createdCount++;
        } else {
            echo "❌ Ошибка создания папки: $mainFolder<br>";
            continue;
        }
    }
    
    // Создаем подпапку files в основной папке
    $filesPath = $mainPath . DIRECTORY_SEPARATOR . UPLOAD_SUBFOLDER;
    if (!is_dir($filesPath)) {
        if (mkdir($filesPath, 0755, true)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;✅ Создана папка для файлов: $mainFolder/" . UPLOAD_SUBFOLDER . "<br>";
            $createdCount++;
        } else {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ Ошибка создания папки для файлов: $mainFolder/" . UPLOAD_SUBFOLDER . "<br>";
        }
    }
    
    // Создаем подпапки
    foreach ($subFolders as $subFolder) {
        $subPath = $mainPath . DIRECTORY_SEPARATOR . $subFolder;
        if (!is_dir($subPath)) {
            if (mkdir($subPath, 0755, true)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;✅ Создана подпапка: $mainFolder/$subFolder<br>";
                $createdCount++;
            } else {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ Ошибка создания подпапки: $mainFolder/$subFolder<br>";
            }
        }
        
        // Создаем подпапку files в каждой подпапке
        $subFilesPath = $subPath . DIRECTORY_SEPARATOR . UPLOAD_SUBFOLDER;
        if (!is_dir($subFilesPath)) {
            if (mkdir($subFilesPath, 0755, true)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;✅ Создана папка для файлов: $mainFolder/$subFolder/" . UPLOAD_SUBFOLDER . "<br>";
                $createdCount++;
            } else {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;❌ Ошибка создания папки для файлов: $mainFolder/$subFolder/" . UPLOAD_SUBFOLDER . "<br>";
            }
        }
    }
}

echo "<br><strong>Итого создано папок: $createdCount</strong><br>";
echo "<br><a href='index.php'>Перейти к системе загрузки файлов</a>";

// Удаляем этот файл после использования
if (file_exists(__FILE__)) {
    unlink(__FILE__);
    echo "<br><small>Файл create_test_folders.php удален</small>";
}
?>

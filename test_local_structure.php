<?php
/**
 * Скрипт для создания локальной тестовой структуры папок
 * Демонстрирует новую функциональность с подпапкой files
 */

echo "<h2>Создание локальной тестовой структуры папок</h2>";

// Создаем локальную тестовую структуру
$testBasePath = 'test_output';
if (!is_dir($testBasePath)) {
    if (mkdir($testBasePath, 0755, true)) {
        echo "✅ Создана тестовая папка: $testBasePath<br>";
    } else {
        echo "❌ Ошибка создания тестовой папки: $testBasePath<br>";
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
    $mainPath = $testBasePath . DIRECTORY_SEPARATOR . $mainFolder;
    
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
    $filesPath = $mainPath . DIRECTORY_SEPARATOR . 'files';
    if (!is_dir($filesPath)) {
        if (mkdir($filesPath, 0755, true)) {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;✅ Создана папка для файлов: $mainFolder/files<br>";
            $createdCount++;
        } else {
            echo "&nbsp;&nbsp;&nbsp;&nbsp;❌ Ошибка создания папки для файлов: $mainFolder/files<br>";
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
        $subFilesPath = $subPath . DIRECTORY_SEPARATOR . 'files';
        if (!is_dir($subFilesPath)) {
            if (mkdir($subFilesPath, 0755, true)) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;✅ Создана папка для файлов: $mainFolder/$subFolder/files<br>";
                $createdCount++;
            } else {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;❌ Ошибка создания папки для файлов: $mainFolder/$subFolder/files<br>";
            }
        }
    }
}

echo "<br><strong>Итого создано папок: $createdCount</strong><br>";

// Создаем несколько тестовых файлов для демонстрации
echo "<br><h3>Создание тестовых файлов:</h3>";

$testFiles = [
    'центр/files/test_document.pdf' => 'Тестовый PDF документ',
    'центр/отдел1/files/report.xlsx' => 'Отчет Excel',
    'деревня/ферма/files/photo.jpg' => 'Фотография фермы',
    'город/офис/files/contract.docx' => 'Контракт Word'
];

foreach ($testFiles as $filePath => $content) {
    $fullPath = $testBasePath . DIRECTORY_SEPARATOR . $filePath;
    $dirPath = dirname($fullPath);
    
    if (!is_dir($dirPath)) {
        mkdir($dirPath, 0755, true);
    }
    
    if (file_put_contents($fullPath, $content)) {
        echo "✅ Создан тестовый файл: $filePath<br>";
    } else {
        echo "❌ Ошибка создания файла: $filePath<br>";
    }
}

echo "<br><strong>Структура создана успешно!</strong><br>";
echo "<br><strong>Структура папок:</strong><br>";
echo "<pre>";
echo "test_output/\n";
foreach ($testFolders as $mainFolder => $subFolders) {
    echo "├── $mainFolder/\n";
    echo "│   ├── files/          ← Файлы загружаются сюда\n";
    foreach ($subFolders as $subFolder) {
        echo "│   ├── $subFolder/\n";
        echo "│   │   └── files/   ← Файлы загружаются сюда\n";
    }
    echo "│   └── ...\n";
}
echo "└── ...\n";
echo "</pre>";

echo "<br><a href='index.php'>Перейти к системе загрузки файлов</a>";
echo "<br><small>Примечание: Для работы с сетевой папкой настройте NETWORK_PATH в config.php</small>";
?>

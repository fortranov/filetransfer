<?php
session_start();

// Подключаем конфигурацию и класс логирования
require_once 'config.php';
require_once 'Logger.php';

// Проверяем доступность сетевого пути
$networkAvailable = checkNetworkPath();
$networkPermissions = checkNetworkPermissions();

$logger = new Logger();

// Обработка загрузки файла
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && isset($_POST['folder'])) {
    $selectedFolder = $_POST['folder'];
    $targetDir = getFullPath($selectedFolder);
    
    // Создаем папку если её нет
    if (!is_dir($targetDir)) {
        if (!@mkdir($targetDir, 0755, true)) {
            $_SESSION['message'] = "Ошибка: Не удалось создать папку в сетевом каталоге";
            $_SESSION['message_type'] = 'error';
            header('Location: index.php');
            exit;
        }
    }
    
    $file = $_FILES['file'];
    $fileName = basename($file['name']);
    $targetPath = $targetDir . $fileName;
    
    // Проверяем, что файл был загружен
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Проверяем расширение файла
        $allowedExtensions = ALLOWED_EXTENSIONS;
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        if (in_array($fileExtension, $allowedExtensions)) {
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $_SESSION['message'] = "Файл '$fileName' успешно загружен в папку '$selectedFolder'";
                $_SESSION['message_type'] = 'success';
                
                // Логируем успешную загрузку
                $logger->logFileUpload($fileName, $file['size'], $selectedFolder);
            } else {
                $_SESSION['message'] = "Ошибка при сохранении файла";
                $_SESSION['message_type'] = 'error';
                
                // Логируем ошибку
                $logger->logError("Ошибка при сохранении файла: $fileName");
            }
        } else {
            $_SESSION['message'] = "Недопустимый тип файла. Разрешены: " . implode(', ', $allowedExtensions);
            $_SESSION['message_type'] = 'error';
            
            // Логируем ошибку недопустимого типа файла
            $logger->logError("Недопустимый тип файла: $fileName (расширение: $fileExtension)");
        }
    } else {
        $_SESSION['message'] = "Ошибка при загрузке файла";
        $_SESSION['message_type'] = 'error';
        
        // Логируем ошибку загрузки
        $logger->logError("Ошибка при загрузке файла: " . $file['error']);
    }
    
    header('Location: index.php');
    exit;
}

// Получаем список папок из сетевого каталога с подпапками
$folders = getNetworkFolders();

// Проверяем и создаем необходимые папки
ensureDirectoriesExist();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система загрузки файлов</title>
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
        
        .upload-area {
            border: 3px dashed #3498db;
            border-radius: 15px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            background: rgba(52, 152, 219, 0.05);
        }
        
        .upload-area:hover {
            border-color: #2980b9;
            background: rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }
        
        .upload-area.dragover {
            border-color: #27ae60;
            background: rgba(39, 174, 96, 0.1);
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
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
        
        .folder-info {
            background: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        
        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.3);
        }
        
        .selected-file {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
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
                            <i class="fas fa-cloud-upload-alt me-3"></i>
                            Система загрузки файлов
                        </h1>
                        <p class="mb-0">Выберите папку и загрузите необходимые документы</p>
                        <div class="mt-3">
                            <a href="files.php" class="btn btn-outline-light me-2">
                                <i class="fas fa-folder-open me-2"></i>
                                Просмотр файлов
                            </a>
                            <a href="logs.php" class="btn btn-outline-light me-2">
                                <i class="fas fa-file-alt me-2"></i>
                                Просмотр логов
                            </a>
                            <a href="setup_network.php" class="btn btn-outline-light">
                                <i class="fas fa-cog me-2"></i>
                                Настройка сети
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
                        
                        <!-- Информация о сетевом подключении -->
                        <div class="folder-info">
                            <h5 class="mb-2">
                                <i class="fas fa-network-wired me-2"></i>
                                Состояние сетевого подключения
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">
                                        <strong>Сетевой путь:</strong> 
                                        <span class="text-muted"><?php echo htmlspecialchars(NETWORK_PATH); ?></span>
                                    </p>
                                    <p class="mb-1">
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
                                    <p class="mb-1">
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
                                    <h6 class="mb-2">
                                        <i class="fas fa-folder-open me-2"></i>
                                        Доступные папки
                                    </h6>
                                    <?php if (empty($folders)): ?>
                                        <p class="mb-0 text-muted">Папки будут созданы автоматически при первой загрузке</p>
                                    <?php else: ?>
                                        <p class="mb-0">
                                            <strong>Найдено папок:</strong> 
                                            <?php 
                                            $totalFolders = 0;
                                            foreach ($folders as $folder) {
                                                $totalFolders++;
                                                $totalFolders += count($folder['subfolders']);
                                            }
                                            echo $totalFolders;
                                            ?>
                                            <br>
                                                                                         <small class="text-muted">
                                                 <?php 
                                                 $folderNames = [];
                                                 foreach ($folders as $folder) {
                                                     $folderNames[] = $folder['name'] . '/' . UPLOAD_SUBFOLDER;
                                                     foreach ($folder['subfolders'] as $subfolder) {
                                                         $folderNames[] = $folder['name'] . '/' . $subfolder['name'] . '/' . UPLOAD_SUBFOLDER;
                                                     }
                                                 }
                                                 echo implode(', ', $folderNames);
                                                 ?>
                                             </small>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Форма загрузки -->
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="upload-area" id="uploadArea">
                                <div class="mb-4">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <h4>Выберите файл для загрузки</h4>
                                    <p class="text-muted">Перетащите файл сюда или нажмите кнопку ниже</p>
                                </div>
                                
                                <div class="file-input-wrapper mb-3">
                                    <input type="file" name="file" id="fileInput" class="file-input" required>
                                    <label for="fileInput" class="file-input-label">
                                        <i class="fas fa-file-upload me-2"></i>
                                        Выбрать файл
                                    </label>
                                </div>
                                
                                <div class="selected-file" id="selectedFile"></div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <label for="folder" class="form-label">
                                        <i class="fas fa-folder me-2"></i>
                                        Выберите папку назначения
                                    </label>
                                    <select name="folder" id="folder" class="form-select" required>
                                        <option value="">Выберите папку...</option>
                                        <?php foreach ($folders as $folder): ?>
                                                                                         <optgroup label="<?php echo htmlspecialchars($folder['name']); ?>">
                                                 <option value="<?php echo htmlspecialchars($folder['path']); ?>">
                                                     📁 <?php echo htmlspecialchars($folder['name']); ?> → 📂 <?php echo UPLOAD_SUBFOLDER; ?>
                                                 </option>
                                                 <?php foreach ($folder['subfolders'] as $subfolder): ?>
                                                     <option value="<?php echo htmlspecialchars($subfolder['path']); ?>">
                                                         &nbsp;&nbsp;&nbsp;&nbsp;📂 <?php echo htmlspecialchars($subfolder['name']); ?> → 📂 <?php echo UPLOAD_SUBFOLDER; ?>
                                                     </option>
                                                 <?php endforeach; ?>
                                             </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-upload me-2"></i>
                                        Загрузить
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <!-- Информация о поддерживаемых форматах -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Поддерживаемые форматы файлов
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Документы:</strong> PDF, DOC, DOCX, TXT, XLS, XLSX
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <strong>Изображения:</strong> JPG, JPEG, PNG, GIF
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Drag and drop функциональность
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const selectedFile = document.getElementById('selectedFile');

        // Обработка выбора файла
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                selectedFile.innerHTML = `
                    <i class="fas fa-file me-2"></i>
                    <strong>Выбран файл:</strong> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                `;
            }
        });

        // Drag and drop события
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('dragover');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('dragover');
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                fileInput.files = files;
                const file = files[0];
                selectedFile.innerHTML = `
                    <i class="fas fa-file me-2"></i>
                    <strong>Выбран файл:</strong> ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                `;
            }
        }

        // Валидация формы
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            const folder = document.getElementById('folder').value;
            
            if (!file) {
                e.preventDefault();
                alert('Пожалуйста, выберите файл для загрузки');
                return;
            }
            
            if (!folder) {
                e.preventDefault();
                alert('Пожалуйста, выберите папку назначения');
                return;
            }
        });
    </script>
</body>
</html>

<?php
// 数据库配置
$servername = "mysql.sqlpub.com:3306";
$username = "yangan";
$password = "oupypBSeolQEhDiU";
$dbname = "yangan";

// 文件目录配置
$targetDir = "D:/图片/0529/";
$baseUrl = "https://photo-7bi.pages.dev/0529/";

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 获取数据库已有链接
$existingLinks = [];
$result = $conn->query("SELECT link FROM gzy");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $existingLinks[] = $row['link'];
    }
}

// 获取所有文件列表
$files = [];
if ($handle = opendir($targetDir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $fileLink = $baseUrl . $entry;
            
            // 过滤已存在的文件
            if (!in_array($fileLink, $existingLinks)) {
                $filePath = $targetDir . $entry;
                $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                $type = 'file';
                
                $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $videoExt = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
                
                if (in_array($ext, $imageExt)) {
                    $type = 'image';
                } elseif (in_array($ext, $videoExt)) {
                    $type = 'video';
                }
                
                $files[] = [
                    'name' => pathinfo($entry, PATHINFO_FILENAME),
                    'link' => $fileLink,
                    'type' => $type,
                    'file_time' => date('Y-m-d H:i:s', filemtime($filePath))
                ];
            }
        }
    }
    closedir($handle);
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $start = intval($_POST['start_row']);
    $end = intval($_POST['end_row']);
    $fileList = json_decode($_POST['file_list'], true);
    $currentTime = date('Y-m-d H:i:s');

    if ($start < 1 || $end > count($fileList) || $start > $end) {
        die("无效的行数范围");
    }

    $stmt = $conn->prepare("INSERT INTO gzy (name, link, type, created_at) VALUES (?, ?, ?, ?)");
    $count = 0;

    for ($i = $start - 1; $i < $end; $i++) {
        $file = $fileList[$i];
        
        $check = $conn->prepare("SELECT id FROM gzy WHERE link = ?");
        $check->bind_param("s", $file['link']);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows === 0) {
            $stmt->bind_param("ssss", $file['name'], $file['link'], $file['type'], $currentTime);
            $stmt->execute();
            $count++;
        }
        $check->close();
    }

    echo "<script>alert('成功上传 {$count} 个文件'); window.location.href=window.location.href;</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>批量文件上传</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f8f9fa;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
        }
        .preview-cell img, .preview-cell video {
            max-width: 150px;
            max-height: 100px;
            object-fit: contain;
        }
        .controls {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .control-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        input[type="number"] {
            padding: 8px;
            width: 80px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>批量文件上传</h1>
        <p>共发现 <?php echo count($files); ?> 个文件</p>
        
        <form method="post">
            <table>
                <thead>
                    <tr>
                        <th>行号</th>
                        <th>文件名</th>
                        <th>类型</th>
                        <th>文件时间</th>
                        <th>预览</th>
                        <th>链接</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $index => $file): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($file['name']); ?></td>
                        <td><?php echo $file['type']; ?></td>
                        <td><?php echo $file['file_time']; ?></td>
                        <td class="preview-cell">
                            <?php if ($file['type'] === 'image'): ?>
                                <img src="<?php echo $file['link']; ?>" alt="预览">
                            <?php elseif ($file['type'] === 'video'): ?>
                                <video controls muted playsinline>
                                    <source src="<?php echo $file['link']; ?>" type="video/mp4">
                                </video>
                            <?php else: ?>
                                <span>无预览</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($file['link']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="controls">
                <div class="control-group">
                    <label>开始行号：</label>
                    <input type="number" name="start_row" min="1" max="<?php echo count($files); ?>" required>
                </div>
                <div class="control-group">
                    <label>结束行号：</label>
                    <input type="number" name="end_row" min="1" max="<?php echo count($files); ?>" required>
                </div>
                <button type="submit" name="submit">确认上传选中文件</button>
            </div>

            <input type="hidden" name="file_list" value="<?php echo htmlspecialchars(json_encode($files)); ?>">
        </form>
    </div>

    <script>
        // 自动设置行号范围
        document.addEventListener('DOMContentLoaded', function() {
            const startInput = document.querySelector('input[name="start_row"]');
            const endInput = document.querySelector('input[name="end_row"]');
            const maxValue = <?php echo count($files); ?>;

            if (startInput && endInput) {
                startInput.max = maxValue;
                endInput.max = maxValue;
                endInput.value = maxValue;

                startInput.addEventListener('change', function() {
                    endInput.min = this.value;
                });

                endInput.addEventListener('change', function() {
                    startInput.max = this.value;
                });
            }
        });
    </script>
</body>
</html>
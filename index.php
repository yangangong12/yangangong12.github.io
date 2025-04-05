<?php
// 数据库配置
$servername = "mysql.sqlpub.com:3306";
$username = "yangan";
$password = "oupypBSeolQEhDiU";
$dbname = "yangan";

// 文件目录配置
$targetDir = "D:/图片/0529/"  // 修改为实际文件存放目录
$baseUrl = "https://photo-7bi.pages.dev/0529/";

// 创建数据库连接
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $start = intval($_POST['start_row']);
    $end = intval($_POST['end_row']);
    $fileList = json_decode($_POST['file_list'], true);
    $currentTime = date('Y-m-d H:i:s'); // 获取当前时间

    // 验证行数范围
    if ($start < 1 || $end > count($fileList) || $start > $end) {
        die("无效的行数范围");
    }

    // 准备插入语句（包含created_at字段）
    $stmt = $conn->prepare("INSERT INTO gzy (name, link, type, created_at) VALUES (?, ?, ?, ?)");
    $count = 0;

    for ($i = $start - 1; $i < $end; $i++) {
        $file = $fileList[$i];
        
        // 检查是否已存在
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

    echo "成功上传 {$count} 个文件";
    exit;
}

// 获取文件列表
$files = [];
if ($handle = opendir($targetDir)) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $filePath = $targetDir . $entry;
            $type = 'file';
            
            // 判断文件类型
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $videoExt = ['mp4', 'mov', 'avi', 'mkv', 'webm'];
            
            if (in_array($ext, $imageExt)) {
                $type = 'image';
            } elseif (in_array($ext, $videoExt)) {
                $type = 'video';
            }
            
            // 获取文件修改时间作为参考时间
            $fileTime = date('Y-m-d H:i:s', filemtime($filePath));
            
            $files[] = [
                'name' => pathinfo($entry, PATHINFO_FILENAME),
                // 正确写法（只拼接文件名）
'link' => $baseUrl . $entry, // $entry是文件名
                'type' => $type,
                'file_time' => $fileTime  // 用于显示，实际使用当前时间
            ];
        }
    }
    closedir($handle);
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

        .time-options {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .time-option {
            margin: 0.5rem 0;
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
                                <video controls>
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

            <div class="time-options">
                <h3>时间设置</h3>
                <div class="time-option">
                    <input type="radio" id="time_current" name="time_option" value="current" checked>
                    <label for="time_current">使用当前时间 (<?php echo date('Y-m-d H:i:s'); ?>)</label>
                </div>
                <div class="time-option">
                    <input type="radio" id="time_file" name="time_option" value="file">
                    <label for="time_file">使用文件修改时间</label>
                </div>
            </div>

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
        // 可以添加JavaScript来增强交互性
        document.addEventListener('DOMContentLoaded', function() {
            // 示例：自动设置结束行号为最大值
            const endRowInput = document.querySelector('input[name="end_row"]');
            if (endRowInput) {
                endRowInput.value = endRowInput.max;
            }
        });
    </script>
</body>
</html>
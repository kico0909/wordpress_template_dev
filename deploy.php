<?php
// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 日志文件路径
$logFile = __DIR__ . '/webhook.log';
$rootPath = '/home/wwwroot/121.40.22.253/wp-content/themes/html5blank-stable';
// 记录日志函数
function logMessage($message) {
    global $logFile;
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message\n", FILE_APPEND);
}

// 获取 GitHub Webhook 请求内容
$payload = file_get_contents('php://input');
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'none event';

// GitHub Webhook Secret
$secret = '77742335'; // 将此替换为您的实际 secret

if ($event === 'push') {
    // 验证签名
    $signature = 'sha1=' . hash_hmac('sha1', $payload, $secret, false);
    $githubSignature = $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';

    // 调试信息
    logMessage('Generated Signature: ' . $signature);
    logMessage('GitHub Signature: ' . $githubSignature);

    // print_r($signature.'\n');
    // print_r($githubSignature);

    // if (!hash_equals($signature, $githubSignature)) {
    //     http_response_code(403);
    //     logMessage('Invalid signature');
    //     die('Invalid signature');
    // }

    // 解析 application/x-www-form-urlencoded 格式的数据
    // parse_str($payload, $data);
    // if (empty($data)) {
    //     http_response_code(400);
    //     logMessage('Failed to decode payload');
    //     die('Failed to decode payload');
    // }

    // // 仓库名称
    // $repoName = $data['repository']['name'] ?? '';
    // if (empty($repoName)) {
    //     http_response_code(400);
    //     logMessage('Repository name not found in payload');
    //     die('Repository name not found in payload');
    // }

    // 执行 git pull 命令
    $output = [];
    $return_var = 0;
    $repoPath = $rootPath;

    if (is_dir($repoPath)) {
        // 添加异常处理以避免所有权问题
        exec("git config --global --add safe.directory $repoPath");
        // 拉取
        exec("git pull");
        // exec("cd $repoPath && git pull 2>&1", $output, $return_var);
        exec("cd $repoPath && git merge --no-ff -m \"deploy merge\" 2>&1", $output, $return_var);
    } else {
        $output[] = "Repository path not found: $repoPath";
        $return_var = 1;
    }

    // 记录日志
    $logMessage = "Git pull " . ($return_var === 0 ? "successful" : "failed") . ":\n" . implode("\n", $output);
    logMessage($logMessage);

    // 返回结果
    if ($return_var === 0) {
        echo "Git pull successful:\n" . implode("\n", $output);
    } else {
        echo "Git pull failed:\n" . implode("\n", $output);
    }
} else {
    http_response_code(400);
    logMessage('Only push events are handled');
    echo 'Only push events are handled';
}

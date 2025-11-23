<?php
/**
 * GitHub Webhook Auto-Deploy Script
 * 
 * Setup:
 * 1. Upload file này lên: /home/mapselli676e/domains/diavatly.cloud/public_html/iso2/deploy.php
 * 2. Tạo secret key (ví dụ: 'your-secret-key-here-change-this')
 * 3. Vào GitHub repo → Settings → Webhooks → Add webhook
 *    - Payload URL: https://diavatly.cloud/iso2/deploy.php
 *    - Content type: application/json
 *    - Secret: [secret key của bạn]
 *    - Events: Just the push event
 * 4. Test bằng cách push code → tự động deploy
 */

// Đổi secret này thành key riêng của bạn
define('SECRET_KEY', 'your-secret-key-here-change-this');

// Path đến repo trên server
define('REPO_PATH', '/home/mapselli676e/domains/diavatly.cloud/public_html/iso2');

// Verify GitHub webhook signature
function verifySignature($payload, $signature) {
    $hash = 'sha256=' . hash_hmac('sha256', $payload, SECRET_KEY);
    return hash_equals($hash, $signature);
}

// Get payload
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Verify signature nếu có secret
if (SECRET_KEY !== 'your-secret-key-here-change-this') {
    if (!verifySignature($payload, $signature)) {
        http_response_code(403);
        die('Invalid signature');
    }
}

// Parse payload
$data = json_decode($payload, true);

// Chỉ deploy khi push vào main branch
if (isset($data['ref']) && $data['ref'] === 'refs/heads/main') {
    // Log deployment
    $logFile = REPO_PATH . '/deploy.log';
    $timestamp = date('Y-m-d H:i:s');
    
    // Change to repo directory và pull
    chdir(REPO_PATH);
    
    // Git pull
    $output = [];
    $return = 0;
    exec('git pull origin main 2>&1', $output, $return);
    
    // Log result
    $logMessage = sprintf(
        "[%s] Deploy triggered by %s\nCommit: %s\nMessage: %s\nResult: %s\nOutput: %s\n\n",
        $timestamp,
        $data['pusher']['name'] ?? 'Unknown',
        $data['head_commit']['id'] ?? 'Unknown',
        $data['head_commit']['message'] ?? 'Unknown',
        $return === 0 ? 'SUCCESS' : 'FAILED',
        implode("\n", $output)
    );
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $return === 0 ? 'success' : 'error',
        'message' => $return === 0 ? 'Deployed successfully' : 'Deployment failed',
        'output' => $output
    ]);
} else {
    echo json_encode([
        'status' => 'skipped',
        'message' => 'Not main branch, skipping deployment'
    ]);
}
?>

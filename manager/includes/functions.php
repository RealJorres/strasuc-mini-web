<?php
function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function displayAlert($message, $type = 'info') {
    $class = match($type) {
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        default => 'alert-info'
    };
    
    return "<div class='alert $class alert-dismissible fade show' role='alert'>
        $message
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}

function formatDate($dateString) {
    return date('M j, Y g:i A', strtotime($dateString));
}
?>
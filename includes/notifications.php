<?php
function sendNotification($pdo, $userId, $title, $message) {
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)"
    );
    return $stmt->execute([$userId, $title, $message]);
}

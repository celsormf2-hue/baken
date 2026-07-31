<?php require_once __DIR__ . '/lib/security.php'; start_secure_session(); $_SESSION = []; session_destroy(); redirect('/login.php');

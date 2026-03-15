<?php

declare(strict_types=1);

function attempt_login(string $username, string $password): bool
{
    $user = query_one('SELECT * FROM users WHERE username = :username AND active = 1', ['username' => $username]);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    execute_sql('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $user['id']]);
    audit_log((int) $user['id'], 'user', (int) $user['id'], 'login');
    return true;
}

function remember_post_login_redirect(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return;
    }

    $requestUri = trim((string) ($_SERVER['REQUEST_URI'] ?? ''));
    if ($requestUri === '' || str_contains($requestUri, "\r") || str_contains($requestUri, "\n")) {
        return;
    }

    if (str_contains($requestUri, 'page=login')) {
        return;
    }

    $_SESSION['post_login_redirect'] = $requestUri;
}

function consume_post_login_redirect(): string
{
    $target = trim((string) ($_SESSION['post_login_redirect'] ?? ''));
    unset($_SESSION['post_login_redirect']);

    if ($target === '' || str_contains($target, "\r") || str_contains($target, "\n") || str_contains($target, 'page=login')) {
        return 'index.php';
    }

    if (preg_match('#^https?://#i', $target)) {
        return 'index.php';
    }

    return $target;
}

function logout_user(): void
{
    if (!empty($_SESSION['user_id'])) {
        audit_log((int) $_SESSION['user_id'], 'user', (int) $_SESSION['user_id'], 'logout');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array
{
    static $user = false;

    if ($user !== false) {
        return $user;
    }

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $user = query_one('SELECT * FROM users WHERE id = :id', ['id' => $_SESSION['user_id']]);
    return $user;
}

function require_login(): void
{
    if (!current_user()) {
        remember_post_login_redirect();
        flash('error', 'Zaloguj sie, aby kontynuowac.');
        redirect_to('index.php?page=login');
    }

    if (current_user()['must_change_password'] && (($_GET['page'] ?? 'home') !== 'change-password')) {
        flash('error', 'To konto wymaga zmiany hasla przed dalsza praca.');
        redirect_to('index.php?page=change-password');
    }
}

function require_user_management(): void
{
    require_login();
    if (!can_manage_users()) {
        flash('error', 'Brak uprawnień do zarządzania użytkownikami.');
        redirect_to('index.php');
    }
}

function require_role_management(): void
{
    require_login();
    if (!can_manage_roles()) {
        flash('error', 'Brak uprawnień do zarządzania rolami.');
        redirect_to('index.php');
    }
}

function require_audit_history_access(): void
{
    require_login();
    if (!can_view_audit_history()) {
        flash('error', 'Brak uprawnień do historii zdarzeń.');
        redirect_to('index.php');
    }
}

function require_editor(): void
{
    require_login();
    if (!can_edit_records()) {
        flash('error', 'Brak uprawnień do edycji rekordów.');
        redirect_to('index.php');
    }
}

function require_superadmin(): void
{
    require_login();
    if (!can_manage_dictionaries()) {
        flash('error', 'Brak uprawnień do obsługi słowników systemowych.');
        redirect_to('index.php');
    }
}

function require_settings_access(): void
{
    require_login();
    if (!can_manage_settings()) {
        flash('error', 'Brak uprawnień do konfiguracji systemu.');
        redirect_to('index.php');
    }
}


function require_csv_export(): void
{
    require_login();
    if (!can_export_csv()) {
        flash('error', 'Brak uprawnień do eksportu CSV.');
        redirect_to('index.php');
    }
}

function require_csv_import(): void
{
    require_login();
    if (!can_import_csv()) {
        flash('error', 'Brak uprawnień do importu CSV.');
        redirect_to('index.php');
    }
}

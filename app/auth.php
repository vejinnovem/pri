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

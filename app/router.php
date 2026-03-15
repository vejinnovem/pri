<?php

declare(strict_types=1);

// Główny router aplikacji: obsługuje akcje i renderuje właściwe widoki dla parametru `page`.
$page = $_GET['page'] ?? 'home';

// Sekcja logowania i wylogowania użytkownika.
if ($page === 'login') {
    if (current_user()) {
        redirect_to('index.php');
    }

    if (is_post()) {
        verify_csrf();
        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (attempt_login($username, $password)) {
            flash('success', 'Zalogowano.');
            redirect_to('index.php');
        }
        flash('error', 'Nieprawidłowy login lub hasło.');
        redirect_to('index.php?page=login');
    }

    render_header('Logowanie');
    ?>
    <section class="panel login-panel">
        <h2>Logowanie</h2>
        <p class="muted">Zaloguj się, aby przeglądać lub edytować inwentarz Press Reset.</p>
        <form method="post">
            <?= csrf_field() ?>
            <div>
                <label for="username">Login</label>
                <input id="username" name="username" required autocomplete="username">
            </div>
            <div>
                <label for="password">Hasło</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
            </div>
            <div class="actions" style="margin-top: 14px;">
                <button class="button primary" type="submit">Zaloguj się</button>
            </div>
        </form>
        <p class="muted" style="margin-top: 18px;">Alpha seed: konto SuperAdmin zostało założone w bazie.</p>
    </section>
    <?php
    render_footer();
    exit;
}

if ($page === 'logout') {
    require_login();
    if (!is_post()) {
        redirect_to('index.php');
    }
    verify_csrf();
    logout_user();
    session_start();
        flash('success', 'Wylogowano.');
        redirect_to('index.php?page=login');
}

require_login();

// Sekcja samoobsługi konta (zmiana hasła).
if ($page === 'change-password') {
    $user = current_user();
    $mustChangePassword = (bool) ($user['must_change_password'] ?? false);

    if (is_post()) {
        verify_csrf();
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        $user = current_user();
        $mustChangePassword = (bool) ($user['must_change_password'] ?? false);

        if (!$user) {
            flash('error', 'Nie znaleziono aktywnego użytkownika.');
            redirect_to('index.php?page=login');
        }
        if (!$mustChangePassword && !password_verify($current, $user['password_hash'])) {
            flash('error', 'Aktualne hasło jest nieprawidłowe.');
            redirect_to('index.php?page=change-password');
        }
        if (strlen($new) < 10) {
            flash('error', 'Nowe hasło musi mieć co najmniej 10 znaków.');
            redirect_to('index.php?page=change-password');
        }
        if ($new !== $confirm) {
            flash('error', 'Nowe hasła nie są zgodne.');
            redirect_to('index.php?page=change-password');
        }

        execute_sql(
            'UPDATE users SET password_hash = :password_hash, must_change_password = 0 WHERE id = :id',
            ['password_hash' => password_hash($new, PASSWORD_DEFAULT), 'id' => $user['id']]
        );
        audit_log((int) $user['id'], 'user', (int) $user['id'], 'change_password');
        flash('success', 'Hasło zostało zmienione.');
        redirect_to('index.php');
    }

    render_header('Zmiana hasła');
    ?>
    <section class="panel">
        <h2>Zmiana hasła</h2>
        <?php if ($mustChangePassword): ?>
            <p class="muted">To konto wymaga ustawienia nowego hasła przed dalszą pracą. Nie trzeba ponownie wpisywać hasła tymczasowego.</p>
        <?php endif; ?>
        <form method="post" class="form-grid">
            <?= csrf_field() ?>
            <?php if (!$mustChangePassword): ?>
                <div>
                    <label for="current_password">Aktualne hasło</label>
                    <input id="current_password" type="password" name="current_password" required>
                </div>
            <?php endif; ?>
            <div>
                <label for="new_password">Nowe hasło</label>
                <input id="new_password" type="password" name="new_password" required>
            </div>
            <div>
                <label for="confirm_password">Powtórz nowe hasło</label>
                <input id="confirm_password" type="password" name="confirm_password" required>
            </div>
            <div style="flex-basis: 100%;">
                <button class="button primary" type="submit">Zapisz hasło</button>
            </div>
        </form>
    </section>
    <?php
    render_footer();
    exit;
}

// Sekcja administracji użytkownikami.
if ($page === 'users') {
    require_user_management();

    if (is_post()) {
        verify_csrf();
        $action = $_POST['action'] ?? '';
        $current = current_user();

        if ($action === 'create') {
            $username = trim($_POST['username'] ?? '');
            $displayName = trim($_POST['display_name'] ?? '');
            $role = trim((string) ($_POST['role'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $allowedRoles = manageable_role_values(can_manage_root_roles());

            if ($username === '') {
                flash('error', 'Login użytkownika jest wymagany.');
                redirect_to('index.php?page=users');
            }
            if ($displayName === '') {
                flash('error', 'Nazwa wyświetlana jest wymagana.');
                redirect_to('index.php?page=users');
            }
            if ($role === '') {
                flash('error', 'Wybierz rolę dla nowego użytkownika.');
                redirect_to('index.php?page=users');
            }
            if (strlen($password) < 10) {
                flash('error', 'Hasło startowe musi mieć co najmniej 10 znaków.');
                redirect_to('index.php?page=users');
            }
            if (!in_array($role, $allowedRoles, true)) {
                flash('error', 'Nie możesz nadać tej roli nowemu użytkownikowi.');
                redirect_to('index.php?page=users');
            }

            try {
                execute_sql(
                    'INSERT INTO users (username, display_name, password_hash, role, active, must_change_password) VALUES (:username, :display_name, :password_hash, :role, 1, 1)',
                    [
                        'username' => $username,
                        'display_name' => $displayName,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                    ]
                );
            } catch (PDOException $exception) {
                flash('error', is_unique_violation($exception) ? 'Login użytkownika już istnieje.' : 'Nie udało się utworzyć użytkownika.');
                redirect_to('index.php?page=users');
            }
            $newId = (int) db()->lastInsertId();
            audit_log((int) $current['id'], 'user', $newId, 'create_user', ['username' => $username, 'role' => $role]);
            flash('success', 'Użytkownik został utworzony.');
            redirect_to('index.php?page=users');
        }

        if ($action === 'reset-password') {
            $targetId = (int) ($_POST['user_id'] ?? 0);
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $targetUser = query_one(
                'SELECT u.id, u.role, r.can_manage_root_roles AS role_can_manage_root_roles
                 FROM users u
                 LEFT JOIN roles r ON r.slug = u.role
                 WHERE u.id = :id',
                ['id' => $targetId]
            );
            if ($targetId < 1 || strlen($newPassword) < 10) {
                flash('error', 'Niepoprawne dane resetu hasła.');
                redirect_to('index.php?page=users');
            }
            if (!$targetUser) {
                flash('error', 'Nie znaleziono użytkownika.');
                redirect_to('index.php?page=users');
            }
            if ((int) ($targetUser['role_can_manage_root_roles'] ?? 0) === 1 && !can_manage_root_roles()) {
                flash('error', 'Tylko Root SuperAdmin może resetować hasło konta z tą rolą.');
                redirect_to('index.php?page=users');
            }
            execute_sql(
                'UPDATE users SET password_hash = :password_hash, must_change_password = 1 WHERE id = :id',
                ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $targetId]
            );
            audit_log((int) $current['id'], 'user', $targetId, 'reset_password');
            flash('success', 'Hasło zostało zresetowane.');
            redirect_to('index.php?page=users');
        }

        if ($action === 'update-profile') {
            $targetId = (int) ($_POST['user_id'] ?? 0);
            $username = trim((string) ($_POST['username'] ?? ''));
            $displayName = trim((string) ($_POST['display_name'] ?? ''));
            $role = (string) ($_POST['role'] ?? '');
            $allowedRoles = manageable_role_values(can_manage_root_roles());
            $targetUser = query_one(
                'SELECT u.id, u.role, r.can_manage_root_roles AS role_can_manage_root_roles
                 FROM users u
                 LEFT JOIN roles r ON r.slug = u.role
                 WHERE u.id = :id',
                ['id' => $targetId]
            );
            if ($targetId < 1 || $username === '' || $displayName === '' || !$targetUser) {
                flash('error', 'Login, nazwa wyświetlana i poprawny użytkownik są wymagane.');
                redirect_to('index.php?page=users');
            }
            if (!in_array($role, $allowedRoles, true)) {
                flash('error', 'Nie możesz nadać tej roli.');
                redirect_to('index.php?page=users');
            }
            if (!can_manage_root_roles() && (int) ($targetUser['role_can_manage_root_roles'] ?? 0) === 1) {
                flash('error', 'Tylko Root SuperAdmin może zmieniać konto z tą rolą.');
                redirect_to('index.php?page=users');
            }
            if (!can_manage_root_roles() && role_assignment_is_protected((string) $targetUser['role']) && $role !== (string) $targetUser['role']) {
                flash('error', 'Tylko Root SuperAdmin może zmieniać przypisanie ról SuperAdmin/Root SuperAdmin.');
                redirect_to('index.php?page=users');
            }
            if ((int) $current['id'] === $targetId && (int) ($targetUser['role_can_manage_root_roles'] ?? 0) === 1) {
                $newRoleDefinition = role_definition($role);
                if ((int) ($newRoleDefinition['can_manage_root_roles'] ?? 0) !== 1) {
                    flash('error', 'Nie można samodzielnie odebrać sobie roli Root SuperAdmin.');
                    redirect_to('index.php?page=users');
                }
            }
            try {
                execute_sql(
                    'UPDATE users SET username = :username, display_name = :display_name, role = :role WHERE id = :id',
                    ['username' => $username, 'display_name' => $displayName, 'role' => $role, 'id' => $targetId]
                );
            } catch (PDOException $exception) {
                flash('error', is_unique_violation($exception) ? 'Taki login już istnieje.' : 'Nie udało się zaktualizować użytkownika.');
                redirect_to('index.php?page=users');
            }
            audit_log((int) $current['id'], 'user', $targetId, 'update_user_profile', ['username' => $username, 'display_name' => $displayName, 'role' => $role]);
            flash('success', 'Zaktualizowano dane użytkownika.');
            redirect_to('index.php?page=users');
        }

        if ($action === 'delete') {
            $targetId = (int) ($_POST['user_id'] ?? 0);
            $targetUser = query_one(
                'SELECT u.id, u.role, r.can_manage_root_roles AS role_can_manage_root_roles
                 FROM users u
                 LEFT JOIN roles r ON r.slug = u.role
                 WHERE u.id = :id',
                ['id' => $targetId]
            );
            if ($targetId === (int) $current['id']) {
                flash('error', 'Nie można dezaktywować aktualnie zalogowanego użytkownika.');
                redirect_to('index.php?page=users');
            }
            if (!$targetUser) {
                flash('error', 'Nie znaleziono użytkownika.');
                redirect_to('index.php?page=users');
            }
            if ((int) ($targetUser['role_can_manage_root_roles'] ?? 0) === 1 && !can_manage_root_roles()) {
                flash('error', 'Tylko Root SuperAdmin może dezaktywować konto z tą rolą.');
                redirect_to('index.php?page=users');
            }
            execute_sql('UPDATE users SET active = 0 WHERE id = :id', ['id' => $targetId]);
            audit_log((int) $current['id'], 'user', $targetId, 'deactivate_user');
            flash('success', 'Użytkownik został dezaktywowany.');
            redirect_to('index.php?page=users');
        }

        if ($action === 'reactivate') {
            $targetId = (int) ($_POST['user_id'] ?? 0);
            $targetUser = query_one(
                'SELECT u.id, u.role, r.can_manage_root_roles AS role_can_manage_root_roles
                 FROM users u
                 LEFT JOIN roles r ON r.slug = u.role
                 WHERE u.id = :id',
                ['id' => $targetId]
            );
            if ($targetId < 1) {
                flash('error', 'Niepoprawne dane reaktywacji użytkownika.');
                redirect_to('index.php?page=users');
            }
            if (!$targetUser) {
                flash('error', 'Nie znaleziono użytkownika.');
                redirect_to('index.php?page=users');
            }
            if ((int) ($targetUser['role_can_manage_root_roles'] ?? 0) === 1 && !can_manage_root_roles()) {
                flash('error', 'Tylko Root SuperAdmin może aktywować konto z tą rolą.');
                redirect_to('index.php?page=users');
            }
            execute_sql('UPDATE users SET active = 1 WHERE id = :id', ['id' => $targetId]);
            audit_log((int) $current['id'], 'user', $targetId, 'reactivate_user');
            flash('success', 'Użytkownik został aktywowany.');
            redirect_to('index.php?page=users');
        }
    }

    $users = query_all(
        'SELECT u.*, r.name AS role_name, r.can_manage_root_roles AS role_can_manage_root_roles
         FROM users u
         LEFT JOIN roles r ON r.slug = u.role
         ORDER BY u.active DESC, COALESCE(r.sort_order, 9999), u.display_name'
    );
    render_header('Użytkownicy');
    ?>
    <section class="split">
        <div class="panel">
            <h2>Użytkownicy</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Login</th>
                        <th>Rola</th>
                        <th>Status</th>
                        <th>Ostatnie logowanie</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= h($user['display_name']) ?></strong><br><span class="muted"><?= h($user['username']) ?></span></td>
                            <td><?= h($user['role_name'] ?: role_label($user['role'])) ?></td>
                            <td><?= $user['active'] ? 'Aktywny' : 'Nieaktywny' ?></td>
                            <td><?= h($user['last_login_at'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td colspan="4">
                                <?php $canManageThisRootRole = ((int) ($user['role_can_manage_root_roles'] ?? 0) !== 1) || can_manage_root_roles(); ?>
                                <?php $canChangeAssignedRole = can_manage_root_roles() || !role_assignment_is_protected((string) $user['role']); ?>
                                <?php if ($canManageThisRootRole): ?>
                                    <details class="media-details" style="margin: 0 0 10px; padding-top: 0; border-top: 0;">
                                        <summary>Edytuj dane użytkownika</summary>
                                        <form method="post" class="form-grid" style="margin-top: 12px;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                            <input type="hidden" name="action" value="update-profile">
                                            <div>
                                                <label for="display_name_<?= (int) $user['id'] ?>">Nazwa wyświetlana</label>
                                                <input id="display_name_<?= (int) $user['id'] ?>" name="display_name" value="<?= h($user['display_name']) ?>" required>
                                            </div>
                                            <div>
                                                <label for="username_<?= (int) $user['id'] ?>">Login użytkownika</label>
                                                <input id="username_<?= (int) $user['id'] ?>" name="username" value="<?= h($user['username']) ?>" required>
                                            </div>
                                            <div>
                                                <label for="role_<?= (int) $user['id'] ?>">Rola</label>
                                                <?php if ($canChangeAssignedRole): ?>
                                                <select id="role_<?= (int) $user['id'] ?>" name="role">
                                                    <?php foreach (manageable_role_values(can_manage_root_roles()) as $roleOption): ?>
                                                        <option value="<?= h($roleOption) ?>"<?= $user['role'] === $roleOption ? ' selected' : '' ?>><?= h(role_label($roleOption)) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <?php else: ?>
                                                <input type="hidden" name="role" value="<?= h($user['role']) ?>">
                                                <div class="readonly-value"><?= h(role_label($user['role'])) ?></div>
                                                <div class="muted helper-text">Tę rolę może przypisać lub zdjąć wyłącznie Root SuperAdmin.</div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="actions" style="align-items: end;">
                                                <button class="button" type="submit">Zapisz dane</button>
                                            </div>
                                        </form>
                                    </details>
                                <?php else: ?>
                                    <div class="muted" style="margin-bottom: 10px;">To konto ma rolę Root SuperAdmin. Zwykły SuperAdmin widzi je na liście, ale nie może zmieniać jego danych, roli ani hasła.</div>
                                <?php endif; ?>
                                <?php if ($user['active']): ?>
                                    <?php if ($canManageThisRootRole): ?>
                                        <details class="media-details" style="margin: 0 0 10px; padding-top: 0; border-top: 0;">
                                            <summary>Reset hasła</summary>
                                            <form method="post" class="form-grid" style="margin-top: 12px;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                                <input type="hidden" name="action" value="reset-password">
                                                <div>
                                                    <label for="new_password_<?= (int) $user['id'] ?>">Nowe hasło tymczasowe</label>
                                                    <input id="new_password_<?= (int) $user['id'] ?>" name="new_password" required>
                                                </div>
                                                <div class="actions" style="align-items: end;">
                                                    <button class="button" type="submit">Reset hasła</button>
                                                </div>
                                            </form>
                                        </details>
                                        <details class="media-details" style="margin: 0; padding-top: 0; border-top: 0;">
                                            <summary>Dezaktywuj konto</summary>
                                            <form method="post" class="actions" style="margin-top: 12px;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button class="button danger" type="submit">Dezaktywuj</button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="muted" style="margin-bottom: 10px;">To konto jest już nieaktywne. Akcje resetu hasła i dezaktywacji są ukryte.</div>
                                    <?php if ($canManageThisRootRole): ?>
                                        <details class="media-details" style="margin: 0; padding-top: 0; border-top: 0;">
                                            <summary>Aktywuj konto</summary>
                                            <form method="post" class="actions" style="margin-top: 12px;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                                <input type="hidden" name="action" value="reactivate">
                                                <button class="button primary" type="submit">Aktywuj konto</button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel">
            <h2>Nowy użytkownik</h2>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <div>
                    <label for="username">Login</label>
                    <input id="username" name="username" required>
                </div>
                <div>
                    <label for="display_name">Nazwa wyświetlana</label>
                    <input id="display_name" name="display_name" required>
                </div>
                <div>
                    <label for="role">Rola</label>
                    <select id="role" name="role" required>
                        <?php foreach (manageable_role_values(can_manage_root_roles()) as $roleOption): ?>
                            <option value="<?= h($roleOption) ?>"><?= h(role_label($roleOption)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="password">Hasło startowe</label>
                    <input id="password" type="password" name="password" minlength="10" required>
                    <div class="muted helper-text">Minimum 10 znaków.</div>
                </div>
                <div class="actions" style="margin-top: 12px;">
                    <button class="button primary" type="submit">Utwórz konto</button>
                </div>
            </form>
        </div>
    </section>
    <?php
    render_footer();
    exit;
}

// Sekcja konfiguracji systemowej.
if ($page === 'settings') {
    require_settings_access();

    $deadlineValue = (string) app_setting('festival_deadline', app_config('festival_deadline'));
    $deadlineDate = '';
    $deadlineTime = '';
    if ($deadlineValue !== '') {
        $deadline = (new DateTimeImmutable($deadlineValue))->setTimezone(new DateTimeZone('Europe/Warsaw'));
        $deadlineDate = $deadline->format('Y-m-d');
        $deadlineTime = $deadline->format('H:i:s');
    }

    $thresholdFields = [
        'threshold_needs_service_warning' => 'Do naprawy: żółty',
        'threshold_needs_service_warning_high' => 'Do naprawy: pomarańczowy',
        'threshold_needs_service_danger' => 'Do naprawy: czerwony',
        'threshold_open_items_warning' => 'Pozycje z otwartymi zadaniami: żółty',
        'threshold_open_items_warning_high' => 'Pozycje z otwartymi zadaniami: pomarańczowy',
        'threshold_open_items_danger' => 'Pozycje z otwartymi zadaniami: czerwony',
        'threshold_open_tasks_warning' => 'Otwarte zadania: żółty',
        'threshold_open_tasks_warning_high' => 'Otwarte zadania: pomarańczowy',
        'threshold_open_tasks_danger' => 'Otwarte zadania: czerwony',
    ];
    render_header('Konfiguracja');
    ?>
    <section class="panel settings-deadline-panel">
            <h2>Termin festiwalu</h2>
            <p class="muted">Ta wartość zasila countdown w głównym bannerze. Kalendarz przeglądarki pozwala kliknąć datę, ale datę i godzinę nadal można wpisać ręcznie.</p>
            <form method="post" action="index.php?page=settings-save" class="form-grid settings-deadline-form">
                <?= csrf_field() ?>
                <input type="hidden" name="section" value="deadline">
                <div>
                    <label for="festival_deadline_date">Data deadline</label>
                    <input id="festival_deadline_date" type="date" name="festival_deadline_date" value="<?= h($deadlineDate) ?>" required>
                </div>
                <div>
                    <label for="festival_deadline_time">Godzina deadline</label>
                    <input id="festival_deadline_time" type="time" name="festival_deadline_time" step="1" value="<?= h($deadlineTime) ?>" required>
                </div>
                <div class="actions settings-deadline-actions">
                    <button class="button primary" type="submit">Zapisz termin</button>
                </div>
            </form>
    </section>
    <section class="panel">
        <h2>Progi dashboardu</h2>
        <p class="muted">Kolory kafelków dla wskaźników alarmowych. Wartości rosnąco: żółty, pomarańczowy, czerwony.</p>
        <form method="post" action="index.php?page=settings-save" class="form-grid">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="thresholds">
            <?php foreach ($thresholdFields as $key => $label): ?>
                <div>
                    <label for="<?= h($key) ?>"><?= h($label) ?></label>
                    <input id="<?= h($key) ?>" type="number" min="0" name="<?= h($key) ?>" value="<?= h((string) app_setting($key, app_setting_defaults()[$key] ?? '0')) ?>" required>
                </div>
            <?php endforeach; ?>
            <div style="flex-basis: 100%;">
                <button class="button primary" type="submit">Zapisz progi</button>
            </div>
        </form>
    </section>
    <?php
    render_footer();
    exit;
}

// Sekcja zarządzania rolami i uprawnieniami.
if ($page === 'roles') {
    require_role_management();

    if (is_post()) {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $current = current_user();
        $name = trim((string) ($_POST['name'] ?? ''));
        $roleSlug = trim((string) ($_POST['role_slug'] ?? ''));
        $permissionKeys = [
            'can_manage_users',
            'can_manage_roles',
            'can_edit_records',
            'can_upload_images',
            'can_delete_images',
            'can_create_tasks',
            'can_update_tasks',
            'can_change_task_status',
            'can_delete_tasks',
            'can_manage_settings',
            'can_manage_dictionaries',
            'can_view_audit_history',
            'can_export_csv',
            'can_import_csv',
        ];

        if ($action === 'create-role') {
            if ($name === '') {
                flash('error', 'Nazwa roli jest wymagana.');
                redirect_to('index.php?page=roles');
            }
            $slug = slugify($name);
            if ($slug === '') {
                flash('error', 'Nie udało się wygenerować identyfikatora roli.');
                redirect_to('index.php?page=roles');
            }
            $payload = [
                'name' => $name,
                'slug' => $slug,
                'sort_order' => (int) ($_POST['sort_order'] ?? 100),
                'is_system' => 0,
            ];
            foreach ($permissionKeys as $permissionKey) {
                $payload[$permissionKey] = (int) (($_POST[$permissionKey] ?? '0') === '1');
            }
            $payload['can_manage_root_roles'] = 0;
            if ($payload['can_edit_records'] === 1) {
                $payload['can_upload_images'] = 1;
            }
            try {
                execute_sql(
                    'INSERT INTO roles (name, slug, sort_order, can_manage_users, can_manage_roles, can_manage_root_roles, can_edit_records, can_upload_images, can_delete_images, can_create_tasks, can_update_tasks, can_change_task_status, can_delete_tasks, can_manage_settings, can_manage_dictionaries, can_view_audit_history, can_export_csv, can_import_csv, is_system)
                     VALUES (:name, :slug, :sort_order, :can_manage_users, :can_manage_roles, :can_manage_root_roles, :can_edit_records, :can_upload_images, :can_delete_images, :can_create_tasks, :can_update_tasks, :can_change_task_status, :can_delete_tasks, :can_manage_settings, :can_manage_dictionaries, :can_view_audit_history, :can_export_csv, :can_import_csv, :is_system)',
                    $payload
                );
            } catch (PDOException $exception) {
                flash('error', is_unique_violation($exception) ? 'Rola o takiej nazwie lub identyfikatorze już istnieje.' : 'Nie udało się utworzyć roli.');
                redirect_to('index.php?page=roles');
            }
            refresh_role_cache();
            audit_log((int) $current['id'], 'settings', 0, 'create_role', ['name' => $name, 'slug' => $slug]);
            flash('success', 'Rola została utworzona.');
            redirect_to('index.php?page=roles');
        }

        if ($action === 'update-role') {
            $role = role_definition($roleSlug);
            if (!$role) {
                flash('error', 'Nie znaleziono roli.');
                redirect_to('index.php?page=roles');
            }
            if ((int) ($role['can_manage_root_roles'] ?? 0) === 1 && !can_manage_root_roles()) {
                flash('error', 'Tylko Root SuperAdmin może zmieniać role z uprawnieniem root.');
                redirect_to('index.php?page=roles');
            }
            if ((int) ($role['can_manage_root_roles'] ?? 0) === 1) {
                flash('error', 'Rola Root SuperAdmin jest niemodyfikowalna.');
                redirect_to('index.php?page=roles');
            }
            $payload = [
                'slug' => $roleSlug,
                'name' => $name !== '' ? $name : (string) $role['name'],
                'sort_order' => (int) ($_POST['sort_order'] ?? (int) ($role['sort_order'] ?? 100)),
            ];
            foreach ($permissionKeys as $permissionKey) {
                $payload[$permissionKey] = (int) (($_POST[$permissionKey] ?? '0') === '1');
            }
            $payload['can_manage_root_roles'] = (int) ($role['can_manage_root_roles'] ?? 0);
            if ($payload['can_edit_records'] === 1) {
                $payload['can_upload_images'] = 1;
            }
            execute_sql(
                'UPDATE roles SET name = :name, sort_order = :sort_order, can_manage_users = :can_manage_users, can_manage_roles = :can_manage_roles, can_manage_root_roles = :can_manage_root_roles, can_edit_records = :can_edit_records, can_upload_images = :can_upload_images, can_delete_images = :can_delete_images, can_create_tasks = :can_create_tasks, can_update_tasks = :can_update_tasks, can_change_task_status = :can_change_task_status, can_delete_tasks = :can_delete_tasks, can_manage_settings = :can_manage_settings, can_manage_dictionaries = :can_manage_dictionaries, can_view_audit_history = :can_view_audit_history, can_export_csv = :can_export_csv, can_import_csv = :can_import_csv WHERE slug = :slug',
                $payload
            );
            refresh_role_cache();
            audit_log((int) $current['id'], 'settings', 0, 'update_role', ['slug' => $roleSlug, 'name' => $payload['name']]);
            flash('success', 'Rola została zaktualizowana.');
            redirect_to('index.php?page=roles');
        }

        if ($action === 'delete-role') {
            $role = role_definition($roleSlug);
            if (!$role) {
                flash('error', 'Nie znaleziono roli.');
                redirect_to('index.php?page=roles');
            }
            if ((int) ($role['is_system'] ?? 0) === 1) {
                flash('error', 'Ról systemowych nie można usuwać.');
                redirect_to('index.php?page=roles');
            }
            if ((int) ($role['can_manage_root_roles'] ?? 0) === 1 && !can_manage_root_roles()) {
                flash('error', 'Tylko Root SuperAdmin może usuwać role z uprawnieniem root.');
                redirect_to('index.php?page=roles');
            }
            if ((int) ($role['can_manage_root_roles'] ?? 0) === 1) {
                flash('error', 'Rola Root SuperAdmin jest niemodyfikowalna.');
                redirect_to('index.php?page=roles');
            }
            $inUse = (int) (query_one('SELECT COUNT(*) AS total FROM users WHERE role = :role', ['role' => $roleSlug])['total'] ?? 0);
            if ($inUse > 0) {
                flash('error', 'Nie można usunąć roli, dopóki jest przypisana do użytkowników.');
                redirect_to('index.php?page=roles');
            }
            execute_sql('DELETE FROM roles WHERE slug = :slug', ['slug' => $roleSlug]);
            refresh_role_cache();
            audit_log((int) $current['id'], 'settings', 0, 'delete_role', ['slug' => $roleSlug, 'name' => $role['name']]);
            flash('success', 'Rola została usunięta.');
            redirect_to('index.php?page=roles');
        }
    }

    $roles = query_all(
        'SELECT r.*, COUNT(u.id) AS users_count
         FROM roles r
         LEFT JOIN users u ON u.role = r.slug
         GROUP BY r.id, r.name, r.slug, r.sort_order, r.can_manage_users, r.can_manage_roles, r.can_manage_root_roles, r.can_edit_records, r.can_upload_images, r.can_delete_images, r.can_create_tasks, r.can_update_tasks, r.can_change_task_status, r.can_delete_tasks, r.can_manage_settings, r.can_manage_dictionaries, r.can_view_audit_history, r.can_export_csv, r.can_import_csv, r.is_system, r.created_at
         ORDER BY r.sort_order, r.name'
    );

    $permissionLabels = [
        'can_manage_users' => 'Zarządzanie użytkownikami',
        'can_manage_roles' => 'Zarządzanie rolami',
        'can_edit_records' => 'Edycja sprzętu',
        'can_upload_images' => 'Dodawanie zdjęć',
        'can_delete_images' => 'Usuwanie zdjęć',
        'can_create_tasks' => 'Tworzenie zadań',
        'can_update_tasks' => 'Aktualizacje zadań',
        'can_change_task_status' => 'Zmiana statusu zadań',
        'can_delete_tasks' => 'Usuwanie zadań',
        'can_manage_settings' => 'Konfiguracja i lokalizacje',
        'can_manage_dictionaries' => 'Słowniki',
        'can_view_audit_history' => 'Historia zdarzeń',
        'can_export_csv' => 'Eksport CSV sprzętu',
        'can_import_csv' => 'Import CSV sprzętu',
    ];

    render_header('Role');
    ?>
    <section class="split">
        <div class="panel">
            <h2>Role</h2>
            <p class="muted">Każda rola ma zawsze dostęp tylko do odczytu sprzętu, zdjęć, historii i zadań. Poniżej są wyłącznie uprawnienia dodatkowe.</p>
            <?php foreach ($roles as $role): ?>
                <?php $isImmutableRootRole = (int) ($role['can_manage_root_roles'] ?? 0) === 1; ?>
                <?php $canEditRole = !$isImmutableRootRole && ((((int) ($role['can_manage_root_roles'] ?? 0) !== 1) || can_manage_root_roles())); ?>
                <form method="post" class="form-grid" style="margin-bottom: 16px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update-role">
                    <input type="hidden" name="role_slug" value="<?= h($role['slug']) ?>">
                    <div>
                        <label for="role_name_<?= h($role['slug']) ?>">Nazwa roli</label>
                        <input id="role_name_<?= h($role['slug']) ?>" name="name" value="<?= h($role['name']) ?>" <?= $canEditRole ? '' : 'disabled' ?> required>
                    </div>
                    <div>
                        <label>Slug</label>
                        <div class="readonly-value"><?= h($role['slug']) ?></div>
                    </div>
                    <div>
                        <label for="role_sort_<?= h($role['slug']) ?>">Kolejność</label>
                        <input id="role_sort_<?= h($role['slug']) ?>" type="number" min="0" name="sort_order" value="<?= (int) $role['sort_order'] ?>" <?= $canEditRole ? '' : 'disabled' ?> required>
                    </div>
                    <div>
                        <label>Użytkownicy</label>
                        <div class="readonly-value"><?= (int) $role['users_count'] ?></div>
                    </div>
                    <div class="role-permissions" style="flex-basis: 100%; display: grid; grid-template-columns: 28px minmax(240px, 1fr); gap: 8px 14px; align-items: center; margin-top: 6px;">
                        <input type="checkbox" checked disabled aria-label="Stały podgląd sprzętu, zdjęć i zadań">
                        <div class="muted">Stały podgląd sprzętu, zdjęć i zadań</div>
                        <?php foreach ($permissionLabels as $permissionKey => $label): ?>
                            <input type="checkbox" data-permission="<?= h($permissionKey) ?>" name="<?= h($permissionKey) ?>" value="1"<?= ((int) ($role[$permissionKey] ?? 0) === 1) ? ' checked' : '' ?><?= $canEditRole ? '' : ' disabled' ?>>
                            <div><?= h($label) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ((int) ($role['can_manage_root_roles'] ?? 0) === 1): ?>
                        <div style="flex-basis: 100%;" class="muted">Rola Root SuperAdmin jest wbudowana i niemodyfikowalna. Uprawnienia są zawsze aktywne.</div>
                    <?php endif; ?>
                    <div class="actions" style="flex-basis: 100%;">
                        <?php if ($canEditRole): ?>
                            <button class="button" type="submit">Zapisz rolę</button>
                        <?php endif; ?>
                        <?php if ((int) ($role['is_system'] ?? 0) !== 1 && $canEditRole): ?>
                            <button class="button danger" type="submit" name="action" value="delete-role" onclick="return confirm('Usunąć tę rolę? Operacja powiedzie się tylko, jeśli żaden użytkownik jej nie używa.');">Usuń rolę</button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
        <div class="panel">
            <h2>Nowa rola</h2>
            <form method="post" class="form-grid">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create-role">
                <div>
                    <label for="role_name_create">Nazwa roli</label>
                    <input id="role_name_create" name="name" required>
                </div>
                <div>
                    <label for="role_sort_create">Kolejność</label>
                    <input id="role_sort_create" type="number" min="0" name="sort_order" value="100" required>
                </div>
                <div class="role-permissions" style="flex-basis: 100%; display: grid; grid-template-columns: 28px minmax(240px, 1fr); gap: 8px 14px; align-items: center;">
                    <input type="checkbox" checked disabled aria-label="Stały podgląd sprzętu, zdjęć i zadań">
                    <div class="muted">Stały podgląd sprzętu, zdjęć i zadań</div>
                    <?php foreach ($permissionLabels as $permissionKey => $label): ?>
                        <input type="checkbox" data-permission="<?= h($permissionKey) ?>" name="<?= h($permissionKey) ?>" value="1"<?= in_array($permissionKey, ['can_edit_records', 'can_upload_images'], true) ? ' checked' : '' ?>>
                        <div><?= h($label) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="actions" style="flex-basis: 100%;">
                    <button class="button primary" type="submit">Utwórz rolę</button>
                </div>
            </form>
            <script>
            document.querySelectorAll('.role-permissions').forEach(function (container) {
                var editRecords = container.querySelector('input[data-permission="can_edit_records"]');
                var uploadImages = container.querySelector('input[data-permission="can_upload_images"]');
                var deleteImages = container.querySelector('input[data-permission="can_delete_images"]');

                function syncDependencies() {
                    if (!editRecords || !uploadImages) {
                        return;
                    }
                    var canEdit = editRecords.checked;
                    if (!canEdit) {
                        uploadImages.checked = false;
                        uploadImages.disabled = true;
                        if (deleteImages) {
                            deleteImages.checked = false;
                            deleteImages.disabled = true;
                        }
                        return;
                    }
                    uploadImages.checked = true;
                    uploadImages.disabled = true;
                    if (deleteImages) {
                        deleteImages.disabled = !uploadImages.checked;
                    }
                }

                if (editRecords) {
                    editRecords.addEventListener('change', syncDependencies);
                }
                if (uploadImages) {
                    uploadImages.addEventListener('change', syncDependencies);
                }
                syncDependencies();
            });
            </script>
        </div>
    </section>
    <?php
    render_footer();
    exit;
}

// Sekcja zarządzania słownikami.
if ($page === 'dictionaries') {
    require_superadmin();

    $dictionaryTables = [
        'categories' => inventory_dictionary_rows('categories'),
        'condition_statuses' => inventory_dictionary_rows('condition_statuses'),
        'ownership_statuses' => inventory_dictionary_rows('ownership_statuses'),
    ];

    render_header('Słowniki');
    ?>
    <section class="panel">
        <div class="topbar">
            <div>
                <h2>Słowniki systemowe</h2>
                <p class="muted">Edycja nazw dostępnych kategorii, statusów sprzętu i statusów formalnych. Slugi pozostają techniczne i stabilne.</p>
            </div>
        </div>
        <?php foreach ($dictionaryTables as $table => $rows): ?>
            <?php $config = dictionary_table_config($table); ?>
            <section style="margin-top: 18px;">
                <h3><?= h($config['label']) ?></h3>
                <form method="post" action="index.php?page=settings-save" class="actions" style="margin-bottom: 14px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="section" value="dictionary-create">
                    <input type="hidden" name="dictionary_table" value="<?= h($table) ?>">
                    <input type="hidden" name="return_page" value="index.php?page=dictionaries">
                    <input name="name" placeholder="Nowa pozycja słownika" required>
                    <?php if ($table === 'categories'): ?>
                        <input name="code_prefix" placeholder="CMP" maxlength="3" required>
                    <?php endif; ?>
                    <button class="button primary" type="submit">Dodaj pozycję</button>
                </form>
                <?php if ($table === 'categories'): ?>
                    <p class="muted" style="margin: -4px 0 14px;">Prefiks kodu musi być unikalny i zasila automatyczne kody rekordów `PR-XXX-XXXX`.</p>
                <?php endif; ?>
                <?php if (!$rows): ?>
                    <p class="muted">Brak pozycji w tym słowniku.</p>
                <?php else: ?>
                    <?php foreach ($rows as $row): ?>
                        <form method="post" action="index.php?page=settings-save" class="form-grid" style="margin-bottom: 14px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="section" value="dictionary-update">
                            <input type="hidden" name="dictionary_table" value="<?= h($table) ?>">
                            <input type="hidden" name="dictionary_id" value="<?= (int) $row['id'] ?>">
                            <input type="hidden" name="return_page" value="index.php?page=dictionaries">
                            <div>
                                <label for="<?= h($table) ?>_name_<?= (int) $row['id'] ?>">Nazwa</label>
                                <input id="<?= h($table) ?>_name_<?= (int) $row['id'] ?>" name="name" value="<?= h($row['name']) ?>" required>
                            </div>
                            <div>
                                <label>Slug techniczny</label>
                                <div class="readonly-value"><?= h($row['slug']) ?></div>
                            </div>
                            <?php if ($table === 'categories'): ?>
                                <div>
                                    <label for="<?= h($table) ?>_code_<?= (int) $row['id'] ?>">Prefiks kodu</label>
                                    <input id="<?= h($table) ?>_code_<?= (int) $row['id'] ?>" name="code_prefix" value="<?= h((string) ($row['code_prefix'] ?? 'GEN')) ?>" maxlength="3" required>
                                </div>
                                <div>
                                    <label>Użycie</label>
                                    <div class="readonly-value"><?= dictionary_usage_count($table, $row) ?></div>
                                    <div class="muted helper-text">Liczba rekordów sprzętu w tej kategorii.</div>
                                </div>
                            <?php else: ?>
                                <div>
                                    <label for="<?= h($table) ?>_sort_<?= (int) $row['id'] ?>">Kolejność</label>
                                    <input id="<?= h($table) ?>_sort_<?= (int) $row['id'] ?>" type="number" min="0" name="sort_order" value="<?= (int) ($row['sort_order'] ?? 0) ?>" required>
                                </div>
                                <div>
                                    <label>Użycie</label>
                                    <div class="readonly-value"><?= dictionary_usage_count($table, $row) ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="actions" style="align-items: end;">
                                <button class="button" type="submit">Zapisz nazwę</button>
                                <button class="button danger" type="submit" name="section" value="dictionary-delete" onclick="return confirm('Usunąć tę pozycję słownika? Operacja powiedzie się tylko, jeśli nic z niej nie korzysta.');">Usuń</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </section>
    <?php
    render_footer();
    exit;
}

if ($page === 'settings-save') {
    require_login();
    verify_csrf();

    $section = (string) ($_POST['section'] ?? '');
    $userId = (int) current_user()['id'];
    $returnPage = trim((string) ($_POST['return_page'] ?? 'index.php?page=settings'));

    if ($section === 'deadline') {
        require_settings_access();
        $rawDate = trim((string) ($_POST['festival_deadline_date'] ?? ''));
        $rawTime = trim((string) ($_POST['festival_deadline_time'] ?? ''));
        if ($rawDate === '' || $rawTime === '') {
            flash('error', 'Data i godzina festiwalu są wymagane.');
            redirect_to('index.php?page=settings');
        }

        try {
            $value = (new DateTimeImmutable($rawDate . ' ' . $rawTime, new DateTimeZone('Europe/Warsaw')))->format(DateTimeInterface::ATOM);
        } catch (Throwable) {
            flash('error', 'Nieprawidłowy format daty deadline.');
            redirect_to('index.php?page=settings');
        }

        set_app_settings(['festival_deadline' => $value]);
        audit_log($userId, 'settings', 0, 'update_deadline', ['festival_deadline' => $value]);
        flash('success', 'Zapisano termin festiwalu.');
        redirect_to('index.php?page=settings');
    }

    if ($section === 'thresholds') {
        require_settings_access();
        $keys = [
            'threshold_needs_service_warning',
            'threshold_needs_service_warning_high',
            'threshold_needs_service_danger',
            'threshold_open_items_warning',
            'threshold_open_items_warning_high',
            'threshold_open_items_danger',
            'threshold_open_tasks_warning',
            'threshold_open_tasks_warning_high',
            'threshold_open_tasks_danger',
        ];
        $payload = [];
        foreach ($keys as $key) {
            $value = (int) ($_POST[$key] ?? -1);
            if ($value < 0) {
                flash('error', 'Progi muszą być liczbami dodatnimi lub zerem.');
                redirect_to('index.php?page=settings');
            }
            $payload[$key] = (string) $value;
        }

        set_app_settings($payload);
        audit_log($userId, 'settings', 0, 'update_dashboard_thresholds', $payload);
        flash('success', 'Zapisano progi dashboardu.');
        redirect_to('index.php?page=settings');
    }

    if (in_array($section, ['dictionary-create', 'dictionary-update', 'dictionary-delete'], true)) {
        require_superadmin();
        $table = (string) ($_POST['dictionary_table'] ?? '');
        if (!in_array($table, ['categories', 'condition_statuses', 'ownership_statuses'], true)) {
            flash('error', 'Nieobsługiwany słownik.');
            redirect_to($returnPage);
        }
        $config = dictionary_table_config($table);
        $dictionaryId = (int) ($_POST['dictionary_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($section === 'dictionary-create') {
            if ($name === '') {
                flash('error', 'Nazwa pozycji słownika jest wymagana.');
                redirect_to($returnPage);
            }
            $slug = slugify($name);
            if ($slug === '') {
                flash('error', 'Nie udało się wygenerować poprawnego sluga pozycji słownika.');
                redirect_to($returnPage);
            }

            $params = ['name' => $name, 'slug' => $slug];
            $sql = sprintf('INSERT INTO %s (name, slug', $table);
            $sqlValues = ' VALUES (:name, :slug';
            if ($table === 'categories') {
                $params['code_prefix'] = normalize_category_code((string) ($_POST['code_prefix'] ?? suggested_category_code($name)));
                if (category_code_in_use($params['code_prefix'])) {
                    flash('error', 'Prefiks kodu kategorii musi być unikalny.');
                    redirect_to($returnPage);
                }
                $sql .= ', code_prefix';
                $sqlValues .= ', :code_prefix';
            } else {
                $params['sort_order'] = (int) ($_POST['sort_order'] ?? 0);
                $sql .= ', sort_order';
                $sqlValues .= ', :sort_order';
            }
            $sql .= ')' . $sqlValues . ')';

            try {
                execute_sql($sql, $params);
            } catch (PDOException $exception) {
                flash('error', is_unique_violation($exception) ? 'Taka pozycja słownika już istnieje.' : 'Nie udało się dodać pozycji słownika.');
                redirect_to($returnPage);
            }

            refresh_inventory_dictionary_cache($table);
            audit_log($userId, 'settings', 0, 'create_dictionary_entry', ['dictionary_table' => $table, 'name' => $name, 'slug' => $slug]);
            flash('success', 'Dodano pozycję słownika.');
            redirect_to($returnPage);
        }

        if ($dictionaryId < 1) {
            flash('error', 'Nie wskazano pozycji słownika.');
            redirect_to($returnPage);
        }

        $entry = query_one(sprintf('SELECT * FROM %s WHERE id = :id', $table), ['id' => $dictionaryId]);
        if (!$entry) {
            flash('error', 'Nie znaleziono pozycji słownika.');
            redirect_to($returnPage);
        }

        if ($section === 'dictionary-update') {
            if ($name === '') {
                flash('error', 'Nazwa pozycji słownika jest wymagana.');
                redirect_to($returnPage);
            }
            $newSlug = slugify($name);
            if ($newSlug === '') {
                flash('error', 'Nie udało się wygenerować poprawnego sluga pozycji słownika.');
                redirect_to($returnPage);
            }

            $params = ['id' => $dictionaryId, 'name' => $name, 'slug' => $newSlug];
            $sql = sprintf('UPDATE %s SET name = :name, slug = :slug', $table);
            if ($table === 'categories') {
                $params['code_prefix'] = normalize_category_code((string) ($_POST['code_prefix'] ?? suggested_category_code($name)));
                if (category_code_in_use($params['code_prefix'], $dictionaryId)) {
                    flash('error', 'Prefiks kodu kategorii musi być unikalny.');
                    redirect_to($returnPage);
                }
                $sql .= ', code_prefix = :code_prefix';
            } else {
                $sortOrder = (int) ($_POST['sort_order'] ?? 0);
                $params['sort_order'] = $sortOrder;
                $sql .= ', sort_order = :sort_order';
            }
            $sql .= ' WHERE id = :id';

            try {
                execute_sql($sql, $params);
            } catch (PDOException $exception) {
                flash('error', is_unique_violation($exception) ? 'Taka pozycja słownika już istnieje.' : 'Nie udało się zaktualizować pozycji słownika.');
                redirect_to($returnPage);
            }

            if ($entry['slug'] !== $newSlug) {
                if ($table === 'condition_statuses') {
                    execute_sql(
                        'UPDATE equipment SET condition_status = :new_slug WHERE condition_status = :old_slug',
                        ['new_slug' => $newSlug, 'old_slug' => $entry['slug']]
                    );
                } elseif ($table === 'ownership_statuses') {
                    execute_sql(
                        'UPDATE equipment SET ownership_status = :new_slug WHERE ownership_status = :old_slug',
                        ['new_slug' => $newSlug, 'old_slug' => $entry['slug']]
                    );
                }
            }

            refresh_inventory_dictionary_cache($table);
            audit_log($userId, 'settings', 0, 'update_dictionary_entry', ['dictionary_table' => $table, 'id' => $dictionaryId, 'name' => $name, 'slug' => $newSlug, 'previous_slug' => $entry['slug']]);
            flash('success', 'Zapisano pozycję słownika.');
            redirect_to($returnPage);
        }

        $referenceParams = $table === 'categories'
            ? ['id' => $dictionaryId]
            : ['slug' => $entry['slug']];
        $inUse = (int) (query_one($config['reference_sql'], $referenceParams)['total'] ?? 0);
        if ($inUse > 0) {
            flash('error', 'Nie można usunąć tej pozycji słownika, dopóki jest używana przez sprzęt.');
            redirect_to($returnPage);
        }

        execute_sql(sprintf('DELETE FROM %s WHERE id = :id', $table), ['id' => $dictionaryId]);
        refresh_inventory_dictionary_cache($table);
        audit_log($userId, 'settings', 0, 'delete_dictionary_entry', ['dictionary_table' => $table, 'id' => $dictionaryId, 'name' => $entry['name'], 'slug' => $entry['slug']]);
        flash('success', 'Usunięto pozycję słownika.');
        redirect_to($returnPage);
    }

    if ($section === 'location-create') {
        require_settings_access();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            flash('error', 'Nazwa lokalizacji jest wymagana.');
            redirect_to($returnPage);
        }
        $slug = slugify($name);
        if ($slug === '') {
            flash('error', 'Nie udało się wygenerować poprawnego sluga lokalizacji.');
            redirect_to($returnPage);
        }
        try {
            execute_sql('INSERT INTO locations (name, slug) VALUES (:name, :slug)', ['name' => $name, 'slug' => $slug]);
        } catch (PDOException $exception) {
            flash('error', is_unique_violation($exception) ? 'Lokalizacja o takiej nazwie już istnieje.' : 'Nie udało się dodać lokalizacji.');
            redirect_to($returnPage);
        }
        audit_log($userId, 'settings', 0, 'create_location', ['name' => $name, 'slug' => $slug]);
        flash('success', 'Dodano lokalizację.');
        redirect_to($returnPage);
    }

    if ($section === 'location-update') {
        require_settings_access();
        $locationId = (int) ($_POST['location_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($locationId < 1 || $name === '') {
            flash('error', 'Niepoprawne dane lokalizacji.');
            redirect_to($returnPage);
        }
        $slug = slugify($name);
        try {
            execute_sql('UPDATE locations SET name = :name, slug = :slug WHERE id = :id', ['name' => $name, 'slug' => $slug, 'id' => $locationId]);
        } catch (PDOException $exception) {
            flash('error', is_unique_violation($exception) ? 'Taka lokalizacja już istnieje.' : 'Nie udało się zaktualizować lokalizacji.');
            redirect_to($returnPage);
        }
        execute_sql(
            'UPDATE equipment e
             LEFT JOIN locations l ON l.id = e.location_id
             LEFT JOIN location_places lp ON lp.id = e.location_place_id
             SET e.location_text = TRIM(CONCAT(COALESCE(l.name, \'\'), IF(l.name IS NOT NULL AND lp.name IS NOT NULL, \' / \', \'\'), COALESCE(lp.name, \'\')))
             WHERE e.location_id = :location_id',
            ['location_id' => $locationId]
        );
        audit_log($userId, 'settings', 0, 'update_location', ['location_id' => $locationId, 'name' => $name]);
        flash('success', 'Zapisano lokalizację.');
        redirect_to($returnPage);
    }

    if ($section === 'location-delete') {
        require_settings_access();
        $locationId = (int) ($_POST['location_id'] ?? 0);
        $inUse = (int) (query_one('SELECT COUNT(*) AS total FROM equipment WHERE location_id = :id', ['id' => $locationId])['total'] ?? 0);
        $placesCount = (int) (query_one('SELECT COUNT(*) AS total FROM location_places WHERE location_id = :id', ['id' => $locationId])['total'] ?? 0);
        if ($inUse > 0 || $placesCount > 0) {
            flash('error', 'Nie można usunąć lokalizacji, dopóki ma powiązane miejsca lub sprzęt.');
            redirect_to($returnPage);
        }
        execute_sql('DELETE FROM locations WHERE id = :id', ['id' => $locationId]);
        audit_log($userId, 'settings', 0, 'delete_location', ['location_id' => $locationId]);
        flash('success', 'Usunięto lokalizację.');
        redirect_to($returnPage);
    }

    if ($section === 'place-create') {
        require_settings_access();
        $locationId = (int) ($_POST['location_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($locationId < 1 || $name === '') {
            flash('error', 'Lokalizacja i nazwa miejsca są wymagane.');
            redirect_to($returnPage);
        }
        $slug = slugify($name);
        try {
            execute_sql(
                'INSERT INTO location_places (location_id, name, slug) VALUES (:location_id, :name, :slug)',
                ['location_id' => $locationId, 'name' => $name, 'slug' => $slug]
            );
        } catch (PDOException $exception) {
            flash('error', is_unique_violation($exception) ? 'Takie miejsce już istnieje w tej lokalizacji.' : 'Nie udało się dodać miejsca.');
            redirect_to($returnPage);
        }
        audit_log($userId, 'settings', 0, 'create_location_place', ['location_id' => $locationId, 'name' => $name]);
        flash('success', 'Dodano miejsce.');
        redirect_to($returnPage);
    }

    if ($section === 'place-update') {
        require_settings_access();
        $placeId = (int) ($_POST['place_id'] ?? 0);
        $locationId = (int) ($_POST['location_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($placeId < 1 || $locationId < 1 || $name === '') {
            flash('error', 'Niepoprawne dane miejsca.');
            redirect_to($returnPage);
        }
        $slug = slugify($name);
        try {
            execute_sql(
                'UPDATE location_places SET location_id = :location_id, name = :name, slug = :slug WHERE id = :id',
                ['location_id' => $locationId, 'name' => $name, 'slug' => $slug, 'id' => $placeId]
            );
        } catch (PDOException $exception) {
            flash('error', is_unique_violation($exception) ? 'Takie miejsce już istnieje w tej lokalizacji.' : 'Nie udało się zaktualizować miejsca.');
            redirect_to($returnPage);
        }
        execute_sql(
            'UPDATE equipment e
             LEFT JOIN locations l ON l.id = e.location_id
             LEFT JOIN location_places lp ON lp.id = e.location_place_id
             SET e.location_id = :location_id,
                 e.location_text = TRIM(CONCAT(COALESCE(l.name, \'\'), IF(l.name IS NOT NULL AND lp.name IS NOT NULL, \' / \', \'\'), COALESCE(lp.name, \'\')))
             WHERE e.location_place_id = :place_id',
            ['location_id' => $locationId, 'place_id' => $placeId]
        );
        audit_log($userId, 'settings', 0, 'update_location_place', ['place_id' => $placeId, 'location_id' => $locationId, 'name' => $name]);
        flash('success', 'Zapisano miejsce.');
        redirect_to($returnPage);
    }

    if ($section === 'place-delete') {
        require_settings_access();
        $placeId = (int) ($_POST['place_id'] ?? 0);
        $inUse = (int) (query_one('SELECT COUNT(*) AS total FROM equipment WHERE location_place_id = :id', ['id' => $placeId])['total'] ?? 0);
        if ($inUse > 0) {
            flash('error', 'Nie można usunąć miejsca, dopóki jest przypisane do sprzętu.');
            redirect_to($returnPage);
        }
        execute_sql('DELETE FROM location_places WHERE id = :id', ['id' => $placeId]);
        audit_log($userId, 'settings', 0, 'delete_location_place', ['place_id' => $placeId]);
        flash('success', 'Usunięto miejsce.');
        redirect_to($returnPage);
    }

    redirect_to('index.php?page=settings');
}

// Sekcja lokalizacji i miejsc składowania.
if ($page === 'locations') {
    require_settings_access();
    $locations = query_all(
        'SELECT l.id, l.name, l.slug,
                COUNT(DISTINCT e.id) AS equipment_count,
                COUNT(DISTINCT lp.id) AS places_count
         FROM locations l
         LEFT JOIN location_places lp ON lp.location_id = l.id
         LEFT JOIN equipment e ON e.location_id = l.id
         GROUP BY l.id, l.name, l.slug
         ORDER BY l.name'
    );

    render_header('Lokalizacje');
    ?>
    <section class="panel">
        <div class="topbar">
            <div>
                <h2>Lokalizacje</h2>
                <p class="muted">Słownik głównych lokalizacji typu magazyn, szkoła, ratusz lub dom prywatny.</p>
            </div>
        </div>
        <form method="post" action="index.php?page=settings-save" class="actions" style="margin-bottom: 18px;">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="location-create">
            <input type="hidden" name="return_page" value="index.php?page=locations">
            <input name="name" placeholder="Nowa lokalizacja, np. Ratusz" required>
            <button class="button primary" type="submit">Dodaj lokalizację</button>
        </form>
        <?php if (!$locations): ?>
            <p class="muted">Brak zdefiniowanych lokalizacji.</p>
        <?php else: ?>
            <?php foreach ($locations as $location): ?>
                <form method="post" action="index.php?page=settings-save" class="form-grid" style="margin-bottom: 16px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="location_id" value="<?= (int) $location['id'] ?>">
                    <input type="hidden" name="return_page" value="index.php?page=locations">
                    <div>
                        <label for="location_name_<?= (int) $location['id'] ?>">Nazwa lokalizacji</label>
                        <input id="location_name_<?= (int) $location['id'] ?>" name="name" value="<?= h($location['name']) ?>" required>
                    </div>
                    <div>
                        <label>Identyfikator techniczny</label>
                        <div class="readonly-value"><?= h($location['slug']) ?></div>
                        <div class="muted helper-text">Automatyczny zapis bez polskich znaków i spacji. Służy do stabilnego trzymania danych w bazie.</div>
                    </div>
                    <div>
                        <label>Miejsca</label>
                        <div class="readonly-value"><a href="index.php?page=settings-location&id=<?= (int) $location['id'] ?>"><?= (int) $location['places_count'] ?></a></div>
                    </div>
                    <div>
                        <label>Sprzęty</label>
                        <div class="readonly-value"><a href="index.php?page=equipment-list&location_id=<?= (int) $location['id'] ?>"><?= (int) $location['equipment_count'] ?></a></div>
                    </div>
                    <div class="actions" style="flex-basis: 100%;">
                        <a class="button" href="index.php?page=settings-location&id=<?= (int) $location['id'] ?>">Szczegóły lokalizacji</a>
                        <button class="button" type="submit" name="section" value="location-update">Zapisz nazwę</button>
                        <button class="button danger" type="submit" name="section" value="location-delete" onclick="return confirm('Usunąć lokalizację? Operacja powiedzie się tylko, jeśli nie ma powiązanych miejsc ani sprzętów.');">Usuń lokalizację</button>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
    <?php
    render_footer();
    exit;
}

if ($page === 'settings-location') {
    require_settings_access();
    $location = query_one(
        'SELECT l.*,
                COUNT(DISTINCT e.id) AS equipment_count,
                COUNT(DISTINCT lp.id) AS places_count
         FROM locations l
         LEFT JOIN equipment e ON e.location_id = l.id
         LEFT JOIN location_places lp ON lp.location_id = l.id
         WHERE l.id = :id
         GROUP BY l.id, l.name, l.slug, l.created_at',
        ['id' => (int) ($_GET['id'] ?? 0)]
    );

    if (!$location) {
        flash('error', 'Nie znaleziono lokalizacji.');
        redirect_to('index.php?page=settings');
    }

    $places = query_all(
        'SELECT lp.*, COUNT(e.id) AS equipment_count
         FROM location_places lp
         LEFT JOIN equipment e ON e.location_place_id = lp.id
         WHERE lp.location_id = :location_id
         GROUP BY lp.id, lp.location_id, lp.name, lp.slug, lp.created_at
         ORDER BY lp.name',
        ['location_id' => $location['id']]
    );

    render_header('Lokalizacja');
    ?>
    <section class="panel">
        <div class="topbar">
            <div>
                <div class="badge">Lokalizacja</div>
                <h2><?= h($location['name']) ?></h2>
                <p class="muted">Identyfikator techniczny: <?= h($location['slug']) ?></p>
            </div>
            <div class="actions">
                <a class="button" href="index.php?page=locations">Wróć do lokalizacji</a>
                <a class="button" href="index.php?page=equipment-list&location_id=<?= (int) $location['id'] ?>">Pokaż sprzęt</a>
            </div>
        </div>
        <form method="post" action="index.php?page=settings-save" class="form-grid" style="margin-bottom: 18px;">
            <?= csrf_field() ?>
            <input type="hidden" name="location_id" value="<?= (int) $location['id'] ?>">
            <input type="hidden" name="return_page" value="index.php?page=settings-location&id=<?= (int) $location['id'] ?>">
            <div>
                <label for="location_detail_name">Nazwa lokalizacji</label>
                <input id="location_detail_name" name="name" value="<?= h($location['name']) ?>" required>
            </div>
            <div>
                <label>Identyfikator techniczny</label>
                <div class="readonly-value"><?= h($location['slug']) ?></div>
                <div class="muted helper-text">Automatyczny zapis bez polskich znaków i spacji. Służy do stabilnego trzymania danych w bazie.</div>
            </div>
            <div class="actions" style="align-items: end;">
                <button class="button" type="submit" name="section" value="location-update">Zapisz nazwę</button>
                <button class="button danger" type="submit" name="section" value="location-delete" onclick="return confirm('Usunąć lokalizację? Operacja powiedzie się tylko, jeśli nie ma powiązanych miejsc ani sprzętów.');">Usuń lokalizację</button>
            </div>
        </form>
        <div class="topbar" style="margin-top: 8px;">
            <div>
                <h3>Miejsca w lokalizacji</h3>
                <p class="muted">Tutaj można dodać nowe miejsce oraz zarządzać istniejącymi.</p>
            </div>
        </div>
        <form method="post" action="index.php?page=settings-save" class="form-grid" style="margin-bottom: 18px;">
            <?= csrf_field() ?>
            <input type="hidden" name="section" value="place-create">
            <input type="hidden" name="location_id" value="<?= (int) $location['id'] ?>">
            <input type="hidden" name="return_page" value="index.php?page=settings-location&id=<?= (int) $location['id'] ?>">
            <div>
                <label for="place_name">Nowe miejsce</label>
                <input id="place_name" name="name" placeholder="Np. Szafa A" required>
            </div>
            <div class="actions" style="align-items: end;">
                <button class="button primary" type="submit">Dodaj miejsce</button>
            </div>
        </form>
        <section class="stats" style="margin-bottom: 12px;">
            <article class="card">
                <strong><?= (int) $location['places_count'] ?></strong>
                Miejsca w lokalizacji
            </article>
            <article class="card">
                <strong><?= (int) $location['equipment_count'] ?></strong>
                Sprzęty w lokalizacji
            </article>
        </section>
        <?php if (!$places): ?>
            <p class="muted">Brak miejsc przypisanych do tej lokalizacji.</p>
        <?php else: ?>
            <?php foreach ($places as $place): ?>
                <form method="post" action="index.php?page=settings-save" class="form-grid" style="margin-bottom: 16px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="place_id" value="<?= (int) $place['id'] ?>">
                    <input type="hidden" name="location_id" value="<?= (int) $location['id'] ?>">
                    <input type="hidden" name="return_page" value="index.php?page=settings-location&id=<?= (int) $location['id'] ?>">
                    <div>
                        <label for="place_name_<?= (int) $place['id'] ?>">Nazwa miejsca</label>
                        <input id="place_name_<?= (int) $place['id'] ?>" name="name" value="<?= h($place['name']) ?>" required>
                    </div>
                    <div>
                        <label>Identyfikator techniczny</label>
                        <div class="readonly-value"><?= h($place['slug']) ?></div>
                    </div>
                    <div>
                        <label>Sprzęty</label>
                        <div class="readonly-value"><a href="index.php?page=equipment-list&place_id=<?= (int) $place['id'] ?>"><?= (int) $place['equipment_count'] ?></a></div>
                    </div>
                    <div class="actions" style="align-items: end;">
                        <button class="button" type="submit" name="section" value="place-update">Zapisz miejsce</button>
                        <button class="button danger" type="submit" name="section" value="place-delete" onclick="return confirm('Usunąć miejsce? Operacja powiedzie się tylko, jeśli nie ma przypisanego sprzętu.');">Usuń miejsce</button>
                    </div>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
    <?php
    render_footer();
    exit;
}

if ($page === 'equipment-save') {
    require_editor();
    verify_csrf();
    $current = current_user();
    $id = (int) ($_POST['id'] ?? 0);
    $payload = [
        'category_id' => (int) ($_POST['category_id'] ?? 0),
        'parent_equipment_id' => ($_POST['parent_equipment_id'] ?? '') !== '' ? (int) $_POST['parent_equipment_id'] : null,
        'location_id' => ($_POST['location_id'] ?? '') !== '' ? (int) $_POST['location_id'] : null,
        'location_place_id' => ($_POST['location_place_id'] ?? '') !== '' ? (int) $_POST['location_place_id'] : null,
        'inventory_code' => trim($_POST['inventory_code'] ?? ''),
        'inventory_code_manual_override' => (int) ($_POST['inventory_code_manual_override'] ?? 0) === 1 ? 1 : 0,
        'title' => trim($_POST['title'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? ''),
        'model' => trim($_POST['model'] ?? ''),
        'production_year' => trim($_POST['production_year'] ?? ''),
        'condition_status' => $_POST['condition_status'] ?? 'inventory',
        'ownership_status' => $_POST['ownership_status'] ?? 'unknown',
        'location_text' => trim($_POST['location_text'] ?? ''),
        'barcode_value' => trim($_POST['barcode_value'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
    ];

    $categoryExists = $payload['category_id'] > 0
        ? query_one('SELECT id FROM categories WHERE id = :id', ['id' => $payload['category_id']])
        : null;
    $conditionExists = $payload['condition_status'] !== ''
        ? query_one('SELECT slug FROM condition_statuses WHERE slug = :slug', ['slug' => $payload['condition_status']])
        : null;
    $ownershipExists = $payload['ownership_status'] !== ''
        ? query_one('SELECT slug FROM ownership_statuses WHERE slug = :slug', ['slug' => $payload['ownership_status']])
        : null;

    if ($payload['location_place_id']) {
        $place = query_one(
            'SELECT lp.id, lp.location_id, l.name AS location_name, lp.name AS place_name
             FROM location_places lp
             JOIN locations l ON l.id = lp.location_id
             WHERE lp.id = :id',
            ['id' => $payload['location_place_id']]
        );
        if (!$place) {
            flash('error', 'Wybrane miejsce nie istnieje.');
            redirect_to($id ? 'index.php?page=equipment-edit&id=' . $id : 'index.php?page=equipment-new');
        }
        $payload['location_id'] = (int) $place['location_id'];
        $payload['location_text'] = $place['location_name'] . ' / ' . $place['place_name'];
    } elseif ($payload['location_id']) {
        $location = query_one('SELECT id, name FROM locations WHERE id = :id', ['id' => $payload['location_id']]);
        if (!$location) {
            flash('error', 'Wybrana lokalizacja nie istnieje.');
            redirect_to($id ? 'index.php?page=equipment-edit&id=' . $id : 'index.php?page=equipment-new');
        }
        $payload['location_text'] = $location['name'];
        $payload['location_place_id'] = null;
    }

    if ($payload['category_id'] < 1 || $payload['title'] === '') {
        flash('error', 'Kategoria i nazwa zwyczajowa są wymagane.');
        redirect_to($id ? 'index.php?page=equipment-edit&id=' . $id : 'index.php?page=equipment-new');
    }
    if (!$categoryExists) {
        flash('error', 'Wybrana kategoria nie istnieje.');
        redirect_to($id ? 'index.php?page=equipment-edit&id=' . $id : 'index.php?page=equipment-new');
    }
    if (!$conditionExists) {
        flash('error', 'Wybrany status sprzętu nie istnieje.');
        redirect_to($id ? 'index.php?page=equipment-edit&id=' . $id : 'index.php?page=equipment-new');
    }
    if (!$ownershipExists) {
        flash('error', 'Wybrany status formalny nie istnieje.');
        redirect_to($id ? 'index.php?page=equipment-edit&id=' . $id : 'index.php?page=equipment-new');
    }
    if ($payload['inventory_code'] === '') {
        $generatedCode = generate_next_inventory_code($payload['category_id']);
        if ($generatedCode === null) {
            flash('error', 'Nie udało się wygenerować kodu inwentarzowego dla wybranej kategorii.');
            redirect_to($id ? 'index.php?page=equipment-edit&id=' . $id : 'index.php?page=equipment-new');
        }
        $payload['inventory_code'] = $generatedCode;
        $payload['inventory_code_manual_override'] = 0;
    }

    if ($id > 0) {
        $payload['id'] = $id;
        $payload['updated_by'] = $current['id'];
        try {
            execute_sql(
                'UPDATE equipment SET category_id = :category_id, parent_equipment_id = :parent_equipment_id, location_id = :location_id, location_place_id = :location_place_id, inventory_code = :inventory_code, inventory_code_manual_override = :inventory_code_manual_override, title = :title, manufacturer = :manufacturer, model = :model, production_year = :production_year, condition_status = :condition_status, ownership_status = :ownership_status, location_text = :location_text, barcode_value = :barcode_value, notes = :notes, updated_by = :updated_by WHERE id = :id',
                $payload
            );
        } catch (PDOException $exception) {
            flash('error', is_unique_violation($exception) ? 'Kod inwentarzowy musi być unikalny.' : 'Nie udało się zapisać rekordu.');
            redirect_to('index.php?page=equipment-edit&id=' . $id);
        }
        audit_log((int) $current['id'], 'equipment', $id, 'update_equipment', ['inventory_code' => $payload['inventory_code']]);
        flash('success', 'Rekord sprzętu został zaktualizowany.');
        redirect_to('index.php?page=item&id=' . $id);
    }

    $payload['created_by'] = $current['id'];
    $payload['updated_by'] = $current['id'];
    $payload['qr_token'] = strtolower(bin2hex(random_bytes(8)));
    try {
        execute_sql(
            'INSERT INTO equipment (category_id, parent_equipment_id, location_id, location_place_id, inventory_code, inventory_code_manual_override, title, manufacturer, model, production_year, condition_status, ownership_status, location_text, barcode_value, qr_token, notes, created_by, updated_by) VALUES (:category_id, :parent_equipment_id, :location_id, :location_place_id, :inventory_code, :inventory_code_manual_override, :title, :manufacturer, :model, :production_year, :condition_status, :ownership_status, :location_text, :barcode_value, :qr_token, :notes, :created_by, :updated_by)',
            $payload
        );
    } catch (PDOException $exception) {
        flash('error', is_unique_violation($exception) ? 'Kod inwentarzowy musi być unikalny.' : 'Nie udało się dodać rekordu.');
        redirect_to('index.php?page=equipment-new');
    }
    $newId = (int) db()->lastInsertId();
    audit_log((int) $current['id'], 'equipment', $newId, 'create_equipment', ['inventory_code' => $payload['inventory_code']]);
    flash('success', 'Dodano nowy rekord sprzętu.');
    redirect_to('index.php?page=item&id=' . $newId);
}

if ($page === 'equipment-image-upload') {
    require_login();
    if (!can_upload_images()) {
        flash('error', 'Brak uprawnień do dodawania zdjęć.');
        redirect_to('index.php');
    }
    verify_csrf();
    $equipmentId = (int) ($_POST['equipment_id'] ?? 0);
    $equipment = query_one('SELECT * FROM equipment WHERE id = :id', ['id' => $equipmentId]);
    if (!$equipment) {
        flash('error', 'Nie znaleziono sprzętu.');
        redirect_to('index.php?page=equipment-list');
    }
    if (!isset($_FILES['image'])) {
        flash('error', 'Nie odebrano pliku w żądaniu.');
        redirect_to('index.php?page=item&id=' . $equipmentId);
    }
    $uploadError = (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $message = match ($uploadError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Plik jest zbyt duży. Limit uploadu to 8 MB.',
            UPLOAD_ERR_PARTIAL => 'Przesyłanie pliku zostało przerwane. Spróbuj ponownie.',
            UPLOAD_ERR_NO_FILE => 'Nie wybrano pliku.',
            UPLOAD_ERR_NO_TMP_DIR => 'Brakuje katalogu tymczasowego dla uploadu.',
            UPLOAD_ERR_CANT_WRITE => 'Serwer nie mógł zapisać pliku tymczasowego.',
            UPLOAD_ERR_EXTENSION => 'Upload został zatrzymany przez rozszerzenie PHP.',
            default => 'Wystąpił nieznany błąd uploadu.',
        };
        flash('error', $message);
        redirect_to('index.php?page=item&id=' . $equipmentId);
    }
    if (empty($_FILES['image']['tmp_name'])) {
        flash('error', 'Plik nie został poprawnie przesłany.');
        redirect_to('index.php?page=item&id=' . $equipmentId);
    }
    $mime = mime_content_type($_FILES['image']['tmp_name']) ?: '';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        flash('error', 'Obsługiwane są tylko JPG, PNG i WEBP.');
        redirect_to('index.php?page=item&id=' . $equipmentId);
    }
    if ((int) ($_FILES['image']['size'] ?? 0) > 8 * 1024 * 1024) {
        flash('error', 'Maksymalny rozmiar pliku to 8 MB.');
        redirect_to('index.php?page=item&id=' . $equipmentId);
    }
    ensure_upload_dir();
    $extension = match ($mime) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };
    $fileName = sprintf('%d-%s.%s', $equipmentId, bin2hex(random_bytes(5)), $extension);
    $target = upload_dir() . '/' . $fileName;
    if (!process_uploaded_image($_FILES['image']['tmp_name'], $target, $mime, 800, 800)) {
        flash('error', 'Nie udało się przeskalować i zapisać pliku.');
        redirect_to('index.php?page=item&id=' . $equipmentId);
    }
    execute_sql(
        'INSERT INTO equipment_images (equipment_id, file_path, original_name, uploaded_by) VALUES (:equipment_id, :file_path, :original_name, :uploaded_by)',
        [
            'equipment_id' => $equipmentId,
            'file_path' => public_upload_path($fileName),
            'original_name' => $_FILES['image']['name'],
            'uploaded_by' => current_user()['id'],
        ]
    );
    touch_equipment($equipmentId, (int) current_user()['id']);
    audit_log((int) current_user()['id'], 'equipment', $equipmentId, 'upload_image', ['file' => $fileName]);
    flash('success', 'Zdjęcie zostało dodane.');
    redirect_to('index.php?page=item&id=' . $equipmentId);
}

if ($page === 'equipment-image-delete') {
    require_login();
    if (!can_delete_images()) {
        flash('error', 'Brak uprawnień do usuwania zdjęć.');
        redirect_to('index.php');
    }
    verify_csrf();
    $imageId = (int) ($_POST['image_id'] ?? 0);
    $image = query_one('SELECT * FROM equipment_images WHERE id = :id', ['id' => $imageId]);
    if (!$image) {
        flash('error', 'Nie znaleziono zdjęcia.');
        redirect_to('index.php?page=equipment-list');
    }
    $absolutePath = app_config('base_path') . '/' . $image['file_path'];
    if (is_file($absolutePath)) {
        unlink($absolutePath);
    }
    execute_sql('DELETE FROM equipment_images WHERE id = :id', ['id' => $imageId]);
    touch_equipment((int) $image['equipment_id'], (int) current_user()['id']);
    audit_log((int) current_user()['id'], 'equipment', (int) $image['equipment_id'], 'delete_image', ['image_id' => $imageId]);
    flash('success', 'Zdjęcie zostało usunięte.');
    redirect_to('index.php?page=item&id=' . (int) $image['equipment_id']);
}

if ($page === 'task-create') {
    require_login();
    if (!can_create_tasks()) {
        flash('error', 'Brak uprawnień do tworzenia zadań.');
        redirect_to('index.php');
    }
    verify_csrf();
    $equipmentId = (int) ($_POST['equipment_id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $initialUpdate = trim((string) ($_POST['initial_update'] ?? ''));
    $equipment = query_one('SELECT id FROM equipment WHERE id = :id', ['id' => $equipmentId]);

    if (!$equipment) {
        flash('error', 'Nie znaleziono sprzętu.');
        redirect_to('index.php?page=equipment-list');
    }
    if ($title === '') {
        flash('error', 'Tytuł zadania jest wymagany.');
        redirect_to('index.php?page=item&id=' . $equipmentId);
    }

    $user = current_user();
    execute_sql(
        'INSERT INTO equipment_tasks (equipment_id, title, status, created_by, updated_by) VALUES (:equipment_id, :title, :status, :created_by, :updated_by)',
        [
            'equipment_id' => $equipmentId,
            'title' => $title,
            'status' => 'open',
            'created_by' => $user['id'],
            'updated_by' => $user['id'],
        ]
    );
    $taskId = (int) db()->lastInsertId();
    touch_equipment($equipmentId, (int) $user['id']);
    audit_log((int) $user['id'], 'equipment', $equipmentId, 'create_task', ['task_id' => $taskId, 'title' => $title]);

    if ($initialUpdate !== '') {
        execute_sql(
            'INSERT INTO equipment_task_updates (task_id, message, created_by) VALUES (:task_id, :message, :created_by)',
            [
                'task_id' => $taskId,
                'message' => $initialUpdate,
                'created_by' => $user['id'],
            ]
        );
        audit_log((int) $user['id'], 'equipment', $equipmentId, 'task_update', ['task_id' => $taskId]);
    }

    flash('success', 'Dodano zadanie do sprzętu.');
    redirect_to('index.php?page=item&id=' . $equipmentId);
}

if ($page === 'task-update') {
    require_login();
    if (!can_update_tasks()) {
        flash('error', 'Brak uprawnień do aktualizacji zadań.');
        redirect_to('index.php');
    }
    verify_csrf();
    $taskId = (int) ($_POST['task_id'] ?? 0);
    $message = trim((string) ($_POST['message'] ?? ''));
    $task = query_one('SELECT * FROM equipment_tasks WHERE id = :id', ['id' => $taskId]);

    if (!$task) {
        flash('error', 'Nie znaleziono zadania.');
        redirect_to('index.php?page=equipment-list');
    }
    if ($message === '') {
        flash('error', 'Treść aktualizacji jest wymagana.');
        redirect_to('index.php?page=item&id=' . (int) $task['equipment_id']);
    }

    execute_sql(
        'INSERT INTO equipment_task_updates (task_id, message, created_by) VALUES (:task_id, :message, :created_by)',
        [
            'task_id' => $taskId,
            'message' => $message,
            'created_by' => current_user()['id'],
        ]
    );
    execute_sql('UPDATE equipment_tasks SET updated_by = :updated_by WHERE id = :id', ['updated_by' => current_user()['id'], 'id' => $taskId]);
    touch_equipment((int) $task['equipment_id'], (int) current_user()['id']);
    audit_log((int) current_user()['id'], 'equipment', (int) $task['equipment_id'], 'task_update', ['task_id' => $taskId]);
    flash('success', 'Dodano aktualizację zadania.');
    redirect_to('index.php?page=item&id=' . (int) $task['equipment_id']);
}

if ($page === 'task-status') {
    require_login();
    if (!can_change_task_status()) {
        flash('error', 'Brak uprawnień do zmiany statusu zadań.');
        redirect_to('index.php');
    }
    verify_csrf();
    $taskId = (int) ($_POST['task_id'] ?? 0);
    $newStatus = (string) ($_POST['status'] ?? '');
    $message = trim((string) ($_POST['message'] ?? ''));
    $task = query_one('SELECT * FROM equipment_tasks WHERE id = :id', ['id' => $taskId]);

    if (!$task) {
        flash('error', 'Nie znaleziono zadania.');
        redirect_to('index.php?page=equipment-list');
    }

    $userId = (int) current_user()['id'];
    $params = ['id' => $taskId, 'updated_by' => $userId];
    $sql = 'UPDATE equipment_tasks SET status = :status, updated_by = :updated_by';
    $params['status'] = $newStatus;

    if ($newStatus === 'completed') {
        $sql .= ', completed_by = :completed_by, completed_at = NOW(), rejected_by = NULL, rejected_at = NULL';
        $params['completed_by'] = $userId;
        if ($message !== '') {
            $message = 'zakończono: ' . $message;
        }
    } elseif ($newStatus === 'rejected') {
        $sql .= ', rejected_by = :rejected_by, rejected_at = NOW(), completed_by = NULL, completed_at = NULL';
        $params['rejected_by'] = $userId;
        if ($message !== '') {
            $message = 'odrzucono: ' . $message;
        }
    } else {
        $sql .= ', completed_by = NULL, completed_at = NULL, rejected_by = NULL, rejected_at = NULL';
        if ($message !== '') {
            $message = 'przywrócono: ' . $message;
        }
    }

    $sql .= ' WHERE id = :id';
    execute_sql($sql, $params);
    touch_equipment((int) $task['equipment_id'], $userId);
    if ($message !== '') {
        execute_sql(
            'INSERT INTO equipment_task_updates (task_id, message, created_by) VALUES (:task_id, :message, :created_by)',
            [
                'task_id' => $taskId,
                'message' => $message,
                'created_by' => $userId,
            ]
        );
    }
    audit_log($userId, 'equipment', (int) $task['equipment_id'], 'task_status', ['task_id' => $taskId, 'status' => $newStatus]);
    flash('success', 'Zmieniono status zadania.');
    redirect_to('index.php?page=item&id=' . (int) $task['equipment_id']);
}

if ($page === 'task-delete') {
    require_login();
    if (!can_delete_tasks()) {
        flash('error', 'Brak uprawnień do usuwania zadań.');
        redirect_to('index.php');
    }
    verify_csrf();
    $taskId = (int) ($_POST['task_id'] ?? 0);
    $task = query_one('SELECT * FROM equipment_tasks WHERE id = :id', ['id' => $taskId]);

    if (!$task) {
        flash('error', 'Nie znaleziono zadania.');
        redirect_to('index.php?page=equipment-list');
    }

    execute_sql('DELETE FROM equipment_tasks WHERE id = :id', ['id' => $taskId]);
    touch_equipment((int) $task['equipment_id'], (int) current_user()['id']);
    audit_log((int) current_user()['id'], 'equipment', (int) $task['equipment_id'], 'delete_task', ['task_id' => $taskId, 'title' => $task['title']]);
    flash('success', 'Zadanie zostało usunięte.');
    redirect_to('index.php?page=item&id=' . (int) $task['equipment_id']);
}

if ($page === 'item-label') {
    $item = query_one(
        'SELECT e.*, c.name AS category_name, l.name AS location_name, lp.name AS place_name
         FROM equipment e
         JOIN categories c ON c.id = e.category_id
         LEFT JOIN locations l ON l.id = e.location_id
         LEFT JOIN location_places lp ON lp.id = e.location_place_id
         WHERE e.id = :id',
        ['id' => (int) ($_GET['id'] ?? 0)]
    );

    if (!$item) {
        flash('error', 'Nie znaleziono rekordu.');
        redirect_to('index.php?page=equipment-list');
    }

    $qrUrl = qr_target_url($item);
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($item['inventory_code']) ?> | Etykieta</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body class="label-print-page">
    <main class="label-print-shell">
        <?= qr_label_markup($qrUrl, (string) $item['inventory_code'], 320) ?>
    </main>
    </body>
    </html>
    <?php
    exit;
}

if ($page === 'audit-log') {
    require_audit_history_access();

    $filters = [
        'date_from' => trim((string) ($_GET['date_from'] ?? '')),
        'date_to' => trim((string) ($_GET['date_to'] ?? '')),
        'user_id' => (int) ($_GET['user_id'] ?? 0),
        'equipment_id' => (int) ($_GET['equipment_id'] ?? 0),
    ];

    $where = [];
    $params = [];
    if ($filters['date_from'] !== '') {
        $where[] = 'a.created_at >= :date_from';
        $params['date_from'] = $filters['date_from'] . ' 00:00:00';
    }
    if ($filters['date_to'] !== '') {
        $where[] = 'a.created_at <= :date_to';
        $params['date_to'] = $filters['date_to'] . ' 23:59:59';
    }
    if ($filters['user_id'] > 0) {
        $where[] = 'a.user_id = :user_id';
        $params['user_id'] = $filters['user_id'];
    }
    if ($filters['equipment_id'] > 0) {
        $where[] = 'a.entity_type = :equipment_type AND a.entity_id = :equipment_id';
        $params['equipment_type'] = 'equipment';
        $params['equipment_id'] = $filters['equipment_id'];
    }

    $sql = 'SELECT a.*, u.display_name, e.inventory_code, e.title,
                   target_user.display_name AS target_display_name,
                   target_user.username AS target_username
            FROM audit_logs a
            LEFT JOIN users u ON u.id = a.user_id
            LEFT JOIN equipment e ON e.id = a.entity_id AND a.entity_type = \'equipment\'
            LEFT JOIN users target_user ON target_user.id = a.entity_id AND a.entity_type = \'user\'';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY a.created_at DESC, a.id DESC LIMIT 200';

    $entries = query_all($sql, $params);
    $users = query_all('SELECT id, display_name, username FROM users ORDER BY display_name');
    $equipmentOptions = query_all('SELECT id, inventory_code, title FROM equipment ORDER BY inventory_code');

    render_header('Historia zdarzeń');
    ?>
    <section class="panel">
        <div class="topbar">
            <div>
                <h2>Historia zdarzeń</h2>
                <p class="muted">Pełniejszy widok audytu z filtrowaniem po dacie, użytkowniku i sprzęcie.</p>
            </div>
        </div>
        <form method="get" class="form-grid" style="margin-bottom: 18px;">
            <input type="hidden" name="page" value="audit-log">
            <div>
                <label for="date_from">Data od</label>
                <input id="date_from" type="date" name="date_from" value="<?= h($filters['date_from']) ?>">
            </div>
            <div>
                <label for="date_to">Data do</label>
                <input id="date_to" type="date" name="date_to" value="<?= h($filters['date_to']) ?>">
            </div>
            <div>
                <label for="user_id">Użytkownik</label>
                <select id="user_id" name="user_id">
                    <option value="">Wszyscy</option>
                    <?php foreach ($users as $userOption): ?>
                        <option value="<?= (int) $userOption['id'] ?>"<?= $filters['user_id'] === (int) $userOption['id'] ? ' selected' : '' ?>><?= h($userOption['display_name']) ?> (<?= h($userOption['username']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="flex: 2 1 320px;">
                <label for="equipment_id">Sprzęt</label>
                <select id="equipment_id" name="equipment_id">
                    <option value="">Wszystkie rekordy</option>
                    <?php foreach ($equipmentOptions as $equipmentOption): ?>
                        <option value="<?= (int) $equipmentOption['id'] ?>"<?= $filters['equipment_id'] === (int) $equipmentOption['id'] ? ' selected' : '' ?>><?= h($equipmentOption['inventory_code'] . ' - ' . $equipmentOption['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="actions" style="align-items: end;">
                <button class="button" type="submit">Filtruj</button>
                <a class="button" href="index.php?page=audit-log">Wyczyść</a>
            </div>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Data</th>
                    <th>Użytkownik</th>
                    <th>Zdarzenie</th>
                    <th>Cel</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$entries): ?>
                    <tr><td colspan="4" class="muted">Brak zdarzeń dla wybranych filtrów.</td></tr>
                <?php endif; ?>
                <?php foreach ($entries as $entry): ?>
                    <?php $details = !empty($entry['details_json']) ? (json_decode((string) $entry['details_json'], true) ?: []) : []; ?>
                    <tr>
                        <td><?= h($entry['created_at']) ?></td>
                        <td><?= h($entry['display_name'] ?? 'system') ?></td>
                        <td><?= h(audit_action_label($entry['action_name'])) ?></td>
                        <td><?= audit_target_label_html($entry, $details) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
    render_footer();
    exit;
}

if ($page === 'equipment-form-meta') {
    require_editor();

    header('Content-Type: application/json; charset=utf-8');

    $action = (string) ($_GET['action'] ?? '');
    if ($action === 'inventory-code') {
        $categoryId = (int) ($_GET['category_id'] ?? 0);
        $code = $categoryId > 0 ? generate_next_inventory_code($categoryId) : null;
        echo json_encode(['inventory_code' => $code], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($action === 'location-places') {
        $locationId = (int) ($_GET['location_id'] ?? 0);
        $places = $locationId > 0
            ? query_all('SELECT id, name FROM location_places WHERE location_id = :location_id ORDER BY name', ['location_id' => $locationId])
            : [];
        echo json_encode(['places' => $places], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'invalid_action'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}


if ($page === 'equipment-export-csv') {
    require_csv_export();

    $rows = query_all(
        'SELECT e.*, c.name AS category_name, c.slug AS category_slug, c.code_prefix AS category_code_prefix,
                l.name AS location_name, l.slug AS location_slug,
                lp.name AS place_name, lp.slug AS place_slug,
                parent.inventory_code AS parent_inventory_code,
                creator.username AS created_by_username, updater.username AS updated_by_username,
                creator.display_name AS created_by_name, updater.display_name AS updated_by_name,
                (SELECT COUNT(*) FROM equipment_images ei WHERE ei.equipment_id = e.id) AS images_count,
                (SELECT COUNT(*) FROM equipment_tasks et WHERE et.equipment_id = e.id) AS tasks_count
         FROM equipment e
         JOIN categories c ON c.id = e.category_id
         LEFT JOIN locations l ON l.id = e.location_id
         LEFT JOIN location_places lp ON lp.id = e.location_place_id
         LEFT JOIN equipment parent ON parent.id = e.parent_equipment_id
         JOIN users creator ON creator.id = e.created_by
         JOIN users updater ON updater.id = e.updated_by
         ORDER BY e.updated_at DESC, e.inventory_code ASC'
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="equipment-export-' . date('Ymd-His') . '.csv"');

    $out = fopen('php://output', 'wb');
    if ($out === false) {
        http_response_code(500);
        exit('Nie udało się wygenerować pliku CSV.');
    }

    $headers = [
        'inventory_code', 'title', 'category_name', 'category_slug', 'category_code_prefix', 'manufacturer', 'model', 'production_year',
        'condition_status_name', 'condition_status_slug', 'ownership_status_name', 'ownership_status_slug',
        'location_name', 'location_slug', 'place_name', 'place_slug', 'location_text',
        'barcode_value', 'notes', 'parent_inventory_code', 'inventory_code_manual_override', 'qr_token',
        'created_at', 'updated_at', 'created_by_username', 'created_by_name', 'updated_by_username', 'updated_by_name',
        'images_count', 'tasks_count'
    ];
    fputcsv($out, $headers);

    foreach ($rows as $row) {
        fputcsv($out, [
            csv_value($row['inventory_code'] ?? ''),
            csv_value($row['title'] ?? ''),
            csv_value($row['category_name'] ?? ''),
            csv_value($row['category_slug'] ?? ''),
            csv_value($row['category_code_prefix'] ?? ''),
            csv_value($row['manufacturer'] ?? ''),
            csv_value($row['model'] ?? ''),
            csv_value((string) ($row['production_year'] ?? '')),
            csv_value(condition_label((string) ($row['condition_status'] ?? ''))),
            csv_value($row['condition_status'] ?? ''),
            csv_value(ownership_label((string) ($row['ownership_status'] ?? ''))),
            csv_value($row['ownership_status'] ?? ''),
            csv_value($row['location_name'] ?? ''),
            csv_value($row['location_slug'] ?? ''),
            csv_value($row['place_name'] ?? ''),
            csv_value($row['place_slug'] ?? ''),
            csv_value($row['location_text'] ?? ''),
            csv_value($row['barcode_value'] ?? ''),
            csv_value($row['notes'] ?? ''),
            csv_value($row['parent_inventory_code'] ?? ''),
            (int) ($row['inventory_code_manual_override'] ?? 0),
            csv_value($row['qr_token'] ?? ''),
            csv_value($row['created_at'] ?? ''),
            csv_value($row['updated_at'] ?? ''),
            csv_value($row['created_by_username'] ?? ''),
            csv_value($row['created_by_name'] ?? ''),
            csv_value($row['updated_by_username'] ?? ''),
            csv_value($row['updated_by_name'] ?? ''),
            (int) ($row['images_count'] ?? 0),
            (int) ($row['tasks_count'] ?? 0),
        ]);
    }

    fclose($out);
    audit_log((int) current_user()['id'], 'equipment', 0, 'export_equipment_csv', ['rows' => count($rows)]);
    exit;
}

if ($page === 'equipment-import-csv') {
    require_csv_import();

    if (!is_post()) {
        redirect_to('index.php?page=equipment-list');
    }
    verify_csrf();

    if (!isset($_FILES['csv_file']) || (int) ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        flash('error', 'Nie udało się wczytać pliku CSV.');
        redirect_to('index.php?page=equipment-list');
    }

    $tmpPath = (string) ($_FILES['csv_file']['tmp_name'] ?? '');
    $handle = fopen($tmpPath, 'rb');
    if ($handle === false) {
        flash('error', 'Nie udało się otworzyć pliku CSV.');
        redirect_to('index.php?page=equipment-list');
    }

    $header = fgetcsv($handle);
    if (!is_array($header)) {
        fclose($handle);
        flash('error', 'Plik CSV jest pusty lub uszkodzony.');
        redirect_to('index.php?page=equipment-list');
    }

    $headerMap = [];
    foreach ($header as $i => $column) {
        $headerMap[trim((string) $column)] = (int) $i;
    }

    $requiredColumns = ['inventory_code', 'title', 'category_name'];
    foreach ($requiredColumns as $required) {
        if (!array_key_exists($required, $headerMap)) {
            fclose($handle);
            flash('error', 'Brak wymaganej kolumny: ' . $required . '.');
            redirect_to('index.php?page=equipment-list');
        }
    }

    $current = current_user();
    $imported = 0;
    $updated = 0;
    $conflicts = [];
    $pendingParents = [];
    $line = 1;

    while (($data = fgetcsv($handle)) !== false) {
        $line++;
        $col = static function (array $row, array $map, string $name): string {
            if (!array_key_exists($name, $map)) {
                return '';
            }
            return trim((string) ($row[$map[$name]] ?? ''));
        };

        $inventoryCode = $col($data, $headerMap, 'inventory_code');
        $title = $col($data, $headerMap, 'title');
        $categoryName = $col($data, $headerMap, 'category_name');
        if ($inventoryCode === '' || $title === '' || $categoryName === '') {
            $conflicts[] = 'Wiersz ' . $line . ': brak wymaganych pól (inventory_code/title/category_name).';
            continue;
        }

        try {
            $categoryId = ensure_category_for_import(
                $categoryName,
                $col($data, $headerMap, 'category_slug'),
                $col($data, $headerMap, 'category_code_prefix')
            );
            $conditionSlug = ensure_dictionary_entry_for_import(
                'condition_statuses',
                $col($data, $headerMap, 'condition_status_name'),
                $col($data, $headerMap, 'condition_status_slug') !== '' ? $col($data, $headerMap, 'condition_status_slug') : 'inventory'
            );
            $ownershipSlug = ensure_dictionary_entry_for_import(
                'ownership_statuses',
                $col($data, $headerMap, 'ownership_status_name'),
                $col($data, $headerMap, 'ownership_status_slug') !== '' ? $col($data, $headerMap, 'ownership_status_slug') : 'unknown'
            );

            $locationId = null;
            $locationName = $col($data, $headerMap, 'location_name');
            $locationSlug = $col($data, $headerMap, 'location_slug');
            if ($locationName !== '' || $locationSlug !== '') {
                $locationId = ensure_location_for_import($locationName !== '' ? $locationName : $locationSlug, $locationSlug);
            }

            $placeId = null;
            $placeName = $col($data, $headerMap, 'place_name');
            $placeSlug = $col($data, $headerMap, 'place_slug');
            if ($locationId !== null && ($placeName !== '' || $placeSlug !== '')) {
                $placeId = ensure_location_place_for_import($locationId, $placeName !== '' ? $placeName : $placeSlug, $placeSlug);
            }

            $locationText = $col($data, $headerMap, 'location_text');
            if ($locationText === '' && $locationName !== '') {
                $locationText = $locationName . ($placeName !== '' ? ' / ' . $placeName : '');
            }

            $payload = [
                'category_id' => $categoryId,
                'title' => $title,
                'manufacturer' => $col($data, $headerMap, 'manufacturer'),
                'model' => $col($data, $headerMap, 'model'),
                'production_year' => $col($data, $headerMap, 'production_year') !== '' ? (int) $col($data, $headerMap, 'production_year') : null,
                'condition_status' => $conditionSlug,
                'ownership_status' => $ownershipSlug,
                'location_id' => $locationId,
                'location_place_id' => $placeId,
                'location_text' => $locationText,
                'barcode_value' => $col($data, $headerMap, 'barcode_value'),
                'notes' => $col($data, $headerMap, 'notes'),
                'inventory_code_manual_override' => $col($data, $headerMap, 'inventory_code_manual_override') === '1' ? 1 : 0,
                'inventory_code' => $inventoryCode,
                'updated_by' => (int) $current['id'],
            ];

            $existing = query_one('SELECT id FROM equipment WHERE inventory_code = :inventory_code', ['inventory_code' => $inventoryCode]);
            if ($existing) {
                $payload['id'] = (int) $existing['id'];
                execute_sql(
                    'UPDATE equipment SET category_id = :category_id, title = :title, manufacturer = :manufacturer, model = :model, production_year = :production_year, condition_status = :condition_status, ownership_status = :ownership_status, location_id = :location_id, location_place_id = :location_place_id, location_text = :location_text, barcode_value = :barcode_value, notes = :notes, inventory_code_manual_override = :inventory_code_manual_override, updated_by = :updated_by WHERE id = :id',
                    $payload
                );
                $equipmentId = (int) $existing['id'];
                $updated++;
            } else {
                $payload['created_by'] = (int) $current['id'];
                $payload['qr_token'] = strtolower(bin2hex(random_bytes(8)));
                execute_sql(
                    'INSERT INTO equipment (category_id, location_id, location_place_id, inventory_code, inventory_code_manual_override, title, manufacturer, model, production_year, condition_status, ownership_status, location_text, barcode_value, qr_token, notes, created_by, updated_by) VALUES (:category_id, :location_id, :location_place_id, :inventory_code, :inventory_code_manual_override, :title, :manufacturer, :model, :production_year, :condition_status, :ownership_status, :location_text, :barcode_value, :qr_token, :notes, :created_by, :updated_by)',
                    $payload
                );
                $equipmentId = (int) db()->lastInsertId();
                $imported++;
            }

            $parentCode = $col($data, $headerMap, 'parent_inventory_code');
            if ($parentCode !== '') {
                $pendingParents[] = ['equipment_id' => $equipmentId, 'parent_inventory_code' => $parentCode, 'line' => $line];
            }
        } catch (Throwable $exception) {
            $conflicts[] = 'Wiersz ' . $line . ': ' . $exception->getMessage();
        }
    }

    fclose($handle);

    foreach ($pendingParents as $parentLink) {
        $parent = query_one('SELECT id FROM equipment WHERE inventory_code = :inventory_code', ['inventory_code' => $parentLink['parent_inventory_code']]);
        if (!$parent) {
            $conflicts[] = 'Wiersz ' . $parentLink['line'] . ': nie znaleziono parent_inventory_code=' . $parentLink['parent_inventory_code'] . '.';
            continue;
        }
        if ((int) $parent['id'] === (int) $parentLink['equipment_id']) {
            $conflicts[] = 'Wiersz ' . $parentLink['line'] . ': sprzęt nie może być własnym parentem.';
            continue;
        }
        execute_sql(
            'UPDATE equipment SET parent_equipment_id = :parent_id WHERE id = :id',
            ['parent_id' => (int) $parent['id'], 'id' => (int) $parentLink['equipment_id']]
        );
    }

    audit_log((int) $current['id'], 'equipment', 0, 'import_equipment_csv', [
        'imported' => $imported,
        'updated' => $updated,
        'conflicts' => count($conflicts),
    ]);

    if ($conflicts !== []) {
        $preview = implode(' | ', array_slice($conflicts, 0, 4));
        if (count($conflicts) > 4) {
            $preview .= ' | ...';
        }
        flash('error', 'Import zakończony z konfliktami (' . count($conflicts) . '): ' . $preview);
    }

    flash('success', 'Import CSV zakończony. Dodano: ' . $imported . ', zaktualizowano: ' . $updated . '.');
    redirect_to('index.php?page=equipment-list');
}

// Lista sprzętu i filtrowanie.
if ($page === 'equipment-list') {
    $filters = [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'category_id' => (int) ($_GET['category_id'] ?? 0),
        'location_id' => (int) ($_GET['location_id'] ?? 0),
        'place_id' => (int) ($_GET['place_id'] ?? 0),
        'condition_status' => trim((string) ($_GET['condition_status'] ?? '')),
        'ownership_status' => trim((string) ($_GET['ownership_status'] ?? '')),
    ];

    $where = [];
    $params = [];

    if ($filters['q'] !== '') {
        $where[] = '(e.inventory_code LIKE :search OR e.title LIKE :search OR e.manufacturer LIKE :search OR e.model LIKE :search OR e.location_text LIKE :search OR l.name LIKE :search OR lp.name LIKE :search)';
        $params['search'] = '%' . $filters['q'] . '%';
    }
    if ($filters['category_id'] > 0) {
        $where[] = 'e.category_id = :category_id';
        $params['category_id'] = $filters['category_id'];
    }
    if ($filters['location_id'] > 0) {
        $where[] = 'e.location_id = :location_id';
        $params['location_id'] = $filters['location_id'];
    }
    if ($filters['place_id'] > 0) {
        $where[] = 'e.location_place_id = :place_id';
        $params['place_id'] = $filters['place_id'];
    }
    if ($filters['condition_status'] !== '') {
        $where[] = 'e.condition_status = :condition_status';
        $params['condition_status'] = $filters['condition_status'];
    }
    if ($filters['ownership_status'] !== '') {
        $where[] = 'e.ownership_status = :ownership_status';
        $params['ownership_status'] = $filters['ownership_status'];
    }

    $sql = 'SELECT e.*, c.name AS category_name, u.display_name AS updated_by_name, l.name AS location_name, lp.name AS place_name, img.file_path AS image_path
            FROM equipment e
            JOIN categories c ON c.id = e.category_id
            JOIN users u ON u.id = e.updated_by
            LEFT JOIN locations l ON l.id = e.location_id
            LEFT JOIN location_places lp ON lp.id = e.location_place_id
            LEFT JOIN (
                SELECT ei.equipment_id, ei.file_path
                FROM equipment_images ei
                INNER JOIN (
                    SELECT equipment_id, MIN(id) AS min_id
                    FROM equipment_images
                    GROUP BY equipment_id
                ) first_img ON first_img.min_id = ei.id
            ) img ON img.equipment_id = e.id';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY e.updated_at DESC, e.inventory_code ASC';
    $equipment = query_all($sql, $params);
    render_header('Sprzęt');
    ?>
    <section class="panel">
        <div class="topbar">
            <div>
                <h2>Lista sprzętu</h2>
                <p class="muted">Widok roboczy dla katalogowania, statusów i podstawowych relacji.</p>
            </div>
            <div class="actions">
                <?php if (can_export_csv()): ?>
                    <a class="button" href="index.php?page=equipment-export-csv">Eksport CSV</a>
                <?php endif; ?>
                <?php if (can_import_csv()): ?>
                    <form method="post" action="index.php?page=equipment-import-csv" enctype="multipart/form-data" class="inline-form">
                        <?= csrf_field() ?>
                        <label class="button" style="cursor: pointer;">
                            Import CSV
                            <input type="file" name="csv_file" accept=".csv,text/csv" required onchange="this.form.submit()" style="display:none;">
                        </label>
                    </form>
                <?php endif; ?>
                <?php if (can_edit_records()): ?>
                    <a class="button primary" href="index.php?page=equipment-new">Dodaj sprzęt</a>
                <?php endif; ?>
            </div>
        </div>
        <form method="get" class="form-grid" style="margin-bottom: 18px;">
            <input type="hidden" name="page" value="equipment-list">
            <div>
                <label for="q">Szukaj</label>
                <input id="q" name="q" value="<?= h($filters['q']) ?>" placeholder="Kod, nazwa zwyczajowa, producent, model, lokalizacja">
            </div>
            <div>
                <label for="category_id">Kategoria</label>
                <select id="category_id" name="category_id" onchange="this.form.submit()">
                    <option value="">Wszystkie</option>
                    <?= category_options($filters['category_id'] > 0 ? $filters['category_id'] : null) ?>
                </select>
            </div>
            <div>
                <label for="location_id">Lokalizacja</label>
                <select id="location_id" name="location_id" onchange="this.form.submit()"><?= location_options($filters['location_id'] > 0 ? $filters['location_id'] : null) ?></select>
            </div>
            <div>
                <label for="place_id">Miejsce</label>
                <select id="place_id" name="place_id" onchange="this.form.submit()"><?= location_place_options($filters['place_id'] > 0 ? $filters['place_id'] : null, $filters['location_id'] > 0 ? $filters['location_id'] : null) ?></select>
            </div>
            <div>
                <label for="condition_status">Stan / status</label>
                <select id="condition_status" name="condition_status" onchange="this.form.submit()"><?= status_options('condition_statuses', $filters['condition_status']) ?></select>
            </div>
            <div>
                <label for="ownership_status">Status formalny</label>
                <select id="ownership_status" name="ownership_status" onchange="this.form.submit()"><?= status_options('ownership_statuses', $filters['ownership_status']) ?></select>
            </div>
            <div class="actions" style="align-items: end;">
                <button class="button" type="submit">Filtruj</button>
                <a class="button" href="index.php?page=equipment-list">Wyczyść</a>
            </div>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Kod</th>
                    <th>Foto</th>
                    <th>Nazwa zwyczajowa</th>
                    <th>Kategoria</th>
                    <th>Lokalizacja</th>
                    <th>Status</th>
                    <th>Status formalny</th>
                    <th>Aktualizacja</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$equipment): ?>
                    <tr>
                        <td colspan="8" class="muted">Brak rekordów dla aktualnych filtrów.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($equipment as $row): ?>
                    <tr>
                        <td><a href="index.php?page=item&id=<?= (int) $row['id'] ?>"><?= h($row['inventory_code']) ?></a></td>
                        <td>
                            <?php if (!empty($row['image_path'])): ?>
                                <img class="thumb" src="<?= h($row['image_path']) ?>" alt="<?= h($row['title']) ?>">
                            <?php else: ?>
                                <div class="thumb-placeholder">brak</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= h($row['title']) ?></strong><br><span class="muted"><?= h($row['manufacturer']) ?> / <?= h($row['model']) ?></span></td>
                        <td><?= h($row['category_name']) ?></td>
                        <td><?= h(location_label($row) ?: 'Brak') ?></td>
                        <td><span class="badge"><?= h(condition_label($row['condition_status'])) ?></span></td>
                        <td><?= h(ownership_label($row['ownership_status'])) ?></td>
                        <td><?= h($row['updated_by_name']) ?><br><span class="muted"><?= h($row['updated_at']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php
    render_footer();
    exit;
}

// Formularz dodawania/edycji sprzętu.
if ($page === 'equipment-new' || $page === 'equipment-edit') {
    require_editor();
    $isNewEquipment = $page === 'equipment-new';
    $equipment = [
        'id' => 0,
        'category_id' => null,
        'parent_equipment_id' => null,
        'location_id' => null,
        'location_place_id' => null,
        'inventory_code' => '',
        'inventory_code_manual_override' => 0,
        'title' => '',
        'manufacturer' => '',
        'model' => '',
        'production_year' => '',
        'condition_status' => 'inventory',
        'ownership_status' => 'unknown',
        'location_text' => '',
        'barcode_value' => '',
        'notes' => '',
    ];

    if ($page === 'equipment-edit') {
        $equipment = query_one('SELECT * FROM equipment WHERE id = :id', ['id' => (int) ($_GET['id'] ?? 0)]);
        if (!$equipment) {
            flash('error', 'Nie znaleziono rekordu sprzetu.');
            redirect_to('index.php?page=equipment-list');
        }
    }

    render_header($page === 'equipment-new' ? 'Nowy sprzet' : 'Edycja sprzetu');
    ?>
    <section class="panel">
        <h2><?= $page === 'equipment-new' ? 'Nowy rekord sprzetu' : 'Edycja rekordu sprzetu' ?></h2>
        <form method="post" action="index.php?page=equipment-save" id="equipment-form" data-is-new="<?= $isNewEquipment ? '1' : '0' ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $equipment['id'] ?>">
            <input type="hidden" id="inventory_code_manual_override" name="inventory_code_manual_override" value="<?= (int) ($equipment['inventory_code_manual_override'] ?? 0) ?>">
            <div class="form-grid">
                <div>
                    <label for="category_id">Kategoria</label>
                    <select id="category_id" name="category_id" required><?= category_options($equipment['category_id'] ? (int) $equipment['category_id'] : null, $isNewEquipment, 'Wybierz kategorię') ?></select>
                </div>
                <div>
                    <label for="inventory_code">Kod inwentarzowy</label>
                    <input id="inventory_code" name="inventory_code" value="<?= h($equipment['inventory_code']) ?>" placeholder="Uzupełni się po wyborze kategorii">
                    <div class="muted helper-text" id="inventory-code-state"><?= ((int) ($equipment['inventory_code_manual_override'] ?? 0) === 1) ? 'Tryb ręczny: kod został nadpisany przez użytkownika.' : 'Tryb automatyczny: kod podpowiada się z kategorii, ale nadal można go ręcznie skorygować.' ?></div>
                </div>
                <div style="flex: 2 1 320px;">
                    <label for="title">Nazwa zwyczajowa</label>
                    <input id="title" name="title" value="<?= h($equipment['title']) ?>" required>
                </div>
                <div>
                    <label for="manufacturer">Producent</label>
                    <input id="manufacturer" name="manufacturer" value="<?= h($equipment['manufacturer']) ?>">
                </div>
                <div>
                    <label for="model">Model</label>
                    <input id="model" name="model" value="<?= h($equipment['model']) ?>">
                </div>
                <div>
                    <label for="production_year">Rok</label>
                    <input id="production_year" name="production_year" value="<?= h($equipment['production_year']) ?>">
                </div>
                <div>
                    <label for="condition_status">Stan / status</label>
                    <select id="condition_status" name="condition_status"><?= dictionary_options('condition_statuses', $equipment['condition_status']) ?></select>
                </div>
                <div>
                    <label for="ownership_status">Status formalny</label>
                    <select id="ownership_status" name="ownership_status"><?= dictionary_options('ownership_statuses', $equipment['ownership_status']) ?></select>
                </div>
                <div>
                    <label for="location_id">Lokalizacja</label>
                    <select id="location_id" name="location_id"><?= location_options($equipment['location_id'] ? (int) $equipment['location_id'] : null) ?></select>
                </div>
                <div>
                    <label for="location_place_id">Miejsce w lokalizacji</label>
                    <select id="location_place_id" name="location_place_id"><?= location_place_options($equipment['location_place_id'] ? (int) $equipment['location_place_id'] : null, $equipment['location_id'] ? (int) $equipment['location_id'] : null) ?></select>
                </div>
                <div style="flex-basis: 100%;">
                    <label for="location_text">Opis lokalizacji / fallback</label>
                    <input id="location_text" name="location_text" value="<?= h($equipment['location_text']) ?>" placeholder="Używane, jeśli rekord nie jest jeszcze przypisany do słownika.">
                </div>
                <div>
                    <label for="barcode_value">Kod kreskowy / numer</label>
                    <input id="barcode_value" name="barcode_value" value="<?= h($equipment['barcode_value']) ?>">
                </div>
                <div style="flex-basis: 100%;">
                    <label for="notes">Notatki</label>
                    <textarea id="notes" name="notes"><?= h($equipment['notes']) ?></textarea>
                </div>
                <div>
                    <label for="parent_equipment_id">Sprzęt nadrzędny</label>
                    <select id="parent_equipment_id" name="parent_equipment_id"><?= equipment_parent_options($equipment['parent_equipment_id'] ? (int) $equipment['parent_equipment_id'] : null, $equipment['id'] ? (int) $equipment['id'] : null) ?></select>
                </div>
            </div>
            <div class="actions" style="margin-top: 16px;">
                <button class="button primary" type="submit">Zapisz rekord</button>
            </div>
        </form>
        <script>
        (function () {
            var form = document.getElementById('equipment-form');
            if (!form) {
                return;
            }

            var isNewEquipment = form.dataset.isNew === '1';
            var categoryField = document.getElementById('category_id');
            var inventoryCodeField = document.getElementById('inventory_code');
            var inventoryCodeManualField = document.getElementById('inventory_code_manual_override');
            var inventoryCodeState = document.getElementById('inventory-code-state');
            var locationField = document.getElementById('location_id');
            var placeField = document.getElementById('location_place_id');
            var lastGeneratedCode = isNewEquipment ? (inventoryCodeField.value || '') : '';

            function updateInventoryCodeState() {
                if (!inventoryCodeManualField || !inventoryCodeState) {
                    return;
                }
                inventoryCodeState.textContent = inventoryCodeManualField.value === '1'
                    ? 'Tryb ręczny: kod został nadpisany przez użytkownika.'
                    : 'Tryb automatyczny: kod podpowiada się z kategorii, ale nadal można go ręcznie skorygować.';
            }

            function updateInventoryCode() {
                if (!categoryField || !inventoryCodeField || !inventoryCodeManualField) {
                    return;
                }

                var categoryId = categoryField.value;
                if (!categoryId) {
                    if (inventoryCodeManualField.value !== '1' && inventoryCodeField.value === lastGeneratedCode) {
                        inventoryCodeField.value = '';
                    }
                    lastGeneratedCode = '';
                    inventoryCodeManualField.value = '0';
                    updateInventoryCodeState();
                    return;
                }

                fetch('index.php?page=equipment-form-meta&action=inventory-code&category_id=' + encodeURIComponent(categoryId), {headers: {'Accept': 'application/json'}})
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        if (!payload || !payload.inventory_code) {
                            return;
                        }
                        if (inventoryCodeManualField.value !== '1' && (inventoryCodeField.value === '' || inventoryCodeField.value === lastGeneratedCode)) {
                            inventoryCodeField.value = payload.inventory_code;
                            lastGeneratedCode = payload.inventory_code;
                            inventoryCodeManualField.value = '0';
                            updateInventoryCodeState();
                        }
                    })
                    .catch(function () {});
            }

            function updateLocationPlaces() {
                if (!locationField || !placeField) {
                    return;
                }

                var locationId = locationField.value;
                if (!locationId) {
                    placeField.innerHTML = '<option value="">Brak</option>';
                    return;
                }

                fetch('index.php?page=equipment-form-meta&action=location-places&location_id=' + encodeURIComponent(locationId), {headers: {'Accept': 'application/json'}})
                    .then(function (response) { return response.json(); })
                    .then(function (payload) {
                        placeField.innerHTML = '';
                        var emptyOption = document.createElement('option');
                        emptyOption.value = '';
                        emptyOption.textContent = 'Brak';
                        placeField.appendChild(emptyOption);
                        (payload.places || []).forEach(function (place) {
                            var option = document.createElement('option');
                            option.value = String(place.id);
                            option.textContent = String(place.name);
                            placeField.appendChild(option);
                        });
                    })
                    .catch(function () {});
            }

            if (categoryField) {
                categoryField.addEventListener('change', updateInventoryCode);
            }
            if (inventoryCodeField && inventoryCodeManualField) {
                inventoryCodeField.addEventListener('input', function () {
                    if (inventoryCodeField.value === '' || inventoryCodeField.value === lastGeneratedCode) {
                        inventoryCodeManualField.value = '0';
                    } else {
                        inventoryCodeManualField.value = '1';
                    }
                    updateInventoryCodeState();
                });
            }
            if (locationField) {
                locationField.addEventListener('change', function () {
                    placeField.value = '';
                    updateLocationPlaces();
                });
            }

            updateInventoryCodeState();
        }());
        </script>
    </section>
    <?php
    render_footer();
    exit;
}

// Karta szczegółów pojedynczego elementu sprzętu.
if ($page === 'item') {
    $item = query_one(
        'SELECT e.*, c.name AS category_name, creator.display_name AS created_by_name, updater.display_name AS updated_by_name, parent.inventory_code AS parent_code, parent.title AS parent_title, l.name AS location_name, lp.name AS place_name
         FROM equipment e
         JOIN categories c ON c.id = e.category_id
         JOIN users creator ON creator.id = e.created_by
         JOIN users updater ON updater.id = e.updated_by
         LEFT JOIN equipment parent ON parent.id = e.parent_equipment_id
         LEFT JOIN locations l ON l.id = e.location_id
         LEFT JOIN location_places lp ON lp.id = e.location_place_id
         WHERE e.id = :id',
        ['id' => (int) ($_GET['id'] ?? 0)]
    );

    if (!$item) {
        flash('error', 'Nie znaleziono rekordu.');
        redirect_to('index.php?page=equipment-list');
    }

    $images = query_all('SELECT * FROM equipment_images WHERE equipment_id = :id ORDER BY created_at DESC', ['id' => $item['id']]);
    $primaryImage = $images[0] ?? null;
    $qrUrl = qr_target_url($item);
    $tasks = query_all(
        'SELECT t.*, creator.display_name AS created_by_name, updater.display_name AS updated_by_name, completer.display_name AS completed_by_name, rejecter.display_name AS rejected_by_name
         FROM equipment_tasks t
         JOIN users creator ON creator.id = t.created_by
         JOIN users updater ON updater.id = t.updated_by
         LEFT JOIN users completer ON completer.id = t.completed_by
         LEFT JOIN users rejecter ON rejecter.id = t.rejected_by
         WHERE t.equipment_id = :equipment_id
         ORDER BY FIELD(t.status, "open", "completed", "rejected"), t.updated_at DESC, t.created_at DESC',
        ['equipment_id' => $item['id']]
    );
    $taskUpdates = [];
    if ($tasks) {
        $taskIds = array_map(static fn(array $task): int => (int) $task['id'], $tasks);
        $taskUpdatesRows = query_all(
            'SELECT tu.*, u.display_name
             FROM equipment_task_updates tu
             JOIN users u ON u.id = tu.created_by
             WHERE tu.task_id IN (' . implode(',', $taskIds) . ')
             ORDER BY tu.created_at DESC, tu.id DESC'
        );
        foreach ($taskUpdatesRows as $row) {
            $taskUpdates[(int) $row['task_id']][] = $row;
        }
    }
    $openTasks = array_values(array_filter($tasks, static fn(array $task): bool => $task['status'] === 'open'));
    $audit = query_all(
        'SELECT a.*, u.display_name
         FROM audit_logs a
         LEFT JOIN users u ON u.id = a.user_id
         WHERE a.entity_type = :entity_type AND a.entity_id = :entity_id
         ORDER BY a.created_at DESC
         LIMIT 20',
        ['entity_type' => 'equipment', 'entity_id' => $item['id']]
    );

    render_header($item['inventory_code']);
    ?>
    <section class="split">
        <div>
            <section class="panel">
                <div class="topbar">
                    <div>
                        <div class="badge"><?= h($item['category_name']) ?></div>
                        <h2><?= h($item['inventory_code']) ?> - <?= h($item['title']) ?></h2>
                    </div>
                    <?php if (can_edit_records()): ?>
                        <a class="button primary" href="index.php?page=equipment-edit&id=<?= (int) $item['id'] ?>">Edytuj</a>
                    <?php endif; ?>
                </div>
                <div class="form-grid">
                    <div><label>Producent</label><div><?= h($item['manufacturer']) ?></div></div>
                    <div><label>Model</label><div><?= h($item['model']) ?></div></div>
                    <div><label>Rok</label><div><?= h($item['production_year']) ?></div></div>
                    <div><label>Status</label><div><?= h(condition_label($item['condition_status'])) ?></div></div>
                    <div><label>Status formalny</label><div><?= h(ownership_label($item['ownership_status'])) ?></div></div>
                    <div><label>Lokalizacja</label><div><?= h(location_label($item) ?: 'Brak') ?></div></div>
                    <div><label>Kod kreskowy / numer</label><div><?= h($item['barcode_value']) ?></div></div>
                    <div><label>Sprzęt nadrzędny</label><div><?= $item['parent_code'] ? h($item['parent_code'] . ' - ' . $item['parent_title']) : 'Brak' ?></div></div>
                    <div><label>Utworzył</label><div><?= h($item['created_by_name']) ?><br><span class="muted"><?= h($item['created_at']) ?></span></div></div>
                    <div><label>Ostatnia aktualizacja</label><div><?= h($item['updated_by_name']) ?><br><span class="muted"><?= h($item['updated_at']) ?></span></div></div>
                    <div style="flex-basis: 100%;"><label>Notatki</label><div><?= nl2br(h($item['notes'])) ?></div></div>
                </div>
                <div class="qr-block" style="margin-top: 18px;">
                    <?= qr_label_markup($qrUrl, (string) $item['inventory_code'], 220) ?>
                    <div>
                        <label>URL dla QR</label>
                        <div><a href="<?= h($qrUrl) ?>"><?= h($qrUrl) ?></a></div>
                        <div class="actions" style="margin-top: 14px;">
                            <a class="button" href="index.php?page=item-label&id=<?= (int) $item['id'] ?>" target="_blank" rel="noopener">Drukuj etykietę</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="panel">
                <div class="topbar">
                    <div>
                        <h2>Zadania</h2>
                        <p class="muted">Zadania serwisowe i organizacyjne powiązane z tym sprzętem.</p>
                    </div>
                </div>
                <?php if (can_create_tasks()) : ?>
                    <form method="post" action="index.php?page=task-create" class="form-grid" style="margin-bottom: 20px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="equipment_id" value="<?= (int) $item['id'] ?>">
                        <div style="flex-basis: 100%;">
                            <label for="task_title">Nowe zadanie</label>
                            <input id="task_title" name="title" placeholder="Np. Do przelutowania Agnus" required>
                        </div>
                        <div style="flex-basis: 100%;">
                            <label for="initial_update">Pierwsza aktualizacja</label>
                            <textarea id="initial_update" name="initial_update" placeholder="Np. Układ uszkodzony, trzeba znaleźć zamiennik."></textarea>
                        </div>
                        <div class="actions" style="flex-basis: 100%;">
                            <button class="button primary" type="submit">Dodaj zadanie</button>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if (!$tasks): ?>
                    <p class="muted">Brak zadań dla tego sprzętu.</p>
                <?php else: ?>
                    <div class="task-list">
                        <?php foreach ($tasks as $task): ?>
                            <article class="task-card task-<?= h($task['status']) ?>">
                                <div class="topbar" style="margin-bottom: 12px;">
                                    <div>
                                        <div class="badge"><?= h(task_status_label($task['status'])) ?></div>
                                        <h3 style="margin: 10px 0 6px;"><?= h($task['title']) ?></h3>
                                        <p class="muted" style="margin: 0;">Dodał(a): <?= h($task['created_by_name']) ?> / <?= h($task['created_at']) ?></p>
                                        <p class="muted" style="margin: 6px 0 0;">Ostatnia zmiana: <?= h($task['updated_by_name']) ?> / <?= h($task['updated_at']) ?></p>
                                        <?php if ($task['status'] === 'completed' && $task['completed_by_name']): ?>
                                            <p class="muted" style="margin: 6px 0 0;">Zakończył(a): <?= h($task['completed_by_name']) ?> / <?= h($task['completed_at']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($task['status'] === 'rejected' && $task['rejected_by_name']): ?>
                                            <p class="muted" style="margin: 6px 0 0;">Odrzucił(a): <?= h($task['rejected_by_name']) ?> / <?= h($task['rejected_at']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                <?php if (can_update_tasks() || can_change_task_status() || can_delete_tasks()): ?>
                                    <?php if ($task['status'] !== 'completed' && (can_update_tasks() || can_change_task_status())): ?>
                                        <form method="post" action="index.php?page=task-update" class="form-grid task-form" style="margin-bottom: 14px;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                            <div style="flex-basis: 100%;">
                                                <label for="task_update_<?= (int) $task['id'] ?>">Aktualizacja / opis zmiany</label>
                                                <textarea id="task_update_<?= (int) $task['id'] ?>" name="message" placeholder="Np. układ zamówiony / zrobione / temat nieaktualny."></textarea>
                                            </div>
                                            <div class="actions" style="flex-basis: 100%;">
                                                <?php if (can_update_tasks()): ?>
                                                <button class="button" type="submit">Dodaj aktualizację</button>
                                                <?php endif; ?>
                                                <?php if (can_change_task_status() && $task['status'] !== 'open'): ?>
                                                    <button class="button" type="submit" name="status" value="open" formaction="index.php?page=task-status">Przywróć do otwartych</button>
                                                <?php endif; ?>
                                                <?php if (can_change_task_status()): ?>
                                                <button class="button primary" type="submit" name="status" value="completed" formaction="index.php?page=task-status">Oznacz jako zakończone</button>
                                                <?php if ($task['status'] !== 'rejected'): ?>
                                                    <button class="button" type="submit" name="status" value="rejected" formaction="index.php?page=task-status">Odrzuć</button>
                                                <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (can_delete_tasks()): ?>
                                    <form method="post" action="index.php?page=task-delete">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                        <button class="button danger" type="submit" onclick="return confirm('Usunąć zadanie wraz ze wszystkimi aktualizacjami? Tej operacji nie da się cofnąć.');">Usuń zadanie</button>
                                    </form>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (!empty($taskUpdates[(int) $task['id']])): ?>
                                    <div class="task-updates">
                                        <?php foreach ($taskUpdates[(int) $task['id']] as $update): ?>
                                            <div class="task-update-entry">
                                                <strong><?= h($update['display_name']) ?></strong>
                                                <span class="muted"><?= h($update['created_at']) ?></span>
                                                <div><?= nl2br(h($update['message'])) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="muted">Brak aktualizacji dla tego zadania.</p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <div>
            <section class="panel">
                <h2>Zdjęcie główne</h2>
                <?php if ($primaryImage): ?>
                    <img class="detail-media" src="<?= h($primaryImage['file_path']) ?>" alt="<?= h($primaryImage['original_name']) ?>">
                <?php else: ?>
                    <p class="muted">Brak zdjęcia głównego dla tego rekordu.</p>
                <?php endif; ?>

                <details class="media-details" style="margin-top: 16px;">
                    <summary>Zarządzaj zdjęciami i pokaż pozostałe</summary>
                    <div style="margin-top: 16px;">
                        <?php if (can_upload_images()): ?>
                            <form method="post" action="index.php?page=equipment-image-upload" enctype="multipart/form-data" class="actions" style="margin-bottom: 16px;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="equipment_id" value="<?= (int) $item['id'] ?>">
                                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>
                                <button class="button primary" type="submit">Dodaj zdjęcie</button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$images): ?>
                            <p class="muted">Brak zdjęć dla tego rekordu.</p>
                        <?php else: ?>
                            <div class="image-list">
                                <?php foreach ($images as $image): ?>
                                    <article class="image-card">
                                        <img src="<?= h($image['file_path']) ?>" alt="<?= h($image['original_name']) ?>">
                                        <div class="muted" style="margin-top: 8px;"><?= h($image['original_name']) ?></div>
                                        <?php if (can_delete_images()): ?>
                                            <form method="post" action="index.php?page=equipment-image-delete" style="margin-top: 8px;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                                <button class="button danger" type="submit">Usuń zdjęcie</button>
                                            </form>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </details>
            </section>
            <section class="panel">
                <h2>Otwarte zadania</h2>
                <?php if (!$openTasks): ?>
                    <p class="muted">Brak otwartych zadań dla tego sprzętu.</p>
                <?php else: ?>
                    <div class="task-summary-list">
                        <?php foreach ($openTasks as $task): ?>
                            <?php $latestUpdate = $taskUpdates[(int) $task['id']][0] ?? null; ?>
                            <article class="task-summary-item">
                                <strong><?= h($task['title']) ?></strong>
                                <div class="muted">Ostatnia zmiana: <?= h($task['updated_by_name']) ?> / <?= h($task['updated_at']) ?></div>
                                <?php if ($latestUpdate): ?>
                                    <div style="margin-top: 8px;"><?= nl2br(h($latestUpdate['message'])) ?></div>
                                    <div class="muted" style="margin-top: 6px;"><?= h($latestUpdate['display_name']) ?> / <?= h($latestUpdate['created_at']) ?></div>
                                <?php else: ?>
                                    <div class="muted" style="margin-top: 8px;">Brak aktualizacji dla zadania.</div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
            <section class="panel">
                <h2>Historia zmian</h2>
                <?php if (!$audit): ?>
                    <p class="muted">Brak wpisów audytu.</p>
                <?php else: ?>
                    <?php foreach ($audit as $entry): ?>
                        <div style="padding: 10px 0; border-bottom: 1px solid rgba(72,53,27,0.12);">
                            <strong><?= h($entry['action_name']) ?></strong><br>
                            <span class="muted"><?= h($entry['display_name'] ?? 'system') ?> / <?= h($entry['created_at']) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </section>
    <?php
    render_footer();
    exit;
}

$stats = [
    'equipment_total' => (int) (query_one('SELECT COUNT(*) AS total FROM equipment')['total'] ?? 0),
    'equipment_ready' => (int) (query_one("SELECT COUNT(*) AS total FROM equipment WHERE condition_status IN ('new', 'inventory', 'assigned')")['total'] ?? 0),
    'equipment_needs_service' => (int) (query_one("SELECT COUNT(*) AS total FROM equipment WHERE condition_status = 'needs_service'")['total'] ?? 0),
    'equipment_with_open_tasks' => (int) (query_one("SELECT COUNT(DISTINCT equipment_id) AS total FROM equipment_tasks WHERE status = 'open'")['total'] ?? 0),
    'open_tasks_total' => (int) (query_one("SELECT COUNT(*) AS total FROM equipment_tasks WHERE status = 'open'")['total'] ?? 0),
    'completed_tasks_24h' => (int) (query_one("SELECT COUNT(*) AS total FROM equipment_tasks WHERE status = 'completed' AND completed_at >= (NOW() - INTERVAL 1 DAY)")['total'] ?? 0),
];

$dashboardThresholds = [
    'equipment_needs_service' => [
        'warning' => app_setting_int('threshold_needs_service_warning', 5),
        'warning_high' => app_setting_int('threshold_needs_service_warning_high', 10),
        'danger' => app_setting_int('threshold_needs_service_danger', 20),
    ],
    'equipment_with_open_tasks' => [
        'warning' => app_setting_int('threshold_open_items_warning', 5),
        'warning_high' => app_setting_int('threshold_open_items_warning_high', 10),
        'danger' => app_setting_int('threshold_open_items_danger', 20),
    ],
    'open_tasks_total' => [
        'warning' => app_setting_int('threshold_open_tasks_warning', 10),
        'warning_high' => app_setting_int('threshold_open_tasks_warning_high', 20),
        'danger' => app_setting_int('threshold_open_tasks_danger', 40),
    ],
];

$dashboardCards = [
    [
        'value' => $stats['equipment_total'],
        'label' => 'Liczba pozycji',
        'hint' => 'Wszystkie rekordy sprzętu w bazie.',
        'class' => 'alert-normal',
    ],
    [
        'value' => $stats['equipment_ready'],
        'label' => 'Sprzęty sprawne',
        'hint' => 'Status: nowy, zinwentaryzowany lub przypisany.',
        'class' => 'alert-normal',
    ],
    [
        'value' => $stats['equipment_needs_service'],
        'label' => 'Sprzęty do naprawy',
        'hint' => 'Status `needs_service`.',
        'class' => stat_alert_class($stats['equipment_needs_service'], $dashboardThresholds['equipment_needs_service']),
    ],
    [
        'value' => $stats['equipment_with_open_tasks'],
        'label' => 'Pozycje z otwartymi zadaniami',
        'hint' => 'Liczba rekordów sprzętu z co najmniej jednym otwartym zadaniem.',
        'class' => stat_alert_class($stats['equipment_with_open_tasks'], $dashboardThresholds['equipment_with_open_tasks']),
    ],
    [
        'value' => $stats['open_tasks_total'],
        'label' => 'Otwarte zadania',
        'hint' => 'Łącznie wszystkich niezakończonych zadań.',
        'class' => stat_alert_class($stats['open_tasks_total'], $dashboardThresholds['open_tasks_total']),
    ],
    [
        'value' => $stats['completed_tasks_24h'],
        'label' => 'Zakończone w 24h',
        'hint' => 'Zadania ukończone w ostatniej dobie.',
        'class' => 'alert-normal',
    ],
];

$recentEquipment = query_all(
    'SELECT e.id, e.inventory_code, e.title, c.name AS category_name,
            COALESCE(activity.last_activity_at, e.updated_at) AS updated_at,
            COALESCE(activity.last_actor_name, updater.display_name) AS updated_by_name
     FROM equipment e
     JOIN categories c ON c.id = e.category_id
     JOIN users updater ON updater.id = e.updated_by
     LEFT JOIN (
         SELECT a.entity_id AS equipment_id, a.created_at AS last_activity_at, u.display_name AS last_actor_name
         FROM audit_logs a
         LEFT JOIN users u ON u.id = a.user_id
         JOIN (
             SELECT entity_id, MAX(id) AS max_id
             FROM audit_logs
             WHERE entity_type = \'equipment\'
             GROUP BY entity_id
         ) latest ON latest.max_id = a.id
         WHERE a.entity_type = \'equipment\'
     ) activity ON activity.equipment_id = e.id
     ORDER BY COALESCE(activity.last_activity_at, e.updated_at) DESC, e.inventory_code ASC
     LIMIT 5'
);

$recentAudit = query_all(
    'SELECT a.action_name, a.created_at, a.entity_type, a.entity_id, a.details_json, u.display_name,
            e.inventory_code, e.title,
            target_user.display_name AS target_display_name,
            target_user.username AS target_username
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.user_id
     LEFT JOIN equipment e ON e.id = a.entity_id AND a.entity_type = \'equipment\'
     LEFT JOIN users target_user ON target_user.id = a.entity_id AND a.entity_type = \'user\'
     ORDER BY a.created_at DESC
     LIMIT 8'
);

// Domyślny widok po zalogowaniu: dashboard.
render_header('Dashboard');
?>
<section class="stats panel">
    <?php foreach ($dashboardCards as $card): ?>
        <article class="card <?= h($card['class']) ?>">
            <strong><?= (int) $card['value'] ?></strong>
            <?= h($card['label']) ?>
            <div class="muted stat-hint"><?= h($card['hint']) ?></div>
        </article>
    <?php endforeach; ?>
</section>

<section class="grid">
    <section class="panel">
        <div class="topbar">
            <div>
                <h2>Ostatnio aktualizowany sprzęt</h2>
                <p class="muted">Seed zawiera trzy placeholderowe rekordy do pierwszego oglądania systemu.</p>
            </div>
            <a class="button" href="index.php?page=equipment-list">Pełna lista</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Kod</th>
                    <th>Nazwa zwyczajowa</th>
                    <th>Kategoria</th>
                    <th>Aktualizacja</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentEquipment as $item): ?>
                    <tr>
                        <td><a href="index.php?page=item&id=<?= (int) $item['id'] ?>"><?= h($item['inventory_code']) ?></a></td>
                        <td><?= h($item['title']) ?></td>
                        <td><?= h($item['category_name']) ?></td>
                        <td><?= h($item['updated_by_name']) ?><br><span class="muted"><?= h($item['updated_at']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="topbar">
            <h2>Ostatnie zdarzenia</h2>
            <?php if (can_view_audit_history()): ?>
                <a class="button" href="index.php?page=audit-log">Więcej...</a>
            <?php endif; ?>
        </div>
        <?php foreach ($recentAudit as $entry): ?>
            <?php
            $details = [];
            if (!empty($entry['details_json'])) {
                $details = json_decode((string) $entry['details_json'], true) ?: [];
            }
            $targetLabel = audit_target_label_html($entry, $details);
            ?>
            <div style="padding: 10px 0; border-bottom: 1px solid rgba(72,53,27,0.12);">
                <strong><?= h(audit_action_label($entry['action_name'])) ?></strong>
                <span class="muted">· <?= $targetLabel ?></span>
                <br>
                <span class="muted"><?= h($entry['display_name'] ?? 'system') ?> / <?= h($entry['created_at']) ?></span>
            </div>
        <?php endforeach; ?>
    </section>
</section>
<?php
render_footer();

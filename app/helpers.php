<?php

declare(strict_types=1);

function app_config(string $key)
{
    global $config;
    return $config[$key] ?? null;
}

function app_setting_defaults(): array
{
    return [
        'festival_deadline' => (string) app_config('festival_deadline'),
        'threshold_needs_service_warning' => '5',
        'threshold_needs_service_warning_high' => '10',
        'threshold_needs_service_danger' => '20',
        'threshold_open_items_warning' => '5',
        'threshold_open_items_warning_high' => '10',
        'threshold_open_items_danger' => '20',
        'threshold_open_tasks_warning' => '10',
        'threshold_open_tasks_warning_high' => '20',
        'threshold_open_tasks_danger' => '40',
    ];
}

function inventory_dictionary_defaults(): array
{
    return [
        'categories' => [
            ['name' => 'Komputery', 'slug' => 'komputery', 'code_prefix' => 'CMP'],
            ['name' => 'Konsole', 'slug' => 'konsole', 'code_prefix' => 'CON'],
            ['name' => 'Monitory i TV', 'slug' => 'monitory-tv', 'code_prefix' => 'DSP'],
        ],
        'condition_statuses' => [
            ['name' => 'Nowy wpis', 'slug' => 'new', 'sort_order' => 10],
            ['name' => 'W inwentaryzacji', 'slug' => 'inventory', 'sort_order' => 20],
            ['name' => 'Wymaga serwisu', 'slug' => 'needs_service', 'sort_order' => 30],
            ['name' => 'Przypisany', 'slug' => 'assigned', 'sort_order' => 40],
            ['name' => 'Zarchiwizowany', 'slug' => 'archived', 'sort_order' => 50],
        ],
        'ownership_statuses' => [
            ['name' => 'Nieustalony', 'slug' => 'unknown', 'sort_order' => 10],
            ['name' => 'Na fundację', 'slug' => 'foundation', 'sort_order' => 20],
            ['name' => 'Wypożyczenie', 'slug' => 'loan', 'sort_order' => 30],
            ['name' => 'W trakcie ustaleń', 'slug' => 'pending', 'sort_order' => 40],
        ],
    ];
}

function role_defaults(): array
{
    return [
        [
            'slug' => 'root_superadmin',
            'name' => 'Root SuperAdmin',
            'sort_order' => 10,
            'can_manage_users' => 1,
            'can_manage_roles' => 1,
            'can_manage_root_roles' => 1,
            'can_edit_records' => 1,
            'can_upload_images' => 1,
            'can_delete_images' => 1,
            'can_create_tasks' => 1,
            'can_update_tasks' => 1,
            'can_change_task_status' => 1,
            'can_delete_tasks' => 1,
            'can_manage_settings' => 1,
            'can_manage_dictionaries' => 1,
            'can_view_audit_history' => 1,
            'can_export_csv' => 1,
            'can_import_csv' => 1,
            'is_system' => 1,
        ],
        [
            'slug' => 'superadmin',
            'name' => 'SuperAdmin',
            'sort_order' => 20,
            'can_manage_users' => 1,
            'can_manage_roles' => 1,
            'can_manage_root_roles' => 0,
            'can_edit_records' => 1,
            'can_upload_images' => 1,
            'can_delete_images' => 1,
            'can_create_tasks' => 1,
            'can_update_tasks' => 1,
            'can_change_task_status' => 1,
            'can_delete_tasks' => 1,
            'can_manage_settings' => 1,
            'can_manage_dictionaries' => 1,
            'can_view_audit_history' => 1,
            'can_export_csv' => 1,
            'can_import_csv' => 1,
            'is_system' => 1,
        ],
        [
            'slug' => 'admin',
            'name' => 'Admin',
            'sort_order' => 30,
            'can_manage_users' => 0,
            'can_manage_roles' => 0,
            'can_manage_root_roles' => 0,
            'can_edit_records' => 1,
            'can_upload_images' => 1,
            'can_delete_images' => 1,
            'can_create_tasks' => 1,
            'can_update_tasks' => 1,
            'can_change_task_status' => 1,
            'can_delete_tasks' => 1,
            'can_manage_settings' => 1,
            'can_manage_dictionaries' => 0,
            'can_view_audit_history' => 1,
            'can_export_csv' => 1,
            'can_import_csv' => 0,
            'is_system' => 1,
        ],
        [
            'slug' => 'readonly',
            'name' => 'ReadOnly',
            'sort_order' => 40,
            'can_manage_users' => 0,
            'can_manage_roles' => 0,
            'can_manage_root_roles' => 0,
            'can_edit_records' => 0,
            'can_upload_images' => 0,
            'can_delete_images' => 0,
            'can_create_tasks' => 0,
            'can_update_tasks' => 0,
            'can_change_task_status' => 0,
            'can_delete_tasks' => 0,
            'can_manage_settings' => 0,
            'can_manage_dictionaries' => 0,
            'can_view_audit_history' => 0,
            'can_export_csv' => 0,
            'can_import_csv' => 0,
            'is_system' => 1,
        ],
    ];
}

function app_setting(string $key, mixed $default = null): mixed
{
    global $appSettingsCache;

    if (!is_array($appSettingsCache)) {
        $appSettingsCache = app_setting_defaults();
        try {
            $rows = query_all('SELECT setting_key, setting_value FROM app_settings');
            foreach ($rows as $row) {
                $appSettingsCache[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable) {
            // Ustawienia z bazy mogą jeszcze nie istnieć podczas pierwszego deployu.
        }
    }

    return $appSettingsCache[$key] ?? $default;
}

function app_setting_int(string $key, int $default): int
{
    return (int) app_setting($key, (string) $default);
}

function set_app_settings(array $settings): void
{
    foreach ($settings as $key => $value) {
        execute_sql(
            'INSERT INTO app_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            [
                'setting_key' => $key,
                'setting_value' => (string) $value,
            ]
        );
    }

    refresh_app_settings_cache();
}

function refresh_app_settings_cache(): void
{
    global $appSettingsCache;

    $defaults = app_setting_defaults();
    try {
        $rows = query_all('SELECT setting_key, setting_value FROM app_settings');
        foreach ($rows as $row) {
            $defaults[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable) {
    }

    $appSettingsCache = $defaults;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = (string) ($_POST['_csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), $token)) {
        http_response_code(419);
        exit('Nieprawidłowy token formularza.');
    }
}

function flash(?string $type = null, ?string $message = null): array
{
    if ($type !== null && $message !== null) {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
        return [];
    }

    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function role_label(string $role): string
{
    return role_definition($role)['name'] ?? $role;
}

function condition_label(string $status): string
{
    return dictionary_label('condition_statuses', $status);
}

function ownership_label(string $status): string
{
    return dictionary_label('ownership_statuses', $status);
}

function task_status_label(string $status): string
{
    return match ($status) {
        'open' => 'Do wykonania',
        'completed' => 'Zakończone',
        'rejected' => 'Odrzucone',
        default => $status,
    };
}

function audit_action_label(string $action): string
{
    return match ($action) {
        'create_equipment' => 'Dodano rekord sprzętu',
        'update_equipment' => 'Zaktualizowano rekord sprzętu',
        'upload_image' => 'Dodano zdjęcie',
        'delete_image' => 'Usunięto zdjęcie',
        'create_task' => 'Dodano zadanie',
        'task_update' => 'Dodano aktualizację zadania',
        'task_status' => 'Zmieniono status zadania',
        'delete_task' => 'Usunięto zadanie',
        'create_user' => 'Utworzono użytkownika',
        'reset_password' => 'Zresetowano hasło',
        'update_user_profile' => 'Zmieniono dane użytkownika',
        'deactivate_user' => 'Dezaktywowano użytkownika',
        'reactivate_user' => 'Aktywowano użytkownika',
        'change_password' => 'Zmieniono hasło',
        'update_deadline' => 'Zmieniono termin festiwalu',
        'update_dashboard_thresholds' => 'Zmieniono progi dashboardu',
        'create_location' => 'Dodano lokalizację',
        'update_location' => 'Zmieniono lokalizację',
        'delete_location' => 'Usunięto lokalizację',
        'create_location_place' => 'Dodano miejsce w lokalizacji',
        'update_location_place' => 'Zmieniono miejsce w lokalizacji',
        'delete_location_place' => 'Usunięto miejsce w lokalizacji',
        'create_dictionary_entry' => 'Dodano pozycję słownika',
        'update_dictionary_entry' => 'Zmieniono pozycję słownika',
        'delete_dictionary_entry' => 'Usunięto pozycję słownika',
        'create_role' => 'Dodano rolę',
        'update_role' => 'Zmieniono rolę',
        'delete_role' => 'Usunięto rolę',
        'export_equipment_csv' => 'Wyeksportowano CSV sprzętu',
        'import_equipment_csv' => 'Zaimportowano CSV sprzętu',
        default => $action,
    };
}

function can_manage_users(): bool
{
    return current_user_can('can_manage_users');
}

function can_manage_roles(): bool
{
    return current_user_can('can_manage_roles');
}

function can_manage_root_roles(): bool
{
    return current_user_can('can_manage_root_roles');
}

function can_edit_records(): bool
{
    return current_user_can('can_edit_records');
}

function can_manage_settings(): bool
{
    return current_user_can('can_manage_settings');
}

function can_manage_dictionaries(): bool
{
    return current_user_can('can_manage_dictionaries');
}

function can_view_audit_history(): bool
{
    return current_user_can('can_view_audit_history');
}

function can_upload_images(): bool
{
    return current_user_can('can_upload_images') || can_edit_records();
}

function can_delete_images(): bool
{
    return current_user_can('can_delete_images');
}

function can_create_tasks(): bool
{
    return current_user_can('can_create_tasks');
}

function can_update_tasks(): bool
{
    return current_user_can('can_update_tasks');
}

function can_change_task_status(): bool
{
    return current_user_can('can_change_task_status');
}

function can_delete_tasks(): bool
{
    return current_user_can('can_delete_tasks');
}

function can_export_csv(): bool
{
    return current_user_can('can_export_csv');
}

function can_import_csv(): bool
{
    return current_user_can('can_import_csv');
}

function csv_value(?string $value): string
{
    $value = (string) $value;
    if ($value === '') {
        return '';
    }
    return trim(str_replace(["\r", "\n", "\t"], ' ', $value));
}

function csv_strip_utf8_bom(string $value): string
{
    return preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;
}

function csv_slug_or_fallback(string $preferred, string $fallback): string
{
    $slug = slugify($preferred);
    if ($slug !== '') {
        return $slug;
    }
    return slugify($fallback);
}

function ensure_category_for_import(string $name, string $slug, string $codePrefix): int
{
    $slug = csv_slug_or_fallback($slug, $name);
    $codePrefix = normalize_category_code($codePrefix !== '' ? $codePrefix : suggested_category_code($name));

    $existingBySlug = $slug !== ''
        ? query_one('SELECT id, code_prefix FROM categories WHERE slug = :slug', ['slug' => $slug])
        : null;
    if ($existingBySlug) {
        if (normalize_category_code((string) ($existingBySlug['code_prefix'] ?? '')) !== $codePrefix && !category_code_in_use($codePrefix, (int) $existingBySlug['id'])) {
            execute_sql('UPDATE categories SET code_prefix = :code_prefix WHERE id = :id', ['code_prefix' => $codePrefix, 'id' => (int) $existingBySlug['id']]);
            refresh_inventory_dictionary_cache('categories');
        }
        return (int) $existingBySlug['id'];
    }

    if ($slug === '') {
        $slug = 'kategoria-' . strtolower(bin2hex(random_bytes(4)));
    }

    if (category_code_in_use($codePrefix)) {
        $suffix = 1;
        do {
            $codePrefix = normalize_category_code(substr($codePrefix, 0, 2) . (string) ($suffix % 10));
            $suffix++;
        } while (category_code_in_use($codePrefix));
    }

    execute_sql(
        'INSERT INTO categories (name, slug, code_prefix) VALUES (:name, :slug, :code_prefix)',
        ['name' => $name !== '' ? $name : $slug, 'slug' => $slug, 'code_prefix' => $codePrefix]
    );
    refresh_inventory_dictionary_cache('categories');
    return (int) db()->lastInsertId();
}

function ensure_dictionary_entry_for_import(string $table, string $name, string $slug): string
{
    $slug = csv_slug_or_fallback($slug, $name);
    if ($slug === '') {
        throw new RuntimeException('Nie można ustalić sluga dla słownika: ' . $table);
    }

    $existing = query_one(sprintf('SELECT slug FROM %s WHERE slug = :slug', $table), ['slug' => $slug]);
    if ($existing) {
        return $slug;
    }

    $maxSort = (int) (query_one(sprintf('SELECT COALESCE(MAX(sort_order), 0) AS max_sort FROM %s', $table))['max_sort'] ?? 0);
    execute_sql(
        sprintf('INSERT INTO %s (name, slug, sort_order) VALUES (:name, :slug, :sort_order)', $table),
        ['name' => $name !== '' ? $name : $slug, 'slug' => $slug, 'sort_order' => $maxSort + 10]
    );
    refresh_inventory_dictionary_cache($table);
    return $slug;
}

function ensure_location_for_import(string $name, string $slug): int
{
    $slug = csv_slug_or_fallback($slug, $name);
    $existing = $slug !== '' ? query_one('SELECT id FROM locations WHERE slug = :slug', ['slug' => $slug]) : null;
    if ($existing) {
        return (int) $existing['id'];
    }

    if ($name === '') {
        throw new RuntimeException('Brak nazwy lokalizacji.');
    }

    if ($slug === '') {
        $slug = 'lokalizacja-' . strtolower(bin2hex(random_bytes(4)));
    }
    execute_sql('INSERT INTO locations (name, slug) VALUES (:name, :slug)', ['name' => $name, 'slug' => $slug]);
    return (int) db()->lastInsertId();
}

function ensure_location_place_for_import(int $locationId, string $name, string $slug): int
{
    $slug = csv_slug_or_fallback($slug, $name);
    $existing = $slug !== ''
        ? query_one('SELECT id FROM location_places WHERE location_id = :location_id AND slug = :slug', ['location_id' => $locationId, 'slug' => $slug])
        : null;
    if ($existing) {
        return (int) $existing['id'];
    }

    if ($name === '') {
        throw new RuntimeException('Brak nazwy miejsca w lokalizacji.');
    }

    if ($slug === '') {
        $slug = 'miejsce-' . strtolower(bin2hex(random_bytes(4)));
    }
    execute_sql(
        'INSERT INTO location_places (location_id, name, slug) VALUES (:location_id, :name, :slug)',
        ['location_id' => $locationId, 'name' => $name, 'slug' => $slug]
    );
    return (int) db()->lastInsertId();
}

function slugify(string $value): string
{
    $value = trim(mb_strtolower($value));
    $value = str_replace(
        ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ż', 'ź'],
        ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z'],
        $value
    );
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
    return trim($value, '-');
}

function dictionary_table_config(string $table): array
{
    return match ($table) {
        'categories' => [
            'label' => 'Kategorie sprzętu',
            'entity_label' => 'kategorię',
            'reference_sql' => 'SELECT COUNT(*) AS total FROM equipment WHERE category_id = :id',
            'reference_key' => 'category_id',
            'page' => 'dictionaries',
        ],
        'condition_statuses' => [
            'label' => 'Statusy sprzętu',
            'entity_label' => 'status sprzętu',
            'reference_sql' => 'SELECT COUNT(*) AS total FROM equipment WHERE condition_status = :slug',
            'reference_key' => 'condition_status',
            'page' => 'dictionaries',
        ],
        'ownership_statuses' => [
            'label' => 'Statusy formalne',
            'entity_label' => 'status formalny',
            'reference_sql' => 'SELECT COUNT(*) AS total FROM equipment WHERE ownership_status = :slug',
            'reference_key' => 'ownership_status',
            'page' => 'dictionaries',
        ],
        default => throw new InvalidArgumentException('Nieobsługiwany słownik: ' . $table),
    };
}

function inventory_dictionary_rows(string $table): array
{
    global $inventoryDictionaryCache;

    if (!isset($inventoryDictionaryCache[$table])) {
        $defaults = inventory_dictionary_defaults()[$table] ?? [];
        $rows = $defaults;
        try {
            $rows = query_all(sprintf('SELECT * FROM %s ORDER BY %s', $table, $table === 'categories' ? 'name, id' : 'sort_order, name, id'));
        } catch (Throwable) {
        }
        $inventoryDictionaryCache[$table] = $rows;
    }

    return $inventoryDictionaryCache[$table];
}

function refresh_inventory_dictionary_cache(?string $table = null): void
{
    global $inventoryDictionaryCache;

    if ($table === null) {
        $inventoryDictionaryCache = [];
        return;
    }

    unset($inventoryDictionaryCache[$table]);
}

function dictionary_label(string $table, string $slug): string
{
    foreach (inventory_dictionary_rows($table) as $row) {
        if ((string) ($row['slug'] ?? '') === $slug) {
            return (string) ($row['name'] ?? $slug);
        }
    }

    return $slug;
}

function dictionary_options(string $table, ?string $selectedSlug = null, bool $includePlaceholder = false, string $placeholder = 'Wszystkie'): string
{
    $options = '';
    if ($includePlaceholder) {
        $options .= sprintf('<option value="">%s</option>', h($placeholder));
    }

    foreach (inventory_dictionary_rows($table) as $row) {
        $slug = (string) ($row['slug'] ?? '');
        $selected = $slug === $selectedSlug ? ' selected' : '';
        $options .= sprintf('<option value="%s"%s>%s</option>', h($slug), $selected, h((string) ($row['name'] ?? $slug)));
    }

    return $options;
}

function category_name_by_id(int $categoryId): string
{
    foreach (inventory_dictionary_rows('categories') as $row) {
        if ((int) ($row['id'] ?? 0) === $categoryId) {
            return (string) ($row['name'] ?? (string) $categoryId);
        }
    }

    return (string) $categoryId;
}

function normalize_category_code(string $value): string
{
    $value = strtoupper(preg_replace('/[^A-Z0-9]+/', '', $value) ?? '');
    if ($value === '') {
        return 'GEN';
    }

    return substr(str_pad($value, 3, 'X'), 0, 3);
}

function suggested_category_code(string $name): string
{
    $slug = slugify($name);
    if ($slug === '') {
        return 'GEN';
    }

    $parts = array_values(array_filter(explode('-', $slug)));
    if (count($parts) >= 3) {
        return normalize_category_code($parts[0][0] . $parts[1][0] . $parts[2][0]);
    }
    if (count($parts) === 2) {
        return normalize_category_code(substr($parts[0], 0, 2) . substr($parts[1], 0, 1));
    }

    return normalize_category_code(substr($parts[0], 0, 3));
}

function category_code_by_id(int $categoryId): ?string
{
    foreach (inventory_dictionary_rows('categories') as $row) {
        if ((int) ($row['id'] ?? 0) === $categoryId) {
            return normalize_category_code((string) ($row['code_prefix'] ?? 'GEN'));
        }
    }

    return null;
}

function category_code_in_use(string $codePrefix, ?int $excludeId = null): bool
{
    $params = ['code_prefix' => normalize_category_code($codePrefix)];
    $sql = 'SELECT COUNT(*) AS total FROM categories WHERE code_prefix = :code_prefix';
    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeId;
    }

    return (int) (query_one($sql, $params)['total'] ?? 0) > 0;
}

function dictionary_usage_count(string $table, array $row): int
{
    return match ($table) {
        'categories' => (int) (query_one('SELECT COUNT(*) AS total FROM equipment WHERE category_id = :id', ['id' => (int) $row['id']])['total'] ?? 0),
        'condition_statuses' => (int) (query_one('SELECT COUNT(*) AS total FROM equipment WHERE condition_status = :slug', ['slug' => (string) $row['slug']])['total'] ?? 0),
        'ownership_statuses' => (int) (query_one('SELECT COUNT(*) AS total FROM equipment WHERE ownership_status = :slug', ['slug' => (string) $row['slug']])['total'] ?? 0),
        default => 0,
    };
}

function infer_category_code_from_equipment(int $categoryId): ?string
{
    $rows = query_all(
        'SELECT inventory_code FROM equipment WHERE category_id = :category_id ORDER BY id DESC',
        ['category_id' => $categoryId]
    );

    $counts = [];
    foreach ($rows as $row) {
        $code = (string) ($row['inventory_code'] ?? '');
        if (preg_match('/^PR-([A-Z0-9]{3})-(\d{4})$/', $code, $matches) === 1) {
            $prefix = normalize_category_code($matches[1]);
            $counts[$prefix] = ($counts[$prefix] ?? 0) + 1;
        }
    }

    if ($counts === []) {
        return null;
    }

    arsort($counts);
    return (string) array_key_first($counts);
}

function generate_next_inventory_code(int $categoryId): ?string
{
    $prefix = category_code_by_id($categoryId);
    if ($prefix === null) {
        return null;
    }

    $rows = query_all(
        'SELECT inventory_code FROM equipment WHERE category_id = :category_id ORDER BY id DESC',
        ['category_id' => $categoryId]
    );

    $maxSequence = 0;
    foreach ($rows as $row) {
        $code = (string) ($row['inventory_code'] ?? '');
        if (preg_match('/^PR-[A-Z0-9]{3}-(\d{4})$/', $code, $matches) === 1) {
            $maxSequence = max($maxSequence, (int) $matches[1]);
        }
    }

    return sprintf('PR-%s-%04d', $prefix, $maxSequence + 1);
}

function role_assignment_is_protected(string $slug): bool
{
    return in_array($slug, ['root_superadmin', 'superadmin'], true);
}

function manageable_role_values(bool $includeProtected = false): array
{
    $roles = [];
    foreach (role_rows() as $role) {
        if (!$includeProtected && role_assignment_is_protected((string) ($role['slug'] ?? ''))) {
            continue;
        }
        $roles[] = (string) $role['slug'];
    }
    return $roles;
}

function role_rows(): array
{
    global $roleCache;

    if (!is_array($roleCache)) {
        $roleCache = role_defaults();
        try {
            $roleCache = query_all('SELECT * FROM roles ORDER BY sort_order, name, id');
        } catch (Throwable) {
        }
    }

    return $roleCache;
}

function refresh_role_cache(): void
{
    global $roleCache;

    $roleCache = null;
}

function role_definition(string $slug): ?array
{
    foreach (role_rows() as $role) {
        if ((string) ($role['slug'] ?? '') === $slug) {
            return $role;
        }
    }

    return null;
}

function current_user_can(string $permissionKey): bool
{
    $user = current_user();
    if (!$user) {
        return false;
    }

    $role = role_definition((string) ($user['role'] ?? ''));
    return (int) ($role[$permissionKey] ?? 0) === 1;
}

function location_label(array $row): string
{
    $locationName = trim((string) ($row['location_name'] ?? ''));
    $placeName = trim((string) ($row['place_name'] ?? ''));
    if ($locationName !== '' && $placeName !== '') {
        return $locationName . ' / ' . $placeName;
    }
    if ($locationName !== '') {
        return $locationName;
    }
    if ($placeName !== '') {
        return $placeName;
    }
    return trim((string) ($row['location_text'] ?? ''));
}

function location_options(?int $selectedId = null): string
{
    $options = '<option value="">Brak</option>';
    foreach (query_all('SELECT id, name FROM locations ORDER BY name') as $location) {
        $selected = ((int) $location['id'] === $selectedId) ? ' selected' : '';
        $options .= sprintf('<option value="%d"%s>%s</option>', (int) $location['id'], $selected, h($location['name']));
    }
    return $options;
}

function location_place_options(?int $selectedId = null, ?int $locationId = null): string
{
    $options = '<option value="">Brak</option>';
    $sql = 'SELECT lp.id, lp.name, l.name AS location_name
            FROM location_places lp
            JOIN locations l ON l.id = lp.location_id';
    $params = [];
    if ($locationId) {
        $sql .= ' WHERE lp.location_id = :location_id';
        $params['location_id'] = $locationId;
    }
    $sql .= ' ORDER BY l.name, lp.name';

    foreach (query_all($sql, $params) as $place) {
        $selected = ((int) $place['id'] === $selectedId) ? ' selected' : '';
        $label = $locationId ? $place['name'] : ($place['location_name'] . ' / ' . $place['name']);
        $options .= sprintf('<option value="%d"%s>%s</option>', (int) $place['id'], $selected, h($label));
    }
    return $options;
}

function upload_dir(): string
{
    return app_config('base_path') . '/uploads';
}

function public_upload_path(string $fileName): string
{
    return 'uploads/' . $fileName;
}

function ensure_upload_dir(): void
{
    $dir = upload_dir();
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

function request_scheme(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        return $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https' : 'http';
    }

    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
}

function request_base_url(): string
{
    $scheme = request_scheme();
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'), '/');

    return sprintf('%s://%s%s', $scheme, $host, $base === '/' ? '' : $base);
}

function qr_target_url(array $equipment): string
{
    return sprintf(
        '%s/index.php?page=item&id=%d&qr=%s',
        request_base_url(),
        (int) $equipment['id'],
        rawurlencode((string) $equipment['qr_token'])
    );
}

function qr_svg_markup(string $content, int $size = 220): string
{
    $autoload = '/usr/share/php/Bacon/BaconQrCode/autoload.php';
    if (!is_file($autoload)) {
        return '<div class="muted">QR niedostępny: brak biblioteki.</div>';
    }

    require_once $autoload;

    $renderer = new \BaconQrCode\Renderer\ImageRenderer(
        new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, 2),
        new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
    );

    $writer = new \BaconQrCode\Writer($renderer);
    return $writer->writeString($content);
}

function qr_label_markup(string $content, string $inventoryCode, int $size = 220): string
{
    return sprintf(
        '<div class="qr-label"><div class="qr-svg">%s</div><div class="qr-label-code">%s</div></div>',
        qr_svg_markup($content, $size),
        h($inventoryCode)
    );
}

function create_image_resource(string $sourcePath, string $mime): GdImage|false
{
    return match ($mime) {
        'image/jpeg' => imagecreatefromjpeg($sourcePath),
        'image/png' => imagecreatefrompng($sourcePath),
        'image/webp' => imagecreatefromwebp($sourcePath),
        default => false,
    };
}

function save_image_resource(GdImage $image, string $targetPath, string $mime): bool
{
    return match ($mime) {
        'image/jpeg' => imagejpeg($image, $targetPath, 82),
        'image/png' => imagepng($image, $targetPath, 6),
        'image/webp' => imagewebp($image, $targetPath, 82),
        default => false,
    };
}

function process_uploaded_image(string $sourcePath, string $targetPath, string $mime, int $maxWidth = 800, int $maxHeight = 800): bool
{
    $info = @getimagesize($sourcePath);
    if ($info === false) {
        return false;
    }

    [$sourceWidth, $sourceHeight] = $info;
    $source = create_image_resource($sourcePath, $mime);
    if (!$source) {
        return false;
    }

    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight, 1);
    $targetWidth = max(1, (int) round($sourceWidth * $ratio));
    $targetHeight = max(1, (int) round($sourceHeight * $ratio));

    if ($ratio >= 1) {
        $result = save_image_resource($source, $targetPath, $mime);
        imagedestroy($source);
        return $result;
    }

    $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
    }

    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
    $result = save_image_resource($canvas, $targetPath, $mime);
    imagedestroy($source);
    imagedestroy($canvas);

    return $result;
}

function audit_log(int $userId, string $entityType, int $entityId, string $action, array $details = []): void
{
    execute_sql(
        'INSERT INTO audit_logs (user_id, entity_type, entity_id, action_name, details_json) VALUES (:user_id, :entity_type, :entity_id, :action_name, :details_json)',
        [
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action_name' => $action,
            'details_json' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]
    );
}

function touch_equipment(int $equipmentId, int $userId): void
{
    execute_sql(
        'UPDATE equipment SET updated_by = :updated_by, updated_at = NOW() WHERE id = :id',
        ['updated_by' => $userId, 'id' => $equipmentId]
    );
}

function is_unique_violation(PDOException $exception): bool
{
    return ($exception->errorInfo[1] ?? null) === 1062;
}

function ensure_inventory_schema(): void
{
    static $done = false;

    if ($done) {
        return;
    }

    execute_sql(
        'CREATE TABLE IF NOT EXISTS condition_statuses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(120) NOT NULL UNIQUE,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    execute_sql(
        'CREATE TABLE IF NOT EXISTS ownership_statuses (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(120) NOT NULL UNIQUE,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    execute_sql(
        'CREATE TABLE IF NOT EXISTS roles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(120) NOT NULL UNIQUE,
            sort_order INT UNSIGNED NOT NULL DEFAULT 0,
            can_manage_users TINYINT(1) NOT NULL DEFAULT 0,
            can_manage_roles TINYINT(1) NOT NULL DEFAULT 0,
            can_manage_root_roles TINYINT(1) NOT NULL DEFAULT 0,
            can_edit_records TINYINT(1) NOT NULL DEFAULT 0,
            can_upload_images TINYINT(1) NOT NULL DEFAULT 0,
            can_delete_images TINYINT(1) NOT NULL DEFAULT 0,
            can_create_tasks TINYINT(1) NOT NULL DEFAULT 0,
            can_update_tasks TINYINT(1) NOT NULL DEFAULT 0,
            can_change_task_status TINYINT(1) NOT NULL DEFAULT 0,
            can_delete_tasks TINYINT(1) NOT NULL DEFAULT 0,
            can_manage_settings TINYINT(1) NOT NULL DEFAULT 0,
            can_manage_dictionaries TINYINT(1) NOT NULL DEFAULT 0,
            can_view_audit_history TINYINT(1) NOT NULL DEFAULT 0,
            can_export_csv TINYINT(1) NOT NULL DEFAULT 0,
            can_import_csv TINYINT(1) NOT NULL DEFAULT 0,
            is_system TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $categoryCodeColumn = query_one(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'categories' AND COLUMN_NAME = 'code_prefix'"
    );
    if (!$categoryCodeColumn) {
        execute_sql("ALTER TABLE categories ADD COLUMN code_prefix VARCHAR(3) NOT NULL DEFAULT 'GEN' AFTER slug");
    }

    foreach (inventory_dictionary_defaults() as $table => $rows) {
        $tableHasRows = (int) (query_one(sprintf('SELECT COUNT(*) AS total FROM %s', $table))['total'] ?? 0) > 0;
        if ($tableHasRows) {
            continue;
        }

        foreach ($rows as $row) {
            $params = [
                'name' => $row['name'],
                'slug' => $row['slug'],
            ];

            if ($table === 'categories') {
                $params['code_prefix'] = normalize_category_code((string) ($row['code_prefix'] ?? suggested_category_code($row['name'])));
                execute_sql(
                    'INSERT INTO categories (name, slug, code_prefix) VALUES (:name, :slug, :code_prefix)
                     ON DUPLICATE KEY UPDATE id = id',
                    $params
                );
                continue;
            }

            $params['sort_order'] = (int) ($row['sort_order'] ?? 0);
            execute_sql(
                sprintf(
                    'INSERT INTO %s (name, slug, sort_order) VALUES (:name, :slug, :sort_order)
                     ON DUPLICATE KEY UPDATE id = id',
                    $table
                ),
                $params
            );
        }
    }

    $roleRowsExist = (int) (query_one('SELECT COUNT(*) AS total FROM roles')['total'] ?? 0) > 0;
    if (!$roleRowsExist) {
        foreach (role_defaults() as $role) {
            execute_sql(
                'INSERT INTO roles (name, slug, sort_order, can_manage_users, can_manage_roles, can_manage_root_roles, can_edit_records, can_upload_images, can_delete_images, can_create_tasks, can_update_tasks, can_change_task_status, can_delete_tasks, can_manage_settings, can_manage_dictionaries, can_view_audit_history, can_export_csv, can_import_csv, is_system)
                 VALUES (:name, :slug, :sort_order, :can_manage_users, :can_manage_roles, :can_manage_root_roles, :can_edit_records, :can_upload_images, :can_delete_images, :can_create_tasks, :can_update_tasks, :can_change_task_status, :can_delete_tasks, :can_manage_settings, :can_manage_dictionaries, :can_view_audit_history, :can_export_csv, :can_import_csv, :is_system)',
                $role
            );
        }
    }

    $roleColumns = [
        'can_manage_roles' => 'ALTER TABLE roles ADD COLUMN can_manage_roles TINYINT(1) NOT NULL DEFAULT 0 AFTER can_manage_users',
        'can_upload_images' => 'ALTER TABLE roles ADD COLUMN can_upload_images TINYINT(1) NOT NULL DEFAULT 0 AFTER can_edit_records',
        'can_delete_images' => 'ALTER TABLE roles ADD COLUMN can_delete_images TINYINT(1) NOT NULL DEFAULT 0 AFTER can_upload_images',
        'can_create_tasks' => 'ALTER TABLE roles ADD COLUMN can_create_tasks TINYINT(1) NOT NULL DEFAULT 0 AFTER can_delete_images',
        'can_update_tasks' => 'ALTER TABLE roles ADD COLUMN can_update_tasks TINYINT(1) NOT NULL DEFAULT 0 AFTER can_create_tasks',
        'can_change_task_status' => 'ALTER TABLE roles ADD COLUMN can_change_task_status TINYINT(1) NOT NULL DEFAULT 0 AFTER can_update_tasks',
        'can_delete_tasks' => 'ALTER TABLE roles ADD COLUMN can_delete_tasks TINYINT(1) NOT NULL DEFAULT 0 AFTER can_change_task_status',
        'can_view_audit_history' => 'ALTER TABLE roles ADD COLUMN can_view_audit_history TINYINT(1) NOT NULL DEFAULT 0 AFTER can_manage_dictionaries',
        'can_export_csv' => 'ALTER TABLE roles ADD COLUMN can_export_csv TINYINT(1) NOT NULL DEFAULT 0 AFTER can_view_audit_history',
        'can_import_csv' => 'ALTER TABLE roles ADD COLUMN can_import_csv TINYINT(1) NOT NULL DEFAULT 0 AFTER can_export_csv',
    ];
    $addedRoleColumns = false;
    foreach ($roleColumns as $columnName => $sql) {
        $columnExists = query_one(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = :column_name",
            ['column_name' => $columnName]
        );
        if (!$columnExists) {
            execute_sql($sql);
            $addedRoleColumns = true;
        }
    }

    if ($addedRoleColumns) {
        foreach (role_defaults() as $defaultRole) {
            execute_sql(
                'UPDATE roles
                 SET can_manage_roles = :can_manage_roles,
                     can_upload_images = :can_upload_images,
                     can_delete_images = :can_delete_images,
                     can_create_tasks = :can_create_tasks,
                     can_update_tasks = :can_update_tasks,
                     can_change_task_status = :can_change_task_status,
                     can_delete_tasks = :can_delete_tasks,
                     can_view_audit_history = :can_view_audit_history,
                     can_export_csv = :can_export_csv,
                     can_import_csv = :can_import_csv
                 WHERE slug = :slug AND is_system = 1',
                [
                    'slug' => $defaultRole['slug'],
                    'can_manage_roles' => $defaultRole['can_manage_roles'],
                    'can_upload_images' => $defaultRole['can_upload_images'],
                    'can_delete_images' => $defaultRole['can_delete_images'],
                    'can_create_tasks' => $defaultRole['can_create_tasks'],
                    'can_update_tasks' => $defaultRole['can_update_tasks'],
                    'can_change_task_status' => $defaultRole['can_change_task_status'],
                    'can_delete_tasks' => $defaultRole['can_delete_tasks'],
                    'can_view_audit_history' => $defaultRole['can_view_audit_history'],
                    'can_export_csv' => $defaultRole['can_export_csv'],
                    'can_import_csv' => $defaultRole['can_import_csv'],
                ]
            );
        }
    }

    $conditionType = query_one(
        "SELECT COLUMN_TYPE, DATA_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipment' AND COLUMN_NAME = 'condition_status'"
    );
    if (($conditionType['DATA_TYPE'] ?? '') === 'enum') {
        execute_sql("ALTER TABLE equipment MODIFY condition_status VARCHAR(120) NOT NULL DEFAULT 'inventory'");
    }

    $ownershipType = query_one(
        "SELECT COLUMN_TYPE, DATA_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipment' AND COLUMN_NAME = 'ownership_status'"
    );
    if (($ownershipType['DATA_TYPE'] ?? '') === 'enum') {
        execute_sql("ALTER TABLE equipment MODIFY ownership_status VARCHAR(120) NOT NULL DEFAULT 'unknown'");
    }

    $manualOverrideColumn = query_one(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipment' AND COLUMN_NAME = 'inventory_code_manual_override'"
    );
    if (!$manualOverrideColumn) {
        execute_sql("ALTER TABLE equipment ADD COLUMN inventory_code_manual_override TINYINT(1) NOT NULL DEFAULT 0 AFTER inventory_code");
    }

    $defaultCategoryCodes = [];
    foreach (inventory_dictionary_defaults()['categories'] as $defaultCategory) {
        $defaultCategoryCodes[(string) $defaultCategory['slug']] = normalize_category_code((string) ($defaultCategory['code_prefix'] ?? suggested_category_code((string) $defaultCategory['name'])));
    }

    foreach (inventory_dictionary_rows('categories') as $category) {
        $currentCode = normalize_category_code((string) ($category['code_prefix'] ?? ''));
        $inferredCode = infer_category_code_from_equipment((int) $category['id']);
        $targetCode = $currentCode;
        if ($inferredCode !== null && $targetCode !== $inferredCode) {
            $hasMatchingPrefix = (int) (query_one(
                'SELECT COUNT(*) AS total FROM equipment WHERE category_id = :category_id AND inventory_code LIKE :pattern',
                [
                    'category_id' => (int) $category['id'],
                    'pattern' => 'PR-' . $targetCode . '-%',
                ]
            )['total'] ?? 0);
            if ($hasMatchingPrefix === 0) {
                $targetCode = $inferredCode;
            }
        } elseif ($targetCode === 'GEN') {
            $targetCode = $defaultCategoryCodes[(string) ($category['slug'] ?? '')]
                ?? suggested_category_code((string) ($category['name'] ?? ''));
        }
        execute_sql(
            'UPDATE categories SET code_prefix = :code_prefix WHERE id = :id',
            ['code_prefix' => $targetCode, 'id' => (int) $category['id']]
        );
    }

    $roleType = query_one(
        "SELECT COLUMN_TYPE, DATA_TYPE
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'"
    );
    if (($roleType['DATA_TYPE'] ?? '') === 'enum') {
        execute_sql("ALTER TABLE users MODIFY role VARCHAR(120) NOT NULL");
    }

    $rootExists = (int) (query_one("SELECT COUNT(*) AS total FROM users WHERE role = 'root_superadmin'")['total'] ?? 0);
    if ($rootExists === 0) {
        $seedRoot = query_one("SELECT id FROM users WHERE username = 'pressreset-root' LIMIT 1");
        if ($seedRoot) {
            execute_sql(
                "UPDATE users SET role = 'root_superadmin' WHERE id = :id",
                ['id' => (int) $seedRoot['id']]
            );
        } else {
            $fallbackRoot = query_one("SELECT id FROM users WHERE role = 'superadmin' ORDER BY id ASC LIMIT 1");
            if ($fallbackRoot) {
                execute_sql(
                    "UPDATE users SET role = 'root_superadmin' WHERE id = :id",
                    ['id' => (int) $fallbackRoot['id']]
                );
            }
        }
    }

    refresh_inventory_dictionary_cache();
    refresh_role_cache();
    $done = true;
}

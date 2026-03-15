CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    display_name VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(120) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    code_prefix VARCHAR(3) NOT NULL DEFAULT 'GEN',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS condition_statuses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ownership_statuses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS roles (
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
);

CREATE TABLE IF NOT EXISTS locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS location_places (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_location_place (location_id, slug),
    CONSTRAINT fk_location_places_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS equipment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    parent_equipment_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    location_place_id INT UNSIGNED NULL,
    inventory_code VARCHAR(60) NOT NULL UNIQUE,
    inventory_code_manual_override TINYINT(1) NOT NULL DEFAULT 0,
    title VARCHAR(180) NOT NULL,
    manufacturer VARCHAR(120) NOT NULL,
    model VARCHAR(120) NOT NULL,
    production_year VARCHAR(20) NOT NULL DEFAULT '',
    condition_status VARCHAR(120) NOT NULL DEFAULT 'inventory',
    ownership_status VARCHAR(120) NOT NULL DEFAULT 'unknown',
    location_text VARCHAR(180) NOT NULL DEFAULT '',
    barcode_value VARCHAR(120) NOT NULL DEFAULT '',
    qr_token VARCHAR(80) NOT NULL UNIQUE,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_equipment_parent FOREIGN KEY (parent_equipment_id) REFERENCES equipment(id) ON DELETE SET NULL,
    CONSTRAINT fk_equipment_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    CONSTRAINT fk_equipment_location_place FOREIGN KEY (location_place_id) REFERENCES location_places(id) ON DELETE SET NULL,
    CONSTRAINT fk_equipment_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_equipment_updated_by FOREIGN KEY (updated_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS equipment_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_images_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    CONSTRAINT fk_equipment_images_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS equipment_tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT UNSIGNED NOT NULL,
    title VARCHAR(220) NOT NULL,
    status ENUM('open', 'completed', 'rejected') NOT NULL DEFAULT 'open',
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED NOT NULL,
    completed_by INT UNSIGNED NULL,
    rejected_by INT UNSIGNED NULL,
    completed_at DATETIME NULL,
    rejected_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_tasks_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    CONSTRAINT fk_equipment_tasks_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_equipment_tasks_updated_by FOREIGN KEY (updated_by) REFERENCES users(id),
    CONSTRAINT fk_equipment_tasks_completed_by FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_equipment_tasks_rejected_by FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS equipment_task_updates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_task_updates_task FOREIGN KEY (task_id) REFERENCES equipment_tasks(id) ON DELETE CASCADE,
    CONSTRAINT fk_equipment_task_updates_user FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    action_name VARCHAR(80) NOT NULL,
    details_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO categories (name, slug, code_prefix)
VALUES
    ('Komputery', 'komputery', 'CMP'),
    ('Konsole', 'konsole', 'CON'),
    ('Monitory i TV', 'monitory-tv', 'DSP')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO condition_statuses (name, slug, sort_order)
VALUES
    ('Nowy wpis', 'new', 10),
    ('W inwentaryzacji', 'inventory', 20),
    ('Wymaga serwisu', 'needs_service', 30),
    ('Przypisany', 'assigned', 40),
    ('Zarchiwizowany', 'archived', 50)
ON DUPLICATE KEY UPDATE
    id = id;

INSERT INTO ownership_statuses (name, slug, sort_order)
VALUES
    ('Nieustalony', 'unknown', 10),
    ('Na fundację', 'foundation', 20),
    ('Wypożyczenie', 'loan', 30),
    ('W trakcie ustaleń', 'pending', 40)
ON DUPLICATE KEY UPDATE
    id = id;

INSERT INTO roles (name, slug, sort_order, can_manage_users, can_manage_roles, can_manage_root_roles, can_edit_records, can_upload_images, can_delete_images, can_create_tasks, can_update_tasks, can_change_task_status, can_delete_tasks, can_manage_settings, can_manage_dictionaries, can_view_audit_history, can_export_csv, can_import_csv, is_system)
VALUES
    ('Root SuperAdmin', 'root_superadmin', 10, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
    ('SuperAdmin', 'superadmin', 20, 1, 1, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
    ('Admin', 'admin', 30, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 0, 1, 1, 0, 1),
    ('ReadOnly', 'readonly', 40, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1)
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO locations (name, slug)
VALUES
    ('Magazyn A', 'magazyn-a'),
    ('Magazyn B', 'magazyn-b')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO location_places (location_id, name, slug)
SELECT l.id, 'Regał 1', 'regal-1'
FROM locations l
WHERE l.slug = 'magazyn-a'
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO location_places (location_id, name, slug)
SELECT l.id, 'Regał 2', 'regal-2'
FROM locations l
WHERE l.slug = 'magazyn-a'
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO location_places (location_id, name, slug)
SELECT l.id, 'Strefa TV', 'strefa-tv'
FROM locations l
WHERE l.slug = 'magazyn-b'
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (username, display_name, password_hash, role, active, must_change_password)
VALUES
    ('pressreset-root', 'Press Reset SuperAdmin', '$2y$10$/t2RGfVUKycwCjFC3ZbjBuRnZdk6Lv7IWB4VoIBBITZJqZ8XLfifW', 'root_superadmin', 1, 0),
    ('pressreset-admin', 'Press Reset Admin', '$2y$10$0vsCxDI1zR7qjfZJknx47OqjS6JKiEWNY3d2x/bU1OsdwiE1/VVvy', 'admin', 1, 1),
    ('pressreset-view', 'Press Reset ReadOnly', '$2y$10$1IW.8Il2iyA/5Z9PkFUmc.Z.p/fpy8Qzw7Mv01FXH1UZ2Pvk1St0W', 'readonly', 1, 1)
ON DUPLICATE KEY UPDATE
    display_name = VALUES(display_name),
    password_hash = VALUES(password_hash),
    role = VALUES(role),
    active = VALUES(active),
    must_change_password = VALUES(must_change_password);

INSERT INTO equipment (category_id, parent_equipment_id, location_id, location_place_id, inventory_code, inventory_code_manual_override, title, manufacturer, model, production_year, condition_status, ownership_status, location_text, barcode_value, qr_token, notes, created_by, updated_by)
SELECT c.id, NULL, l.id, lp.id, 'PR-CMP-0001', 0, 'Amiga 500 zestaw podstawowy', 'Commodore', 'Amiga 500', '1989', 'inventory', 'foundation', 'Magazyn A / Regał 1', '590000000001', 'pr-cmp-0001', 'Placeholder alpha: jednostka centralna do testow katalogowania.', u.id, u.id
FROM categories c
JOIN users u ON u.username = 'pressreset-root'
JOIN locations l ON l.slug = 'magazyn-a'
JOIN location_places lp ON lp.location_id = l.id AND lp.slug = 'regal-1'
WHERE c.slug = 'komputery'
AND NOT EXISTS (SELECT 1 FROM equipment WHERE inventory_code = 'PR-CMP-0001');

INSERT INTO equipment (category_id, parent_equipment_id, location_id, location_place_id, inventory_code, inventory_code_manual_override, title, manufacturer, model, production_year, condition_status, ownership_status, location_text, barcode_value, qr_token, notes, created_by, updated_by)
SELECT c.id, NULL, l.id, lp.id, 'PR-CON-0001', 0, 'Sega Mega Drive z padem', 'Sega', 'Mega Drive II', '1993', 'inventory', 'pending', 'Magazyn A / Regał 2', '590000000002', 'pr-con-0001', 'Placeholder alpha: konsola do testow relacji z akcesoriami.', u.id, u.id
FROM categories c
JOIN users u ON u.username = 'pressreset-root'
JOIN locations l ON l.slug = 'magazyn-a'
JOIN location_places lp ON lp.location_id = l.id AND lp.slug = 'regal-2'
WHERE c.slug = 'konsole'
AND NOT EXISTS (SELECT 1 FROM equipment WHERE inventory_code = 'PR-CON-0001');

INSERT INTO equipment (category_id, parent_equipment_id, location_id, location_place_id, inventory_code, inventory_code_manual_override, title, manufacturer, model, production_year, condition_status, ownership_status, location_text, barcode_value, qr_token, notes, created_by, updated_by)
SELECT c.id, NULL, l.id, lp.id, 'PR-DSP-0001', 0, 'Monitor CRT 14 cali', 'Sony', 'Trinitron KV-14', '1998', 'needs_service', 'unknown', 'Magazyn B / Strefa TV', '590000000003', 'pr-dsp-0001', 'Placeholder alpha: ekran do testow statusow i notatek technicznych.', u.id, u.id
FROM categories c
JOIN users u ON u.username = 'pressreset-root'
JOIN locations l ON l.slug = 'magazyn-b'
JOIN location_places lp ON lp.location_id = l.id AND lp.slug = 'strefa-tv'
WHERE c.slug = 'monitory-tv'
AND NOT EXISTS (SELECT 1 FROM equipment WHERE inventory_code = 'PR-DSP-0001');

INSERT INTO audit_logs (user_id, entity_type, entity_id, action_name, details_json)
SELECT u.id, 'system', 0, 'seed', JSON_OBJECT('source', 'schema.sql')
FROM users u
WHERE u.username = 'pressreset-root'
AND NOT EXISTS (SELECT 1 FROM audit_logs WHERE action_name = 'seed');

INSERT INTO app_settings (setting_key, setting_value)
VALUES
    ('festival_deadline', '2027-09-01T00:00:00+02:00'),
    ('threshold_needs_service_warning', '5'),
    ('threshold_needs_service_warning_high', '10'),
    ('threshold_needs_service_danger', '20'),
    ('threshold_open_items_warning', '5'),
    ('threshold_open_items_warning_high', '10'),
    ('threshold_open_items_danger', '20'),
    ('threshold_open_tasks_warning', '10'),
    ('threshold_open_tasks_warning_high', '20'),
    ('threshold_open_tasks_danger', '40')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

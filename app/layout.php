<?php

declare(strict_types=1);

// Funkcje pomocnicze warstwy prezentacji i widoków (layout, selecty, etykiety).
function render_primary_navigation_links(): void
{
    ?>
    <a class="nav-pill" href="index.php">Dashboard</a>
    <a class="nav-pill" href="index.php?page=equipment-list">Sprzęt</a>
    <?php if (can_manage_settings()): ?>
        <a class="nav-pill" href="index.php?page=settings">Konfiguracja</a>
        <a class="nav-pill" href="index.php?page=locations">Lokalizacje</a>
    <?php endif; ?>
    <?php if (can_view_audit_history()): ?>
        <a class="nav-pill" href="index.php?page=audit-log">Historia zdarzeń</a>
    <?php endif; ?>
    <?php if (can_manage_dictionaries()): ?>
        <a class="nav-pill" href="index.php?page=dictionaries">Słowniki</a>
    <?php endif; ?>
    <?php if (can_manage_roles()): ?>
        <a class="nav-pill" href="index.php?page=roles">Role</a>
    <?php endif; ?>
    <?php if (can_manage_users()): ?>
        <a class="nav-pill" href="index.php?page=users">Użytkownicy</a>
    <?php endif;
}

function render_header(string $title): void
{
    $user = current_user();
    $appTitle = app_config('app_title');
    $styleVersion = (string) (@filemtime(__DIR__ . '/../assets/style.css') ?: time());
    $deadlineIso = (string) app_setting('festival_deadline', app_config('festival_deadline'));
    $deadlineCard = null;
    if ($deadlineIso) {
        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Warsaw'));
        $deadline = new DateTimeImmutable($deadlineIso, new DateTimeZone('Europe/Warsaw'));
        $isPast = $deadline <= $now;
        $deadlineCard = [
            'label' => $isPast ? 'Termin minął' : 'Do otwarcia festiwalu',
            'date' => $deadline->format(DateTimeInterface::ATOM),
            'display_date' => $deadline->format('Y-m-d H:i:s'),
            'summary' => $isPast ? 'Termin docelowy został przekroczony.' : '',
        ];
    }
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?> | <?= h($appTitle) ?></title>
        <link rel="stylesheet" href="assets/style.css?v=<?= h($styleVersion) ?>">
    </head>
    <body>
    <main class="shell">
    <?php if ($user): ?>
        <section class="hero">
            <div class="hero-head">
                <div class="hero-copy">
                    <div class="badge"><?= h(role_label($user['role'])) ?></div>
                    <h1><?= h(app_config('app_name')) ?></h1>
                    <p>Alpha systemu inwentaryzacji sprzętu fundacji Press Reset. Wersja robocza obejmuje logowanie, role, audit log, podstawowy CRUD oraz upload zdjęć.</p>
                </div>
                <?php if ($deadlineCard): ?>
                    <div class="hero-countdown">
                        <div class="countdown-card" data-deadline="<?= h($deadlineCard['date']) ?>">
                            <strong><?= h($deadlineCard['label']) ?></strong>
                            <span class="countdown-value"><?= h($deadlineCard['summary']) ?></span>
                            <small>Cel: <?= h($deadlineCard['display_date']) ?></small>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="hero-user">
                    <div class="muted"><?= h($user['display_name']) ?></div>
                    <div><a class="nav-pill" href="index.php?page=change-password">Zmień hasło</a></div>
                </div>
            </div>
            <nav class="topbar desktop-topbar">
                <div class="actions">
                    <?php render_primary_navigation_links(); ?>
                </div>
                <form method="post" action="index.php?page=logout" class="inline-form">
                    <?= csrf_field() ?>
                    <button class="nav-pill" type="submit">Wyloguj</button>
                </form>
            </nav>
            <details class="mobile-nav">
                <summary class="nav-pill">Menu i konto</summary>
                <div class="mobile-nav-panel">
                    <div class="actions">
                        <?php render_primary_navigation_links(); ?>
                    </div>
                    <div class="mobile-nav-account">
                        <a class="nav-pill" href="index.php?page=change-password">Zmień hasło</a>
                        <form method="post" action="index.php?page=logout" class="inline-form">
                            <?= csrf_field() ?>
                            <button class="nav-pill" type="submit">Wyloguj</button>
                        </form>
                    </div>
                </div>
            </details>
        </section>
    <?php endif;

    foreach (flash() as $message): ?>
        <div class="flash <?= h($message['type']) ?>"><?= h($message['message']) ?></div>
    <?php endforeach;
}

function render_footer(): void
{
    ?>
    <script>
    document.querySelectorAll('.countdown-card[data-deadline]').forEach(function (card) {
        var output = card.querySelector('.countdown-value');
        var target = Date.parse(card.dataset.deadline || '');
        if (!output || Number.isNaN(target)) {
            return;
        }

        function pad(value) {
            return String(value).padStart(2, '0');
        }

        function updateCountdown() {
            var diff = target - Date.now();
            if (diff <= 0) {
                output.textContent = '00 dni 00:00:00';
                card.classList.add('countdown-expired');
                return;
            }

            var totalSeconds = Math.floor(diff / 1000);
            var days = Math.floor(totalSeconds / 86400);
            var hours = Math.floor((totalSeconds % 86400) / 3600);
            var minutes = Math.floor((totalSeconds % 3600) / 60);
            var seconds = totalSeconds % 60;
            output.textContent = days + ' dni ' + pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
        }

        updateCountdown();
        window.setInterval(updateCountdown, 1000);
    });
    </script>
    </main></body></html>
    <?php
}

function category_options(?int $selectedId = null, bool $includePlaceholder = false, string $placeholder = 'Wybierz kategorię'): string
{
    $options = $includePlaceholder ? sprintf('<option value="">%s</option>', h($placeholder)) : '';
    foreach (inventory_dictionary_rows('categories') as $category) {
        $selected = ((int) $category['id'] === $selectedId) ? ' selected' : '';
        $options .= sprintf('<option value="%d"%s>%s</option>', (int) $category['id'], $selected, h($category['name']));
    }
    return $options;
}

function equipment_parent_options(?int $selectedId = null, ?int $excludeId = null): string
{
    $options = '<option value="">Brak</option>';
    $params = [];
    $sql = 'SELECT id, inventory_code, title FROM equipment';
    if ($excludeId) {
        $sql .= ' WHERE id != :exclude_id';
        $params['exclude_id'] = $excludeId;
    }
    $sql .= ' ORDER BY inventory_code';
    foreach (query_all($sql, $params) as $row) {
        $selected = ((int) $row['id'] === $selectedId) ? ' selected' : '';
        $label = sprintf('%s - %s', $row['inventory_code'], $row['title']);
        $options .= sprintf('<option value="%d"%s>%s</option>', (int) $row['id'], $selected, h($label));
    }
    return $options;
}

function status_options(string $table, string $selectedValue, string $placeholder = 'Wszystkie'): string
{
    return dictionary_options($table, $selectedValue !== '' ? $selectedValue : null, true, $placeholder);
}

function audit_target_label_html(array $entry, array $details = []): string
{
    if ($entry['entity_type'] === 'equipment' && !empty($entry['inventory_code'])) {
        return sprintf(
            '<a href="index.php?page=item&id=%d">%s</a> · %s',
            (int) $entry['entity_id'],
            h((string) $entry['inventory_code']),
            h((string) ($entry['title'] ?? ''))
        );
    }

    if (in_array($entry['action_name'], ['create_role', 'update_role', 'delete_role'], true)) {
        return !empty($details['name']) ? 'Rola · ' . h((string) $details['name']) : 'Rola systemowa';
    }

    if (in_array($entry['action_name'], ['create_dictionary_entry', 'update_dictionary_entry', 'delete_dictionary_entry'], true)) {
        $dictionaryTable = (string) ($details['dictionary_table'] ?? '');
        if (in_array($dictionaryTable, ['categories', 'condition_statuses', 'ownership_statuses'], true)) {
            $label = dictionary_table_config($dictionaryTable)['label'];
            if (!empty($details['name'])) {
                $label .= ' · ' . h((string) $details['name']);
            }
            return $label;
        }
        return 'Słownik systemowy';
    }

    if (in_array($entry['action_name'], ['update_location', 'create_location'], true)) {
        return !empty($details['name']) ? 'Lokalizacja · ' . h((string) $details['name']) : 'Lokalizacja';
    }

    if (in_array($entry['action_name'], ['update_location_place', 'create_location_place'], true)) {
        return !empty($details['name']) ? 'Miejsce · ' . h((string) $details['name']) : 'Miejsce w lokalizacji';
    }

    if ($entry['action_name'] === 'delete_location') {
        return 'Lokalizacja';
    }

    if ($entry['action_name'] === 'delete_location_place') {
        return 'Miejsce w lokalizacji';
    }

    if ($entry['entity_type'] === 'settings') {
        return 'Konfiguracja systemu';
    }

    if ($entry['entity_type'] === 'user') {
        if (!empty($entry['target_username'])) {
            return 'Użytkownik · ' . h((string) $entry['target_username']);
        }
        if (!empty($details['username'])) {
            return 'Użytkownik · ' . h((string) $details['username']);
        }
        if (!empty($entry['target_display_name'])) {
            return 'Użytkownik · ' . h((string) $entry['target_display_name']);
        }
        return 'Użytkownik';
    }

    if ($entry['action_name'] === 'seed' || $entry['entity_type'] === 'system') {
        return 'Inicjalizacja systemu';
    }

    return h((string) $entry['entity_type']) . ' #' . (int) $entry['entity_id'];
}

function stat_alert_class(int $value, array $thresholds): string
{
    if ($value >= ($thresholds['danger'] ?? PHP_INT_MAX)) {
        return 'alert-danger';
    }
    if ($value >= ($thresholds['warning_high'] ?? PHP_INT_MAX)) {
        return 'alert-warning-high';
    }
    if ($value >= ($thresholds['warning'] ?? PHP_INT_MAX)) {
        return 'alert-warning';
    }
    return 'alert-normal';
}

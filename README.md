# Press Reset Inventory Alpha

Minimalna aplikacja PHP dla pilotażu inwentaryzacji sprzętu fundacji Press Reset.

## Konfiguracja
- skopiuj `config.example.php` do `config.php` i wpisz lokalne dane dostępu do bazy
- `AGENTS.md`, `config.php`, backupy, uploady użytkowników i katalog `storage` są celowo wykluczone z repozytorium

## Logowanie seed
- SuperAdmin: `pressreset-root`
- Hasło SuperAdmin: zapisane w `AGENTS.md`

## Uruchomienie bazy
```bash
mysql --defaults-file=/home/ubuntu/.my.pressreset-inventory.cnf < /var/www/html/pr/schema.sql
```

## Zakres
- logowanie i role: dynamiczne role oparte o słownik ról, z domyślnymi rolami `Root SuperAdmin`, `SuperAdmin`, `Admin`, `ReadOnly`
- role mają rozdzielone uprawnienia m.in. dla użytkowników, ról, zdjęć, aktualizacji zadań, zmian statusu zadań i operacji usuwania
- istnieje osobna strona `Historia zdarzeń` z filtrowaniem po dacie, użytkowniku i sprzęcie
- podstawowy CRUD sprzętu
- słowniki `SuperAdmin` dla kategorii, statusów sprzętu i statusów formalnych
- automatyczne uzupełnianie kodu inwentarzowego po wyborze kategorii
- rozróżnienie kodu automatycznego i ręcznie nadpisanego w formularzu sprzętu
- zdjęcia sprzętu
- historia zmian
- trzy rekordy placeholderowe

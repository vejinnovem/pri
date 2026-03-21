# Press Reset Inventory v0.1.0-alpha

Pierwszy release alpha aplikacji `Press Reset Inventory` do pilotażowej inwentaryzacji sprzętu fundacji Press Reset.

## Zawartość release

- aplikacja PHP pod Apache z routingiem `index.php?page=...`
- logowanie i dynamiczne role: `Root SuperAdmin`, `SuperAdmin`, `Admin`, `ReadOnly`
- CRUD sprzętu z historią zmian
- zdjęcia sprzętu z lokalnym uploadem i skalowaniem do `800x800`
- zadania per sprzęt wraz z aktualizacjami i statusami
- lokalne generowanie QR i widok etykiety do druku
- dashboard, konfiguracja, słowniki, lokalizacje i historia zdarzeń

## Snapshot bazy

- plik `release/pressreset_inventory_alpha-release-2026-03-21.sql` zawiera gotowy snapshot release
- snapshot zachowuje aktualny stan konfiguracji, lokalizacji, słowników, ról i rekordów sprzętu
- dane `audit_logs` zostały celowo wyczyszczone przed publikacją

## Konta startowe

- `pressreset-root` / `PR-SuperAdmin-2026!`
- `pressreset-admin` / `PR-Admin-2026!`
- `pressreset-view` / `PR-View-2026!`

Konta `pressreset-admin` i `pressreset-view` wymuszają zmianę hasła przy pierwszym logowaniu.

## Import

```bash
mysql -u root -p < release/pressreset_inventory_alpha-release-2026-03-21.sql
```

# Release SQL

Plik `pressreset_inventory_alpha-release-2026-03-21.sql` zawiera pełny snapshot bazy do release.

Import:

```bash
mysql -u root -p < release/pressreset_inventory_alpha-release-2026-03-21.sql
```

Snapshot zachowuje aktualny stan:
- konfiguracji aplikacji,
- lokalizacji i miejsc,
- rekordów sprzętu,
- słowników i ról,
- trzech uzgodnionych kont startowych.

Historia zdarzeń została wyczyszczona w artefakcie release, żeby nie publikować roboczych loginów i starych zmian administracyjnych.

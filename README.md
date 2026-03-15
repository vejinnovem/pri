# Press Reset Inventory Alpha

Minimalna aplikacja PHP dla pilotażu inwentaryzacji sprzętu fundacji Press Reset.

## Wymagane pakiety Ubuntu
- serwer WWW i PHP dla Apache:
  - `apache2`
  - `libapache2-mod-php`
  - `php`
  - `php-cli`
  - `php8.3`
- moduły PHP używane przez aplikację:
  - `php-mysql`
  - `php-gd`
  - `php-mbstring`
  - `php-intl`
  - `php-xml`
  - `php-zip`
- baza i narzędzia CLI:
  - serwer zgodny z MySQL / MariaDB
  - `mysql-client`
- QR i PDF:
  - `php-bacon-qr-code`
  - `php-imagick`
  - `php8.3-imagick`
  - `php-dasprid-enum`
  - `php-tcpdf`

## Szybka instalacja na Ubuntu
```bash
sudo apt update
sudo apt install -y \
  apache2 \
  libapache2-mod-php \
  php \
  php-cli \
  php8.3 \
  php-mysql \
  php-gd \
  php-mbstring \
  php-intl \
  php-xml \
  php-zip \
  mysql-client \
  php-bacon-qr-code \
  php-imagick \
  php8.3-imagick \
  php-dasprid-enum \
  php-tcpdf
```

## Wymagane rozszerzenia PHP
- `pdo_mysql`
- `mysqli`
- `gd`
- `mbstring`
- `intl`
- `session`
- `xml`
- `SimpleXML`
- `zip`

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

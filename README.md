# KLXM Installer Proxy

Proxy- und Verwaltungsservice fuer private REDAXO Addons auf GitHub.

## Wichtige Voraussetzung

Dieses Projekt ist die Server-/Proxy-Seite.
Fuer die Installation und Updates im REDAXO-System wird zusaetzlich das AddOn `client_installer` benoetigt.

- Ohne `client_installer` kann der Proxy zwar Pakete und Versionen liefern, aber keine AddOn-Installation im Zielsystem ausfuehren.
- `client_installer` nutzt die API dieses Proxys (`packages`, `versions`, `download`) fuer den eigentlichen Installations- und Update-Flow.

## Funktionen

- Admin Area fuer Kunden und Sponsoren
- Dynamische GitHub-Bereiche (Owner/Orgs beliebig hinzufuegen)
- Eigene Settings-Seite fuer GitHub-Bereiche und Branding
- Freigabe-Matrix: welches Repo fuer welchen Kunden erlaubt ist
- API-Tokens pro Kunde mit Rotation
- Proxy-API fuer AddOn-Installation und Updates
- Download-Streaming aus privaten GitHub-Repositories
- Modernes UI mit Light/Dark/Auto Theme, Sponsoren-Highlight, Brandingfarben und Logo

## Installation

1. In dieses Verzeichnis wechseln.
2. Optional: `composer install`
3. Browser auf `.../installer/install.php` oeffnen.
4. Datenbank eintragen (MySQL/MariaDB).
5. Admin-Konto anlegen.
6. Im Adminbereich mindestens einen GitHub-Owner mit Token hinterlegen und private Repositories synchronisieren.
7. Kunden/Sponsoren anlegen und pro Benutzer die erlaubten Repositories freigeben.
8. Im Ziel-REDAXO das AddOn `client_installer` installieren und mit diesem Proxy verbinden.

## Docker Dev Setup

Fuer lokale Entwicklung ist ein Docker-Setup enthalten (`docker-compose.yml`).

### Voraussetzungen

- Docker Desktop oder Docker Engine + Compose

### Schnellstart

1. In den Projektordner wechseln.
2. Container starten:

	```bash
	docker compose up -d --build
	```

3. Setup im Browser aufrufen:

	- `http://localhost:8088/install.php`

4. Im Setup diese DB-Werte verwenden:

	- Host: `db`
	- Port: `3306`
	- Datenbank: `klxm_installer_proxy`
	- Benutzer: `klxm`
	- Passwort: `klxm`

5. Danach Login unter:

	- `http://localhost:8088/index.php?route=login`

### Wichtige Hinweise fuer Entwicklung

- `config.php` wird absichtlich nicht versioniert.
- Bei Docker-Entwicklung wird `config.php` durch den Setup-Prozess erzeugt.
- Das Volume `db_data` speichert den MariaDB-Stand persistent.

### Nützliche Kommandos

```bash
# Logs anzeigen
docker compose logs -f

# Container stoppen
docker compose down

# Container + DB-Volume entfernen (Reset)
docker compose down -v
```

## Zusammenspiel mit client_installer

Der typische Ablauf ist:

1. Admin pflegt Owner-Token und Repo-Freigaben im KLXM Installer Proxy.
2. Kunde/Sponsor erhaelt eigenen API-Token aus dem Proxy.
3. `client_installer` im Zielsystem nutzt den API-Token und ruft die Proxy-Endpunkte auf.
4. Der Proxy liefert Versionen und ZIP-Downloads aus privaten GitHub-Repositories aus.
5. `client_installer` installiert/aktualisiert das AddOn im REDAXO-System.

## API

### Auth

Bearer Token via `Authorization: Bearer klxm_xxx`

### Endpunkte

- `GET /installer/index.php?route=api/v1/packages`
- `GET /installer/index.php?route=api/v1/versions&owner=KLXM&repo=mein_repo`
- `GET /installer/index.php?route=api/v1/download&owner=KLXM&repo=mein_repo&ref=v1.2.3`

## Login

- Admins, Kunden und Sponsoren melden sich mit eigenem E-Mail/Passwort-Konto an.
- Kunden und Sponsoren sehen nur ihre freigegebenen privaten Repositories und koennen eigene API-Tokens rotieren.
- Admins koennen ausserdem fuer Kunden/Sponsoren neue Tokens erzeugen.

## Passkey

Das Datenmodell fuer Passkeys ist enthalten (`passkeys` Tabelle). Fuer produktionsreife WebAuthn-Verifikation empfiehlt sich die Aktivierung mit einer spezialisierten Bibliothek oder einem zentralen Identity-Provider (z. B. Keycloak mit Passkey/WebAuthn).

## Sicherheitshinweise

- GitHub-Zugriff bleibt serverseitig.
- Kunden erhalten nur eigene API-Tokens.
- Freigaben werden strikt pro Kunde und Repository geprueft.
- Token-Hashes werden gespeichert, nie Klartext.
- Audit-Log fuer Admin- und Sicherheitsereignisse.

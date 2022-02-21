[![Image](../imgs/Klyqa_Logo.png)](https://klyqa.de)

### Splitter

Dieses Modul stellt die Kommunikation mit dem Klyqa Server her.  

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Stellt die Verbindung zum Klyqa Server her

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- Klyqa KL-E27C
- Klyqa KL-E27W

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.

### 4. Einrichten der Instanzen in IP-Symcon

- In IP-Symcon an beliebiger Stelle `Instanz hinzufügen` auswählen und `Klyqa Splitter` auswählen, welches unter dem Hersteller `Klyqa` aufgeführt ist.
- Es wird eine neue `Klyqa Splitter` Instanz unter der Kategorie `Splitter Instanzen` angelegt.

__Konfigurationsseite__:

Name            | Beschreibung
--------------- | -----------------------------------------
Aktiv           | Splitter Instanz in- / aktiv schalten
E-Mail Adresse  | E-Mail Adresse (Klyqa Konto)
Kennwort        | Kennwort (Klyqa Konto)
Timeout         | Netzwerk Timeout

__Aktionsbereich__:

Name                    | Beschreibung
----------------------- | ----------------------------------------------
Token                   |
Entwicklerbereich       |
Access Token            | Access Token
Access Token übernehmen | Access Token übernehmen
Access Token löschen    | Access Token löschen
Debug Tokens            | Debug Tokens

__Vorgehensweise__:

* Geben Sie Ihre E-Mail Adresse und Ihr Passwort für Ihr Klyqa Konto an und übernehmen anschließend die Änderungen.

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt.  
Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Es werden keine Statusvariablen angelegt.

##### Profile:

Es werden keine Profile verwendet.

### 6. WebFront

Die `Klyqa Splitter` Instanz hat im WebFront keine Funktionalität.

### 7. PHP-Befehlsreferenz

* Gemäss verfügbarer Auflistung in IP-Symcon
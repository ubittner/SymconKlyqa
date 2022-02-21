[![Image](../imgs/Klyqa_Logo.png)](https://klyqa.de)

### Device (Gerät)

Dieses Modul steuert eine Klyqa Lampe.  

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

* Lampe schalten (Aus / An)
* Status (Farbe)
* Modus schalten (Farbe / Weisston)
* Farbe auswählen (RGB)
* Voreinstellungen (5 verschiedene Weisstöne in °K)
* Temperatur auswählen (2700°K bis 6500°K)
* Helligkeit verändern (1% bis 100%)

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- Klyqa Splitter Instanz
- Klyqa KL-E27C
- Klyqa KL-E27W

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.

- Sofern noch keine `Klyqa Splitter` Instanz in IP-Symcon vorhanden ist, so beginnen Sie mit der Installation der `Klyqa Splitter` Instanz.
- Hier finden Sie die [Dokumentation](../Splitter) zur `Klyqa Splitter` Instanz.

* Sofern noch keine `Klyqa Konfigurator` Instanz in IP-Symcon vorhanden ist, so beginnen Sie mit der Installation der `Klyqa Konfigurator` Instanz.
* Hier finden Sie die [Dokumentation](../Configurator) zur `Klyqa Konfigurator` Instanz.

- Alternativ könenn Sie das Klyqa Gerät auch manuell anlegen. Lesen Sie bitte dafür diese Dokumentation weiter durch.

### 4. Einrichten der Instanzen in IP-Symcon

- In IP-Symcon an beliebiger Stelle `Instanz hinzufügen` auswählen und `Klyqa Gerät` auswählen, welches unter dem Hersteller `Klyqa` aufgeführt ist.
- Es wird eine neue Instanz `Klyqa Gerät` angelegt.

__Konfigurationsseite__:

Name                        | Beschreibung
--------------------------- | -----------------------------------------
Cloud Geräte ID             | Cloud Geräte ID
Gerätename                  | Gerätename
Schaltprofil                | Schaltprofil
Aktualisierungsintervall    | Aktualisierungsinterval

__Aktionsbereich__:

Name                        | Beschreibung
--------------------------- | -----------------------------------------
Entwicklerbereich           |
Gerätestaus aktualisieren   | Aktualisiert denb Status des Gerätes

__Vorgehensweise__:

* Geben Sie bei manueller Erstellung der Instanz `Klyqa Gerät` die Daten für `Cloud Geräte ID` und `Gerätename` in der Instanzkonfiguration an. 
* Wählen Sie das entsprechende Schaltprofil für das Gerät aus und übernehmen anschließend alle Änderungen.

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt.  
Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name        | Typ     | Beschreibung
----------- | ------- | ----------------------------------------------------
Power       | boolean | Power (Aus / An)
Status      | integer | Farbe
Mode        | integer | Modus (Farbe / Weisston)
Color       | integer | Farbe (RGB)
Presets     | integer | Voreinstellungen (5 Weisstöne °K)
Temperature | integer | Temperatur (2700°K bis 6500°K)
Brightness  | integer | Helliggkeit (1% bis 100%)

##### Profile:

KLYQADEV.InstanzID.Name

Name        | Typ
----------- | -------
Mode        | integer
Presets     | integer
Temperature | integer
Brightness  | integer

Wird die Instanz `Klyqa Gerät` gelöscht, so werden automatisch die oben aufgeführten Profile gelöscht.

### 6. WebFront

Die Funktionalität, die das Modul im WebFront bietet:

* Lampe schalten (Aus / An)
* Status (Farbe)
* Modus schalten (Farbe / Weisston)
* Farbe auswählen (RGB)
* Voreinstellungen (5 verschiedene Weisstöne in °K)
* Temperatur auswählen (2700°K bis 6500°K)
* Helligkeit verändern (1% bis 100%)

### 7. PHP-Befehlsreferenz

* Gemäss verfügbarer Auflistung in IP-Symcon
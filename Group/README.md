[![Image](../imgs/Klyqa_Logo.png)](https://klyqa.de)

### Group (Gruppe)

Dieses Modul steuert eine Gruppe von Klyqa Lampen oder einem Raum.  

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

* Gruppe schalten (Aus / An)
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

- Alternativ könenn Sie die Klyqa Gruppe auch manuell anlegen. Lesen Sie bitte dafür diese Dokumentation weiter durch.

### 4. Einrichten der Instanzen in IP-Symcon

- In IP-Symcon an beliebiger Stelle `Instanz hinzufügen` auswählen und `Klyqa Gruppe` auswählen, welches unter dem Hersteller `Klyqa` aufgeführt ist.
- Es wird eine neue Instanz `Klyqa Gruppe` angelegt.

__Konfigurationsseite__:

Name                        | Beschreibung
--------------------------- | ---------------------------
Gruppen ID                  | Gruppen ID
Gruppenname                 | Gruppenname
Aktualisierungsintervall    | Aktualisierungsinterval

__Aktionsbereich__:

Name                        | Beschreibung
--------------------------- | ---------------------------
Schaltprofil                | Schaltprofil
Entwicklerbereich           |
Gruppengeräte ermitteln     | Gruppengeräte ermitteln
Schaltprofil ermitteln      | Schaltprofil ermitteln
Gruppenstatus aktualisieren | Gruppenstatus aktualisieren

__Vorgehensweise__:

* Geben Sie bei manueller Erstellung der Instanz `Klyqa Gruppe` die Daten für `Gruppen ID` und `Gruppenname` in der Instanzkonfiguration an und übernehmen die Änderungen.

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt.  
Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name        | Typ     | Beschreibung
----------- | ------- | -------------------------
Power       | boolean | Power (Aus / An)
Brightness  | integer | Helliggkeit (1% bis 100%)

##### Profile:

KLYQAGRP.InstanzID.Name

Name        | Typ
----------- | -------
Brightness  | integer

Wird die Instanz `Klyqa Gruppe` gelöscht, so werden automatisch die oben aufgeführten Profile gelöscht.

### 6. WebFront

* Gruppe schalten (Aus / An)
* Helligkeit verändern (1% bis 100%)

### 7. PHP-Befehlsreferenz

* Gemäss verfügbarer Auflistung in IP-Symcon
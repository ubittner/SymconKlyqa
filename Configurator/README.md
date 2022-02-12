[![Image](../imgs/Klyqa_Logo.png)](https://klyqa.de)

### Configurator (Konfigurator)

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

* TBD

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- Klyqa Splitter Insatnz
- Klyqa KL-E27C
- Klyqa KL-E27W

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.

### 4. Einrichten der Instanzen in IP-Symcon

* TBD

__Konfigurationsseite__:

Name            | Beschreibung
--------------- | -----------------------------------------
Kategorie       | Auswahl der Kategorie für die Klyqa Geräte
Klyqa Geräte    | Liste der verfügbaren Klyqa Geräte

__Schaltflächen__:

Name            | Beschreibung
--------------- | -----------------------------------------------------------------
Alle erstellen  | Erstellt für alle aufgelisteten Klyqa Geräte jeweils eine Instanz
Erstellen       | Erstellt für das ausgewählte Klyqa Gerät eine Instanz        

__Vorgehensweise__:

* Über die Schaltfläche `AKTUALISIEREN` können Sie die Liste der verfügbaren Klyqa Geräte jederzeit aktualisieren.  
- Wählen Sie `ALLE ERSTELLEN` oder wählen Sie ein Klyqa Gerät aus der Liste aus und drücken dann die Schaltfläche `ERSTELLEN`, um das Klyqa Gerät automatisch anzulegen.
* Sofern noch keine `Klyqa Splitter` Instanz installiert wurde, muss einmalig beim Erstellen der `Klyqa Konfigurator` Instanz die Konfiguration der `Klyqa Splitter` Instanz vorgenommen werden.  
- Geben Sie Ihre E-Mail Adresse und Ihr Passwort für Ihr Klyqa Konto an.
* Wählen Sie anschließend `WEITER` aus.  

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt.  
Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Es werden keine Statusvariablen angelegt.

##### Profile:

Es werden keine Profile verwendet.

### 6. WebFront

Die `Klyqa Konfigurator` Instanz hat im WebFront keine Funktionalität.

### 7. PHP-Befehlsreferenz

Es ist keine Befehlsreferenz verfügbar.
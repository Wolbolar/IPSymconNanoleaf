# IPSymconNanoleaf
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-%3E%205.1-green.svg)](https://www.symcon.de/service/dokumentation/installation/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![StyleCI](https://github.styleci.io/repos/108720991/shield?branch=master)](https://github.styleci.io/repos/108720991)

Modul für IP-Symcon ab Version 5.1 ermöglicht das Schalten von Nanoleaf

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)  
6. [Anhang](#6-anhang)  

## 1. Funktionsumfang

Mit dem Modul lässt sie ein Nanoleaf von IP-Symcon aus schalten.

### Funktionen:  

 - Ein/Aus 
 - Farbauswahl
 - Farbton
 - Sättigung
 - Helligkeit
 - Farbtemperatur
 - Effekt setzten
	  
## 2. Voraussetzungen

 - IP-Symcon 5.1
 - Nanoleaf

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://<IP-Symcon IP>:3777/console/_ öffnen. 


Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](img/store_icon.png?raw=true "open store")

Im Suchfeld nun

```
Nanoleaf
```  

eingeben

![Store](img/module_store_search.png?raw=true "module search")

und schließend das Modul auswählen und auf _Installieren_

![Store](img/install.png?raw=true "install")

drücken.


#### Alternatives Installieren über Modules Instanz

Den Objektbaum _Öffnen_.

![Objektbaum](img/objektbaum.png?raw=true "Objektbaum")	

Die Instanz _'Modules'_ unterhalb von Kerninstanzen im Objektbaum von IP-Symcon (>=Ver. 5.x) mit einem Doppelklick öffnen und das  _Plus_ Zeichen drücken.

![Modules](img/Modules.png?raw=true "Modules")	

![Plus](img/plus.png?raw=true "Plus")	

![ModulURL](img/add_module.png?raw=true "Add Module")
 
Im Feld die folgende URL eintragen und mit _OK_ bestätigen:

```
https://github.com/Wolbolar/IPSymconNanoleaf
```  
	
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_    

Es wird im Standard der Zweig (Branch) _master_ geladen, dieser enthält aktuelle Änderungen und Anpassungen.
Nur der Zweig _master_ wird aktuell gehalten.

![Master](img/master.png?raw=true "master") 

Sollte eine ältere Version von IP-Symcon die kleiner ist als Version 5.1 (min 4.3) eingesetzt werden, ist auf das Zahnrad rechts in der Liste zu klicken.
Es öffnet sich ein weiteres Fenster,

![SelectBranch](img/select_branch.png?raw=true "select branch") 

hier kann man auf einen anderen Zweig wechseln, für ältere Versionen kleiner als 5.1 (min 4.3) ist hier
_Old-Version_ auszuwählen. 


### b. Einrichtung in IP-Symcon
	
In IP-Symcon unterhalb des Kategorie _Discovery Instances_ nun _Instanz hinzufügen_ (_Rechtsklick -> Objekt hinzufügen -> Instanz_) wählen und __*Nanoleaf Discovery*__ hinzufügen.
Anschließend die Discovery Instanz öffnen, eine Kategorie auswählen unter der das Gerät angelegt werden soll und das Gerät erzeugen.

### c. Pairen mit Nanoleaf

Wenn die Geräte Instanz das erste mal geöffnet wird muss zunächt ein Token für die Kommunikation angefordert werden. 
Dazu den Ein Schalter an dem Nanoleaf Geräts für circa 5-7 Sekunden gedrückt halten. Die LED neben den Schalter beginnt zu blinken. Nun in der _Nanoleaf_ Instanz auf den Button
_**Token abholen**_ drücken. Wenn der Token erfolgreich abgeholt werden konnte erscheint dieser im Konfigurationsformular der Instanz. Sobald ein Token vorhanden ist sind dann auch Variablen zum Schalten des Geräts verfügbar.


## 4. Funktionsreferenz

### Nanoleaf:

Es kann die Nanoleaf ein und ausgeschaltet werden. Die Helligkeit, der Farbton, die Sättigung und die Farbtemperatur kann verstellt werden.
Es kann ein Effekt eingestellt werden.


## 5. Konfiguration:

### Nanoleaf:

| Eigenschaft | Typ     | Standardwert | Funktion                                  |
| :---------: | :-----: | :----------: | :---------------------------------------: |
| Host        | string  |              | IP Adresse des Nanoleaf                   |
| Port        | integer |    16021     | Port des Nanoleaf                         |
| Token       | string  |              | Authentifizierungstoken                   |
| deviceid    | string  |              | Geräte ID                                 |
| devicename  | string  |              | Geräte Name                               |

Die Werte werden automatisch von Nanoleaf bezogen und in IP-Symcon gesetzt eine manuelle konfiguration ist _nicht notwendig_.


## 6. Anhang

###  a. Funktionen:

#### Nanoleaf:

`Nanoleaf_On(integer $InstanceID)`

Einschalten

`Nanoleaf_Off(integer $InstanceID)`

Ausschalten

`Nanoleaf_Toggle(integer $InstanceID)`

Ein/Ausschalten

`Nanoleaf_SetBrightness(integer $InstanceID, integer $brightness)`

Helligkeit setzten, $brightness 0 - 100

`Nanoleaf_SetHue(integer $InstanceID, integer $hue)`

Farbton setzten, $hue 0 - 359

`Nanoleaf_SetSaturation(integer $InstanceID, integer $saturation)`

Sättigung setzten, $saturation 0 - 100

`Nanoleaf_SelectEffect(integer $InstanceID, string $effect)`

Effekt setzten, $effect mögliche Werte "Color Burst","Flames","Forest","Inner Peace","Nemo","Northern Lights","Romantic","Snowfall"

`Nanoleaf_GetState(integer $InstanceID)`

Liest den Aktuellen Status aus

`Nanoleaf_GetAllInfo(integer $InstanceID)`

Liest alle Infos vom Nanoleaf und gibt einen Array zurück

`Nanoleaf_GetBrightness(integer $InstanceID)`

Liest die Helligkeit aus

`Nanoleaf_GetHue(integer $InstanceID)`

Liest den Farbton aus

`Nanoleaf_GetSaturation(integer $InstanceID)`

Liest die Sättigung aus

`Nanoleaf_GetColortemperature(integer $InstanceID)`

Liest die Farbtemperatur aus

`Nanoleaf_ListEffect(integer $InstanceID)`

Gibt die verfügbaren Effekte aus

`Nanoleaf_UpdateEffectProfile(integer $InstanceID)`

Liest die Effekte neu aus und aktualisiert das Variablenprofil


###  b. GUIDs und Datenaustausch:

#### Nanoleaf:

GUID: `{09AEFA0B-1494-CB8B-A7C0-1982D0D99C7E}` 
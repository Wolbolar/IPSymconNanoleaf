# Nanoleaf

Modul für IP-Symcon ab Version 4.3

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguartion)  
6. [Anhang](#6-anhang)  

## 1. Funktionsumfang

Mit dem Modul lässt sie ein Nanoleaf von IP-Symcon aus schalten.

### Funktionen:  

 - Ein/Aus 
 - Helligkeit
 - Farbton
 - Sättigung
 - Farbtemperatur
 - Effekt setzten
	  

## 2. Voraussetzungen

 - IPS 4.3
 - Nanoleaf

## 3. Installation

### a. Laden des Moduls   

Die IP-Symcon (min Ver. 4.3) Konsole öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

![Modules](docs/Modules.png?raw=true "Modules")

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

![Modules](docs/Hinzufuegen.png?raw=true "Hinzufügen")
 
In dem sich öffnenden Fenster folgende URL hinzufügen:

	
    `https://github.com/Wolbolar/IPSymconNanoleaf`  
    
und mit _OK_ bestätigen.  

        
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_    


### b. Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und _Nanoleaf_ auswählen.
Anschließend in die IO Instanz wechseln und _Socket öffnen_ drücken. 

### c. Pairen mit Nanoleaf

Nanoleaf einschalten und nun circa 1-2 Minuten warten. Die Nanoleaf Splitter Instanz konfiguriert sich selbstständig. Wenn in der Splitter Instanz nach kurzer Wartezeit alle Werte bis auf den Token vorhanden sind muss Nanoleaf mit Ip-Symcon gepairt werden.
Dazu den Ein Schalter an der Nanolaef für circa 5-7 Sekunden gedrückt halten. Die LED neben den Schalter beginnt zu blinken. Nun im _NanoleafSplitter_ auf den Button
_**Token abholen**_ drücken. Die Instanz danach kurz schließen und wieder öffnen, nun sollte der Token ergänzt worden sein. Wenn nun alle Werte komplett sind kann die Nanoleaf von IP-Symcon geschaltet werden.

Nachdem der Token vorhanden ist kann in die Nanoleaf Instanz gewechselt werden und auf den Button _**Nanoleaf Information abholen**_ gedrückt werden. Nachdem die Instanz geschlossen und wieder geöffnet wurde sind nun die Werte vorhanden.

## 4. Funktionsreferenz

### Nanoleaf:

Es kann die Nanoleaf ein und ausgeschaltet werden. Die Helligkeit, der Farbton, die Sättigung und die Farbtemperatur kann verstellt werden.
Es kann ein Effekt eingestellt werden.
	


## 5. Konfiguration:

### Überschrift:

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

#### Überschrift:

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

###  b. GUIDs und Datenaustausch:

#### Überschrift:

GUID: `{09AEFA0B-1494-CB8B-A7C0-1982D0D99C7E}` 
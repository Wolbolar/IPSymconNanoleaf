# IPSymconNanoleaf
[![Version](https://img.shields.io/badge/Symcon-PHPModule-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-%3E%205.1-green.svg)](https://www.symcon.de/en/service/documentation/installation/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![StyleCI](https://github.styleci.io/repos/108720991/shield?branch=master)](https://github.styleci.io/repos/108720991)

Module for IP Symcon Version 5.1 or higher enables the switching of Nanoleaf.

## Documentation

**Table of Contents**

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Installation](#3-installation)
4. [Function reference](#4-functionreference)
5. [Configuration](#5-configuration)
6. [Annex](#6-annex)

## 1. Features

The module can control Nanoleaf from IP Symcon.

## 2. Requirements

- On / off
- color selection
- hue
- saturation
- brightness
- color temperature
- set effect

## 3. Installation

### a. Loading the module

Open the IP Console's web console with _http://<IP-Symcon IP>:3777/console/_.

Then click on the module store (IP-Symcon > 5.1) icon in the upper right corner.

![Store](img/store_icon.png?raw=true "open store")

In the search field type

```
Nanoleaf
```  


![Store](img/module_store_search_en.png?raw=true "module search")

Then select the module and click _Install_

![Store](img/install_en.png?raw=true "install")


#### Install alternative via Modules instance

_Open_ the object tree.

![Objektbaum](img/object_tree.png?raw=true "object tree")	

Open the instance _'Modules'_ below core instances in the object tree of IP-Symcon (>= Ver 5.x) with a double-click and press the _Plus_ button.

![Modules](img/modules.png?raw=true "modules")	

![Plus](img/plus.png?raw=true "Plus")	

![ModulURL](img/add_module.png?raw=true "Add Module")
 
Enter the following URL in the field and confirm with _OK_:


```	
https://github.com/Wolbolar/IPSymconNanoleaf
```
    
and confirm with _OK_.    
    
Then an entry for the module appears in the list of the instance _Modules_

By default, the branch _master_ is loaded, which contains current changes and adjustments.
Only the _master_ branch is kept current.

![Master](img/master.png?raw=true "master") 

If an older version of IP-Symcon smaller than version 5.1 (min 4.3) is used, click on the gear on the right side of the list.
It opens another window,

![SelectBranch](img/select_branch_en.png?raw=true "select branch") 

here you can switch to another branch, for older versions smaller than 5.1 (min 4.3) select _Old-Version_ .

### b.  Setup in IP-Symcon

In IP Symcon, under the category _Discovery Instances_, select _Instance_ (_rightclick -> add object -> instance_) and add __*Nanoleaf Discovery*__.
Then open the discovery instance, select a category under which the device should be created and create the device.

### c. Pairing with Nanoleaf

When the device instance is opened for the first time, a communication token must first be requested.
To do this, hold down the _Power On_ button on the Nanoleaf device for about 5-7 seconds. The LED next to the switch starts to flash. Now in the _Nanoleaf_ instance push the button
_**Get Token**_ . If the token was successfully retrieved, it appears in the configuration form of the instance. As soon as a token is available, there are also variables available for switching the device.

## 4. Function reference

### Nanoleaf:  

Turn the Nanoleaf on and off. The brightness, hue, saturation and color temperature can be adjusted.
An effect can be set.

## 5. Configuration:

### Nanoleaf:

| Property    | Type    | Value        | Description                               |
| :---------: | :-----: | :----------: | :---------------------------------------: |
| Host        | string  |              | IP Adresse des Nanoleaf                   |
| Port        | integer |    16021     | Port des Nanoleaf                         |
| Token       | string  |              | Authentifizierungstoken                   |
| deviceid    | string  |              | Geräte ID                                 |
| devicename  | string  |              | Geräte Name                               |

The values are automatically obtained from Nanoleaf and set in IP Symcon a manual configuration is not necessary.

## 6. Annex

###  a. Functions:

#### Nanoleaf:

`Nanoleaf_On(integer $InstanceID)`

Turn on

`Nanoleaf_Off(integer $InstanceID)`

Turn Off

`Nanoleaf_Toggle(integer $InstanceID)`

Power Toggle 

`Nanoleaf_SetBrightness(integer $InstanceID, integer $brightness)`

Set Brightness, $brightness 0 - 100

`Nanoleaf_SetHue(integer $InstanceID, integer $hue)`

Set Hue, $hue 0 - 359

`Nanoleaf_SetSaturation(integer $InstanceID, integer $saturation)`

Set Saturation, $saturation 0 - 100

`Nanoleaf_SelectEffect(integer $InstanceID, string $effect)`

Select Effect, $effect possible values "Color Burst","Flames","Forest","Inner Peace","Nemo","Northern Lights","Romantic","Snowfall"

`Nanoleaf_GetState(integer $InstanceID)`

Reads the current status

`Nanoleaf_GetAllInfo(integer $InstanceID)`

Reads all info from Nanoleaf and returns an array

`Nanoleaf_GetBrightness(integer $InstanceID)`

Reads the brightness

`Nanoleaf_GetHue(integer $InstanceID)`

Reads the color

`Nanoleaf_GetSaturation(integer $InstanceID)`

Liest die Sättigung aus

`Nanoleaf_GetColortemperature(integer $InstanceID)`

Reads the saturation

`Nanoleaf_ListEffect(integer $InstanceID)`

Returns the available effects

`Nanoleaf_UpdateEffectProfile(integer $InstanceID)`

Reads out the effects and updates the variable profile


###  b. GUIDs and data exchange:

#### Nanoleaf:

GUID: `{09AEFA0B-1494-CB8B-A7C0-1982D0D99C7E}` 


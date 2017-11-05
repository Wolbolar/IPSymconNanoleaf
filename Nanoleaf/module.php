<?
class Nanoleaf extends IPSModule
{

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->ConnectParent("{14192357-B3A8-F2B3-5172-90E14D1B7EEB}"); // Splitter
        $this->RegisterPropertyString("name", "");
        $this->RegisterPropertyString("serialNo", "");
        $this->RegisterPropertyString("firmwareVersion", "");
        $this->RegisterPropertyString("model", "");
        $this->RegisterPropertyInteger("UpdateInterval", "5");
        $this->RegisterTimer('NanoleafTimerUpdate', 5000, 'Nanoleaf_GetAllInfo('.$this->InstanceID.');');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->RegisterVariableBoolean("State", $this->Translate("state"), "~Switch", 1);
        $this->EnableAction("State");
        $this->RegisterVariableInteger("color", $this->Translate("color"), "~HexColor", 2); // Color Hex, integer
        $this->EnableAction("color");
        $this->RegisterProfileInteger("Nanoleaf.Hue", "Light", "", "", 0, 359, 1, 0);
        $this->RegisterVariableInteger("hue", $this->Translate("hue"), "Nanoleaf.Hue", 3); // Hue (0-359), integer
        $this->EnableAction("hue");
        $this->RegisterVariableInteger("saturation", $this->Translate("sat"), "~Intensity.100", 4); // Saturation (0-100)
        $this->EnableAction("saturation");
        $this->RegisterVariableInteger("Brightness", $this->Translate("brightness"), "~Intensity.100", 5); // Brightness (0-100)
        $this->EnableAction("Brightness");


        $this->RegisterProfileInteger("Nanoleaf.Colortemperature", "Light", "", "", 1200, 6500, 100, 0);
        $this->RegisterVariableInteger("colortemperature", $this->Translate("ct"), "Nanoleaf.Colortemperature", 6); // "max" : 6500, "min" : 1200
        $this->EnableAction("colortemperature");
        $effectass = $this->GetEffectArray();
        $this->RegisterProfileIntegerAss("Nanoleaf.Effect", "Light", "", "", 1, 8, 0, 0, $effectass);
        $this->RegisterVariableInteger("effect", $this->Translate("effect"), "Nanoleaf.Effect", 7);
        $this->EnableAction("effect");
        $this->SetUpdateIntervall();
        // Status Aktiv
        $this->SetStatus(102);
    }

    public function DeleteUser(string $token)
    {
        $payload = array("command" => "DeleteUser", "commandvalue" => $token);
        $result = $this->SendToSplitter($payload);
        return $result;
    }

    protected function SetUpdateIntervall()
    {
        $interval = ($this->ReadPropertyInteger("UpdateInterval"))*1000; // interval ms
        $this->SetTimerInterval("NanoleafTimerUpdate", $interval);
    }

    public function UpdateEffectProfile()
    {
        $effectass = $this->GetEffectArray();
        if (IPS_VariableProfileExists("Nanoleaf.Effect"))
        {
            foreach($effectass as $Association)
            {
                IPS_SetVariableProfileAssociation("Nanoleaf.Effect", $Association[0], $Association[1], $Association[2], $Association[3]);
            }
        }
    }

    protected function GetEffectArray()
    {
        $effectass =  Array(
            Array(1, "Color Burst",  "Light", -1),
            Array(2, "Flames",  "Light", -1),
            Array(3, "Forest",  "Light", -1),
            Array(4, "Inner Peace",  "Light", -1),
            Array(5, "Nemo",  "Light", -1),
            Array(6, "Northern Lights",  "Light", -1),
            Array(7, "Romantic",  "Light", -1),
            Array(8, "Snowfall",  "Light", -1)
        );
        $parentid = $this->GetParent();
        if($parentid)
        {
            $host = IPS_GetProperty($parentid, "Host");
            if(!$host == "")
            {
                $effectlist = $this->ListEffect();
                $list = json_decode($effectlist);
                $effectass =  Array( );
                foreach ($list as $key => $effect)
                {
                    $position = $key+1;
                    $effectass[] = array($position, $effect, "Light", -1);
                }
            }
        }
        return $effectass;
    }

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);//array
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;//ConnectionID
    }

    public function GetAllInfo()
    {
        $payload = array("command" => "GetAllInfo");
        $info = $this->SendToSplitter($payload);
        if($info)
        {
            $data = json_decode($info);
            $name = $data->name;
            $serialNo = $data->serialNo;
            $firmwareVersion = $data->firmwareVersion;
            $model = $data->model;

            $state = $data->state->on->value;
            $brightness = $data->state->brightness->value;
            $hue = $data->state->hue->value;
            $sat = $data->state->sat->value;
            $ct = $data->state->ct->value;
            $colormode = $data->state->colorMode;

            SetValue($this->GetIDForIdent("State"), $state);
            SetValue($this->GetIDForIdent("Brightness"), $brightness);
            SetValue($this->GetIDForIdent("hue"), $hue);
            SetValue($this->GetIDForIdent("saturation"), $sat);
            SetValue($this->GetIDForIdent("colortemperature"), $ct);
            $allinfo = array ("name" => $name, "serialnumber" => $serialNo, "firmware" => $firmwareVersion, "model" => $model, "state" => $state, "brightness" => $brightness, "hue" => $hue, "sat" => $sat, "ct" => $ct, "colormode" => $colormode);
            return $allinfo;
        }
        else
        {
            return false; // could not get Info, Token not set
        }
    }

    public function GetState()
    {
        $payload = array("command" => "GetState");
        $state_json = $this->SendToSplitter($payload);
        $state = json_decode($state_json)->value;
        SetValue($this->GetIDForIdent("State"), $state);
        return $state;
    }

    public function On()
    {
       $payload = array("command" => "On");
       $result = $this->SendToSplitter($payload);
       SetValue($this->GetIDForIdent("State"), true);
       return $result;
    }

    public function Off()
    {
        $payload = array("command" => "Off");
        $result = $this->SendToSplitter($payload);
        SetValue($this->GetIDForIdent("State"), false);
        return $result;
    }

    public function Toggle()
    {
        $state = GetValue($this->GetIDForIdent("State"));
        if($state)
        {
            $result = $this->Off();
        }
        else
        {
            $result = $this->On();
        }
        return $result;
    }

    public function SetColor(int $hexcolor)
    {
        $hex = str_pad(dechex($hexcolor), 6, 0, STR_PAD_LEFT);
        $hsv = $this->HEX2HSV($hex);
        SetValue($this->GetIDForIdent("color"), $hexcolor);
        $hue = $hsv['h'];
        $saturation = $hsv['s'];
        $brightness = $hsv['v'];

        $this->SetHue($hue);
        $this->SetSaturation($saturation);
        $this->SetBrightness($brightness);
    }

    protected function GetHSB()
    {
        $hue = GetValue($this->GetIDForIdent("hue"));
        $saturation = GetValue($this->GetIDForIdent("saturation"));
        $brightness = GetValue($this->GetIDForIdent("Brightness"));
        $hsb = array ("hue" => $hue, "saturation" => $saturation, "brightness" => $brightness);
        return $hsb;
    }

    protected function GetColor()
    {
        $color = $this->GetIDForIdent("color");
        return $color;
    }

    protected function HEX2HSV($hex)
    {
        $r = substr($hex, 0, 2);
        $g = substr($hex, 2, 2);
        $b = substr($hex, 4, 2);
        return $this->RGB2HSV(hexdec($r), hexdec($g), hexdec($b));
    }

    protected function HSV2HEX($h, $s, $v)
    {
        $rgb = $this->HSV2RGB($h, $s, $v);
        $r = str_pad(dechex($rgb['r']), 2, 0, STR_PAD_LEFT);
        $g = str_pad(dechex($rgb['g']), 2, 0, STR_PAD_LEFT);
        $b = str_pad(dechex($rgb['b']), 2, 0, STR_PAD_LEFT);
        return $r.$g.$b;
    }

    protected function RGB2HSV($r, $g, $b)
    {
        if (!($r >= 0 && $r <= 255)) throw new Exception("h property must be between 0 and 255, but is: ${r}");
        if (!($g >= 0 && $g <= 255)) throw new Exception("s property must be between 0 and 255, but is: ${g}");
        if (!($b >= 0 && $b <= 255)) throw new Exception("v property must be between 0 and 255, but is: ${b}");
        $r = ($r / 255);
        $g = ($g / 255);
        $b = ($b / 255);
        $maxRGB = max($r, $g, $b);
        $minRGB = min($r, $g, $b);
        $chroma = $maxRGB - $minRGB;
        $v = $maxRGB * 100; // $v 0 - 100
        if ($chroma == 0)
        {
            return array('h' => 0, 's' => 0, 'v' => $v);
        }
        $s = ($chroma / $maxRGB) * 100; // $s 0 - 100
        if ($r == $minRGB)
        {
            $h = 3 - (($g - $b) / $chroma);
        }
        elseif ($b == $minRGB)
        {
            $h = 1 - (($r - $g) / $chroma);
        }
        else
        {// $g == $minRGB
            $h = 5 - (($b - $r) / $chroma);
        }
        $h = $h / 6 * 360; // 0 - 359
        return array('h' => round($h), 's' => round($s), 'v' => round($v));
    }

    protected function HSV2RGB($h, $s, $v)
    {
        if (!($h >= 0 && $h <= 359)) throw new Exception("h property must be between 0 and 359, but is: ${h}");
        if (!($s >= 0 && $s <= 100)) throw new Exception("s property must be between 0 and 100, but is: ${s}");
        if (!($v >= 0 && $v <= 100)) throw new Exception("v property must be between 0 and 100, but is: ${v}");
        $h = $h * 6 / 360;
        $s = $s / 100;
        $v = $v / 100;
        $i = floor($h);
        $f = $h - $i;
        $m = $v * (1 - $s);
        $n = $v * (1 - $s * $f);
        $k = $v * (1 - $s * (1 - $f));
        switch ($i) {
            case 0:
                list($r, $g, $b) = array($v, $k, $m);
                break;
            case 1:
                list($r, $g, $b) = array($n, $v, $m);
                break;
            case 2:
                list($r, $g, $b) = array($m, $v, $k);
                break;
            case 3:
                list($r, $g, $b) = array($m, $n, $v);
                break;
            case 4:
                list($r, $g, $b) = array($k, $m, $v);
                break;
            case 5:
            case 6:
                list($r, $g, $b) = array($v, $m, $n);
                break;
        }
        $r = round($r * 255);
        $g = round($g * 255);
        $b = round($b * 255);
        return array('r' => $r, 'g' => $g, 'b' => $b);
    }

    protected function SetHexColor()
    {
        $hsb = $this->GetHSB();
        $hex = $this->HSV2HEX($hsb["hue"], $hsb["saturation"], $hsb["brightness"]);
        $hexcolor = hexdec($hex);
        SetValue($this->GetIDForIdent("color"), $hexcolor);
    }

    public function SetBrightness(int $brightness)
    {
        $payload = array("command" => "SetBrightness", "commandvalue" => $brightness);
        $result = $this->SendToSplitter($payload);
        SetValue($this->GetIDForIdent("Brightness"), $brightness);
        $this->SetHexColor();
        return $result;
    }

    public function GetBrightness()
    {
        $payload = array("command" => "GetBrightness");
        $brightness_json = $this->SendToSplitter($payload);
        $brightness = json_decode($brightness_json)->value;
        SetValue($this->GetIDForIdent("Brightness"), $brightness);
        return $brightness;
    }

    public function SetHue(int $hue)
    {
        $payload = array("command" => "SetHue", "commandvalue" => $hue);
        $result = $this->SendToSplitter($payload);
        SetValue($this->GetIDForIdent("hue"), $hue);
        $this->SetHexColor();
        return $result;
    }

    public function GetHue()
    {
        $payload = array("command" => "GetHue");
        $hue_json = $this->SendToSplitter($payload);
        $hue = json_decode($hue_json)->value;
        SetValue($this->GetIDForIdent("hue"), $hue);
        return $hue;
    }

    public function SetSaturation(int $sat)
    {
        $payload = array("command" => "SetSaturation", "commandvalue" => $sat);
        $result = $this->SendToSplitter($payload);
        SetValue($this->GetIDForIdent("saturation"), $sat);
        $this->SetHexColor();
        return $result;
    }

    public function GetSaturation()
    {
        $payload = array("command" => "GetSaturation");
        $sat_json = $this->SendToSplitter($payload);
        $sat = json_decode($sat_json)->value;
        SetValue($this->GetIDForIdent("saturation"), $sat);
        return $sat;
    }

    public function SetColortemperature(int $ct)
    {
        $payload = array("command" => "SetColortemperature", "commandvalue" => $ct);
        $result = $this->SendToSplitter($payload);
        SetValue($this->GetIDForIdent("colortemperature"), $ct);
        return $result;
    }

    public function GetColortemperature()
    {
        $payload = array("command" => "GetColortemperature");
        $ct_json = $this->SendToSplitter($payload);
        $ct = json_decode($ct_json)->value;
        SetValue($this->GetIDForIdent("colortemperature"), $ct);
        return $ct;
    }

    public function ColorMode()
    {
        $payload = array("command" => "ColorMode");
        $result = $this->SendToSplitter($payload);
        return $result;
    }

    public function SelectEffect(string $effect) // "Color Burst","Flames","Forest","Inner Peace","Nemo","Northern Lights","Romantic","Snowfall"
    {
        $payload = array("command" => "SelectEffect", "commandvalue" => $effect);
        $result = $this->SendToSplitter($payload);
        $effect_int = "1";
        $effects = $this->GetCurrentEffectProfile();
        foreach ($effects as $key => $effectposition)
        {
            if($effectposition["Name"] == $effect)
            {
                $effect_int = $effectposition["Value"];
            }
        }
        SetValue($this->GetIDForIdent("effect"), $effect_int);
        return $result;
    }

    protected function SelectEffectInt(int $effect) // "Color Burst","Flames","Forest","Inner Peace","Nemo","Northern Lights","Romantic","Snowfall"
    {
        $effectstring = "Snowfall";
        $effects = $this->GetCurrentEffectProfile();
        foreach ($effects as $key => $effectposition)
        {
            if($effectposition["Value"] == $effect)
            {
                $effectstring = $effectposition["Name"];
            }
        }
        $result = $this->SelectEffect($effectstring);
        return $result;
    }

    protected function GetCurrentEffectProfile()
    {
        $effects =	IPS_GetVariableProfile("Nanoleaf.Effect")["Associations"];
        return $effects;
    }

    public function GetEffect()
    {
        $payload = array("command" => "GetEffect");
        $effect = $this->SendToSplitter($payload);
        return $effect;
    }


    public function ListEffect()
    {
        $payload = array("command" => "List");
        $result = $this->SendToSplitter($payload);
        return $result;
    }


    public function GetInfo()
    {
        $info = $this->GetAllInfo();
        $name = $info["name"];
        $serialNo = $info["serialnumber"];
        $firmwareVersion = $info["firmware"];
        $model = $info["model"];
        IPS_SetProperty($this->InstanceID, "name", $name);
        $this->SendDebug("Nanoleaf:", "name: ".$name,0);
        IPS_SetProperty($this->InstanceID, "serialNo", $serialNo);
        $this->SendDebug("Nanoleaf:", "serial number: ".$serialNo,0);
        IPS_SetProperty($this->InstanceID, "firmwareVersion", $firmwareVersion);
        $this->SendDebug("Nanoleaf:", "firmware version: ".$firmwareVersion,0);
        IPS_SetProperty($this->InstanceID, "model", $model);
        $this->SendDebug("Nanoleaf:", "model: ".$model,0);
        IPS_ApplyChanges($this->InstanceID); // Neue Konfiguration übernehmen
    }

    public function GetGlobalOrientation()
    {
        $payload = array("command" => "GetGlobalOrientation");
        $global_orientation_json = $this->SendToSplitter($payload);
        $global_orientation = json_decode($global_orientation_json)->value;
        return $global_orientation;
    }

    public function SetGlobalOrientation(int $orientation)
    {
        $payload = array("command" => "SetGlobalOrientation", "commandvalue" => $orientation);
        $result = $this->SendToSplitter($payload);
        return $result;
    }

    public function Layout()
    {
        $payload = array("command" => "Layout");
        $result = $this->SendToSplitter($payload);
        return $result;
    }

    public function Identify()
    {
        $payload = array("command" => "Identify");
        $result = $this->SendToSplitter($payload);
        return $result;
    }

    public function RequestAction($Ident, $Value)
    {
        switch($Ident) {
            case "State":
                if ($Value == true)
                {
                    $this->On();
                }
                else
                {
                    $this->Off();
                }
                break;
            case "color":
                $this->SetColor($Value);
                break;
            case "Brightness":
                $this->SetBrightness($Value);
                break;
            case "hue":
                $this->SetHue($Value);
                break;
            case "saturation":
                $this->SetSaturation($Value);
                break;
            case "colortemperature":
                $this->SetHue($Value);
                break;
            case "effect":
                $this->SelectEffectInt($Value);
                break;
            default:
                throw new Exception("Invalid ident");
        }
    }

    protected function SendToSplitter($payload)
    {
        //an Splitter schicken
        $result = $this->SendDataToParent(json_encode(Array("DataID" => "{D6ED1E11-A213-0475-13B7-15504C8E7300}", "Buffer" => $payload))); // Interface GUI
        $this->SendDebug("Send Data:",json_encode($payload),0);
        return $result;
    }


    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
    {

        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
                throw new Exception("Variable profile type does not match for profile ".$Name);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite

    }

    protected function RegisterProfileIntegerAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
    {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        }
        /*
        else {
            //undefiened offset
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        */
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits);

        //boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }

    }


    protected function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
    {

        if(!IPS_VariableProfileExists($Name)) {
        IPS_CreateVariableProfile($Name, 2);
        } else {
        $profile = IPS_GetVariableProfile($Name);
        if($profile['ProfileType'] != 2)
        throw new Exception("Variable profile type does not match for profile ".$Name);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);

    }

    protected function RegisterProfileFloatAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
    {
        if ( sizeof($Associations) === 0 ){
        $MinValue = 0;
        $MaxValue = 0;
        }
        /*
        else {
        //undefiened offset
        $MinValue = $Associations[0][0];
        $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        */
        $this->RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits);

        //boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
        foreach($Associations as $Association) {
        IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }

    }

    //Configuration Form
    public function GetConfigurationForm()
    {
        $formhead = $this->FormHead();
        $formactions = $this->FormActions();
        $formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';
        $formstatus = $this->FormStatus();
        return	'{ '.$formhead.$formelementsend.'],'.$formactions.$formstatus.' }';
    }


    protected function FormHead()
    {
        $name = $this->ReadPropertyString("name");
        if($name == "")
        {
            $form = '"elements":
        [
            { "type": "Label", "label": "Nanoleaf" },
            { "type": "Label", "label": "Update Interval Nanoleaf" },
            { "type": "IntervalBox", "name": "UpdateInterval", "caption": "Sekunden" },
        ';
        }
        else
        {
            $form = '"elements":
        [
            { "type": "Label", "label": "Nanoleaf" },
            { "name": "name",                 "type": "ValidationTextBox", "caption": "name" },
            { "name": "serialNo",                 "type": "ValidationTextBox", "caption": "serial number" },
            { "name": "firmwareVersion",                 "type": "ValidationTextBox", "caption": "firmware version" },
            { "name": "model",                 "type": "ValidationTextBox", "caption": "model" },
            { "type": "Label", "label": "Update Interval Nanoleaf" },
            { "type": "IntervalBox", "name": "UpdateInterval", "caption": "Sekunden" },
        ';
        }

        return $form;
    }

    protected function FormActions()
    {
        $form = '"actions":
        [
        { "type": "Button", "label": "On",  "onClick": "Nanoleaf_On($id);" },
        { "type": "Button", "label": "Off",  "onClick": "Nanoleaf_Off($id);" },
        { "type": "Button", "label": "Get Nanoleaf info",  "onClick": "Nanoleaf_GetInfo($id);" },
        { "type": "Button", "label": "Update Effects",  "onClick": "Nanoleaf_UpdateEffectProfile($id);" }
        ],';
        return  $form;
    }

    protected function FormStatus()
    {
        $form = '"status":
        [
        {
        "code": 101,
        "icon": "inactive",
        "caption": "Creating instance."
        },
        {
        "code": 102,
        "icon": "active",
        "caption": "instance created."
        },
        {
        "code": 104,
        "icon": "inactive",
        "caption": "interface closed."
        },
        {
        "code": 202,
        "icon": "error",
        "caption": "special errorcode."
        }
        ]';
        return $form;
    }

}

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
        $this->RegisterVariableInteger("Brightness", $this->Translate("brightness"), "~Intensity.100", 2); // Brightness (0-100)
        $this->EnableAction("Brightness");
        $this->RegisterProfileInteger("Nanoleaf.Hue", "Light", "", "", 0, 359, 1, 0);
        $this->RegisterVariableInteger("hue", $this->Translate("hue"), "Nanoleaf.Hue", 3); // Hue (0-359), integer
        $this->EnableAction("hue");
        $this->RegisterVariableInteger("saturation", $this->Translate("sat"), "~Intensity.100", 4); // Saturation (0-100)
        $this->EnableAction("saturation");
        $this->RegisterProfileInteger("Nanoleaf.Colortemperature", "Light", "", "", 1200, 6500, 100, 0);
        $this->RegisterVariableInteger("colortemperature", $this->Translate("ct"), "Nanoleaf.Colortemperature", 5); // "max" : 6500, "min" : 1200
        $this->EnableAction("colortemperature");
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
        $this->RegisterProfileIntegerAss("Nanoleaf.Effect", "Light", "", "", 1, 8, 0, 0, $effectass);
        $this->RegisterVariableInteger("effect", $this->Translate("effect"), "Nanoleaf.Effect", 6);
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

    public function SetBrightness(int $brightness)
    {
        $payload = array("command" => "SetBrightness", "commandvalue" => $brightness);
        $result = $this->SendToSplitter($payload);
        SetValue($this->GetIDForIdent("Brightness"), $brightness);
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
        if($effect == "Color Burst")
        {
            $effect_int = 1;
        }
        elseif($effect == "Flames")
        {
            $effect_int = 2;
        }
        elseif($effect == "Forest")
        {
            $effect_int = 3;
        }
        elseif($effect == "Inner Peace")
        {
            $effect_int = 4;
        }
        elseif($effect == "Nemo")
        {
            $effect_int = 5;
        }
        elseif($effect == "Northern Lights")
        {
            $effect_int = 6;
        }
        elseif($effect == "Romantic")
        {
            $effect_int = 7;
        }
        elseif($effect == "Snowfall")
        {
            $effect_int = 8;
        }
        SetValue($this->GetIDForIdent("effect"), $effect_int);
        return $result;
    }

    protected function SelectEffectInt(int $effect) // "Color Burst","Flames","Forest","Inner Peace","Nemo","Northern Lights","Romantic","Snowfall"
    {
        $effectstring = "Snowfall";
        if($effect == 1)
        {
            $effectstring = "Color Burst";
        }
        elseif($effect == 2)
        {
            $effectstring = "Flames";
        }
        elseif($effect == 3)
        {
            $effectstring = "Forest";
        }
        elseif($effect == 4)
        {
            $effectstring = "Inner Peace";
        }
        elseif($effect == 5)
        {
            $effectstring = "Nemo";
        }
        elseif($effect == 6)
        {
            $effectstring = "Northern Lights";
        }
        elseif($effect == 7)
        {
            $effectstring = "Romantic";
        }
        elseif($effect == 8)
        {
            $effectstring = "Snowfall";
        }
        $result = $this->SelectEffect($effectstring);
        return $result;
    }

    public function GetEffect()
    {
        $payload = array("command" => "GetEffect");
        $effect = $this->SendToSplitter($payload);
        return $effect;
    }

    /*
    public function ListEffect()
    {
        $payload = array("command" => "List");
        $result = $this->SendToSplitter($payload);
        return $result;
    }
    */

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
        { "type": "Button", "label": "Get Nanoleaf info",  "onClick": "Nanoleaf_GetInfo($id);" }
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

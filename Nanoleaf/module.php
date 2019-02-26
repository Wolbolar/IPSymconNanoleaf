<?
class Nanoleaf extends IPSModule
{

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
		$this->RegisterPropertyString("name", "");
		$this->RegisterPropertyString("deviceid", "");
		$this->RegisterPropertyString("host", "");
		$this->RegisterPropertyString("port", "");
		$this->RegisterPropertyString("uuid", "");
        $this->RegisterAttributeString("serialNo", "");
        $this->RegisterAttributeString("firmwareVersion", "");
        $this->RegisterAttributeString("model", "");
        $this->RegisterPropertyInteger("UpdateInterval", "5");
		$this->RegisterPropertyString("NanoleafInformation", "");
		$this->RegisterAttributeString("Token", "");
        $this->RegisterTimer('NanoleafTimerUpdate', 5000, 'Nanoleaf_GetAllInfo('.$this->InstanceID.');');
		//we will wait until the kernel is ready
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

		if (IPS_GetKernelRunlevel() !== KR_READY) {
			return;
		}

		$this->ValidateConfiguration();
    }

    protected function ValidateConfiguration()
	{
		$token = $this->ReadAttributeString("Token");
		if($token != "")
		{
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
			$this->SetValue("effect", 1);
			$this->SetUpdateIntervall();
		}

		// Status Aktiv
		$this->SetStatus(102);
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
	{

		switch ($Message) {
			case IM_CHANGESTATUS:
				if ($Data[0] === IS_ACTIVE) {
					$this->ApplyChanges();
				}
				break;

			case IPS_KERNELMESSAGE:
				if ($Data[0] === KR_READY) {
					$this->ApplyChanges();
				}
				break;

			default:
				break;
		}
	}

    public function DeleteUser(string $token)
    {
        $payload = array("command" => "DeleteUser", "commandvalue" => $token);
        $result = $this->SendCommand($payload);
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
        $info = $this->SendCommand($payload);
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

            $this->SetValue("State", $state);
			$this->SetValue("Brightness", $brightness);
			$this->SetValue("hue", $hue);
			$this->SetValue("saturation", $sat);
			$this->SetValue("colortemperature", $ct);

            $allinfo = array ("name" => $name, "serialnumber" => $serialNo, "firmware" => $firmwareVersion, "model" => $model, "state" => $state, "brightness" => $brightness, "hue" => $hue, "sat" => $sat, "ct" => $ct, "colormode" => $colormode);
            return $allinfo;
        }
        else
        {
            return false; // could not get Info, Token not set
        }
    }

	public function Authorization()
	{
		/* A user is authorized to access the OpenAPI if they can demonstrate physical access of the Aurora. This is achieved by:
		1. Holding the on-off button down for 5-7 seconds until the LED starts flashing in a pattern
		2. Sending a POST request to the authorization endpoint */
		$host = $this->ReadPropertyString("host");
		$port = $this->ReadPropertyString("port");
		$url = "http://".$host.":".$port."/api/v1/new";
		$ch = curl_init($url);
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_HTTPHEADER => array('Content-type: application/json')
		);
		curl_setopt_array($ch, $options);
		$token_response = curl_exec($ch);
		curl_close($ch);
		$this->SendDebug("Nanoleaf token response: ", json_encode($token_response),0);
		if(empty($token_response))
		{
			echo $this->Translate("Could not get token");
			return false;
		}
		else{
			$token = json_decode($token_response)->auth_token;
			$this->SendDebug("Splitter Received Token:",$token,0);
			$this->WriteAttributeString("Token", $token);
			$this->GetInfo();
			return $token;
		}
	}

	protected function SendCommand($payload)
	{
		$command = $payload["command"];
		$commandvalue = "";
		if(isset($payload["commandvalue"]))
		{
			$commandvalue = $payload["commandvalue"];
		}
		
		
		$host = $this->ReadPropertyString("host");
		$token = $this->ReadAttributeString("Token");
		if ($token == "")
		{
			return false;
		}
		$port = $this->ReadPropertyString("port");
		$url = "http://".$host.":".$port."/api/v1/".$token."/";
		$postfields = "";
		$requesttype = "GET";
		if ($command == "On")
		{
			$url = $url."state";
			$postfields = '{"on" : {"value":true}}';
			$requesttype = "PUT";
		}
		elseif($command == "Off")
		{
			$url = $url."state";
			$postfields = '{"on" : {"value":false}}';
			$requesttype = "PUT";
		}
		elseif($command == "GetState")
		{
			$url = $url."state/on";
			$requesttype = "GET";
		}
		elseif($command == "SetBrightness")
		{
			$url = $url."state";
			$postfields = '{"brightness" : {"value":'.$commandvalue.'}}';
			$requesttype = "PUT";
		}
		elseif($command == "GetBrightness")
		{
			$url = $url."state/brightness";
			$requesttype = "GET";
		}
		elseif($command == "SetHue")
		{
			$url = $url."state";
			$postfields = '{"hue" : {"value":'.$commandvalue.'}}';
			$requesttype = "PUT";
		}
		elseif($command == "GetHue")
		{
			$url = $url."state/hue";
			$requesttype = "GET";
		}
		elseif($command == "SetSaturation")
		{
			$url = $url."state";
			$postfields = '{"sat" : {"value":'.$commandvalue.'}}';
			$requesttype = "PUT";
		}
		elseif($command == "GetSaturation")
		{
			$url = $url."state/sat";
			$requesttype = "GET";
		}
		elseif($command == "SetColortemperature")
		{
			$url = $url."state";
			$postfields = '{"ct" : {"value":'.$commandvalue.'}}';
			$requesttype = "PUT";
		}
		elseif($command == "GetColortemperature")
		{
			$url = $url."state/ct";
			$requesttype = "GET";
		}
		elseif($command == "ColorMode")
		{
			$url = $url."state/colorMode";
			$requesttype = "GET";
		}
		elseif($command == "SelectEffect")
		{
			$url = $url."effects";
			$postfields = '{"select":"'.$commandvalue.'"}';
			$requesttype = "PUT";
		}
		elseif($command == "GetEffect")
		{
			$url = $url."effects/select";
			$requesttype = "GET";
		}
		elseif($command == "List")
		{
			$url = $url."effects/effectsList";
			$requesttype = "GET";
		}
		elseif($command == "Random")
		{
			$url = $url."effects";
			$result = json_decode(Sys_GetURLContent($url."effects/effectsList"),true);
			$postfields = '{"select":"'.$result[array_rand($result)].'"}';
			$requesttype = "PUT";
		}
		elseif($command == "GetAllInfo")
		{
			$requesttype = "GET";
		}
		elseif($command == "DeleteUser")
		{
			$requesttype = "DELETE";
			$url = "http://".$host.":".$port."/api/v1/".$commandvalue;
		}
		elseif($command == "GetGlobalOrientation")
		{
			$requesttype = "GET";
			$url = $url."panelLayout/globalOrientation";
		}
		elseif($command == "SetGlobalOrientation")
		{
			$requesttype = "PUT";
			$postfields = '{"globalOrientation" : {"value":'.$commandvalue.'}}';
			$url = $url."panelLayout";
		}
		elseif($command == "Layout")
		{
			$requesttype = "GET";
			$url = $url."panelLayout/layout";
		}
		elseif($command == "Identify")
		{
			$requesttype = "PUT";
			$url = $url."identify";
			$postfields = "";
		}
		$ch = curl_init($url);
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST => $requesttype,
			CURLOPT_HTTPHEADER => array('Content-type: application/json') ,
		);
		curl_setopt_array($ch, $options);
		if($requesttype == "PUT" || $requesttype == "POST")
		{
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		}
		$result = curl_exec($ch);
		curl_close($ch);
		$this->SendDebug("Nanoleaf Command Response: ", json_encode($result),0);
		return $result;
	}

    public function GetState()
    {
        $payload = array("command" => "GetState");
        $state_json = $this->SendCommand($payload);
        $state = json_decode($state_json)->value;
        SetValue($this->GetIDForIdent("State"), $state);
        return $state;
    }

    public function On()
    {
       $payload = array("command" => "On");
       $result = $this->SendCommand($payload);
       SetValue($this->GetIDForIdent("State"), true);
       return $result;
    }

    public function Off()
    {
        $payload = array("command" => "Off");
        $result = $this->SendCommand($payload);
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
        $result = $this->SendCommand($payload);
        SetValue($this->GetIDForIdent("Brightness"), $brightness);
        $this->SetHexColor();
        return $result;
    }

    public function GetBrightness()
    {
        $payload = array("command" => "GetBrightness");
        $brightness_json = $this->SendCommand($payload);
        $brightness = json_decode($brightness_json)->value;
        SetValue($this->GetIDForIdent("Brightness"), $brightness);
        return $brightness;
    }

    public function SetHue(int $hue)
    {
        $payload = array("command" => "SetHue", "commandvalue" => $hue);
        $result = $this->SendCommand($payload);
        SetValue($this->GetIDForIdent("hue"), $hue);
        $this->SetHexColor();
        return $result;
    }

    public function GetHue()
    {
        $payload = array("command" => "GetHue");
        $hue_json = $this->SendCommand($payload);
        $hue = json_decode($hue_json)->value;
        SetValue($this->GetIDForIdent("hue"), $hue);
        return $hue;
    }

    public function SetSaturation(int $sat)
    {
        $payload = array("command" => "SetSaturation", "commandvalue" => $sat);
        $result = $this->SendCommand($payload);
        SetValue($this->GetIDForIdent("saturation"), $sat);
        $this->SetHexColor();
        return $result;
    }

    public function GetSaturation()
    {
        $payload = array("command" => "GetSaturation");
        $sat_json = $this->SendCommand($payload);
        $sat = json_decode($sat_json)->value;
        SetValue($this->GetIDForIdent("saturation"), $sat);
        return $sat;
    }

    public function SetColortemperature(int $ct)
    {
        $payload = array("command" => "SetColortemperature", "commandvalue" => $ct);
        $result = $this->SendCommand($payload);
        SetValue($this->GetIDForIdent("colortemperature"), $ct);
        return $result;
    }

    public function GetColortemperature()
    {
        $payload = array("command" => "GetColortemperature");
        $ct_json = $this->SendCommand($payload);
        $ct = json_decode($ct_json)->value;
        SetValue($this->GetIDForIdent("colortemperature"), $ct);
        return $ct;
    }

    public function ColorMode()
    {
        $payload = array("command" => "ColorMode");
        $result = $this->SendCommand($payload);
        return $result;
    }

    public function SelectEffect(string $effect) // "Color Burst","Flames","Forest","Inner Peace","Nemo","Northern Lights","Romantic","Snowfall"
    {
        $payload = array("command" => "SelectEffect", "commandvalue" => $effect);
        $result = $this->SendCommand($payload);
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
        $effect = $this->SendCommand($payload);
        return $effect;
    }


    public function ListEffect()
    {
        $payload = array("command" => "List");
        $result = $this->SendCommand($payload);
        return $result;
    }


    public function GetInfo()
    {
        $info = $this->GetAllInfo();
        $name = $info["name"];
        $serialNo = $info["serialnumber"];
        $firmwareVersion = $info["firmware"];
        $model = $info["model"];
        $this->SendDebug("Nanoleaf:", "name: ".$name,0);
        $this->WriteAttributeString("serialNo", $serialNo);
        $this->SendDebug("Nanoleaf:", "serial number: ".$serialNo,0);
		$this->WriteAttributeString("firmwareVersion", $firmwareVersion);
        $this->SendDebug("Nanoleaf:", "firmware version: ".$firmwareVersion,0);
		$this->WriteAttributeString("model", $model);
        $this->SendDebug("Nanoleaf:", "model: ".$model,0);
    }

    public function GetGlobalOrientation()
    {
        $payload = array("command" => "GetGlobalOrientation");
        $global_orientation_json = $this->SendCommand($payload);
        $global_orientation = json_decode($global_orientation_json)->value;
        return $global_orientation;
    }

    public function SetGlobalOrientation(int $orientation)
    {
        $payload = array("command" => "SetGlobalOrientation", "commandvalue" => $orientation);
        $result = $this->SendCommand($payload);
        return $result;
    }

    public function Layout()
    {
        $payload = array("command" => "Layout");
        $result = $this->SendCommand($payload);
        return $result;
    }

    public function Identify()
    {
        $payload = array("command" => "Identify");
        $result = $this->SendCommand($payload);
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

	/***********************************************************
	 * Configuration Form
	 ***********************************************************/

	/**
	 * build configuration form
	 * @return string
	 */
	public function GetConfigurationForm()
	{
		// return current form
		return json_encode([
			'elements' => $this->FormHead(),
			'actions' => $this->FormActions(),
			'status' => $this->FormStatus()
		]);
	}

	/**
	 * return form configurations on configuration step
	 * @return array
	 */
	protected function FormHead()
	{
		$form = [
			[
				'type' => 'Image',
				'image' => 'data:image/png;base64, iVBORw0KGgoAAAANSUhEUgAAAnwAAAB3CAYAAACZtZ28AAAACXBIWXMAAAsTAAALEwEAmpwYAAA4JmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4KPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNS42LWMwNjcgNzkuMTU3NzQ3LCAyMDE1LzAzLzMwLTIzOjQwOjQyICAgICAgICAiPgogICA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPgogICAgICA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIgogICAgICAgICAgICB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIKICAgICAgICAgICAgeG1sbnM6cGhvdG9zaG9wPSJodHRwOi8vbnMuYWRvYmUuY29tL3Bob3Rvc2hvcC8xLjAvIgogICAgICAgICAgICB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIKICAgICAgICAgICAgeG1sbnM6c3RFdnQ9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZUV2ZW50IyIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIj4KICAgICAgICAgPHhtcDpDcmVhdG9yVG9vbD5BZG9iZSBQaG90b3Nob3AgQ0MgMjAxNSAoV2luZG93cyk8L3htcDpDcmVhdG9yVG9vbD4KICAgICAgICAgPHhtcDpDcmVhdGVEYXRlPjIwMTgtMTItMDhUMTk6NTA6NTMrMDE6MDA8L3htcDpDcmVhdGVEYXRlPgogICAgICAgICA8eG1wOk1vZGlmeURhdGU+MjAxOC0xMi0wOVQwOTo0MzoyOSswMTowMDwveG1wOk1vZGlmeURhdGU+CiAgICAgICAgIDx4bXA6TWV0YWRhdGFEYXRlPjIwMTgtMTItMDlUMDk6NDM6MjkrMDE6MDA8L3htcDpNZXRhZGF0YURhdGU+CiAgICAgICAgIDxkYzpmb3JtYXQ+aW1hZ2UvcG5nPC9kYzpmb3JtYXQ+CiAgICAgICAgIDxwaG90b3Nob3A6Q29sb3JNb2RlPjM8L3Bob3Rvc2hvcDpDb2xvck1vZGU+CiAgICAgICAgIDx4bXBNTTpJbnN0YW5jZUlEPnhtcC5paWQ6NzkxNzEwMTYtMDY0MS1lYzQxLTgwZTEtZjI2NzZmMjJiMDcyPC94bXBNTTpJbnN0YW5jZUlEPgogICAgICAgICA8eG1wTU06RG9jdW1lbnRJRD54bXAuZGlkOjc5MTcxMDE2LTA2NDEtZWM0MS04MGUxLWYyNjc2ZjIyYjA3MjwveG1wTU06RG9jdW1lbnRJRD4KICAgICAgICAgPHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD54bXAuZGlkOjc5MTcxMDE2LTA2NDEtZWM0MS04MGUxLWYyNjc2ZjIyYjA3MjwveG1wTU06T3JpZ2luYWxEb2N1bWVudElEPgogICAgICAgICA8eG1wTU06SGlzdG9yeT4KICAgICAgICAgICAgPHJkZjpTZXE+CiAgICAgICAgICAgICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0iUmVzb3VyY2UiPgogICAgICAgICAgICAgICAgICA8c3RFdnQ6YWN0aW9uPmNyZWF0ZWQ8L3N0RXZ0OmFjdGlvbj4KICAgICAgICAgICAgICAgICAgPHN0RXZ0Omluc3RhbmNlSUQ+eG1wLmlpZDo3OTE3MTAxNi0wNjQxLWVjNDEtODBlMS1mMjY3NmYyMmIwNzI8L3N0RXZ0Omluc3RhbmNlSUQ+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDp3aGVuPjIwMTgtMTItMDhUMTk6NTA6NTMrMDE6MDA8L3N0RXZ0OndoZW4+CiAgICAgICAgICAgICAgICAgIDxzdEV2dDpzb2Z0d2FyZUFnZW50PkFkb2JlIFBob3Rvc2hvcCBDQyAyMDE1IChXaW5kb3dzKTwvc3RFdnQ6c29mdHdhcmVBZ2VudD4KICAgICAgICAgICAgICAgPC9yZGY6bGk+CiAgICAgICAgICAgIDwvcmRmOlNlcT4KICAgICAgICAgPC94bXBNTTpIaXN0b3J5PgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpYUmVzb2x1dGlvbj43MjAwMDAvMTAwMDA8L3RpZmY6WFJlc29sdXRpb24+CiAgICAgICAgIDx0aWZmOllSZXNvbHV0aW9uPjcyMDAwMC8xMDAwMDwvdGlmZjpZUmVzb2x1dGlvbj4KICAgICAgICAgPHRpZmY6UmVzb2x1dGlvblVuaXQ+MjwvdGlmZjpSZXNvbHV0aW9uVW5pdD4KICAgICAgICAgPGV4aWY6Q29sb3JTcGFjZT42NTUzNTwvZXhpZjpDb2xvclNwYWNlPgogICAgICAgICA8ZXhpZjpQaXhlbFhEaW1lbnNpb24+NjM2PC9leGlmOlBpeGVsWERpbWVuc2lvbj4KICAgICAgICAgPGV4aWY6UGl4ZWxZRGltZW5zaW9uPjExOTwvZXhpZjpQaXhlbFlEaW1lbnNpb24+CiAgICAgIDwvcmRmOkRlc2NyaXB0aW9uPgogICA8L3JkZjpSREY+CjwveDp4bXBtZXRhPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAg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'
			],
			[
				'type' => 'Label',
				'caption' => 'Nanoleaf'
			]
		];
		$token = $this->ReadAttributeString("Token");
		$uuid = $this->ReadPropertyString("uuid");
		if ($uuid == "") {
			$form = array_merge_recursive(
				$form,
				[
					[
						'type' => 'Label',
						'caption' => 'This device should be created by the Nanoleaf configurator, please open the Nanoleaf configurator and create the device there.'
					]
				]
			);
		}
		if ($token == "" && $uuid != "") {
			$form = array_merge_recursive(
				$form,
				[
					[
						'type' => 'Label',
						'caption' => '1. Hold the on-off button down for 5-7 seconds until the LED starts flashing in a pattern'
					],
					[
						'type' => 'Label',
						'caption' => '2. Press the button below Get Token'
					],
					[
						'type' => 'Button',
						'caption' => 'Get Token',
						'onClick' => 'Nanoleaf_Authorization($id);'
					]
				]
			);
		}
		if ($token != "" && $uuid != "") {
			$form = array_merge_recursive(
				$form,
				[
					[
						'type' => 'Label',
						'caption' => 'Token:'
					],
					[
						'type' => 'Label',
						'caption' => $this->ReadPropertyString("Token")
					],
					[
						'type' => 'List',
						'name' => 'NanoleafInformation',
						'caption' => 'Nanoleaf Information',
						'rowCount' => 2,
						'add' => false,
						'delete' => false,
						'sort' => [
							'column' => 'deviceid',
							'direction' => 'ascending'
						],
						'columns' => [
							[
								'caption' => 'Device ID',
								'name' => 'deviceid',
								'width' => '150px',
								'save' => true,
								'visible' => true
							],
							[
								'caption' => 'IP adress',
								'name' => 'host',
								'width' => '140px',
								'save' => true,
							],
							[
								'caption' => 'Port',
								'name' => 'port',
								'width' => '80px',
								'save' => true,
								'visible' => true
							],
							[
								'caption' => 'Serial Number',
								'name' => 'serialNo',
								'width' => '150px',
								'save' => true,
								'visible' => true
							],
							[
								'caption' => 'Firmware Version',
								'name' => 'firmwareVersion',
								'width' => '140px',
								'save' => true,
								'visible' => true
							],
							[
								'caption' => 'Model',
								'name' => 'model',
								'width' => '80px',
								'save' => true,
								'visible' => true
							]
						],
						'values' => [
							[
								'deviceid' => $this->ReadPropertyString("deviceid"),
								'host' => $this->ReadPropertyString("host"),
								'port' => $this->ReadPropertyString("port"),
								'serialNo' => $this->ReadAttributeString("serialNo"),
								'firmwareVersion' => $this->ReadAttributeString("firmwareVersion"),
								'model' => $this->ReadAttributeString("model")
							]
						]
					],
					[
						'type' => 'Label',
						'caption' => 'Update Interval Nanoleaf'
					],
					[
						'name' => 'UpdateInterval',
						'type' => 'IntervalBox',
						'caption' => 'Sekunden'
					]
				]
			);
		}
		return $form;
	}

	/**
	 * return form actions by token
	 * @return array
	 */
	protected function FormActions()
	{
		$form = [
		];
		$token = $this->ReadAttributeString("Token");
		$uuid = $this->ReadPropertyString("uuid");
		if ($token != "" && $uuid != "") {
			$form = array_merge_recursive(
				$form,
				[
					[
						'type' => 'Button',
						'caption' => 'On',
						'onClick' => 'Nanoleaf_On($id);'
					],
					[
						'type' => 'Button',
						'caption' => 'Off',
						'onClick' => 'Nanoleaf_Off($id);'
					],
					[
						'type' => 'Button',
						'caption' => 'Get Nanoleaf info',
						'onClick' => 'Nanoleaf_GetInfo($id);'
					],
					[
						'type' => 'Button',
						'caption' => 'Update Effects',
						'onClick' => 'Nanoleaf_UpdateEffectProfile($id);'
					]
				]
			);
		}
		return $form;
	}

	/**
	 * return from status
	 * @return array
	 */
	protected function FormStatus()
	{
		$form = [
			[
				'code' => 101,
				'icon' => 'inactive',
				'caption' => 'Creating instance.'
			],
			[
				'code' => 102,
				'icon' => 'active',
				'caption' => 'instance created.'
			],
			[
				'code' => 104,
				'icon' => 'inactive',
				'caption' => 'interface closed.'
			],
			[
				'code' => 201,
				'icon' => 'inactive',
				'caption' => 'Please follow the instructions.'
			],
			[
				'code' => 202,
				'icon' => 'error',
				'caption' => 'special errorcode.'
			]
		];

		return $form;
	}
}

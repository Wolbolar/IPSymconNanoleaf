<?

class NanoleafSplitter extends IPSModule
{

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->RequireParent("{BAB408E0-0A0F-48C3-B14E-9FB2FA81F66A}"); //  I/O
        $this->RegisterPropertyString("Host", "");
        $this->RegisterPropertyInteger("Port", 16021);
        $this->RegisterPropertyString("Token", "");
        $this->RegisterPropertyString("deviceid", "");
        $this->RegisterPropertyString("devicename", "");
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

    }

    public function GetConfigurationForParent()
    {
        //$Config['Host'] = $this->GetHostIP();
        $Config['Port'] = 1900;
        $Config['MulticastIP'] = "239.255.255.250";
        $Config['BindPort'] = 1900;
        $Config['EnableBroadcast'] = false;
        $Config['EnableReuseAddress'] = true;
        $Config['EnableLoopback'] = false;
        return json_encode($Config);
    }

    protected function GetHostIP()
    {
        $ip = exec("sudo ifconfig eth0 | grep 'inet Adresse:' | cut -d: -f2 | awk '{ print $1}'");
        if($ip == "")
        {
            $ipinfo = Sys_GetNetworkInfo ( );
            $ip = $ipinfo[0]['IP'];
        }
        return $ip;
    }

    // Data an Child weitergeben
    // Type String, Declaration can be used when PHP 7 is available
    //public function ReceiveData(string $JSONString)
    public function ReceiveData($JSONString)
    {
        // Empfangene Daten vom IFTTT I/O
        $data = json_decode($JSONString);

        // Hier werden die Daten verarbeitet
        $this->Discover($data);
        return;
    }

    protected function Discover($data)
    {
        $dataio = $data->Buffer;

        // check for NOTIFY
        $foundnotify = stristr($dataio, 'NOTIFY');

        if($foundnotify)
        {
            //$this->SendDebug("Nanoleaf Splitter:", "NOTIFY found ".json_encode($dataio),0);
            //$data_nanoleaf = explode("<CR><LF>", $dataio);
            $data_nanoleaf = explode("\r\n", $dataio);
            $nanoleaf_device = array_search('NT: nanoleaf_aurora:light', $data_nanoleaf);
            if ($nanoleaf_device)
            {
                $this->SendDebug("Nanoleaf Splitter:", "NOTIFY Nanoleaf found ".json_encode($dataio),0);


                $data_nanoleaf_debug = json_encode($data_nanoleaf);
                $this->SendDebug("Nanoleaf Splitter:", "Nanoleaf data ".$data_nanoleaf_debug,0);
                $ip = "";
                $port = "";
                $nl_devicename = "";
                $nl_deviceid = "";
                foreach($data_nanoleaf as $info)
                {
                    $host = stristr($info, "Location: ");

                    if ($host)
                    {
                        $host = substr($info, 17);
                        $host = explode(":", $host);
                        $ip = $host[0];
                        $port = $host[1];
                    }
                    $nl_deviceid_key = stristr($info, "nl-deviceid: ");
                    if ($nl_deviceid_key)
                    {
                        $nl_deviceid = substr($info, 13);
                    }
                    $nl_devicename_key = stristr($info, "nl-devicename: ");
                    if ($nl_devicename_key)
                    {
                        $nl_devicename = substr($info, 15);
                    }
                }
                $this->SetValuesNanoleaf($ip, $port, $nl_devicename, $nl_deviceid);
            }
        }
    }

    protected function SetValuesNanoleaf($ip, $port, $nl_devicename, $nl_deviceid)
    {
        $this->SendDebug("Nanoleaf Splitter:", "Nanoleaf IP: ".$ip,0);
        $this->SendDebug("Nanoleaf Splitter:", "Nanoleaf Port: ".$port,0);
        $this->SendDebug("Nanoleaf Splitter:", "nanoleaf device name: ".$nl_devicename,0);
        $this->SendDebug("Nanoleaf Splitter:", "Nanoleaf device id: ".$nl_deviceid,0);
        $host = $this->ReadPropertyString("Host");
        if($host == "")
        {
            IPS_SetProperty($this->InstanceID, "Host", $ip); // IP setzen
            IPS_SetProperty($this->InstanceID, "Port", $port); // Port setzen
            IPS_SetProperty($this->InstanceID, "devicename", $nl_devicename); // devicename setzen
            IPS_SetProperty($this->InstanceID, "deviceid", $nl_deviceid); // device setzen
            IPS_ApplyChanges($this->InstanceID); // Neue Konfiguration übernehmen
            $this->SendDebug("Nanoleaf Splitter:", "Found Info for Nanoleaf, Host: ".$ip." Port: ".$port." Device Name: ".$nl_devicename." Device ID:".$nl_deviceid,0);
            IPS_ApplyChanges($this->InstanceID); // Neue Konfiguration übernehmen
            // Status Aktiv
            $this->SetStatus(102);
        }
        else
        {
            // check for Nanoleaf info update
            $nanoleaf_deviceid = $this->ReadPropertyString("deviceid");
            $nanoleaf_devicename = $this->ReadPropertyString("devicename");
            $nanoleaf_host = $this->ReadPropertyString("Host");
            $nanoleaf_port = $this->ReadPropertyString("Port");
            if($nl_deviceid == $nanoleaf_deviceid)
            {
                $change_host = false;
                $change_port = false;
                $change_devicename = false;
                $change_deviceid = false;
                if (!$ip == $nanoleaf_host)
                {
                    IPS_SetProperty($this->InstanceID, "Host", $ip); // IP setzen
                    $this->SendDebug("Nanoleaf Splitter:", "Host changed to IP ".$ip,0);
                    $change_host = true;
                }
                if (!$port == $nanoleaf_port)
                {
                    IPS_SetProperty($this->InstanceID, "Port", $port); // Port setzten
                    $this->SendDebug("Nanoleaf Splitter:", "Port changed to ".$port,0);
                    $change_port = true;
                }
                if (!$nl_devicename == $nanoleaf_devicename)
                {
                    IPS_SetProperty($this->InstanceID, "devicename", $nl_devicename); // devicename setzten
                    $this->SendDebug("Nanoleaf Splitter:", "device name changed to ".$nl_devicename,0);
                    $change_devicename = true;
                }
                if (!$nl_deviceid == $nanoleaf_deviceid)
                {
                    IPS_SetProperty($this->InstanceID, "deviceid", $nl_deviceid); // deviceid setzten
                    $this->SendDebug("Nanoleaf Splitter:", "device id changed to ".$nl_deviceid,0);
                    $change_deviceid = true;
                }
                if ($change_host == true || $change_port == true || $change_devicename == true || $change_deviceid == true)
                {
                    IPS_ApplyChanges($this->InstanceID); // Neue Konfiguration übernehmen
                }
            }
        }
    }




    public function MSearch()
    {
        $data  = "M-SEARCH * HTTP/1.1\r\n";
        $data .= "HOST: " . $this->GetHostIP() . "\r\n";
        $data .= "MAN: \"ssdp:discover\"\r\n";
        $data .= "MX: 2\r\n";
        // $data .= "ST: ssdp:all\r\n";
        $data .= "ST: nanoleaf_aurora:light\r\n";
        $data .= "\r\n";

        // Weiterleiten zur I/O Instanz
        $result = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $data))); // TX GUI
        return $result;

    }

    // Type String, Declaration can be used when PHP 7 is available
    //public function ForwardData(string $JSONString)
    public function ForwardData($JSONString)
    {

        // Empfangene Daten von der Device Instanz
        $data = json_decode($JSONString);
        $datasend = $data->Buffer;
        $this->SendDebug("Splitter Forward Data:",json_encode($datasend),0);

        $command = $datasend->command;
        $this->SendDebug("Command:", $command,0);
        if(isset($datasend->commandvalue))
        {
            $commandvalue = $datasend->commandvalue;
            $this->SendDebug("Command Value:",$commandvalue,0);
        }
        else
        {
            $commandvalue = NULL;
        }
        $result = $this->SendCommand($command, $commandvalue);
        return $result;
    }

    protected function SendToNanoleafInstance($payload)
    {
        // Weiterleitung zu allen Gerät-/Device-Instanzen
        $this->SendDataToChildren(json_encode(Array("DataID" => "{C5798059-6764-1A29-F581-926589C303D2}", "Buffer" => $payload))); // Splitter Interface GUI
    }

    public function Authorization()
    {
        /* A user is authorized to access the OpenAPI if they can demonstrate physical access of the Aurora. This is achieved by:
        1. Holding the on-off button down for 5-7 seconds until the LED starts flashing in a pattern
        2. Sending a POST request to the authorization endpoint */
        $host = $this->ReadPropertyString("Host");
        $port = $this->ReadPropertyInteger("Port");
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
        $token = json_decode($token_response)->auth_token;
        $this->SendDebug("Splitter Received Token:",$token,0);
        IPS_SetProperty($this->InstanceID, 'Token', $token);
        @IPS_ApplyChanges($this->InstanceID);
        return $token;
    }

    protected function SendCommand($command, $commandvalue)
    {
        $host = $this->ReadPropertyString("Host");
        $token = $this->ReadPropertyString("Token");
        if ($token == "")
        {
            return false;
        }
        $port = $this->ReadPropertyInteger("Port");
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
        return $result;
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
        $host = $this->ReadPropertyString("Host");
        if($host == "")
        {
            $form = '"elements":
[
{ "type": "Label", "label": "Nanoleaf Splitter" },
';
        }
        else
        {
            $form = '"elements":
[
{ "type": "Label", "label": "Nanoleaf Splitter" },
{ "name": "Host",                 "type": "ValidationTextBox", "caption": "IP-Address/Host" },
{ "type": "NumberSpinner", "name": "Port", "caption": "Port" },
{ "name": "Token",                "type": "ValidationTextBox", "caption": "Token" },
{ "name": "deviceid",            "type": "ValidationTextBox", "caption": "Device ID" },
{ "name": "devicename",          "type": "ValidationTextBox", "caption": "Device Name" },
';
        }


        return $form;
    }

    protected function FormActions()
    {
        // { "type": "Button", "label": "Get Device Info",  "onClick": "Nanoleaf_MSearch($id);" },
        $host = $this->ReadPropertyString("Host");
        if ($host == "")
        {
            $form = '"actions":
[
{ "type": "Label", "label": "1. Turn on the Nanoleaf and then press the button Search Nanoleaf below. The Nanoleaf instance will get configuration from the Nanoleaf." },
{ "type": "Label", "label": "2. Press the button below Get Token" },
{ "type": "Button", "label": "Search Nanoleaf",  "onClick": "NanoleafS_MSearch($id);" }
{ "type": "Label", "label": "3. Wait for 15 seconds, if the process is finished you can open the instance once again and can see the IP adress, deviceid und device name." }
],';
        }
        else
        {
            $form = '"actions":
[
{ "type": "Label", "label": "1. Hold the on-off button down for 5-7 seconds until the LED starts flashing in a pattern" },
{ "type": "Label", "label": "2. Press the button below Get Token" },
{ "type": "Button", "label": "Get Token",  "onClick": "NanoleafS_Authorization($id);" }
],';
        }

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

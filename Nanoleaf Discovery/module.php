<?
declare(strict_types=1);

class NanoleafDiscovery extends IPSModule
{

	public function Create()
	{
		//Never delete this line!
		parent::Create();
		$this->RegisterAttributeString("devices", "[]");
		$this->RegisterPropertyInteger("ImportCategoryID", 0);
		$this->RegisterPropertyBoolean("NanoleafScript", false);

		//we will wait until the kernel is ready
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		$this->RegisterTimer('Discovery', 0, 'NanoleafDiscovery_Discover($_IPS[\'TARGET\']);');
	}

	/**
	 * Interne Funktion des SDK.
	 */
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

		if (IPS_GetKernelRunlevel() !== KR_READY) {
			return;
		}

		$devices = $this->DiscoverDevices();
		if(!empty($devices))
		{
			$this->WriteAttributeString("devices", json_encode($devices));
		}
		$this->SetTimerInterval('Discovery', 300000);

		// Status Error Kategorie zum Import auswählen
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
			case IPS_KERNELSTARTED:
				$devices = $this->DiscoverDevices();
				if(!empty($devices))
				{
					$this->WriteAttributeString("devices", json_encode($devices));
				}
				break;

			default:
				break;
		}
	}

	public function SetupNanoleaf()
	{
		$devices = $this->GetDevices();
		$NanoleafScript = $this->ReadPropertyBoolean('NanoleafScript');
		//Skripte installieren
		if ($NanoleafScript == true) {
			foreach($devices as $device)
			{
				$DeviceCategoryID = $this->CreateNanoleafCategory($device);
				$this->SendDebug("Nanoleaf", "Setup Scripts", 0);
				$this->SetNanoleafInstanceScripts($DeviceCategoryID);
			}
		}
	}

	protected function CreateNanoleafCategory($device)
	{
		$deviceid = $device["nl-deviceid"];
		$host = $device["host"];
		$ident = str_replace('.', '_', $host); // Replaces all . with underline.
		$CategoryID = $this->CreateNanoleafScriptCategory();
		//Prüfen ob Kategorie schon existiert
		$HubCategoryID = @IPS_GetObjectIDByIdent("CatNanoleafDevice_" . $ident, $CategoryID);
		if ($HubCategoryID === false) {
			$HubCategoryID = IPS_CreateCategory();
			IPS_SetName($HubCategoryID,  $deviceid . " (" . $host . ")");
			IPS_SetIdent($HubCategoryID, "CatNanoleafDevice_" . $ident); // Ident muss eindeutig sein
			IPS_SetInfo($HubCategoryID, $host);
			IPS_SetParent($HubCategoryID, $CategoryID);
		}
		$this->SendDebug("Nanoleaf Skript Category", strval($HubCategoryID), 0);
		return $HubCategoryID;
	}

	protected function CreateNanoleafScriptCategory()
	{
		$CategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		//Prüfen ob Kategorie schon existiert
		$NanoleafScriptCategoryID = @IPS_GetObjectIDByIdent("CatNanoleafScripts", $CategoryID);
		if ($NanoleafScriptCategoryID === false) {
			$NanoleafScriptCategoryID = IPS_CreateCategory();
			IPS_SetName($NanoleafScriptCategoryID, $this->Translate("Nanoleaf Scripts"));
			IPS_SetIdent($NanoleafScriptCategoryID, "CatNanoleafScripts");
			IPS_SetInfo($NanoleafScriptCategoryID, $this->Translate("Nanoleaf Scripts"));
			IPS_SetParent($NanoleafScriptCategoryID, $CategoryID);
		}
		$this->SendDebug("Nanoleaf Script Category", strval($NanoleafScriptCategoryID), 0);
		return $NanoleafScriptCategoryID;
	}

	protected function GetCurrentNanoleafDevices()
	{
		$NanoleafInstanceIDList = IPS_GetInstanceListByModuleID('{09AEFA0B-1494-CB8B-A7C0-1982D0D99C7E}'); // Nanoleaf Devices
		$NanoleafInstanceList = [];
		foreach($NanoleafInstanceIDList as $key => $NanoleafInstanceID)
		{
			$deviceid = IPS_GetProperty($NanoleafInstanceID, "deviceid");
			$ident =  str_replace(':', '_', $deviceid); // Replaces all : with underline.
			$ident = str_replace(' ', '_', $ident); // Replaces all space with underline.
			$name = IPS_GetName($NanoleafInstanceID);
			$NanoleafInstanceList[$ident] = ["objid" => $NanoleafInstanceID, "ident" => $ident, "deviceid" => $deviceid, "name" => $name];
		}
		return $NanoleafInstanceList;
	}

	protected function SetNanoleafInstanceScripts($DeviceCategoryID)
	{
		$devices = $this->GetCurrentNanoleafDevices(); // Nanoleaf Devices
		if(!empty($devices)) {
			foreach ($devices as $device) {
				$objid = $device["objid"];
				$name = $device["name"];
				$scriptid_on = $this->SetupScriptPowerOn($objid, $name, $DeviceCategoryID);
				$scriptid_off = $this->SetupScriptPowerOff($objid, $name, $DeviceCategoryID);
				$this->SetupScriptPowerToggle($objid, $name, $scriptid_on, $scriptid_off, $DeviceCategoryID);
			}
		}
	}

	protected function CreateSkript($name, $scriptname, $DeviceCategoryID)
	{
		$command_ident = "Nanoleaf_Device_" . $this->CreateIdent($name) . "_Command_" . $this->CreateIdent($scriptname);
		$ScriptID = @IPS_GetObjectIDByIdent($command_ident, $DeviceCategoryID);
		if ($ScriptID === false) {
			$ScriptID = IPS_CreateScript(0);
			IPS_SetName($ScriptID, $scriptname);
			IPS_SetParent($ScriptID, $DeviceCategoryID);
			IPS_SetIdent($ScriptID, $command_ident);
		}
		return $ScriptID;
	}

	protected function SetupScriptPowerOn($objid, $name, $DeviceCategoryID)
	{
		$ScriptID = $this->CreateSkript($name, "PowerOn", $DeviceCategoryID);
		$content = "<? Nanoleaf_On(" . $objid . ");?>";
		IPS_SetScriptContent($ScriptID, $content);
		return $ScriptID;
	}

	protected function SetupScriptPowerOff($objid, $name, $DeviceCategoryID)
	{
		$ScriptID = $this->CreateSkript($name, "PowerOff", $DeviceCategoryID);
		$content = "<? Nanoleaf_Off(" . $objid . ");?>";
		IPS_SetScriptContent($ScriptID, $content);
		return $ScriptID;
	}

	protected function SetupScriptPowerToggle($objid, $name, $scriptid_on, $scriptid_off, $DeviceCategoryID)
	{
		$ScriptID = $this->CreateSkript($name, "PowerToggle", $DeviceCategoryID);
		$content = "<?".PHP_EOL;
		$content .= "\$status = GetValueBoolean(IPS_GetObjectIDByIdent(\"State\", ".$objid.")); // Status des Geräts auslesen".PHP_EOL;
		$content .= "IPS_LogMessage( \"Nanoleaf:\" , \"NEO Script toggle\" );".PHP_EOL;
		$content .= "if (\$status == false)// Befehl ausführen".PHP_EOL;
		$content .= "		{".PHP_EOL;
		$content .= "		IPS_RunScript(".$scriptid_on.");".PHP_EOL;
		$content .= "	    }".PHP_EOL;
		$content .= "elseif (\$status == true)// Befehl ausführen".PHP_EOL;
		$content .= "		{".PHP_EOL;
		$content .= "	    IPS_RunScript(".$scriptid_off.");".PHP_EOL;
		$content .= "		}".PHP_EOL;
		$content .= "?>";
		IPS_SetScriptContent($ScriptID, $content);
	}

	protected function CreateIdent($str)
	{
		$search = array("ä", "ö", "ü", "ß", "Ä", "Ö",
			"Ü", "&", "é", "á", "ó",
			" :)", " :D", " :-)", " :P",
			" :O", " ;D", " ;)", " ^^",
			" :|", " :-/", ":)", ":D",
			":-)", ":P", ":O", ";D", ";)",
			"^^", ":|", ":-/", "(", ")", "[", "]",
			"<", ">", "!", "\"", "§", "$", "%", "&",
			"/", "(", ")", "=", "?", "`", "´", "*", "'",
			"-", ":", ";", "²", "³", "{", "}",
			"\\", "~", "#", "+", ".", ",",
			"=", ":", "=)");
		$replace = array("ae", "oe", "ue", "ss", "Ae", "Oe",
			"Ue", "und", "e", "a", "o", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "",
			"", "", "", "", "", "", "", "", "", "");

		$str = str_replace($search, $replace, $str);
		$str = str_replace(' ', '_', $str); // Replaces all spaces with underline.
		$how = '_';
		//$str = strtolower(preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str));
		$str = preg_replace("/[^a-zA-Z0-9]+/", trim($how), $str);
		return $str;
	}

	private function SetLocation($devicename, $hubip)
	{
		$category = $this->ReadPropertyInteger("ImportCategoryID");
		$tree_position[] = IPS_GetName($category);
		$parent = IPS_GetObject($category)['ParentID'];
		$tree_position[] = IPS_GetName($parent);
		do {
			$parent = IPS_GetObject($parent)['ParentID'];
			$tree_position[] = IPS_GetName($parent);
		} while ($parent > 0);
		// delete last key
		end($tree_position);
		$lastkey = key($tree_position);
		unset($tree_position[$lastkey]);
		// reverse array
		$tree_position = array_reverse($tree_position);
		array_push($tree_position, $this->Translate('Nanoleaf devices'));
		array_push($tree_position, $devicename . " (" . $hubip . ")");
		$this->SendDebug('Nanoleaf Location', json_encode($tree_position) , 0);
		return $tree_position;
	}

	/**
	 * Liefert alle Geräte.
	 *
	 * @return array configlist all devices
	 */
	private function Get_ListConfiguration()
	{
		$config_list = [];
		$DeviceIDList = IPS_GetInstanceListByModuleID('{09AEFA0B-1494-CB8B-A7C0-1982D0D99C7E}'); // Nanoleaf device
		$devices = $this->DiscoverDevices();
		$this->SendDebug('Nanoleaf discovered devices', json_encode($devices), 0);
		if (!empty($devices)) {
			foreach ($devices as $device) {
				$instanceID = 0;
				$devicename = $device["nl-devicename"];
				$uuid = $device["uuid"];
				$host = $device["host"];
				$port = $device["port"];
				$device_id = $device["nl-deviceid"];
				foreach ($DeviceIDList as $DeviceID) {
					if ($uuid == IPS_GetProperty($DeviceID, 'uuid')) {
						$devicename = IPS_GetName($DeviceID);
						$this->SendDebug('Broadlink Config', 'device found: ' . utf8_decode($devicename) . ' (' . $DeviceID . ')', 0);
						$instanceID = $DeviceID;
					}
				}

				$config_list[] = [
					"instanceID" => $instanceID,
					"id" => $device_id,
					"name" => $devicename,
					"deviceid" => $device_id,
					"host" => $host,
					"port" => $port,
					"uuid" => $uuid,
					"location" => $this->SetLocation($devicename, $host),
					"create" => [
						'moduleID' => '{09AEFA0B-1494-CB8B-A7C0-1982D0D99C7E}',
						'configuration' => [
							'name' => $devicename,
							'deviceid' => $device_id,
							'host' => $host,
							'port' => $port,
							'uuid' => $uuid
						]
					]
				];
			}
		}
		return $config_list;
	}

	private function DiscoverDevices(): array
	{
		$result = array();

		$devices = $this->mSearch();
		$this->SendDebug("Discover Response:", json_encode($devices), 0);
		foreach ($devices as $device) {

			$obj = array();

			$obj['uuid'] = $device["uuid"];
			$this->SendDebug("uuid:", $obj['uuid'], 0);
			$obj['nl-devicename'] = $device["nl-devicename"];
			$this->SendDebug("name:", $obj['nl-devicename'], 0);
			$obj['nl-deviceid'] = $device["nl-devicename"];;
			$this->SendDebug("device id:", $obj['nl-deviceid'], 0);
			$location = $this->GetNanoleafIP($device);
			$obj['host'] = $location["ip"];
			$this->SendDebug("host:", $obj['host'], 0);
			$obj['port'] = $location["port"];
			$this->SendDebug("port:", $obj['port'], 0);
			array_push($result, $obj);
		}
		return $result;
	}

	protected function mSearch($st = 'nanoleaf_aurora:light', $mx = 2, $man = 'ssdp:discover', $from = null, $port = null, $sockTimout = 7)
	{
		$user_agent = "MacOSX/10.8.2 UPnP/1.1 PHP-UPnP/0.0.1a";
		// BUILD MESSAGE
		$msg = 'M-SEARCH * HTTP/1.1' . "\r\n";
		$msg .= 'HOST: 239.255.255.250:1900' . "\r\n";
		$msg .= 'MAN: "' . $man . '"' . "\r\n";
		$msg .= 'MX: ' . $mx . "\r\n";
		$msg .= 'ST:' . $st . "\r\n";
		$msg .= 'USER-AGENT: ' . $user_agent . "\r\n";
		$msg .= '' . "\r\n";
		// MULTICAST MESSAGE
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if (!$socket) {
			return [];
		}
		socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, true);
		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, true);
		// SET TIMEOUT FOR RECIEVE
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $sockTimout, "usec" => 100000));
		//socket_bind($socket, '0.0.0.0', 0);
		if (@socket_sendto($socket, $msg, strlen($msg), 0, '239.255.255.250', 1900) === false) {
			return [];
		}
		// RECIEVE RESPONSE
		$response = array();
		do {
			$buf = null;
			$bytes = @socket_recvfrom($socket, $buf, 2048, 0, $from, $port);
			if ($bytes === false) {
				break;
			}
			if (!is_null($buf)) {
				$response[] = $this->parseMSearchResponse($buf);
			}
		} while (!is_null($buf));
		// CLOSE SOCKET
		socket_close($socket);
		$nanoleaf_response = [];
		foreach($response as $device)
		{
			if(isset($device["st"]))
			{
				if($device["st"] == "nanoleaf_aurora:light")
				{
					$nanoleaf_response[] = ["uuid" => str_ireplace( 'uuid:', '', $device["usn"] ), "location" => $device["location"], "nl-deviceid" => $device["nl-deviceid"], "nl-devicename" => $device["nl-devicename"]];
				}
			}
		}
		//return $response;
		return $nanoleaf_response;
	}

	protected function parseMSearchResponse( $response )
	{
		$responseArr = explode( "\r\n", $response );
		$parsedResponse = array();
		foreach( $responseArr as $key => $row ) {
			if( stripos( $row, 'http' ) === 0 )
				$parsedResponse['http'] = $row;
			if( stripos( $row, 'cach' ) === 0 )
				$parsedResponse['cache-control'] = str_ireplace( 'cache-control: ', '', $row );
			if( stripos( $row, 'date') === 0 )
				$parsedResponse['date'] = str_ireplace( 'date: ', '', $row );
			if( stripos( $row, 'ext') === 0 )
				$parsedResponse['ext'] = str_ireplace( 'ext: ', '', $row );
			if( stripos( $row, 'loca') === 0 )
				$parsedResponse['location'] = str_ireplace( 'location: ', '', $row );
			if( stripos( $row, 'serv') === 0 )
				$parsedResponse['server'] = str_ireplace( 'server: ', '', $row );
			if( stripos( $row, 'st:') === 0 )
				$parsedResponse['st'] = str_ireplace( 'st: ', '', $row );
			if( stripos( $row, 'usn:') === 0 )
				$parsedResponse['usn'] = str_ireplace( 'usn: ', '', $row );
			if( stripos( $row, 'cont') === 0 )
				$parsedResponse['content-length'] = str_ireplace( 'content-length: ', '', $row );
			if( stripos( $row, 'nt:') === 0 )
				$parsedResponse['nt'] = str_ireplace( 'nt: ', '', $row );
			if( stripos( $row, 'nl-deviceid') === 0 )
				$parsedResponse['nl-deviceid'] = str_ireplace( 'nl-deviceid: ', '', $row );
			if( stripos( $row, 'nl-devicename:') === 0 )
				$parsedResponse['nl-devicename'] = str_ireplace( 'nl-devicename: ', '', $row );
		}
		return $parsedResponse;
	}

	protected function GetNanoleafIP($result)
	{
		$location = $result["location"];
		$location = str_ireplace( 'http://', '', $location );
		$location = explode(":", $location);
		$ip = $location[0];
		$port = $location[1];
		$nanoleaf_info = ["ip" => $ip, "port" => $port];
		return $nanoleaf_info;
	}

	public function GetDevices()
	{
		$devices = $this->ReadAttributeString("devices");
		$this->SendDebug("Nanoleaf Devices", $devices, 0);
		$devices = json_decode($devices, true);
		return $devices;
	}

	public function Discover()
	{
		$this->LogMessage($this->Translate('Background Discovery of Nanoleaf Devices'), KL_NOTIFY);
		$devices = $this->DiscoverDevices();
		if(!empty($devices))
		{
			$this->WriteAttributeString("devices", json_encode($devices));
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
		$Form = json_encode([
			'elements' => $this->FormHead(),
			'actions' => $this->FormActions(),
			'status' => $this->FormStatus()
		]);
		$this->SendDebug('FORM', $Form, 0);
		$this->SendDebug('FORM', json_last_error_msg(), 0);
		return $Form;
	}

	/**
	 * return form configurations on configuration step
	 * @return array
	 */
	protected function FormHead()
	{
		$form = [
			[
				'type' => 'Label',
				'label' => 'category for Nanoleaf devices'
			],
			[
				'name' => 'ImportCategoryID',
				'type' => 'SelectCategory',
				'caption' => 'category Nanoleaf'
			],
			[
				'type' => 'Label',
				'label' => 'create scripts for remote control (alternative or addition for remote control via webfront):'
			],
			[
				'name' => 'NanoleafScript',
				'type' => 'CheckBox',
				'caption' => 'Nanoleaf script'
			],
			[
				'name' => 'NanoleafDiscovery',
				'type' => 'Configurator',
				'rowCount' => 20,
				'add' => false,
				'delete' => true,
				'sort' => [
					'column' => 'name',
					'direction' => 'ascending'
				],
				'columns' => [
					[
						'label' => 'ID',
						'name' => 'id',
						'width' => '200px',
						'visible' => false
					],
					[
						'label' => 'device name',
						'name' => 'name',
						'width' => 'auto'
					],
					[
						'label' => 'device id',
						'name' => 'deviceid',
						'width' => '250px',
						'visible' => true
					],
					[
						'label' => 'IP adress',
						'name' => 'host',
						'width' => '140px'
					],
					[
						'label' => 'port',
						'name' => 'port',
						'width' => '80px'
					],
					[
						'label' => 'uuid',
						'name' => 'uuid',
						'width' => '350px'
					]
				],
				'values' => $this->Get_ListConfiguration()
			]
		];
		return $form;
	}

	/**
	 * return form actions by token
	 * @return array
	 */
	protected function FormActions()
	{
		$form = [
			[
				'type' => 'Label',
				'label' => 'create scripts for remote control (alternative or addition for remote control via webfront):'
			],
			[
				'type' => 'Button',
				'label' => 'Setup Nanoleaf',
				'onClick' => 'NanoleafDiscovery_SetupNanoleaf($id);'
			]
		];
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
				'caption' => 'Nanoleaf Discovery created.'
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
			]
		];

		return $form;
	}
}

<?

########## APCUPSD monitoring module for IP-Symcon 4.x ##########

/**
 * @file 		module.php
 *
 * @author 		Ulrich Bittner
 * @copyright  (c) 2017
 * @version 	1.00
 * @date: 		2017-12-03, 11:30
 * @lastchange	2017-12-03, 11:30
 *
 * @see        https://git.ulrich-bittner.info/ubittner/SymconAPCUPSD.git
 *
 * @guids 		{72600213-3AFC-4205-B514-988F612CA156} library
 *          	{3D46AB77-6A09-4614-876D-E4781EFD5046} module
 *
 * @changelog	2017-12-03, 11:30, initial module script version 1.00
 *
 */


class APCUPSD extends IPSModule
{
	public function Create()
	{
		parent::Create();

		// register properties
		$this->RegisterPropertyString("CategoryID", 0);
		$this->RegisterPropertyString("IPAddress", "");
		$this->RegisterPropertyInteger("UpdateTimer", 300);
		$this->RegisterPropertyString("WebFrontID", 0);
		$this->RegisterPropertyBoolean("UseNotification", false);
		// register timer
		$this->RegisterTimer("UpdateInformation", 0, 'APCUPSD_CheckStatus($_IPS[\'TARGET\']);');
	}

	public function ApplyChanges()
	{
		parent::ApplyChanges();

		$this->CheckConfiguration();

		$status = IPS_GetInstance($this->InstanceID)["InstanceStatus"];
		if ($status == 102) {
			// update timer
			$intervall = ($this->ReadPropertyInteger("UpdateTimer"))*1000;
			$this->SetTimerInterval("UpdateInformation", $intervall);
		}
	}


	#####################################################################################################################################
	## start of modul functions 																												  						  ##
	#####################################################################################################################################

	########## public functions ##########

	public function CheckStatus()
	{
		$result = $this->GetStatus();
		print_r($result);

		/*
		$status = $dataArray["STATUS"] ;
		$nomPower = $dataArray["NOMPOWER"] ;
	 	 */
	}


	########## protected functions ##########

	protected function GetStatus ()
	{
		$ip = $this->ReadPropertyString("IPAddress");
		if ($ip != "") {
			$url = "http://".$ip."/cgi-bin/apcupsd/upsfstats.cgi?host=127.0.0.1";
			$ch = curl_init();
			curl_setopt_array($ch, array(	CURLOPT_URL 				=> $url,
													CURLOPT_HEADER 			=> false,
													CURLOPT_RETURNTRANSFER	=> true));
			$result = curl_exec($ch);
			curl_close($ch);
			$xmlData = new SimpleXMLElement($result);
			$data = preg_split( "(\n)", $xmlData->body->blockquote->pre);
			$dataArray = array();
			foreach ($data as $element) {
				$length = strpos($element, ":");
				$keyName = str_replace(' ', '', substr($element, 0, $length));
				$value = substr(strstr($element, ':'), 1);
				$dataArray[$keyName] = $value;
			}
			return ($dataArray) ? $dataArray: false;
		}
	}


	protected function CheckConfiguration ()
	{
		$webFrontID = $this->ReadPropertyString ("WebFrontID");
		$useNotification = $this->ReadPropertyBoolean ("UseNotification");
		if ($useNotification == true && $webFrontID == 0) {
			$this->SetStatus(201);
		}
		else {
			if ($webFrontID != 0) {
				$instanceInfo = IPS_GetInstance ($webFrontID);
				$moduleName = $instanceInfo["ModuleInfo"]["ModuleName"];
				$moduleType = $instanceInfo["ModuleInfo"]["ModuleType"];
				if ($moduleName = "WebFront Configurator" && $moduleType == 4) {
					$this->SetStatus(102);
				}
				else {
					$this->SetStatus(202);
				}
			}
		}
	}


}
?>

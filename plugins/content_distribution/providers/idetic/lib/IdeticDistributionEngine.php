<?php
/**
 * @package plugins.ideticDistribution
 * @subpackage lib
 */
class IdeticDistributionEngine extends DistributionEngine implements 
	IDistributionEngineUpdate,
	IDistributionEngineSubmit,
	IDistributionEngineReport,
	IDistributionEngineDelete,
	IDistributionEngineCloseUpdate,
	IDistributionEngineCloseSubmit,
	IDistributionEngineCloseDelete
{

	const USAGE_COUNTER_PLAYED = 1;
	const USAGE_COUNTER_EMAILED = 2;
	const USAGE_COUNTER_RATED = 3;
	const USAGE_COUNTER_BLOGGED = 4;
	const USAGE_COUNTER_REVIEWED = 5;
	const USAGE_COUNTER_BOOKMARKED = 6;
	const USAGE_COUNTER_PLAYBACKFAILED = 7;
	const USAGE_COUNTER_TIMESPENT = 8;
	const USAGE_COUNTER_RECOMMENDED = 9;

	
	private $domain = 'jukebox.mobitv.com';
	private $submitPath = '/';
	private $updatePath = '';
	private $deletePath = '/';

	protected $tempXmlPath;
	
	/* (non-PHPdoc)
	 * @see DistributionEngine::configure()
	 */
	public function configure(KSchedularTaskConfig $taskConfig)
	{
		$this->tempXmlPath = sys_get_temp_dir();
	
		if($taskConfig->params->ideticSubmitPath)
			$this->submitPath = $taskConfig->params->ideticSubmitPath;
			
		if($taskConfig->params->ideticUpdatePath)
			$this->updatePath = $taskConfig->params->ideticUpdatePath;
			
		if($taskConfig->params->ideticDeletePath)
			$this->deletePath = $taskConfig->params->ideticDeletePath;
			
		if($taskConfig->params->ideticFetchReportPath)
			$this->fetchReportPath = $taskConfig->params->ideticFetchReportPath;
	}

	/* (non-PHPdoc)
	 * @see IDistributionEngineSubmit::submit()
	 */
	public function submit(KalturaDistributionSubmitJobData $data)
	{
		KalturaLog::debug("idetic: submit");
		if(!$data->distributionProfile || !($data->distributionProfile instanceof KalturaIdeticDistributionProfile))
			KalturaLog::err("Distribution profile must be of type KalturaIdeticDistributionProfile");
	
		if(!$data->providerData || !($data->providerData instanceof KalturaIdeticDistributionJobProviderData))
			KalturaLog::err("Provider data must be of type KalturaIdeticDistributionJobProviderData");
		
		$data->remoteId = $this->handleSend($this->submitPath, $data, $data->distributionProfile, $data->providerData);
		
		return true;
	}

	/**
	 * @param string $path
	 * @param KalturaDistributionJobData $data
	 * @param KalturaIdeticDistributionProfile $distributionProfile
	 * @param KalturaIdeticDistributionJobProviderData $providerData
	 * @throws Exception
	 */
	public function handleSend($path, KalturaDistributionJobData $data, KalturaIdeticDistributionProfile $distributionProfile, KalturaIdeticDistributionJobProviderData $providerData)
	{
		$domain = $distributionProfile->domain;
		$username = $distributionProfile->username;
		$password = $distributionProfile->password;
		
		KalturaLog::debug("idetic: send");
		if(!$providerData->xml)
			throw new Exception("XML data not supplied");

		if (!isset($data->remoteId) || $data->remoteId == "")
		{
			$remoteId = uniqid();
		}
		else
		{
			$remoteId = $data->remoteId;
		}
		$fileName = $remoteId . '.xml';
		$srcFile = $this->tempXmlPath . '/' . $fileName;
		$destFile = "{$path}/{$fileName}";
			
		file_put_contents($srcFile, $providerData->xml);
		KalturaLog::debug("XML written to file [$srcFile]");
		
		$fileTransferMgr = kFileTransferMgr::getInstance(kFileTransferMgrType::FTP);
		if(!$fileTransferMgr)
			throw new Exception("FTP manager not loaded");
			
		$fileTransferMgr->login($this->domain, $username, $password);
		$fileTransferMgr->putFile($destFile, $srcFile, true);

		return $remoteId;
//		return $results;
	}

	/* (non-PHPdoc)
	 * @see IDistributionEngineCloseSubmit::closeSubmit()
	 */
	public function closeSubmit(KalturaDistributionSubmitJobData $data)
	{
		$publishState = $this->fetchStatus($data);
		switch($publishState)
		{
			case 'Published':
				return true;
				
			case 'Pending':
				return false;
				
			default:
				KalturaLog::err("Unknown publishState [$publishState]");
				throw new Exception("IDETIC error: $publishState");
				return false;
		}
	}

	/**
	 * @param KalturaDistributionSubmitJobData $data
	 * @return string status
	 */
	public function fetchStatus(KalturaDistributionSubmitJobData $data)
	{
		if(!$data->distributionProfile || !($data->distributionProfile instanceof KalturaIdeticDistributionProfile))
			KalturaLog::err("Distribution profile must be of type KalturaIdeticDistributionProfile");
	
		$fileArray = $this->fetchFilesList($data, $data->distributionProfile);
		
		for	($i=0; $i<count($fileArray); $i++)
		{
			if (preg_match ( "/{$data->remoteId}.rcvd/" , $fileArray[$i] , $matches))
			{
				return "Published";
			}
			else if (preg_match ( "/{$data->remoteId}.*.err/" , $fileArray[$i] , $matches))
			{
				$res = preg_split ("/\./", $matches[0]);
				return $res[1];			
			}
		}
				
		return "Pending";
	}

	/**
	 * @param KalturaDistributionSubmitJobData $data
	 * @throws Exception
	 * @return DOMDocument
	 */
	public function fetchFilesList(KalturaDistributionSubmitJobData $data, KalturaIdeticDistributionProfile $distributionProfile)
	{
		$domain = $distributionProfile->domain;
		$username = $distributionProfile->username;
		$password = $distributionProfile->password;
		
		KalturaLog::debug("Listing content for [$path]");
		
		$fileTransferMgr = kFileTransferMgr::getInstance(kFileTransferMgrType::FTP);
		if(!$fileTransferMgr)
			throw new Exception("FTP manager not loaded");
			
		$fileTransferMgr->login($this->domain, $username, $password);
		return $fileTransferMgr->listDir($path);
	}

	/* (non-PHPdoc)
	 * @see IDistributionEngineDelete::delete()
	 */
	public function delete(KalturaDistributionDeleteJobData $data)
	{
		if(!$data->distributionProfile || !($data->distributionProfile instanceof KalturaIdeticDistributionProfile))
			KalturaLog::err("Distribution profile must be of type KalturaIdeticDistributionProfile");
	
		if(!$data->providerData || !($data->providerData instanceof KalturaIdeticDistributionJobProviderData))
			KalturaLog::err("Provider data must be of type KalturaIdeticDistributionJobProviderData");
		
		$this->handleSend($this->deletePath, $data, $data->distributionProfile, $data->providerData);
		
		return false;
	}

	/* (non-PHPdoc)
	 * @see IDistributionEngineCloseDelete::closeDelete()
	 */
	public function closeDelete(KalturaDistributionDeleteJobData $data)
	{
		$publishState = $this->fetchStatus($data);
		switch($publishState)
		{
			case 'Published':
				return true;
				
			case 'Pending':
				return false;
				
			default:
				KalturaLog::err("Unknown publishState [$publishState]");
				throw new Exception("IDETIC error: $publishState");
				return false;
		}
	}

	/* (non-PHPdoc)
	 * @see IDistributionEngineCloseUpdate::closeUpdate()
	 */
	public function closeUpdate(KalturaDistributionUpdateJobData $data)
	{
		$publishState = $this->fetchStatus($data);
		switch($publishState)
		{
			case 'Published':
				return true;
				
			case 'Pending':
				return false;
				
			default:
				KalturaLog::err("Unknown publishState [$publishState]");
				throw new Exception("IDETIC error: $publishState");
				return false;
		}
	}

	/* (non-PHPdoc)
	 * @see IDistributionEngineReport::fetchReport()
	 */
	public function fetchReport(KalturaDistributionFetchReportJobData $data)
	{
/*		if(!$data->distributionProfile || !($data->distributionProfile instanceof KalturaIdeticDistributionProfile))
			KalturaLog::err("Distribution profile must be of type KalturaIdeticDistributionProfile");
	
		$xml = $this->fetchXML($data, $data->distributionProfile);
			
		$usageNodes = $xml->documentElement->getElementsByTagName('usageItem');
		if(!$usageNodes->length)
			throw new Exception('usageItem node not found in XML');
			
		foreach($usageNodes as $usageNode)
		{
			$typeAttr = $usageNode->attributes->getNamedItem('counterType');
			$usageAttr = $usageNode->attributes->getNamedItem('totalCount');
			if(!$typeAttr || !$usageAttr)
				continue;
				
			switch($typeAttr->value)
			{
				case self::USAGE_COUNTER_PLAYED:
					$data->plays = $usageAttr->value;
					break;
					
				case self::USAGE_COUNTER_EMAILED:
					$data->providerData->emailed = $usageAttr->value;
					break;
					
				case self::USAGE_COUNTER_RATED:
					$data->providerData->rated = $usageAttr->value;
					break;
					
				case self::USAGE_COUNTER_BLOGGED:
					$data->providerData->blogged = $usageAttr->value;
					break;
					
				case self::USAGE_COUNTER_REVIEWED:
					$data->providerData->reviewed = $usageAttr->value;
					break;
					
				case self::USAGE_COUNTER_BOOKMARKED:
					$data->providerData->bookmarked = $usageAttr->value;
					break;
					
				case self::USAGE_COUNTER_PLAYBACKFAILED:
					$data->providerData->playbackFailed = $usageAttr->value;
					break;
					
				case self::USAGE_COUNTER_TIMESPENT:
					$data->providerData->timeSpent = $usageAttr->value;
					break;
					
				case self::USAGE_COUNTER_RECOMMENDED:
					$data->providerData->recommended = $usageAttr->value;
					break;
					
				default:
					KalturaLog::err("Unknown counterType [{$typeAttr->value}]");
					break;
			}
		}
	*?			
		return true;
	*/}

	/* (non-PHPdoc)
	 * @see IDistributionEngineUpdate::update()
	 */
	public function update(KalturaDistributionUpdateJobData $data)
	{
		KalturaLog::debug("idetic: hooray update " . $data->providerData->xml);
		if(!$data->distributionProfile || !($data->distributionProfile instanceof KalturaIdeticDistributionProfile))
			KalturaLog::err("Distribution profile must be of type KalturaIdeticDistributionProfile");
	
		if(!$data->providerData || !($data->providerData instanceof KalturaIdeticDistributionJobProviderData))
			KalturaLog::err("Provider data must be of type KalturaIdeticDistributionJobProviderData");
		
		$this->handleSend($this->updatePath, $data, $data->distributionProfile, $data->providerData);
		
		return false;
	}

}
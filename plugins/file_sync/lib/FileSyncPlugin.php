<?php
class FileSyncPlugin implements IKalturaPermissionsPlugin, IKalturaServicesPlugin
{
	const PLUGIN_NAME = 'fileSync';
	
	public static function getPluginName()
	{
		return self::PLUGIN_NAME;
	}
	
	public function getInstances($intrface)
	{
		if($this instanceof $intrface)
			return array($this);
			
		return array();
	}
	
	public static function getServicesMap()
	{
		$map = array(
			'fileSync' => 'FileSyncService'
		);
		return $map;
	}
	
	public static function getServiceConfig()
	{
		return realpath(dirname(__FILE__).'/../config/file_sync.ct');
	}

	public static function isAllowedPartner($partnerId)
	{
		if($partnerId == Partner::ADMIN_CONSOLE_PARTNER_ID || $partnerId == Partner::BATCH_PARTNER_ID)
			return true;
		
		return false;
	}
}
?>

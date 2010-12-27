<?php
class ContentDistributionSphinxPlugin extends KalturaPlugin implements IKalturaCriteriaFactory
{
	const PLUGIN_NAME = 'contentDistributionSphinx';
	
	public static function getPluginName()
	{
		return self::PLUGIN_NAME;
	}
	
	/**
	 * Creates a new KalturaCriteria for the given object name
	 * 
	 * @param string $objectType object type to create Criteria for.
	 * @return KalturaCriteria derived object
	 */
	public static function getKalturaCriteria($objectType)
	{
		if ($objectType == "EntryDistribution")
			return new SphinxEntryDistributionCriteria();
			
		return null;
	}
}

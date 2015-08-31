<?php

class KalturaCaptureSpaceUpdateResponse extends KalturaObject {
    /**
     * @var string
     */
    public $version;

    /**
     * @var string
     */
    public $md5;

    /**
     * @var string
     */
    public $download_url;

    /**
     * @var string
     */
    public $os;

    private static $map_between_objects = array (
        "version",
        "md5",
        "download_url",
        "os",
    );

    public function getMapBetweenObjects ( )
    {
        return array_merge ( parent::getMapBetweenObjects() , self::$map_between_objects );
    }
}

<?php
/**
 *
 * @service collaajUpdate
 * @package plugins.collaajUpdate
 * @subpackage api.services
 */
class CollaajUpdateService extends KalturaBaseService
{
    /**
     *  Return if there is a newer update according to the givwn OS and version
     *
     * @action clientUpdates
     * @param string $os
     * @param string $version
     * @return KalturaCaptureSpaceUpdateResponse
     * @throws exception
     */
    function clientUpdatesAction ($os, $version)
    {
        $collaajini = new collaajini();
        $returned_object = new KalturaCaptureSpaceUpdateResponse();
        $collaajini->returnUpdateFileUrl($os, $version);
//        $filtered_results = $collaajini->returnUpdateFileUrl($os, $version);
        $returned_object->fromObject($collaajini);
        if ($returned_object) {
            return $returned_object;
        }
        else throw new KalturaAPIException ("No update is available.");
    }

    /**
     * Collaaj install
     *
     * @action serveInstall
     * @param string $os
     * @param string $version
     * @return KalturaCaptureSpaceUpdateResponse
     * @throws exception
     */
    public function serveInstallAction($os, $version)
    {
        /* @var $fileSync FileSync */
        $collaajini = new collaajini();
        $returned_object = new KalturaCaptureSpaceUpdateResponse();
        $collaajini->returnLatestVersionUrl($os, $version);
        if ($collaajini->getVersion()) {
            $returned_object->fromObject($collaajini);

            $filePath = $collaajini->getDownload_url();  // filePath contains the full file name
            if (is_readable($filePath)) {
                $fileName = array_pop(explode('/', $filePath));    // Extracting only the file name
                $mimeType = kFile::mimeType($filePath);
                header("Content-Disposition: attachment; filename=\"$fileName\"");
                return $this->dumpFile($filePath, $mimeType);
            } else throw new KalturaAPIException ("There was a problem reading $filePath\n");
        } else throw new KalturaAPIException ("There seem to be no available versions for ".$os);
    }

    /**
     * Collaaj check for update
     *
     * @action serveUpdate
     * @param string $os
     * @param string $version
     * @return KalturaCaptureSpaceUpdateResponse
     * @throws exception
     */
//    public function clientUpdatesAction($os, $version)
    public function serveUpdateAction($os, $version)
    {
        $collaajini = new collaajini();
        $filtered_results = $collaajini->returnUpdateFileUrl($os, $version);
        if ($filtered_results) {
            $filePath = $filtered_results["download_url"];  // filePath contains the full file name
            if (is_readable($filePath)) {
                $fileName = array_pop(explode('/', $filePath));    // Extracting only the file name
                $mimeType = kFile::mimeType($filePath);
                header("Content-Disposition: attachment; filename=\"$fileName\"");
                return $this->dumpFile($filePath, $mimeType);
            } else throw new KalturaAPIException ("There was a problem reading $filePath\n");
        } else throw new KalturaAPIException ("There seem to be no available versions for ".$os);
    }
}



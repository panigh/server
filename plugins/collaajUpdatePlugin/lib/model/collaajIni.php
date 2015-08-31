<?php

/**
 * @package plugins.CollaajUpdate
 * @subpackage api.objects
 */
class collaajini {

	const INI_FILE_NAME 			        = "/opt/kaltura/app/plugins/collaajUpdatePlugin/collaajClientUpdate.ini";
	const INI_STRUCTURE 			        = "download_url,md5";
	const DOWNLOAD_PREFIX 			        = "/tmp/updates/";
	const AVAILABLE_OS_TYPES 		        = "win,mac";
	const AVAILABLE_FORMAT_TYPES 		    = "win,mac,zip";
	const VERSION_DELIMITER 		        = '_';
    const INPUT_VERSION_DELIMITER	        = '.';
	const UPDATE_KEY 				        = "version";
	const DOWNLOAD_URL 				        = "download_url";
	const MD5 						        = "md5";
	const VALIDATION_LIST 			        = "os,version,download_url";
	const VERSION_PATTEREN_COUNT 	        = 3;

	// private members  ///////////////////////////////////////////////////////////////////////////////////////
	private $ini_content = array();
	private $ini_file_name = "";

	private $returned_data = array (
		"os" => "",
		"version" => "",
		"download_url" => "",
		"md5" => "",
	);

	// Constructor
	public function __construct() {
		$this->ini_file_name = self::INI_FILE_NAME;
		if (is_readable ( $this->ini_file_name )) {
			$temp_content = $this->readIniFile();
			$this->loadIniFile($temp_content);
		} else {
			throw new Exception ("Could not read ".$this->ini_file_name);
		}
	}

	// Functions //////////////////////////////////////////////////////////////////////////////////////////
	private function readIniFile() {
		$temp_content = array();
		if ($this->ini_file_name != '') {
			return parse_ini_file($this->ini_file_name, TRUE);
		}
	}


	private function loadIniFile($content) {
		$split_keys = explode(',', self::INI_STRUCTURE);
		ksort ($content);
		foreach ($content as $key => $value) {
			$this->ini_content[$key] = array();
			foreach ($value as $os => $data) {
				$temp_split = explode(',', $data);
				$temp_array['os'] = $os;
				$temp_array['version'] = $key;
				// $temp_array['key'] = $key;
				for ($index=0; $index < count($split_keys); $index++) {
					$temp_array[ $split_keys[$index] ] = $temp_split[$index];
				}
				$this->ini_content[$key][$os] = $temp_array;
			}
		}
	}


	// Can be turned out to a filter by key
	public function returnFilteredByOs($needed_os) {
		$temp_array = array();
		foreach ($this->ini_content as $key => $value) {
			if (in_array($needed_os, array_keys( $this->ini_content[$key] ))) {
				$temp_array[$key] = $this->ini_content[$key][$needed_os];
			}
		}
		ksort($temp_array);
		return $temp_array;
	}

	public function listAllVersions() {
		$version_array = array();
		$os_types = explode(',', self::AVAILABLE_FORMAT_TYPES);
		foreach ($os_types as $key => $value) {
			array_push($version_array , $this->returnFilteredByOs($value));
		}
		return $version_array;
	}

//	Returns 0 for a similar version, 1 if the client is newer then the checked version, 2 if not
	private function returnNewerVersion($client_version, $ini_version, $delimiter) {
		if ($client_version and $ini_version and $delimiter) {

			if ($client_version == $ini_version)
				return 0;

			$client_ver_split = explode($delimiter, $client_version);
			$ini_ver_split = explode($delimiter, $ini_version);
			for ($index=0; $index < count($client_ver_split); $index++) {
				$result = $client_ver_split[$index] - $ini_ver_split[$index];
				if ($result != 0) {
					if ( $result > 0) return 1;
					else return 2;
				}
			}
		} else throw new Exception ("Unable to check which version is newer. Verify you have provided all the needed vars: client_version, ini_version, delimiter");
	}

	private function returnUrlLink($string) {
		return self::DOWNLOAD_PREFIX.$string;
	}


//	returns latest version install file
	public function returnLatestVersionUrl($needed_os, $current_version) {
		$os_types = explode(',', self::AVAILABLE_OS_TYPES);
		if (in_array($needed_os, $os_types) ){
			$temp_array = $this->returnFilteredByOs($needed_os);
			$latest_version = array_pop($temp_array);
		}
		$result = $this->ini_content[$latest_version["version"]][$needed_os];
		if ($latest_version) {
			$result[self::DOWNLOAD_URL] = self::DOWNLOAD_PREFIX . $this->ini_content[$latest_version["version"]][$needed_os][self::DOWNLOAD_URL];
			$this->setFoundParams($result);
			return $result[self::DOWNLOAD_URL];
		}
		else return NULL;
	}

	public function setFoundParams($input_array) {
		foreach ($input_array as $key => $value) {
			$this->returned_data[$key] = $value;
		}
//		print_r ($this->returned_data);
	}


//	public function returnLatestVersionUrl ($needed_os, $current_version) {
	public function getLatestVersion ($needed_os, $current_version) {
		if ($needed_os and $current_version) {
			$returned_version = $this->getLatestVersion($needed_os, $current_version);

			if ($returned_version) {
//				print_r ($this->ini_content[$returned_version]);
//				$this->runValidations($returned_version, $needed_os); // run ini data validation before returning
				$temp_url = $this->ini_content[$returned_version][$needed_os][self::DOWNLOAD_URL];
				$returned_data_obj = array("url"=>$this->returnUrlLink($temp_url),self::MD5=>$this->ini_content[$returned_version][$needed_os][self::MD5]);
				return $returned_data_obj;
			}
			else throw new Exception ("No available updates found.");
		}
		else throw new Exception ("check returnLatestVersionUrl's parameters");
	}

    public function returnVersionUpdateInfo($needed_os, $current_version) {
        $os_filtered_results = $this->returnFilteredByOs($needed_os);
        $versions = array_keys($os_filtered_results);
        $temp_version_array = array();
        $current_version_split = explode(self::INPUT_VERSION_DELIMITER, $current_version);
        foreach ($versions as $key => $value) {
            $split_version = explode(self::VERSION_DELIMITER, $value);
            if ($split_version[0] == $current_version_split[0])
                array_push($temp_version_array, $split_version);
        }
		ksort($temp_version_array);
//		print_r($temp_version_array);
		if (count ($temp_version_array)>0 ) {
			$returned_version_info = implode('_', array_pop($temp_version_array));
			return $os_filtered_results[$returned_version_info];
		} else
			return NULL;
    }

	public function returnUpdateFileUrl($needed_os, $current_version) {
        $file_formats = array(
			"win" => "win",
			"mac" => "zip",
		);
		$result = $this->returnVersionUpdateInfo($needed_os, $current_version);
//		print_r ( $this->ini_content[$result["version"]][$file_formats[$needed_os]] );
		if ($result) {
            if ($needed_os == "mac") {
                $result = $this->ini_content[$result["version"]][$file_formats[$needed_os]];
            }
            $result["download_url"] = $this->returnUrlLink($result["download_url"]);
        }
        $this->setFoundParams($result);
        return $result;
	}

//  Validation functions	///////////////////////////////////////////////////////////////////////////////////////
	private function runValidations($input_string, $input_os) {
		$validation_list = explode (',',self::VALIDATION_LIST);
		foreach ($validation_list as $key => $value) {
			$function_to_run = "validate".ucfirst($value);
			call_user_func (array($this,$function_to_run), $input_string, $input_os);
		}
	}


	private function validateOs($input_string, $input_os) {
		print $input_string." ";
		$available_format = explode (',',self::AVAILABLE_FORMAT_TYPES);
		if ( !in_array ($input_os, $available_format)) {
			throw new Exception ($input_string ." is not a valid os.");
		}
	}

	private function validateVersion($input_string, $input_os) {
		$version_split = explode( self::VERSION_DELIMITER, $input_string);
		if ( count($version_split) != self::VERSION_PATTEREN_COUNT ) {
			throw new Exception ($input_string ." is not in a valid version format.");
		}

		foreach ($version_split as $key => $value) {
			if (!is_numeric($value) or ($value < 0)) {
				throw new Exception ($input_string ." is not in a valid version format.");
			}
		}
	}

	private function validateDownload_url($input_string, $input_os) {
		$file_extension = array_pop (explode('.', $this->ini_content[$input_string][$input_os][self::DOWNLOAD_URL]));
		switch ($file_extension) {
			case 'win':
				if ($file_extension != "msi")
					throw new Exception ("File extension (".$file_extension.") does not correspond with the requested OS update (".$input_os.")");
				break;
			case 'mac':
				if ($file_extension != "dmg")
					throw new Exception ("File extension (".$file_extension.") does not correspond with the requested OS update (".$input_os.")");
				break;
		}
	}

// Setters & getters	///////////////////////////////////////////////////////////////////////////////////////
	public function getReturned_data() {
		return $this->returned_data;
	}

	public function getOs() {
		return $this->returned_data["os"];
	}

	public function getVersion() {
		return $this->returned_data["version"];
	}

    public function getMd5() {
        return $this->returned_data["md5"];
    }

    public function getDownload_url() {
		return $this->returned_data["download_url"];
    }

	public function getIni_content() {
		return $this->ini_content;
	}

	public function getIni_file_name() {
		return $this->ini_file_name;
	}
}
<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012-2013  Andreas Cord-Landwehr <cola@uni-paderborn.de>
*  (c) 2013       Florian Rittmeier <florianr@asta.uni-paderborn.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * This class povides a central file access control function set.
 *
 * @author Andreas Cord-Landwehr <cola@uni-paderborn.de>
 * @package TYPO3
 * @subpackage	tx_meetings
 */

require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');
require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_access.php');

/**
 * @class	tx_meetings_file_access
 */
class tx_meetings_file_access {
	private $accessObj;
	private $userPrefix;
	private $publicPrefix;
	private $ipPrefix;
	private $salt;

	/**
	 */
	function init($accessObj) {
		// the user-set seed is used to avoid attacks by generating access-ids for known users
		$this->salt = 1; // FIXME let user set the seed
		$this->accessObj = $accessObj;

		// set prefix for public access
		$this->publicPrefix = "public";
		$hash = hash("sha256", $this->accessObj->ipRangeByGrant());
		$this->ipPrefix = "net" . substr($hash, 0, 8);

		// set prefix for user specific access
		$userIP = t3lib_div::getIndpEnv('REMOTE_ADDR');
		$hash = hash("sha256", $salt . intval($GLOBALS['TSFE']->fe_user->user['uid']) . $userIP);
		$this->userPrefix = substr($hash, 0, 8);

		return true;
	}

	/**
	 * @param $target is relative path of symbolic link target
	 * @param $link is relative path of symbolic link
	 * @return access link if link was created successfully/exists, otherwise empty string
	 */
	private function createAccessLink($target, $link, $accessGrant) {
		// create htaccess file only if folder does not exist, yet
		$createSymlinkFolder = !is_dir(PATH_site . dirname($link));
		$forbiddenErrorUrl = "foo.html"; //FIXME

		// create htaccess
		// FIXME create htaccess
		if ($createSymlinkFolder) {
			// test if folder exists and create it
			mkdir(PATH_site . dirname($link));

			$this->generateHtaccessFile(
				$this->accessObj->ipRangeByGrant(),
				$forbiddenErrorUrl, PATH_site . dirname($link) . "/.htaccess");
		}

		// create symlink
		symlink(PATH_site . $target, PATH_site . $link);

		return $link;
	}

	/**
	 * Returns an accessible link for the given $documentUrl with the correct prefix for the
	 * given accessGrant.
	 */
	public function fileLink($fileUrl, $accessGrant) {
		if ($fileUrl == '') {
			debug("Empty file link, aborting link creation");
			return '';
		}

		// remove url path
		$file = array_pop(explode("/",$fileUrl)); // get actual file name

		switch ($accessGrant) {
		case tx_meetings_access::kACCESS_GRANTED_BY_PUBLIC:
			return $this->createAccessLink(
							tx_meetings_div::uploadFolder.$file,
							tx_meetings_div::documentFolder.$this->publicPrefix.'/'.$file,
							$accessGrant
										);
			break;
		case tx_meetings_access::kACCESS_GRANTED_BY_IP:
			//TODO use group prefix
			return $this->createAccessLink(
							tx_meetings_div::uploadFolder.$file,
							tx_meetings_div::documentFolder.$this->ipPrefix.'/'.$file,
							$accessGrant
										);
			break;
		case tx_meetings_access::kACCESS_GRANTED_BY_GROUP:
			return $this->createAccessLink(
							tx_meetings_div::uploadFolder.$file,
							tx_meetings_div::documentFolder.$this->userPrefix.'/'.$file,
							$accessGrant
										);
			break;
		default:
			break;
		}
	}

	/**
	* Generates a htaccess file according to a given ip access admission
	*
	* \param $iprange ip or ip range given by the access admission
	* \param $errorurl url of the error page to display when access the path from an unauthorized ip
	* \param $filepath filepath of the htaccess file which is to be generated
	*
	* \return true if htaccess file could be written
	* \return false if htaccess file could not be written and directory should be deleted
	**/
	private function generateHtaccessFile($iprange, $errorurl, $filepath) {
		// matches 1.2.3.4
		$regex_singleip = "/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/";

		// matches 1.2.3.4/16
		$regex_iprange_cidr = "/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/";

		// matches 1.2.3.4/255.255.0.0
		$regex_iprange_netmask = "/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/";

		// matches 1.2.*.* but neither *.*.*.* nor 1.2.3.4
		$regex_iprange_star = "/^(\d{1,3})\.(\d{1,3}|\*)\.(\d{1,3}|\*)\.\*$/";

		// start output generation
		$output = "order deny,allow\ndeny from all\n";

		if ($iprange == "0.0.0.0/32") {
			$output .= "allow from all\n";
		}
		elseif(preg_match($regex_singleip, $iprange) === 1) {
			// output single ip
			$output .= "allow from " . $iprange . "\n";
		}
		elseif(preg_match($regex_iprange_cidr, $iprange, $ipparts) === 1) {
			// output ip range in CIDR format
			$output .= "allow from " . $iprange . "\n";
		}
		elseif(preg_match($regex_iprange_netmask, $iprange, $ipparts) === 1) {
			// output ip range with netmask
			$output .= "allow from " . $iprange . "\n";
		}
		elseif(preg_match($regex_iprange_star, $iprange, $ipparts) === 1) {
			// output ip range
			$output .= "allow from ";

			// 123.  = 123.0.0.0/8
			$output .= $ipparts[1] . ".";

			if(strcmp($ipparts[2],"*") != 0) {
				// 123.23.  = 123.23.0.0/16
				$output .= $ipparts[2] . ".";

				if(strcmp($ipparts[3],"*") != 0) {
					// 123.23.3. = 123.23.3.0/24
					$output .= $ipparts[3] . ".";
				}
			}
			$output .= "\n";
		}
		else {
			/* $iprange does not follow the expected format.
			* We should not add an allow line, thus all
			* webrequests to the generated directory will
			* fail.
			*/
			// TODO Log a warning that we found an invalid format
			debug("IP range has invalid format, aborting matching.");
		}

		$output .= "ErrorDocument 403 " . $errorurl . "\n";

		debug("write to : " . $filepath);

		if(file_put_contents($filepath, $output, LOCK_EX) === false) {
			return false;
		}
		else {
			return true;
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_file_access.php'])    {
    require_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_file_access.php']);
}
?>

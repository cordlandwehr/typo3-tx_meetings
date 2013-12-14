<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012-2013 Andreas Cord-Landwehr <cola@uni-paderborn.de>
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
	private $salt;

	/**
	 */
	function init($accessObj) {
		// the user-set seed is used to avoid attacks by generating access-ids for known users
		$this->salt = 1; // FIXME let user set the seed
		$this->accessObj = $accessObj;

		// set prefix for public access
		$this->publicPrefix = "public";

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
	private function createAccessLink($target, $link) {
		// test if folder exists and create it
		mkdir(PATH_site.dirname($link));
		// create symlink
		symlink(PATH_site.$target, PATH_site.$link);

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
							tx_meetings_div::documentFolder.$this->publicPrefix.'/'.$file
										);
			break;
		case tx_meetings_access::kACCESS_GRANTED_BY_IP:
			return $this->createAccessLink(
							tx_meetings_div::uploadFolder.$file,
							tx_meetings_div::documentFolder.$this->userPrefix.'/'.$file
										);
			break;
		case tx_meetings_access::kACCESS_GRANTED_BY_GROUP:
			return $this->createAccessLink(
							tx_meetings_div::uploadFolder.$file,
							tx_meetings_div::documentFolder.$this->userPrefix.'/'.$file
										);
			break;
		default:
			break;
		}

	}

}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_file_access.php'])    {
    require_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_file_access.php']);
}
?>

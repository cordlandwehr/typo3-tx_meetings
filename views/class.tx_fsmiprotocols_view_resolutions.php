<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Cord-Landwehr <phoenixx@upb.de>
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

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('fsmi_protocols').'api/class.tx_fsmiprotocols_div.php');
require_once(t3lib_extMgm::extPath('fsmi_protocols').'api/class.tx_fsmiprotocols_notifier.php');
require_once(t3lib_extMgm::extPath('fsmi_protocols').'views/class.tx_fsmiprotocols_view_base.php');

/**
 * Plugin 'Show protocols' for the 'fsmi_protocols' extension.
 *
 * @author	Andreas Cord-Landwehr <phoenixx@upb.de>
 * @package	TYPO3
 * @subpackage	tx_fsmiprotocols
 */
class tx_fsmiprotocols_view_resolutions extends tx_fsmiprotocols_view_base {

	public $year          = 0;
	private $disclosure    = tx_fsmiprotocols_pi1::kDISCLOSURE_STANDARD;
	public $userNotInIPrestrictedNetwork	= true;

	var $LANG;						// language object
	var $cObj;

	function __construct () {
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->LANG = t3lib_div::makeInstance('language');
		// initialization procedure for language
		$this->LLkey = $GLOBALS['TSFE']->lang;
		$this->LOCAL_LANG_loaded = 0;
		$this->pi_loadLL();
	}


	/**
	 * This function prints a <UL> list of all resolutions
	 * @param uid of committee
	 * @return string
	 */
	function printResolutions ($committee, $accessObj) {
		$content = '';
		$resolutions = array();

		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT tx_fsmiprotocols_resolution.uid as uid, tx_fsmiprotocols_list.meeting_date as meeting_date
											FROM tx_fsmiprotocols_resolution, tx_fsmiprotocols_list
											WHERE
												tx_fsmiprotocols_resolution.deleted=0
												AND tx_fsmiprotocols_resolution.hidden=0
												AND tx_fsmiprotocols_resolution.protocol=tx_fsmiprotocols_list.uid
												AND tx_fsmiprotocols_list.committee='.$committee.'
												AND tx_fsmiprotocols_list.deleted=0
												AND tx_fsmiprotocols_list.hidden=0
											 ORDER BY tx_fsmiprotocols_list.meeting_date DESC,
												tx_fsmiprotocols_resolution.resolution_id DESC,
												tx_fsmiprotocols_resolution.name');

		$resolutionDATA = array();
		while($res && $resolutionDATA = mysql_fetch_assoc($res))
			$resolutions[] = $resolutionDATA;

		// create output
		$content .= '<ul>';
		foreach($resolutions as $uid) {
			if ($accessObj->isAccessAllowedResolutions($uid['meeting_date']))
				$content .= '<li>'.$this->printLinkToResolution($uid['uid']).'</li>';
			else
				$content .= '<li title="'.$this->pi_getLL('access_denied').'"><i>'.$this->printResolutionTitle($uid['uid']).'</i></li>';
		}
		$content .= '</ul>';

		return $content;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_protocols/views/class.tx_fsmiprotocols_view_resolutions.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_protocols/views/class.tx_fsmiprotocols_view_resolutions.php']);
}

?>
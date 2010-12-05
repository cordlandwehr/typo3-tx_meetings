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
require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_div.php');
require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_notifier.php');
require_once(t3lib_extMgm::extPath('meetings').'views/class.tx_meetings_view_base.php');

/**
 * Plugin 'Show protocols' for the 'meetings' extension.
 *
 * @author	Andreas Cord-Landwehr <phoenixx@upb.de>
 * @package	TYPO3
 * @subpackage	tx_meetings
 */
class tx_meetings_view_list extends tx_meetings_view_base {
	protected $Display;
	protected $accessObj;
	public $year          = 0;
	private $disclosure    = tx_meetings_pi1::kDISCLOSURE_STANDARD;

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


	function printProtocols ($protocolUIDs, $accessObj) {
		$this->accessObj = $accessObj;
		$contentProtocolList = '';
		$committeeDATA = t3lib_BEfunc::getRecord('tx_meetings_committee_list', $this->committee);

		$contentProtocolList .= '<div><ul>';

		foreach ($protocolUIDs as $protocol)	{
			$protocolDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $protocol);

			/* print list of all protocols for this year */
			// if there is really a plaintext protocol
			$contentProtocolList .= '
				<li style="list-style-image: url('.tx_meetings_div::imgPath.'/document.png)" '.$this->pi_classParam('listrowField-meeting-date').'>';
			$contentProtocolList .= $this->printLinkToSingleMeeting($protocolDATA['uid']);

			if ($this->accessObj->isAccessAllowedProtocols($protocolDATA['meeting_date']))
				if ($protocolDATA['type']==tx_meetings_div::kPROTOCOL_TYPE_PDF)
					$contentProtocolList .= ' '.$this->printLinkToProtocolPDF($protocolDATA['uid'],true);

			$contentProtocolList .= '</li>';

			// print additional documents for protocols
			if ($this->Display['ShowDocumentsElement']) {
				$documents = $this->getDocumentsForProtocol($protocolDATA['uid']);
				if ($this->accessObj->isAccessAllowedDocuments($protocolDATA['meeting_date']))
					foreach($documents as $documentDATA) {
						if ($this->accessObj->isAccessAllowedDocuments($protocolDATA['meeting_date'],$documentDATA['uid']))
							$contentProtocolList .= '<li style="margin-left:20px; list-style-image: url('.tx_meetings_div::imgPath.'/file_additional.png)">'.
								$this->pi_getLL('documents').': '.$this->printLinkToDocument($documentDATA['uid']).'</li>';
						else
							$contentProtocolList .= '<li style="margin-left:20px; list-style-image: url('.tx_meetings_div::imgPath.'/file_additional.png)">'.
								$documentDATA['name'].'</li> ';
					}
				else
					foreach($documents as $documentDATA)
						$contentProtocolList .= '<li style="margin-left:20px; list-style-image: url('.tx_meetings_div::imgPath.'/file_additional.png)">'.
							$documentDATA['name'].'</li> ';
			}

			// print resolutions
			if ($this->Display['ShowResolutionsElement']) {
				$resolutions = $this->getResolutionsForProtocol($protocolDATA['uid']);
				foreach($resolutions as $resolutionDATA)
					$contentProtocolList .= '<li style="margin-left:20px; list-style-image: url('.tx_meetings_div::imgPath.'/file_additional.png)">'.
						$this->printLinkToResolution($resolutionDATA['uid']).
						'</li>';
			}
		}

		$contentProtocolList .= '</ul></div>';

		return $contentProtocolList;
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_list.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/views/class.tx_meetings_view_list.php']);
}

?>
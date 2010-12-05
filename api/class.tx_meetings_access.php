<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Andreas Cord-Landwehr (cola@uni-paderborn.de)
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
* This class povides a central access admission control function set.
* All views should use this class to determine if the current user
* is allowed to view specific content elements.
*
* @author Andreas Cord-Landwehr <cola@uni-paderborn.de>
*/



require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');


/**
 * Script Class to download files as defined in reports
 *
 */
class tx_meetings_access {
	const kACCESS_LEVEL_PUBLIC			= 0;
	const kACCESS_LEVEL_RESTRICTED		= 1;
	const kACCESS_LEVEL_INTERN			= 2;
	const kACCESS_LEVEL_FORBIDDEN		= 3;

	private $committeeUID;
	private $dataAccessLevels = array();
	private $usersAccessLevels = array();		// the access level of the current user (on different periodes)

	function init($committeeUID) {
		if ($committeeUID==0)
			return false;
		$this->committeeUID=$committeeUID;
		$this->initAccessLevels();
		$this->evaluateUsersAccessLevels();
	}

	/**
	 * Computes the user's access level based in IP, groups and access admission of current committee
	 */
	private function evaluateUsersAccessLevels() {
		//setup User's ID and groups
		$userID = intval($GLOBALS['TSFE']->fe_user->user['uid']);
		$userDATA = t3lib_BEfunc::getRecord("fe_users",$userID);
		$usersGroupIDs = explode(',',$userDATA['usergroup']);

		//setup User's IP
		$userIP = t3lib_div::getIndpEnv('REMOTE_ADDR');

		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_meetings_access_admission
											WHERE
												deleted=0 AND hidden=0
												AND committee='.$this->committeeUID.'
											ORDER BY access_level DESC');

		$admissionDATA = array();
		while($res && $admissionDATA = mysql_fetch_assoc($res)) {
			// check IP
			if ($admissionDATA['ip_range']!='' && t3lib_div::cmpIP( $userIP, $admissionDATA['ip_range'] )) {
				$this->usersAccessLevels[] = array (
					'access_level' => $admissionDATA['access_level'],
					'access_from' => $admissionDATA['access_from'],
					'access_until' => $admissionDATA['access_until']
				);
				continue;	// if IP range ok, no need to check up group
			}

			// check groups
			if (in_array($admissionDATA['usergroup'],$usersGroupIDs)) {
				$this->usersAccessLevels[] = array (
					'access_level' => $admissionDATA['access_level'],
					'access_from' => $admissionDATA['access_from'],
					'access_until' => $admissionDATA['access_until']
				);
				continue;	// if IP range ok, no need to check up group
			}
		}

		return true;
	}

	private function initAccessLevels() {
		$committeeDATA = t3lib_BEfunc::getRecord('tx_meetings_committee_list', $this->committeeUID);
		$this->dataAccessLevels['access_level_agendas'] = $committeeDATA['access_level_agendas'];
		$this->dataAccessLevels['access_level_agendas_preliminary'] = $committeeDATA['access_level_agendas_preliminary'];
		$this->dataAccessLevels['access_level_protocols'] = $committeeDATA['access_level_protocols'];
		$this->dataAccessLevels['access_level_protocols_preliminary'] = $committeeDATA['access_level_protocols_preliminary'];
		$this->dataAccessLevels['access_level_documents'] = $committeeDATA['access_level_documents'];
		$this->dataAccessLevels['access_level_resolutions'] = $committeeDATA['access_level_resolutions'];
	}

	/**
	 * General method to check access rights for specific content elements.
	 * @param $meetingDate the questioned meeting date
	 * @param $contentElement the content element given by its DB name
	 * @return boolean value that tells you if access is allowed
	 */
	protected function isAccessAllowedGeneral($meetingDate, $contentElement) {
		// skip on invalid input
		if ($contentElement=='' || $meetingDate==0)
			return false;

		if ($this->dataAccessLevels[$contentElement]==self::kACCESS_LEVEL_PUBLIC)
			return true;
		if ($this->dataAccessLevels[$contentElement]==self::kACCESS_LEVEL_FORBIDDEN)
			return false;

		foreach ($this->usersAccessLevels as $userAccess) {
			// first skip rights if they do not apply
			if ($userAccess['access_from'] > $meetingDate)
				continue;
			if ($userAccess['access_until']!=0 &&  $userAccess['access_until'] < $meetingDate)
				continue;
			// now look up rights:
			if ($userAccess['access_level']>=$this->dataAccessLevels[$contentElement])
				return true;
		}
		// if no access is defined: return false
		return false;
	}

	public function isAccessAllowedAgenda($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_agendas');
	}

	public function isAccessAllowedAgendaPreliminary($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_agendas_preliminary');
	}

	public function isAccessAllowedProtocols($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_protocols');
	}

	public function isAccessAllowedProtocolsPreliminary($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_protocols_preliminary');
	}

	public function isAccessAllowedDocuments($meetingDate, $documentUID=0) {
		$documentDATA = t3lib_BEfunc::getRecord('tx_meetings_documents', $documentUID);
		// if UID missing, or not confidential: standard way
		if ($documentUID==0 || $documentDATA['access_level']==0)		// this means: use default settings
			return $this->isAccessAllowedGeneral($meetingDate, 'access_level_documents');
		// otherwise inspect the document properties
		else {
			if ($this->isAccessAllowedGeneral($meetingDate, 'access_level_documents')==false)
				return false;

			foreach ($this->usersAccessLevels as $userAccess) {
				// first skip rights if they do not apply
				if ($userAccess['access_from'] > $meetingDate)
					continue;
				if ($userAccess['access_until']!=0 &&  $userAccess['access_until'] < $meetingDate)
					continue;
				// now look up rights and ensure to be at least internal
				if ($userAccess['access_level']>=$this->dataAccessLevels['access_level_documents']
					&& $userAccess['access_level']>=self::kACCESS_LEVEL_INTERN
				)
					return true;
			}
		}
	}

	public function isAccessAllowedResolutions($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_resolutions');
	}

}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_access.php'])    {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_access.php']);
}
?>
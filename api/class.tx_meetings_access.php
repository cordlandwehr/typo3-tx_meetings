<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Andreas Cord-Landwehr <cola@uni-paderborn.de>
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
 * @package TYPO3
 * @subpackage	tx_meetings
 */

require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_t3lib.'class.t3lib_tcemain.php');
require_once(PATH_t3lib.'class.t3lib_iconworks.php');

/**
 * Service class for tx_meetings-extension to contribute access-control
 * To use this class for access control, always run its init() function
 * first to set rights level according to selected committee
 * @class	tx_meetings_access
 */
class tx_meetings_access {
	const kACCESS_LEVEL_PUBLIC			= 0;
	const kACCESS_LEVEL_RESTRICTED		= 1;
	const kACCESS_LEVEL_INTERN			= 2;
	const kACCESS_LEVEL_FORBIDDEN		= 3;

	const kACCESS_GRANTED_BY_NO					= 0;
	const kACCESS_GRANTED_BY_PUBLIC				= 1;
	const kACCESS_GRANTED_BY_IP					= 2;	// by current ip address of user
	const kACCESS_GRANTED_BY_GROUP				= 3;	// by group the current user is member of

	private $committeeUID;
	private $dataAccessLevels = array();
	private $usersAccessLevels = array();		// the access level of the current user (on different periodes)

	/**
	 * This function must be run first before using any further function of this
	 * class: all rights access settings are set by configuration present for given committee
	 * @param	$integer	$committeeUID	UID of committee
	 * @return	boolean	returns true IFF success
	 */
	function init ($committeeUID) {
			// return false if now $committeeUID is given
			//TODO also return if no committee with this id is present
		if ($committeeUID==0)
			return false;
		$this->committeeUID=$committeeUID;
		$this->initAccessLevels();
		$this->evaluateUsersAccessLevels();
		return true;
	}

	/**
	 * Computes the user's access level based in IP, groups and access admission of current
	 * committee. Access rights are stored in class variable $this->userAccessLevels
	 * @return	boolean	returns true IFF setting was curried up successfully
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
					'access_until' => $admissionDATA['access_until'],
					'granted_by' => self::kACCESS_GRANTED_BY_IP
				);
				continue;	// if IP range ok, no need to check up group
			}

			// check groups
			if (in_array($admissionDATA['usergroup'],$usersGroupIDs)) {
				$this->usersAccessLevels[] = array (
					'access_level' => $admissionDATA['access_level'],
					'access_from' => $admissionDATA['access_from'],
					'access_until' => $admissionDATA['access_until'],
					'granted_by' => self::kACCESS_GRANTED_BY_GROUP
				);
			}
		}

		return true;
	}


	/**
	 * Set up data access levels with initial values as given by committee.
	 * This function should only be called by init method.
	 * @return	void
	 */
	private function initAccessLevels() {
		$committeeDATA = t3lib_BEfunc::getRecord('tx_meetings_committee', $this->committeeUID);
		$this->dataAccessLevels['access_level_agendas'] = $committeeDATA['access_level_agendas'];
		$this->dataAccessLevels['access_level_agendas_preliminary'] = $committeeDATA['access_level_agendas_preliminary'];
		$this->dataAccessLevels['access_level_protocols'] = $committeeDATA['access_level_protocols'];
		$this->dataAccessLevels['access_level_protocols_preliminary'] = $committeeDATA['access_level_protocols_preliminary'];
		$this->dataAccessLevels['access_level_documents'] = $committeeDATA['access_level_documents'];
		$this->dataAccessLevels['access_level_resolutions'] = $committeeDATA['access_level_resolutions'];
	}


	/**
	 * General method to check access rights for specific content elements.
	 * Note that for this it is required to have all access right initalized
	 * @see init().
	 *
	 * @param	integer	$meetingDate	the questioned meeting date
	 * @param	integer	$contentElement	the content element given by its DB name
	 * @return	boolean	value that tells if user is allowed to acces given content elmeent or not
	 */
	protected function isAccessAllowedGeneral($meetingDate, $contentElement) {
		$accessBy = accessAllowedByGeneral($meetingDate, $contentElement);
		if ($accessBy==self::kACCESS_GRANTED_BY_NO) {
			return false;
		}
		return true;
	}


	/**
	 * General method to identify by which property user has access to data.
	 * Note that for this it is required to have all access right initalized
	 * @see init().
	 *
	 * @param	integer	$meetingDate	the questioned meeting date
	 * @param	integer	$contentElement	the content element given by its DB name
	 * @return	 kACCESS_BY_...
	 */
	protected function accessAllowedByGeneral($meetingDate, $contentElement) {
		// skip on invalid input
		if ($contentElement=='' || $meetingDate==0) {
			return self::kACCESS_GRANTED_BY_NO;
		}
		if ($this->dataAccessLevels[$contentElement]==self::kACCESS_LEVEL_PUBLIC) {
			return self::kACCESS_GRANTED_BY_PUBLIC;
		}
		if ($this->dataAccessLevels[$contentElement]==self::kACCESS_LEVEL_FORBIDDEN) {
			return self::kACCESS_GRANTED_BY_NO;
		}

		foreach ($this->usersAccessLevels as $userAccess) {
			// first skip rights if they do not apply
			if ($userAccess['access_from'] > $meetingDate) {
				continue;
			}
			if ($userAccess['access_until']!=0 && $userAccess['access_until'] < $meetingDate) {
				continue;
			}
			// now look up rights:
			if ($userAccess['access_level']>=$this->dataAccessLevels[$contentElement]) {
				return $userAccess['granted_by'];
			}
		}
		// if no access is defined: return false (=no access)
		return self::kACCESS_GRANTED_BY_NO;
	}


	/**
	 * Indicates if predefined user is allewed to see agenda from predefined committee
	 * for a meeting at given $meetingDate. Note that access rights are given for meeting
	 * intervals each.
	 * @param	integer	$meetingDate	indicates the date of a meeting the user requests access
	 * @return	boolean	returns if preset user is allowed to access
	 */
	public function isAccessAllowedAgenda($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_agendas');
	}

	public function agendaAccessAllowedBy($meetingDate) {
		return $this->accessAllowedByGeneral($meetingDate, 'access_level_agendas');
	}


	/**
	 * Indicates if predefined user is allewed to see preliminary agenda from predefined committee
	 * for a meeting at given $meetingDate. Note that access rights are given for meeting
	 * intervals each.
	 * @param	integer	$meetingDate	indicates the date of a meeting the user requests access
	 * @return	boolean	returns if preset user is allowed to access
	 */
	public function isAccessAllowedAgendaPreliminary($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_agendas_preliminary');
	}


	public function agendaPreliminaryAccessAllowedBy($meetingDate) {
		return $this->accessAllowedByGeneral($meetingDate, 'access_level_agendas_preliminary');
	}


	/**
	 * Indicates if predefined user is allewed to see protocols from predefined committee
	 * for a meeting at given $meetingDate. Note that access rights are given for meeting
	 * intervals each.
	 * @param	integer	$meetingDate	indicates the date of a meeting the user requests access
	 * @return	boolean	returns if preset user is allowed to access
	 */
	public function isAccessAllowedProtocols($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_protocols');
	}


	public function protocolsAccessAllowedBy($meetingDate) {
		return $this->accessAllowedByGeneral($meetingDate, 'access_level_protocols');
	}


	/**
	 * Indicates if predefined user is allewed to see preliminary protocols from predefined committee
	 * for a meeting at given $meetingDate. Note that access rights are given for meeting
	 * intervals each.
	 * @param	integer	$meetingDate	indicates the date of a meeting the user requests access
	 * @return	boolean	returns if preset user is allowed to access
	 */
	public function isAccessAllowedProtocolsPreliminary($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_protocols_preliminary');
	}


	public function protocolsPreliminaryAccessAllowedBy($meetingDate) {
		return $this->accessAllowedByGeneral($meetingDate, 'access_level_protocols_preliminary');
	}


	/**
	 * Indicates if predefined user is allowed to see documents from predefined committee
	 * for a meeting at given $meetingDate. Note that access rights are given for meeting
	 * intervals each.
	 * @param	integer	$meetingDate	indicates the date of a meeting the user requests access
	 * @return	boolean	returns if preset user is allowed to access
	 */
	public function isAccessAllowedDocuments($meetingDate, $documentUID=0) {
		$accessBy = $this->documentsAccessAllowedBy($meetingDate, $documentUID);
		if ($accessBy==self::kACCESS_GRANTED_BY_NO) {
			return false;
		}
		return true;
	}


	public function documentsAccessAllowedBy($meetingDate, $documentUID=0) {
		$documentDATA = t3lib_BEfunc::getRecord('tx_meetings_documents', $documentUID);
		// if UID missing, or not confidential: standard way
		if ($documentUID==0 || $documentDATA['access_level']==0) {		// this means: use default settings
			return $this->accessAllowedByGeneral($meetingDate, 'access_level_documents');
		}
		// otherwise inspect the document properties
		else {
			if ($this->isAccessAllowedGeneral($meetingDate, 'access_level_documents')==false) {
				return self::kACCESS_GRANTED_BY_NO;
			}

			foreach ($this->usersAccessLevels as $userAccess) {
				// first skip rights if they do not apply
				if ($userAccess['access_from'] > $meetingDate) {
					continue;
				}
				if ($userAccess['access_until']!=0 &&  $userAccess['access_until'] < $meetingDate) {
					continue;
				}
				// now look up rights and ensure to be at least internal
				if ($userAccess['access_level']>=$this->dataAccessLevels['access_level_documents']
					&& $userAccess['access_level']>=self::kACCESS_LEVEL_INTERN
				) {
					return $userAccess['granted_by'];
				}
			}
		}
	}


	/**
	 * Indicates if predefined user is allewed to see resolutions from predefined committee
	 * for a meeting at given $meetingDate. Note that access rights are given for meeting
	 * intervals each.
	 * @param	integer	$meetingDate	indicates the date of a meeting the user requests access
	 * @return	boolean	returns if preset user is allowed to access
	 */
	public function isAccessAllowedResolutions($meetingDate) {
		return $this->isAccessAllowedGeneral($meetingDate, 'access_level_resolutions');
	}


	public function resolutionsAccessAllowedBy($meetingDate) {
		return $this->accessAllowedByGeneral($meetingDate, 'access_level_resolutions');
	}


	/**
	 * This static function tells for a given $meeting UID if meeting should be visible. This is done
	 * either by state of hidden or (if requested) by hidden option joint with the
	 * request that there must be at least two reviewers for the meeting.
	 * @param	integer	$meeting	UID of a meeting
	 * @param	integer	$disclosureType	indicates type of disclosure by constants kDISCLOSRUE_*
	 * @return	boolean	true iff meeting information are allowed to be shown
	 */
	static function isDisclosed ($meeting, $disclosureType) {
		$meetingDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $meeting);

		if ($meetingDATA['hidden']==1 || $meetingDATA['deleted']==1 || $meetingDATA['pid']<0)
            return false;

		switch ($disclosureType) {
			case tx_meetings_div::kDISCLOSURE_REVIEWERS: {
				if ($meetingDATA['reviewer_a'] &&  $meetingDATA['reviewer_b'])
					return true;
				break;
			}
			case tx_meetings_div::kDISCLOSURE_STANDARD: {
				if ($meetingDATA['hidden']==0 )
					return true;
				break;
			}
			default:
				return false;
		}
	}

}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_access.php'])    {
    require_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/api/class.tx_meetings_access.php']);
}
?>
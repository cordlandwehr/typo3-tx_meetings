<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Andreas Cord-Landwehr <phoenixx@upb.de>
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
class tx_fsmiprotocols_pi1 extends tslib_pibase {
//TODO deprecated: change to api/div function!
	const kDISCLOSURE_STANDARD 	= 0;
	const kDISCLOSURE_REVIEWERS = 1;

	const kTERM_ACADEMIC_YEAR	= 0;
	const kTERM_COMMON_YEAR 	= 1;

	var $prefixId          = 'tx_fsmiprotocols';		// Same as class name
	var $scriptRelPath     = 'pi1/class.tx_fsmiprotocols_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey            = 'fsmi_protocols';	// The extension key.
	var $pi_checkCHash     = true;
	var	$pi_USER_INT_obj   = 1;

	private $committee     = 0;
	private $year          = 0;
	private $disclosure    = tx_fsmiprotocols_pi1::kDISCLOSURE_STANDARD;
	private $userNotInIPrestrictedNetwork	= true;
	private $Display 	   = array ();

	private $baseView;		// this is the base view class for protocol outputs

	/**
	 * Main method of your PlugIn
	 *
	 * @param	string		$content: The content of the PlugIn
	 * @param	array		$conf: The PlugIn Configuration
	 * @return	The content that should be displayed on the website
	 */
	function main($content,$conf)	{
		$this->conf=$conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_initPIflexForm(); // Init and get the flexform data of the plugin
		$this->pi_USER_INT_obj = 1;
		$this->cache = 0;	// TODO
		$this->pi_checkCHash = TRUE;
// debug($this);
		$GETcommands = t3lib_div::_GP($this->extKey);	// can be both: POST or GET
		$protocolUID = intval($GETcommands['showUid']);
		$resolutionUID = intval($GETcommands['resolutionUid']);
		$this->year = intval($GETcommands['year']);
		$this->piVars['year'] = intval($GETcommands['year']);
		$this->committee = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'uidCommittee'));
		$this->fixYear = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'fixViewYear'));

		// adjust display style
		$this->Display['ListViewType'] = intval($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'ListViewType', 'Display'));
		$this->Display['ShowTitleElement'] = ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'ShowTitleElement', 'Display')==1);
		$this->Display['ShowAgendaElement'] = ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'ShowAgendaElement', 'Display')==1);
		$this->Display['ShowDocumentsElement'] = ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'ShowDocumentsElement', 'Display')==1);
		$this->Display['ShowResolutionsElement'] = ($this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'ShowResolutionsElement', 'Display')==1);

		$this->baseView = t3lib_div::makeInstance(tx_fsmiprotocols_view_base);
		$this->baseView->init($this->committee , $this->year, $this->conf);
		$this->baseView->setDisplay($this->Display);

		// views without breadcrumb
		// IF protocol_uid given: display this protocol
		if ($protocolUID)	{
			$content = $this->baseView->printSingleProtocol($protocolUID);
			return $this->pi_wrapInBaseClass($content);
		}
		if ($this->Display['ListViewType']==tx_fsmiprotocols_view_base::kVIEW_LATEST) {
			$content = $this->baseView->printLatestProtocol($protocolUID);
			return $this->pi_wrapInBaseClass($content);
		}
		if ($resolutionUID) {
			$content = $this->baseView->printSingleResolution($resolutionUID);
			return $this->pi_wrapInBaseClass($content);
		}

		// IF resolutions: directly display, before breadcrumb is printed
		if ($this->Display['ListViewType']==tx_fsmiprotocols_view_base::kVIEW_RESOLUTIONS)
			return $this->baseView->printMeetingList(tx_fsmiprotocols_view_base::kVIEW_RESOLUTIONS,$this->committee);

		// now give usual overview page
		if ($this->fixYear!=0) {
			$this->year = $this->fixYear;
			return $this->pi_wrapInBaseClass($this->printOverview($content));
		} else {
			return $this->pi_wrapInBaseClass(
							$this->printProtocolListNavigationBreadcrumb().
							$this->printOverview($content));
		}
	}

	/**
	 * Shows a list of database entries
	 *
	 * @param	string		$content: content of the PlugIn
	 * @param	array		$conf: PlugIn Configuration
	 * @return	HTML list of table entries
	 */
	function printOverview($content)	{
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();		// Loading the LOCAL_LANG values

		if ($this->piVars['showUid'])	{	// If a single element should be displayed:
			$this->internal['currentTable'] = 'tx_fsmiprotocols_list';
			$this->internal['currentRow'] = $this->pi_getRecord('tx_fsmiprotocols_list',$this->piVars['showUid']);

			$content = $this->baseView->printSingleProtocol($this->piVars['showUid']);
			return $content;
		} else {
				// Make listing query, pass query to SQL database:
			$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_fsmiprotocols_list
												WHERE
													tx_fsmiprotocols_list.committee = '.$this->committee.'
													AND tx_fsmiprotocols_list.deleted=0
													AND tx_fsmiprotocols_list.hidden=0
													AND tx_fsmiprotocols_list.pid > -1
												 ORDER BY meeting_date DESC'
											);

			$committeeDATA = t3lib_BEfunc::getRecord('tx_fsmiprotocols_committee_list', $this->committee);

			$protocolUIDs = array ();
			if ($committeeDATA['term'] == self::kTERM_ACADEMIC_YEAR) {

				while($res && $row = mysql_fetch_assoc($res))	{

					// THIS IS THE GATEKEEPER, IT TAKES CARE THE ONLY THE GOOD PROTOCOLS ARE DISPLAYED
					if (
						// continue if not disclosed
						$this->isDisclosed($row['uid'],$committeeDATA['disclosure'])==false
						||
						// continue if not ok
						( $this->year!=$row['sticky_date']
						  && ! (	// the following describes all correct protocols for this year
							($this->year == strftime('%Y',$row['meeting_date']) && intval(strftime('%m',$row['meeting_date']))>9)
							||
							($this->year == strftime('%Y',$row['meeting_date'])-1 && intval(strftime('%m',$row['meeting_date']))<=9)
						  )
						)
						||
						// if already sticked to other year
						//TODO needs 1 since default is problematic
						( $row['sticky_date']!=0 && $row['sticky_date']!=1 && $this->year!=$row['sticky_date'] )
					)
						continue;

					else
						$protocolUIDs[] = $row['uid'];
				}
			}
			else {// this is the case of a common year
				while($res && $row = mysql_fetch_assoc($res))	{

					// THIS IS THE GATEKEEPER, IT TAKES CARE THE ONLY THE GOOD PROTOCOLS ARE DISPLAYED
					if (
						// continue if not disclosed
						$this->isDisclosed($row['uid'],$committeeDATA['disclosure'])==false
						||
						// continue if not ok
						( $this->year!=$row['sticky_date'] && !($this->year == strftime('%Y',$row['meeting_date'])) )
						||
						// if already sticked to other year
						( $row['sticky_date']!=0 && $row['sticky_date']!=1 && $this->year!=$row['sticky_date'] )
					)
						continue;

					else
						$protocolUIDs[] = $row['uid'];
				}
			}
		}

		return $this->baseView->printMeetingList($this->Display['ListViewType'], $protocolUIDs);
	}




	function printProtocolListNavigationBreadcrumb() {
		// Make listing query, pass query to SQL database:
		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
											FROM tx_fsmiprotocols_list
											WHERE
												tx_fsmiprotocols_list.committee = '.$this->committee.'
												AND tx_fsmiprotocols_list.deleted=0
												AND tx_fsmiprotocols_list.hidden=0
												AND tx_fsmiprotocols_list.pid > 0
											  ORDER BY meeting_date DESC'
										);
		$committeeDATA = t3lib_BEfunc::getRecord('tx_fsmiprotocols_committee_list', $this->committee);
		$menuYears = array();
		while($res && $row = mysql_fetch_assoc($res)) {
			// check if disclosed/published to public
			if ($this->isDisclosed($row['uid'],$committeeDATA['disclosure'])==false)
				continue;

			// set sticky years
			if ($row['sticky_date']!=0 && $row['sticky_date']!=1 && $row['sticky_date']!='') {
				$menuYears[$row['sticky_date']] = $row['sticky_date'];
				continue;
			}

			// set years until December
			if ($committeeDATA['term']==self::kTERM_ACADEMIC_YEAR && intval(strftime('%m',$row['meeting_date']))>9) {
				$menuYears[strftime('%Y',$row['meeting_date'])] =  strftime('%Y',$row['meeting_date']);
				continue;
			}
			elseif ($committeeDATA['term']==self::kTERM_COMMON_YEAR) {
				$menuYears[strftime('%Y',$row['meeting_date'])] =  strftime('%Y',$row['meeting_date']);
				continue;
			}

			// set years from January on
			if ($committeeDATA['term']==self::kTERM_ACADEMIC_YEAR && intval(strftime('%m',$row['meeting_date']))<10) {
				$menuYears[intval(strftime('%Y',$row['meeting_date']))-1] = intval(strftime('%Y',$row['meeting_date']))-1;
				continue;
			}
		}
		// sort it
		arsort($menuYears);

		$contentTopMenu = '<div style="border-top:1px solid; border-bottom:1px solid; padding: 3px;">';
		foreach($menuYears as $currentYear) {
			// if no year selected, start with highest one
			if ($this->piVars['year'] == 0) {
				$this->piVars['year'] = $currentYear;
				$this->year = $currentYear;
			}

			// highlight in menu
 			if ($this->piVars['year'] == $currentYear) {
				// present newest protocols if nothing selected

				$contentTopMenu .= ' <span style="padding: 5px;"><strong>'.
					$this->pi_linkTP(
						($committeeDATA['term']==self::kTERM_ACADEMIC_YEAR? $currentYear.'/'.tx_fsmiprotocols_div::twoDigits($currentYear+1): $currentYear),
						array(
							$this->extKey.'[year]' => $currentYear,
						),
						$this->cache
					).
				'</strong></span> ';
			} else
				$contentTopMenu .= ' <span style="padding: 5px;">'.
					$this->pi_linkTP(
						($committeeDATA['term']==self::kTERM_ACADEMIC_YEAR? $currentYear.'/'.tx_fsmiprotocols_div::twoDigits($currentYear+1): $currentYear),
						array(
							$this->extKey.'[year]' => $currentYear,
						),
						$this->cache
					).
				'</span> ';
		}
		return $contentTopMenu.'</div>';
	}

	//TODO does not fit into current architecture
	function isDisclosed($protocol, $disclosureType) {
		$protocolDB = t3lib_BEfunc::getRecord('tx_fsmiprotocols_list', $protocol);

		switch ($disclosureType) {
			case tx_fsmiprotocols_div::kDISCLOSURE_REVIEWERS: {
				if ($protocolDB['reviewer_a'] &&  $protocolDB['reviewer_b'] && $protocolDB['hidden']==0)
					return true;
				break;
			}
			case tx_fsmiprotocols_div::kDISCLOSURE_STANDARD: {
				if ($protocolDB['hidden']==0)
					return true;
				break;
			}
			default:
				return false;
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_protocols/pi1/class.tx_fsmiprotocols_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/fsmi_protocols/pi1/class.tx_fsmiprotocols_pi1.php']);
}

?>

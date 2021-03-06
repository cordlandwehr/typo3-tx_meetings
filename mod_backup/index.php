<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Cord-Landwehr <cola@uni-paderborn.de>
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
 * Module 'Backup' for the 'meetings' extension.
 *
 * @author	Andreas Cord-Landwehr <cola@uni-paderborn.de>
 * @package	TYPO3
 * @subpackage	tx_meetings
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$LANG->includeLLFile("EXT:meetings/mod_backup/locallang.xml");
require_once (PATH_t3lib."class.t3lib_scbase.php");

require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_div.php');


$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

class tx_meetings_module_backup extends t3lib_SCbase {
	var $TEMP_PATH = '/tmp';

		// path constants
	const kRELATIVE_TAR_PATH = 'typo3temp/meetings_tar/';
	const kRELATIVE_TMP_PATH = 'typo3temp/meetings_tmp/';

		// views
	const kVIEW_CREATE_BACKUPS = 1;

		// actions
	const kACTION_CREATE_BACKUPS = 1;

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			"function" => Array (
				self::kVIEW_CREATE_BACKUPS => $LANG->getLL("function_create"),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		 $this->TEMP_PATH = PATH_site.self::kRELATIVE_TMP_PATH;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

 		if (($this->id && $access) || ($BE_USER->user["admin"] && !$this->id))	{

				// Make Updates
			$postDATA = $_POST;

// 			$this->content.=$this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));

			if (isset($postDATA['backup_task'])) {
				switch (intval($postDATA['backup_task'])) {
					// case of creating backup TAR files
					case self::kACTION_CREATE_BACKUPS: {
						foreach ($postDATA['backup_this_committee'] as $committee => $switch) {
							$files = $this->createFilelist(intval($committee));
							$this->copyFilesToTemp($files);
							$tarName = $this->createTarNameForCommittee(intval($committee));
							$this->createDirTarball($tarName);
						}
						break;
					}
				}
			}

				// Draw the header.
			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="index.php?id='.$this->id.'" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$headerSection = $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"])."<br />".$LANG->sL("LLL:EXT:lang/locallang_core.xml:labels.path").": ".t3lib_div::fixed_lgd_pre($this->pageinfo["_thePath"],50);

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section("",$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,"SET[function]",$this->MOD_SETTINGS["function"],$this->MOD_MENU["function"])));
			$this->content.=$this->doc->divider(5);

			$this->content .= $this->printCreateBackupMenu();

		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance("mediumDoc");
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL("title"));
			$this->content.=$this->doc->header($LANG->getLL("title"));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Print menu to start backup generation
	 *
	 * @return	string	HTML form
	 */
	function printCreateBackupMenu () {
		$content = '<h2>'.$LANG->getLL('backup_title_long').'</h2>';
		$content .= '<table>';
		$content .= '<tr><th>'.$LANG->getLL('meeting').'</th><th>'.$LANG->getLL('last_backup').'</th><th></th></tr>';
		$committees = $this->getAllCommittees();
		foreach ($committees as $committee) {
			$committeeDATA = t3lib_BEfunc::getRecord('tx_meetings_committee', $committee);
			$content .= '<tr><td>'.$committeeDATA['committee_name'].'</td>';
			$content .= '<td>'.$this->linkToMostRecentTar($committee).'</td>';
			$content .= '<td><input type="checkbox" name="backup_this_committee['.$committeeDATA['uid'].']" checked="checked" /></td>';
		}
		$content .= '</table>';

		$content .= '<div><button type="submit" name="backup_task" value="'.self::kACTION_CREATE_BACKUPS.'">'.
			$LANG->getLL('label_submit_backup-committees').
			'</button></div>';

		return $content;
	}

	/**
	 * Creates filelist with arrays (original/relative new path)
	 * @return array
	 */
	function createFilelist ($committee) {
			// Make listing query, pass query to SQL database:
		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT *
												FROM tx_meetings_list
												WHERE
													tx_meetings_list.committee = '.$committee.'
													AND tx_meetings_list.deleted=0
													AND tx_meetings_list.hidden=0
													AND tx_meetings_list.pid > -1
												 ORDER BY meeting_date DESC'
											);

		$committeeDATA = t3lib_BEfunc::getRecord('tx_meetings_committee', $committee);

		$files = array();
		while ($res && $meeting = mysql_fetch_assoc($res)) {
			if ($meeting['protocol_pdf']!='')
				$files[] = array
					(
						'old' => tx_meetings_div::uploadFolder.$meeting['protocol_pdf'],
						'new' => tx_meetings_div::dateToTenureYear($meeting['meeting_date'], $meeting['sticky_date']).
										'/'.
										$this->createMeetingFileTitle ($meeting['uid'])
					);
			$documents = tx_meetings_div::getDocumentsForMeeting ($meeting['uid']);
			$counter = 1;
			foreach($documents as $document) {
				// TODO check for non-pdfs!
				$documentDATA = t3lib_BEfunc::getRecord('tx_meetings_documents', $document);
				if ($documentDATA['document_file']=='')
					continue;
				$files[] = array (
					'old' => tx_meetings_div::uploadFolder.$documentDATA['document_file'],
					'new' => tx_meetings_div::dateToTenureYear($meeting['meeting_date'], $meeting['sticky_date']).'/'.
						strftime('%Y-%m-%d', $meeting['meeting_date']).'/'.
						'document_'.$counter.'_'.$this->stringToFilename($documentDATA['name']).'.pdf'
				);
				$counter++;
			}
			$resolutions = tx_meetings_div::getResolutionsForMeeting($meeting['uid']);
			foreach($resolutions as $resolution) {
				$resolutionDATA = t3lib_BEfunc::getRecord('tx_meetings_resolution', $resolution);
				if ($resolutionDATA['resolution_pdf']=='')
					continue;
				$files[] = array (
					'old' => tx_meetings_div::uploadFolder.$resolutionDATA['resolution_pdf'],
					'new' => tx_meetings_div::dateToTenureYear($meeting['meeting_date'], $meeting['sticky_date']).'/'.
						strftime('%Y-%m-%d', $meeting['meeting_date']).'/'.
						'resolution_'.
							$this->stringToFilename($resolutionDATA['resolution_id']).'_'.
							$this->stringToFilename($resolutionDATA['name']).'.pdf'
				);
			}

		}
		return $files;
	}


	/**
	 * For a meeting's protocol compute filename. This function also prefix name with path
	 * hirarchy according to meeting-date/year.
	 *
	 * @param	integer	$meetingUID	the UID of a meeting
	 * @return	string	filename for the meeting
	 */
	function createMeetingFileTitle ($meetingUID) {
		$meetingDATA = t3lib_BEfunc::getRecord('tx_meetings_list', $meetingUID);

			// configure protocol title
			// FIXME the '0' is needed from transition problem (previously set to 1 by default)
			// delete at version 1.0
		if ($meetingDATA['protocol_name']!='' && $meetingDATA['protocol_name']!='0') {
			$filename = $meetingDATA['protocol_name'];
			$filename = $this->stringToFilename($filename);
			return strftime('%Y-%m-%d', $meetingDATA['meeting_date']).'/'.$filename.'.pdf';

		}
		else
			return strftime('%Y-%m-%d', $meetingDATA['meeting_date']).'/'.
				strftime('%Y-%m-%d', $meetingDATA['meeting_date']).'.pdf';
	}


	/**
	 * This function collects all committes available in database
	 *
	 * @return	array	consists of UIDs of committees
	 */
	function getAllCommittees() {
		$res =$GLOBALS['TYPO3_DB']->sql_query('SELECT uid
												FROM tx_meetings_committee
												WHERE
													deleted=0 AND hidden=0
												ORDER BY committee_name'
											);

		$committees = array();
		while ($res && $committee = mysql_fetch_assoc($res))
			$committees[] = $committee['uid'];
		return $committees;
	}


	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}


	/**
	 * Converts given string to a suable frilename.
	 *
	 * @param	string	$filename	designated name for file
	 * @return	string	usable name of file
	 */
	static function stringToFilename ($filename) {
		$filename = strtolower($filename);
		$filename = str_replace("#","_",$filename);
		$filename = str_replace(" ","_",$filename);
		$filename = str_replace("'","",$filename);
		$filename = str_replace('"',"",$filename);
		$filename = str_replace("__","_",$filename);
		$filename = str_replace("&","and",$filename);
		$filename = str_replace("/","_",$filename);
		$filename = str_replace("\"","_",$filename);
		$filename = str_replace("?","",$filename);
		$filename = str_replace(".","",$filename);

		return $filename;
	}


	/**
	 * This function assigns a unique tar.gz filename to a specific meeting.
	 * Filename contains as preefix the predefined value $stringtoFilename[<this committee>]
	 *
	 * @param	integer	$committee	UID of committee
	 * @return	string	filename as a plain string
	 */
	function createTarNameForCommittee($committee) {
		$committeeDATA = t3lib_BEfunc::getRecord('tx_meetings_committee', intval($committee));
		$tarName = $committeeDATA['committee_name'].'_'.
			$this->stringToFilename($committeeDATA['committee_name']).'.tar.gz';

		return $tarName;
	}


	/**
	 * For a given committee creates link to its tar file (if existing). If not existing function
	 * returns a broken link.
	 *
	 * @param	integer	$committee	UID of committee
	 * @return	string	HTML wrapped link
	 */
	function linkToMostRecentTar ($committee) {
		$tarName = $this->createTarNameForCommittee ($committee);
		return '<a href="../../../../'.self::kRELATIVE_TAR_PATH.$tarName.'" >'.
			$LANG->getLL('backup').' '.
			date('Y-m-d H:i',filemtime(PATH_site.self::kRELATIVE_TAR_PATH.$tarName)).
			'</a>';
	}


	/**
	 * This function copies all designated files to temp directory
	 * @param	array	$files	array of strings of file name including their relative pathes
	 * @return	void
	 */
	function copyFilesToTemp ($files) {
			// deletes and then creates base temp dir
		t3lib_div::rmdir ($this->TEMP_PATH, true);
		t3lib_div::mkdir ($this->TEMP_PATH);

		foreach ($files as $file) {
 			t3lib_div::mkdir_deep($this->TEMP_PATH,t3lib_div::dirname($file['new']));

			if (!copy (PATH_site.$file['old'],$this->TEMP_PATH.$file['new']))
				echo '<p>'.$LANG->getLL('error_could-not-copy-file').'<br />'.$file['old'].'</p>'
				;
		}
	}


	/**
	 * Creates TAR-balls and returns links to them. For this the tarable files must have been copied to
	 * temporary location before. All information and links are returned by echo/print.
	 *
	 * @param	string	$tarName	of the to be generated tar
	 * @return	void
	 */
	function createDirTarball ($tarName) {
			// uploads
		echo '<h2>'.$LANG->getLL('title_archived-files"').'</h2>';
		t3lib_div::mkdir(PATH_site.self::kRELATIVE_TAR_PATH);
		$createCommand = 'cd '.PATH_site.' && tar cvzf '.
			PATH_site.self::kRELATIVE_TAR_PATH.$tarName.' '.self::kRELATIVE_TMP_PATH;

			// run at command line
		exec ($createCommand, $output);
			// put out links
		foreach ($output as $line)
			echo $line.'<br />';
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/mod_backup/index.php'])	{
	require_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/meetings/mod_backup/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_meetings_module_backup');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
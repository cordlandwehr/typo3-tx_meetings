<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

require_once(t3lib_extMgm::extPath('meetings').'api/class.tx_meetings_div.php');

t3lib_extMgm::addToInsertRecords('tx_meetings_list');
t3lib_extMgm::addToInsertRecords('tx_meetings_committee_list');

$TCA["tx_meetings_list"] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_list',
        'label'     => 'meeting_date',
        'label_userFunc' => 'tx_meetings_div->printTCAlabelProtocol',
        'type'		=> 'type',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => TRUE,
        'origUid' => 't3_origuid',
        'default_sortby' => "ORDER BY meeting_date",
        'delete' => 'deleted',
        'enablecolumns' => array (
            'disabled' => 'hidden',
        ),
        'dividers2tabs'=>TRUE,
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_meetings_list.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "hidden, meeting_date, protocol_name, protocol, committee, reviewer_a, reviewer_b",
    )
);

$TCA["tx_meetings_committee_list"] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list',
        'label'     => 'committee_name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => "ORDER BY committee_name, uid",
        'delete' => 'deleted',
        'dividers2tabs'=>TRUE,
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_meetings_committee_list.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "hidden, committee_name, storage_pid",
    )
);

$TCA["tx_meetings_documents"] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
		'default_sortby' => "ORDER BY crdate",
        'hideTable' => 1,
        'delete' => 'deleted',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_meetings_committee_list.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "hidden, name,description,document_file",
    )
);

$TCA["tx_meetings_resolution"] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_resolution',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => "ORDER BY resolution_id, name, uid",
        'hideTable' => 1,
        'delete' => 'deleted',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_meetings_committee_list.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "hidden,name,resolution_id,resolution_text",
    )
);

$TCA["tx_meetings_access_admission"] = array (
    "ctrl" => array (
        'title'     => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_access_admission',
        'label'     => 'name',
        'tstamp'    => 'tstamp',
        'crdate'    => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => "ORDER BY name, usergroup",
        'hideTable' => 1,
        'delete' => 'deleted',
        'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
        'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_meetings_committee_list.gif',
    ),
    "feInterface" => array (
        "fe_admin_fieldList" => "hidden,name,ip_range,usergroup",
    )
);

t3lib_div::loadTCA('tt_content');

/* Here starts pi1 */
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';
t3lib_extMgm::addPlugin(array('LLL:EXT:meetings/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:meetings/flexform/flexform_pi1.xml');

/* Here starts pi2 */
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi2']='layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi2']='pi_flexform';
t3lib_extMgm::addPlugin(array('LLL:EXT:meetings/locallang_db.xml:tt_content.list_type_pi2', $_EXTKEY.'_pi2'),'list_type');
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi2', 'FILE:EXT:meetings/flexform/flexform_pi2.xml');


/* the backend tools */
if (TYPO3_MODE=="BE") {
        $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_meetings_div"] = t3lib_extMgm::extPath($_EXTKEY).'api/class.tx_meetings_div.php';
        t3lib_extMgm::addModule("web","txmeetingsM1","",t3lib_extMgm::extPath($_EXTKEY)."mod_backup/");

        $presetSkinImgs = is_array($TBE_STYLES['skinImg']) ? $TBE_STYLES['skinImg'] : array();    // Means, support for other extensions to add own icons...
}

// include statics
t3lib_extMgm::addStaticFile($_EXTKEY,"static/css/","Meetings CSS");
?>

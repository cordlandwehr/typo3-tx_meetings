<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$TCA["tx_meetings_list"] = array (
    "ctrl" => $TCA["tx_meetings_list"]["ctrl"],
    "interface" => array (
        "showRecordFieldList" => "hidden,meeting_date,not_admitted,protocol_name,protocol"
    ),
    "feInterface" => $TCA["tx_meetings_list"]["feInterface"],
    "columns" => array (
        't3ver_label' => array (
            'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
            'config' => array (
                'type' => 'input',
                'size' => '30',
                'max'  => '30',
            )
        ),
        'hidden' => array (
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
		'type' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.type',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.type.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.type.I.1', '1'),
				),
				'default' => '1'
			)
		),
        "meeting_date" => Array (
            "exclude" => 0,
            "label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.meeting_date",
            "config" => Array (
                "type"     => "input",
                "size"     => "8",
                "max"      => "20",
                "eval"     => "date",
                "checkbox" => "0",
                "default"  => "0"
            )
        ),
        "meeting_time" => Array (
            "exclude" => 0,
            "label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.meeting_time",
            "config" => Array (
                "type"     => "input",
                "size"     => "8",
                "max"      => "20",
                "eval"     => "time",
                "checkbox" => "0",
                "default"  => "0"
            )
        ),
        "meeting_room" => Array (
            "exclude" => 0,
            "label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.meeting_room",
            "config" => Array (
                "type"     => "input",
                "size"     => "20",
				"default"  => ''
            )
        ),
        "sticky_date" => Array (
            "exclude" => 0,
            "label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.sticky_date",
            "config" => Array (
                "type"     => "input",
                "size"     => "4",
                "max"      => "4",
                "eval"     => "int",
                "checkbox" => "1",
                "default"  => ""
            )
        ),
		'agenda' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.agenda',
			"config" => Array (
				"type" => "text",
				"cols" => "48",
				"rows" => "15",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => Array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						"script" => "wizard_rte.php",
					),
				),
			)
		),
        'agenda_preliminary' => array (
            'exclude' => 1,
            'label'   => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.agenda_preliminary',
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
		"protocol_name" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.protocol_name",
			"config" => Array (
				"type"     => "input",
				"size"     => "20",
				"default"  => ''
			)
		),
        "protocol" => Array (
            "exclude" => 0,
            "label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.protocol",
            "config" => Array (
                "type" => "text",
                "wrap" => "OFF",
                "cols" => "48",
                "rows" => "20",
            )
		),
		"protocol_pdf" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.protocol_pdf",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "pdf",
				"max_size" => 20000,
				"uploadfolder" => "uploads/tx_meetings",
				"show_thumbs" => 1,
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"documents" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents",
			"config" => Array (
				"type" => "inline",
				"foreign_table" => "tx_meetings_documents",
				"foreign_field" => "protocol",
				"foreign_table_field" => "protocol_tablename",
				"appearance" => Array (
					"collapseAll" => 1
				),
				"size" => 6,
				"minitems" => 0,
				"maxitems" => 1000
			)
		),
		"resolutions" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_resolution",
			"config" => Array (
				"type" => "inline",
				"foreign_table" => "tx_meetings_resolution",
				"foreign_field" => "protocol",
				"foreign_table_field" => "protocol_tablename",
				"appearance" => Array (
					"collapseAll" => 1
				),
				"size" => 6,
				"minitems" => 0,
				"maxitems" => 1000
			)
		),
        'not_admitted' => array (
            'exclude' => 1,
            "label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.not_admitted",
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
		"reviewer_a" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.reviewer_a",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "fe_users",
				"foreign_table_where" => "ORDER BY fe_users.uid",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"reviewer_b" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.reviewer_b",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "fe_users",
				"foreign_table_where" => "ORDER BY fe_users.uid",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"committee" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list.committee",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "tx_meetings_committee_list",
//				"foreign_table_where" => "AND tx_meetings_committee_list.pid=###STORAGE_PID### ORDER BY tx_meetings_committee_list.uid",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			),
		),
    ),
    "types" => array (
        "0" => array("showitem" => "hidden;;1;;1-1-1, type, protocol_name, meeting_date, meeting_time, meeting_room, committee, agenda;;;richtext[paste|bold|italic|orderedlist|unorderedlist]:rte_transform[mode=ts], agenda_preliminary,--div--;Meeting Information, not_admitted, protocol, documents, resolutions,--div--;Plaintext Quality Ensurance, reviewer_a, reviewer_b"),
        "1" => array("showitem" => "hidden;;1;;1-1-1, type, protocol_name, meeting_date, meeting_time, meeting_room, committee, agenda;;;richtext[paste|bold|italic|orderedlist|unorderedlist]:rte_transform[mode=ts], agenda_preliminary,--div--;Meeting Information, not_admitted, protocol_pdf, documents, resolutions")
    ),
    "palettes" => array (
        "1" => array("showitem" => "sticky_date")
    )
);

$TCA["tx_meetings_committee_list"] = array (
    "ctrl" => $TCA["tx_meetings_committee_list"]["ctrl"],
    "interface" => array (
        "showRecordFieldList" => "hidden,committee_name"
    ),
    "feInterface" => $TCA["tx_meetings_list"]["feInterface"],
    "columns" => array (
        'hidden' => array (
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
		"committee_name" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.committee_name",
			"config" => Array (
				"type"     => "input",
				"size"     => "20",
			)
		),
		'disclosure' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.disclosure',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.disclosure.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.disclosure.I.1', '1'),
				),
			)
		),
		'term' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.term',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.term.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.term.I.1', '1'),
				),
				"default" => 0,
			)
		),
		'access_level_agendas' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level_agendas',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.1', '1'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.2', '2'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.3', '3'),
				),
			)
		),
		'access_level_agendas_preliminary' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level_agendas_preliminary',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.1', '1'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.2', '2'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.3', '3'),
				),
			)
		),
		'access_level_protocols' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level_protocols',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.1', '1'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.2', '2'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.3', '3'),
				),
			)
		),
		'access_level_protocols_preliminary' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level_protocols_preliminary',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.1', '1'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.2', '2'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.3', '3'),
				),
			)
		),
		'access_level_documents' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level_documents',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.1', '1'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.2', '2'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.3', '3'),
				),
			)
		),
		'access_level_resolutions' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level_resolutions',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.1', '1'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.2', '2'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.3', '3'),
				),
			)
		),
		"access_admissions" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_access_admission",
			"config" => Array (
				"type" => "inline",
				"foreign_table" => "tx_meetings_access_admission",
				"foreign_field" => "committee",
				"foreign_table_field" => "committee_tablename",
				"appearance" => Array (
					"collapseAll" => 1
				),
				"size" => 6,
				"minitems" => 0,
				"maxitems" => 1000
			)
		),
		"storage_pid" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.storage_pid",
			"config" => Array (
				"type" => "group",
				"internal_type" => 'db',
				"allowed" => "pages",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			),
		),
    ),
    "types" => array (
        "0" => array("showitem" => "hidden;;1;;1-1-1, committee_name, disclosure, term, storage_pid, --div--;Access Level Settings, access_level_agendas, access_level_agendas_preliminary, access_level_protocols, access_level_protocols_preliminary, access_level_documents, access_level_resolutions, --div--;Access Admissions, access_admissions")
    ),
    "palettes" => array (
        "1" => array("showitem" => "")
    )
);


$TCA['tx_meetings_documents'] = array (
	'ctrl' => $TCA['tx_meetings_documents']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,name,description,document_file'
	),
	'feInterface' => $TCA['tx_meetings_documents']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"protocol" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "tx_meetings_list",
				"foreign_table_where" => " ORDER BY tx_meetings_list.date ",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"protocol_tablename" => Array (
			"exclude" => 1,
			"label" => "foreign table for protocol table", //TODO switch to DB
			"config" => Array (
				"type" => "input",
				"size" => 4,
				"max" => 255,
				"default" => 'tx_meetings_list'
			)
		),
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				"eval" => "required,trim",
			)
		),
		'description' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents.description',
            "config" => Array (
                "type" => "text",
                "wrap" => "OFF",
                "cols" => "48",
                "rows" => "5",
            )
		),
		"document_file" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents.document_file",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "odt,rtf,jpg,pdf,txt,doc,xls",
				"max_size" => 20000,
				"uploadfolder" => "uploads/tx_meetings",
				"show_thumbs" => 1,
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
        'access_level' => array (
            'exclude' => 1,
            "label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents.access_level",
            'config'  => array (
                'type'    => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents.access_level.I.0', '0'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents.access_level.I.1', '1'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents.access_level.I.2', '2'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_documents.access_level.I.3', '3'),
				),
				'default' => '0'
            )
        ),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, name,description,access_level,document_file')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);


$TCA['tx_meetings_resolution'] = array (
	'ctrl' => $TCA['tx_meetings_resolution']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,name,resolution_id,resolution_text'
	),
	'feInterface' => $TCA['tx_meetings_documents']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"protocol" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_list",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "tx_meetings_list",
				"foreign_table_where" => " ORDER BY tx_meetings_list.date ",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"protocol_tablename" => Array (
			"exclude" => 1,
			"label" => "foreign table for protocol table", //TODO switch to DB
			"config" => Array (
				"type" => "input",
				"size" => 4,
				"max" => 255,
				"default" => 'tx_meetings_list'
			)
		),
		'resolution_id' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_resolution.resolution_id',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_resolution.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'resolution_text' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_resolution.resolution_text',
			"config" => Array (
				"type" => "text",
				"cols" => "48",
				"rows" => "15",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => Array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						"script" => "wizard_rte.php",
					),
				),
			)
		),
		"resolution_pdf" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_resolution.resolution_pdf",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "pdf",
				"max_size" => 20000,
				"uploadfolder" => "uploads/tx_meetings",
				"show_thumbs" => 1,
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1,resolution_id,name,resolution_text;;;richtext[paste|bold|italic|orderedlist|unorderedlist|link|image]:rte_transform[mode=ts],resolution_pdf')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_meetings_access_admission'] = array (
	'ctrl' => $TCA['tx_meetings_access_admission']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,name,resolution_id,resolution_text'
	),
	'feInterface' => $TCA['tx_meetings_access_admission']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"committee" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "tx_meetings_committee_list",
				"foreign_table_where" => " ORDER BY tx_meetings_committee_list.date ",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"committee_tablename" => Array (
			"exclude" => 1,
			"label" => "foreign table for committee table", //TODO switch to DB
			"config" => Array (
				"type" => "input",
				"size" => 4,
				"max" => 255,
				"default" => 'tx_meetings_committee_list'
			)
		),
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_access_admission.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		"ip_range" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_access_admission.ip_range",
			"config" => Array (
				"type"     => "input",
				"size"     => "15",
				"max"      => "15",
			)
		),
		"usergroup" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_access_admission.usergroup",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "fe_groups",
				"foreign_table_where" => "ORDER BY fe_groups.uid",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		'access_level' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:meetings/locallang_db.xml:tx_meetings_access_admission.access_level',
			'config' => array (
				'type' => 'radio',
				'items' => array (
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.1', '1'),
					array('LLL:EXT:meetings/locallang_db.xml:tx_meetings_committee_list.access_level.I.2', '2'),
				),
			)
		),
        "applies_until" => Array (
            "exclude" => 0,
            "label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_access_admission.applies_until",
            "config" => Array (
                "type"     => "input",
                "size"     => "8",
                "max"      => "20",
                "eval"     => "date",
                "checkbox" => "0",
                "default"  => "0"
            )
        ),
        "applies_from" => Array (
            "exclude" => 0,
            "label" => "LLL:EXT:meetings/locallang_db.xml:tx_meetings_access_admission.applies_from",
            "config" => Array (
                "type"     => "input",
                "size"     => "8",
                "max"      => "20",
                "eval"     => "date",
                "checkbox" => "0",
                "default"  => "0"
            )
        ),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1,name, ip_range, usergroup, access_level, applies_until, applies_from')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>

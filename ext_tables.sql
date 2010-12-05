#
# Table structure for table 'tx_meetings_list'
#
CREATE TABLE tx_meetings_list (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	type int(11) DEFAULT '0' NOT NULL,
	meeting_date int(11) DEFAULT '0' NOT NULL,
	meeting_room varchar(256) DEFAULT '' NOT NULL,
	meeting_time int(11) DEFAULT '0' NOT NULL,
	sticky_date varchar(4) DEFAULT '' NOT NULL,
	agenda text NOT NULL,
	agenda_preliminary tinyint(4) DEFAULT '0' NOT NULL,
	protocol_name varchar(256) DEFAULT '' NOT NULL,
	not_admitted tinyint(4) DEFAULT '0' NOT NULL,
	protocol text NOT NULL,
	protocol_pdf text NOT NULL,
	documents text NOT NULL,
	resolutions text NOT NULL,
	committee int(11) DEFAULT '0' NOT NULL,
	reviewer_a int(11) DEFAULT '0' NOT NULL,
	reviewer_b int(11) DEFAULT '0' NOT NULL,


	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);

#
# Table structure for table 'tx_meetings_committee_list'
#
CREATE TABLE tx_meetings_committee (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	committee_name varchar(128) DEFAULT '0' NOT NULL,
	storage_pid int(11) DEFAULT '0' NOT NULL,
	disclosure int(11) DEFAULT '0' NOT NULL,
	term int(11) DEFAULT '0' NOT NULL,
	access_level_agendas int(11) DEFAULT '0' NOT NULL,
	access_level_agendas_preliminary int(11) DEFAULT '0' NOT NULL,
	access_level_protocols int(11) DEFAULT '0' NOT NULL,
	access_level_protocols_preliminary int(11) DEFAULT '0' NOT NULL,
	access_level_documents int(11) DEFAULT '0' NOT NULL,
	access_level_resolutions int(11) DEFAULT '0' NOT NULL,
	access_admissions text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
);


#
# Table structure for table 'tx_meetings_documents'
#
CREATE TABLE tx_meetings_documents (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	name tinytext NOT NULL,
	description text NOT NULL,
	protocol int(11) DEFAULT '0' NOT NULL,
	protocol_tablename varchar(255) DEFAULT '' NOT NULL,
	document_file text NOT NULL,
	access_level int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tx_meetings_resolution'
#
CREATE TABLE tx_meetings_resolution (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	name tinytext,
	resolution_id tinytext NOT NULL,
	protocol int(11) DEFAULT '0' NOT NULL,
	protocol_tablename varchar(255) DEFAULT '' NOT NULL,
	resolution_text text,
	resolution_pdf text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tx_meetings_access_admission'
#
CREATE TABLE tx_meetings_access_admission (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	committee int(11) DEFAULT '0' NOT NULL,
	committee_tablename varchar(255) DEFAULT '' NOT NULL,
	name tinytext,
	ip_range varchar(15) DEFAULT '' NOT NULL,
	usergroup int(11) DEFAULT '0' NOT NULL,
	access_level int(11) DEFAULT '0' NOT NULL,
	applies_until int(11) DEFAULT '0' NOT NULL,
	applies_from int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
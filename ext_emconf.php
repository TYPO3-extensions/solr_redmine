<?php

########################################################################
# Extension Manager/Repository config file for ext "solr_redmine".
#
# Auto generated 18-04-2011 21:03
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Apache Solr Redmine Indexer',
	'description' => 'Allows to index redmine project information through a TYPO3 scheduler task into an Apache Solr index.',
	'category' => 'be',
	'author' => 'Ingo Renner',
	'author_email' => 'ingo@typo3.org',
	'shy' => '',
	'dependencies' => 'solr',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'solr' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:4:{s:9:"ChangeLog";s:4:"eff0";s:12:"ext_icon.gif";s:4:"1bdc";s:19:"doc/wizard_form.dat";s:4:"c703";s:20:"doc/wizard_form.html";s:4:"3a66";}',
);

?>
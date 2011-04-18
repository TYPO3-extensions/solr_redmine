<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}


	// adding scheduler tasks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_solrredmine_task_IndexTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'Redmine Solr Indexer',
	'description'      => 'Indexes Redmine project data.',
	'additionalFields' => 'tx_solrredmine_task_IndexTaskAdditionalFieldProvider'
);


?>
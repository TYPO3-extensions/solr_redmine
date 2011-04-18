<?php

$extensionPath = t3lib_extMgm::extPath('solr_redmine');

return array(
	'tx_solrredmine_task_indextask' => $extensionPath . 'classes/task/class.tx_solrredmine_task_indextask.php',
	'tx_solrredmine_task_indextaskadditionalfieldprovider' => $extensionPath . 'classes/task/class.tx_solrredmine_task_indextaskadditionalfieldprovider.php',
);

?>
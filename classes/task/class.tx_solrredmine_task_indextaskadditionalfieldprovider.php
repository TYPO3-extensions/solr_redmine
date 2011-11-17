<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Ingo Renner <ingo@typo3.org>
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
 * Adds additional fields to specify the Solr server to use for indexing
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr_redmine
 */
class tx_solrredmine_task_IndexTaskAdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * Used to define fields to provide the Solr server address and processing
	 * limit when adding or editing a task.
	 *
	 * @param	array					$taskInfo: reference to the array containing the info used in the add/edit form
	 * @param	tx_scheduler_Task		$task: when editing, reference to the current task object. Null when adding.
	 * @param	tx_scheduler_module1	$schedulerModule: reference to the calling object (Scheduler's BE module)
	 * @return	array					Array containg all the information pertaining to the additional fields
	 *									The array is multidimensional, keyed to the task class name and each field's id
	 *									For each field it provides an associative sub-array with the following:
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
		$additionalFields = array();

		if ($schedulerModule->CMD == 'add') {
			$taskInfo['redmineServer']         = '';
			$taskInfo['solrServer']            = '';
			$taskInfo['documentsToIndexLimit'] = 50;
		}

		if ($schedulerModule->CMD == 'edit') {
			$server = $task->getSolrServer();

			$taskInfo['redmineServer']         = $task->getRedmineServer();
			$taskInfo['solrServer']            = $server['rootPageUid'] . '|' . $server['language'];
			$taskInfo['documentsToIndexLimit'] = $task->getDocumentsToIndexLimit();
		}

		$additionalFields['redmineServer'] = array(
			'code'     => '<input type="text" name="tx_scheduler[redmineServer]" value="' . $taskInfo['redmineServer'] . '" />',
			'label'    => 'LLL:EXT:solr_redmine/lang/locallang.xml:schedulerFieldRedmineServer',
			'cshKey'   => '',
			'cshLabel' => ''
		);

		$additionalFields['solrServer'] = array(
			'code'     => $this->getAvailableServersSelector($taskInfo['solrServer']),
			'label'    => 'LLL:EXT:solr/lang/locallang.xml:scheduler_field_server',
			'cshKey'   => '',
			'cshLabel' => ''
		);

		$additionalFields['documentsToIndexLimit'] = array(
			'code'     => '<input type="text" name="tx_scheduler[documentsToIndexLimit]" value="' . intval($taskInfo['documentsToIndexLimit']) . '" />',
			'label'    => 'LLL:EXT:solr_redmine/lang/locallang.xml:schedulerFieldDocumentsToIndexLimit',
			'cshKey'   => '',
			'cshLabel' => ''
		);

		return $additionalFields;
	}

	protected function getAvailableServersSelector($selectedServer = '') {
		$registry = t3lib_div::makeInstance('t3lib_Registry');
		$servers  = $registry->get('tx_solr', 'servers', array());
		$selector = '<select name="tx_scheduler[solrServer]">';

		foreach ($servers as $key => $serverConnectionParameters) {
			$selectedAttribute = '';
			if ($key == $selectedServer) {
				$selectedAttribute = ' selected="selected"';
			}

			$selector .= '<option value="' . $key . '"' . $selectedAttribute . '>'
				. $serverConnectionParameters['label']
				. '</option>';
		}

		$selector .= '</select>';

		return $selector;
	}

	/**
	 * Checks any additional data that is relevant to this task. If the task
	 * class is not relevant, the method is expected to return TRUE
	 *
	 * @param	array					$submittedData: reference to the array containing the data submitted by the user
	 * @param	tx_scheduler_module1	$parentObject: reference to the calling object (Scheduler's BE module)
	 * @return	boolean					True if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {
		$result = FALSE;

			// sanitize Redmine server
		$submittedData['redmineServer'] = filter_var($submittedData['redmineServer'], FILTER_SANITIZE_URL);

			// sanitize Solr server
		$registry = t3lib_div::makeInstance('t3lib_Registry');
		$servers  = $registry->get('tx_solr', 'servers', array());

		$availableServers = array_keys($servers);
		if (in_array($submittedData['solrServer'], $availableServers)) {
			$result = TRUE;
		}

			// sanitize limit
		$submittedData['documentsToIndexLimit'] = intval($submittedData['documentsToIndexLimit']);

		return $result;
	}

	/**
	 * Saves any additional input into the current task object if the task
	 * class matches.
	 *
	 * @param	array				$submittedData: array containing the data submitted by the user
	 * @param	tx_scheduler_Task	$task: reference to the current task object
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$registry = t3lib_div::makeInstance('t3lib_Registry');
		$servers  = $registry->get('tx_solr', 'servers', array());

		$task->setRedmineServer($submittedData['redmineServer']);
		$task->setSolrServer($servers[$submittedData['solrServer']]);
		$task->setDocumentsToIndexLimit($submittedData['documentsToIndexLimit']);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr_redmine/classes/task/class.tx_solrredmine_task_indextaskadditionalfieldprovider.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr_redmine/classes/task/class.tx_solrredmine_task_indextaskadditionalfieldprovider.php']);
}

?>
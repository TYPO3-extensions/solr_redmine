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
 * A task to index Redmine project data
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr_redmine
 */
class tx_solrredmine_task_IndexTask extends tx_scheduler_Task {

	/**
	 * A Solr service instance to interact with the Solr server
	 *
	 * @var	tx_solr_SolrService
	 */
	protected $solrConnection = NULL;

	protected $redmineServer = '';
	protected $solrServer = array();
	protected $documentsToIndexLimit;


	/**
	 * constructor for class tx_solrnntp_scheduler_IndexTask
	 */
	public function __construct() {
		parent::__construct();


	}

	public function getRedmineServer() {
		return $this->redmineServer;
	}

	public function setRedmineServer($redmineServer) {
		$this->redmineServer = filter_var($redmineServer, FILTER_SANITIZE_URL);
	}

	public function getSolrServer() {
		return $this->solrServer;
	}

	public function setSolrServer(array $server) {
		$this->solrServer = $server;
	}

	public function getDocumentsToIndexLimit() {
		return $this->documentsToIndexLimit;
	}

	public function setDocumentsToIndexLimit($limit) {
		$this->documentsToIndexLimit = intval($limit);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr_redmine/classes/task/class.tx_solrredmine_task_indextask.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr_redmine/classes/task/class.tx_solrredmine_task_indextask.php']);
}

?>
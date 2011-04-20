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
	 * Type of the indexed Solr document
	 *
	 * @var	string
	 */
	const ITEM_TYPE_PROJECT = 'tx_solrredmine_project';


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
	 * Initializes a Solr connection
	 *
	 * @return	void
	 */
	protected function initializeSolr() {
		$this->solrConnection = t3lib_div::makeInstance('tx_solr_ConnectionManager')->getConnection(
			$this->solrServer['solrHost'],
			$this->solrServer['solrPort'],
			$this->solrServer['solrPath'],
			$this->solrServer['solrScheme'],
			$this->solrServer['solrUseCurl']
		);
	}

	/**
	 * Fetches chunks of project information and indexes them into Solr
	 *
	 * @return	boolean	Returns TRUE on success, FALSE if no items were indexed or none were found.
	 * @throws	Exception	Throws an exception on indexing errors.
	 * @see	typo3/sysext/scheduler/tx_scheduler_Task#execute()
	 */
	public function execute() {
		$successfullyIndexed = FALSE;
		$this->initializeSolr();

			// index projects
		$this->solrConnection->deleteByType(self::ITEM_TYPE_PROJECT);
		$projects = $this->getRedmineProjects();
		$successfullyIndexed = $this->indexProjects($projects);
		$this->solrConnection->commit();

		return $successfullyIndexed;
	}

	/**
	 * Indexes Redmine projects
	 *
	 * @param	array	$projects An array of Redmine project objects
	 * @return	boolean	TRUE if projects are successfully indexed, FALSE otherwise
	 */
	protected function indexProjects(array $projects) {
		$projectDocuments = array();
		$projectsIndexed = FALSE;

		foreach ($projects as $project) {
			$projectDocuments[] = $this->projectToDocument($project);
		}

		try {
			$response = $this->solrConnection->addDocuments($projectDocuments);
			if ($response->getHttpStatus() == 200) {
				$projectsIndexed = TRUE;
			}
		} catch (Exception $e) {
			foreach ($projectDocuments as $index => $projectDocument) {
				$projectDocuments[$index] = (array) $projectDocument;
			}

			t3lib_div::devLog(
				'Failed to index Redmine projects',
				'solr_redmine',
				3,
				array(
					'documents' => $projectDocuments,
					'response' => (array) $response
				)
			);
		}

		return $projectsIndexed;
	}

	/**
	 * Creates a Solr document for a project
	 *
	 * @param	stdClass	$project A Redmine project
	 * @return	Apache_Solr_Document	The Solr document representation for the project
	 */
	protected function projectToDocument($project) {
		$document = t3lib_div::makeInstance('Apache_Solr_Document');

			// system fields
		$document->setField('id',
			tx_solr_Util::getDocumentId(
				self::ITEM_TYPE_PROJECT,
				$this->solrServer['rootPageUid'],
				$project->id
			)
		);
		$document->setField('appKey',   'EXT:solr_redmine');
		$document->setField('type',     self::ITEM_TYPE_PROJECT);
		$document->setField('siteHash', tx_solr_Util::getSiteHash($this->solrServer['rootPageUid']));

			// content fields
		$document->setField('uid',     $project->id);
		$document->setField('pid',     $this->solrServer['rootPageUid']);
		$document->setField('title',   $project->name);
		$document->setField('content', $project->description);
		$document->setField('created', $this->getIsoDateFromProjectDate($project->created_on));
		$document->setField('changed', $this->getIsoDateFromProjectDate($project->updated_on));
		$document->setField('url',     $this->redmineServer . 'projects/' . $project->identifier);

		$document->setField('identifier_stringS', $project->identifier);

			// typo3.org specific fields

			// FIXME These are #t3o specific fields,
			// for general purpose use add a hook to provide additional fields
		$document->setField('site',             'http://forge.typo3.org');
		$document->setField('siteName_stringS', 'forge_t3o');
		$document->setField('language',         0);
		if (t3lib_div::isFirstPartOfStr($project->identifier, 'extension-')) {
			$extensionKey = substr($project->identifier, 10);
			$document->setField('extensionKey_stringS', $extensionKey);
		}

		return $document;
	}

	/**
	 * Uses Redmine's REST API to retrieve the list of projects.
	 *
	 * NOTE: Due to the stupidness of ordering projects alphabetically by name
	 * we currently have to do full imports everytime. we could do incremental
	 * imports instead if projects were ordered by id.
	 *
	 * @return	void
	 */
	protected function getRedmineProjects() {
		$registry = t3lib_div::makeInstance('t3lib_Registry');

			// no need since projects are ordered alphabetically we can't really detect new ones anyway
#		$lastIndexedProjectOffset = $registry->get('tx_solrredmine', 'lastIndexedProjectOffset', 0);

		$redmineProjectsApiUrl  = $this->redmineServer . 'projects.json';
		$redmineProjectsApiUrl .= '?limit=' .$this->documentsToIndexLimit;
#		$redmineProjectsApiUrl .= '&offset=' . $lastIndexedProjectOffset;

		$errors = array();
		$redmineProjectsJson = t3lib_div::getURL($redmineProjectsApiUrl, FALSE, array(TYPO3_user_agent), $errors);

		if ($errors['error'] || empty($redmineProjectsJson)) {
			t3lib_div::devLog('Failed to retrieve Redmine project data', 'solr_redmine', 3, $errors);
		}

		$redmineProjectsJson = json_decode($redmineProjectsJson);
		$redmineProjects     = $redmineProjectsJson->projects;

		return $redmineProjects;
	}

	/**
	 * Takes the date as formated in the Redmine JSON data and turns it into
	 * an ISO Date.
	 *
	 * @param unknown_type $projectDate
	 */
	protected function getIsoDateFromProjectDate($projectDate) {
		$date = DateTime::createFromFormat('Y/m/d G:i:s O', $projectDate);

		return $date->format('Y-m-d\TH:i:s\Z');
	}


		// getters, setters


	public function getRedmineServer() {
		return $this->redmineServer;
	}

	public function setRedmineServer($redmineServer) {
		$redmineServer = filter_var($redmineServer, FILTER_SANITIZE_URL);

			// force trailing slash /
		if (substr($redmineServer, -1) != '/') {
			$redmineServer .= '/';
		}

		$this->redmineServer = $redmineServer;
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
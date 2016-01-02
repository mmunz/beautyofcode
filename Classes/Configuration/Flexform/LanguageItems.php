<?php
namespace TYPO3\Beautyofcode\Configuration\Flexform;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\Beautyofcode\Highlighter\ConfigurationInterface;
use TYPO3\Beautyofcode\Service\SettingsService;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Function to add select options dynamically (loaded in flexform)
 *
 * @author Felix Nagel <info@felixnagel.com>
 * @author Thomas Juhnke <typo3@van-tomas.de>
 * @package \TYPO3\Beautyofcode\Configuration\Flexform
 */
class LanguageItems {

	/**
	 * ObjectManager
	 *
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * PageRepository
	 *
	 * @var \TYPO3\CMS\Frontend\Page\PageRepository
	 */
	protected $pageRepository;

	/**
	 * TemplateSerice
	 *
	 * @var \TYPO3\CMS\Core\TypoScript\TemplateService
	 */
	protected $templateService;

	/**
	 * SettingsService
	 *
	 * @var SettingsService
	 */
	protected $settingsService;

	/**
	 * ConfigurationInterface
	 *
	 * @var \TYPO3\Beautyofcode\Highlighter\ConfigurationInterface
	 */
	protected $highlighterConfiguration;

	/**
	 * Page uid (PID) for TypoScript generation
	 *
	 * Fallback to root PID (0)
	 *
	 * @var int
	 */
	protected $contentElementPid = 0;

	/**
	 * InjectObjectManager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager ObjectManagerInterface
	 *
	 * @return void
	 */
	public function injectObjectManager(ObjectManagerInterface $objectManager = NULL) {
		if (is_null($objectManager)) {
			$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		}

		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the page repository
	 *
	 * @param \TYPO3\CMS\Frontend\Page\PageRepository $pageRepository PageRepository
	 *
	 * @return void
	 */
	public function injectPageRepository(PageRepository $pageRepository = NULL) {
		if (is_null($pageRepository)) {
			$pageRepository = $this->objectManager->get('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		}

		$this->pageRepository = $pageRepository;
	}

	/**
	 * Injects the template service
	 *
	 * @param \TYPO3\CMS\Core\TypoScript\TemplateService $templateService TemplateService
	 *
	 * @return void
	 */
	public function injectTemplateService(TemplateService $templateService = NULL) {
		if (is_null($templateService)) {
			$templateService = $this->objectManager->get('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
		}

		$this->templateService = $templateService;
	}

	/**
	 * Injects the highlighter configuration
	 *
	 * @param ConfigurationInterface $configuration
	 *
	 * @return void
	 */
	public function injectHighlighterConfiguration(ConfigurationInterface $configuration) {
		if (is_null($configuration)) {
			$configuration = $this->objectManager->get('TYPO3\\Beautyofcode\\Highlighter\\ConfigurationInterface');
		}

		$this->highlighterConfiguration = $configuration;
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public function initialize() {
		$this->injectObjectManager($this->objectManager);
		$this->injectPageRepository($this->pageRepository);
		$this->injectTemplateService($this->templateService);
		$this->injectHighlighterConfiguration($this->highlighterConfiguration);
	}

	/**
	 * This function is called from the flexform and
	 * adds available programming languages to the select options
	 *
	 * @param array $config flexform data
	 *
	 * @return array
	 */
	public function getConfiguredLanguages($config) {
		$this->initialize();

		static $cachedFields = 0;

		if ($cachedFields != 0) {
			$config['items'] = $cachedFields;
		} else {

			// make brushes list to flexform selectbox item array
			$optionList = array();

			if (isset($config['row']['pid']) && is_numeric($config['row']['pid'])) {
				$this->contentElementPid = (int) $config['row']['pid'];
			}

			if ($this->contentElementPid === 0 && isset($config['row']['uid']) && is_numeric($config['row']['uid'])) {
				$this->contentElementPid = $this->getPageUidByRecordUid($config['row']['uid']);
			}

			$brushesArray = $this->getUniqueAndSortedBrushes();

			foreach ($brushesArray as $i => $brush) {
				if (strtolower($brush) === 'plain') {
					continue;
				}
				// skip unknown brushes
				if (!$this->highlighterConfiguration->hasBrushIdentifier($brush)) {
					continue;
				}

				$optionList[$i] = array_reverse(
					$this->highlighterConfiguration->getBrushIdentifierAliasAndLabel($brush)
				);
			}

			$config['items'] = array_merge($config['items'], $optionList);
		}
		$cachedFields = $config['items'];

		return $config;
	}

	/**
	 * Returns the page uid by given record uid
	 *
	 * @param int $recordUid Record uid
	 *
	 * @return int
	 */
	private function getPageUidByRecordUid($recordUid) {
		$recordPid = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
			'pid', 'tt_content', 'uid = ' . $recordUid
		);

		return (int) $recordPid['pid'];
	}

	/**
	 * Returns the global DatabaseConnection instance
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	private function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Returns unique and sorted brushes
	 *
	 * @return array
	 */
	protected function getUniqueAndSortedBrushes() {
		$brushesArray = GeneralUtility::trimExplode(',', $this->getBrushesConfig(), TRUE);

		// make unique
		foreach ($brushesArray as &$value) {
			$value = serialize($value);
		}

		$brushesArray = array_unique($brushesArray);

		foreach ($brushesArray as &$value) {
			$value = unserialize($value);
		}

		// sort a-z
		sort($brushesArray);

		return $brushesArray;
	}

	/**
	 * Get brushes TS config per page
	 *
	 * @return array
	 */
	protected function getBrushesConfig() {
		return $this->getSettingsService($this->contentElementPid)->getTypoScriptByPath('brushes');
	}

	/**
	 * Get the settings service
	 *
	 * @todo Add caching per PID?
	 *
	 * @param int $pid PID of the page
	 *
	 * @return \TYPO3\Beautyofcode\Service\SettingsService
	 */
	public function getSettingsService($pid = 0) {
		return $this->objectManager->get('TYPO3\\Beautyofcode\\Service\\SettingsService', $pid);
	}
}

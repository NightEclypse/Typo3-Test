<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2011 Ingo Renner <ingo@typo3.org>
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
 * This cache factory takes care of instantiating a cache frontend and injecting
 * a certain cache backend. After creation of the new cache, the cache object
 * is registered at the cache manager.
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @author Robert Lemke <robert@typo3.org>
 * @scope singleton
 * @api
 */
class t3lib_cache_Factory implements t3lib_Singleton {

	/**
	 * The current FLOW3 context ("production", "development" etc.)
	 *
	 * TYPO3 v4 note: This variable is always set to "production"
	 * in TYPO3 v4 and only kept in v4 to keep v4 and FLOW3 in sync.
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * A reference to the cache manager
	 *
	 * @var t3lib_cache_Manager
	 */
	protected $cacheManager;

	/**
	 * Constructs this cache factory
	 *
	 * @param string $context The current FLOW3 context
	 * @param t3lib_cache_Manager $cacheManager The cache manager
	 */
	public function __construct($context, t3lib_cache_Manager $cacheManager) {
		$this->context = $context;
		$this->cacheManager = $cacheManager;
		$this->cacheManager->injectCacheFactory($this);
	}

	/**
	 * Factory method which creates the specified cache along with the specified kind of backend.
	 * After creating the cache, it will be registered at the cache manager.
	 *
	 * @param string $cacheIdentifier The name / identifier of the cache to create
	 * @param string $cacheObjectName Object name of the cache frontend
	 * @param string $backendObjectName Object name of the cache backend
	 * @param array $backendOptions (optional) Array of backend options
	 * @return t3lib_cache_frontend_Frontend The created cache frontend
	 * @throws t3lib_cache_exception_InvalidBackend if the cache backend is not valid
	 * @throws t3lib_cache_exception_InvalidCache if the cache frontend is not valid
	 * @api
	 */
	public function create($cacheIdentifier, $cacheObjectName, $backendObjectName, array $backendOptions = array()) {
		$backend = t3lib_div::makeInstance($backendObjectName, $this->context, $backendOptions);
		if (!$backend instanceof t3lib_cache_backend_Backend) {
			throw new t3lib_cache_exception_InvalidBackend(
				'"' . $backendObjectName . '" is not a valid cache backend object.',
				1216304301
			);
		}
		if (is_callable(array($backend, 'initializeObject'))) {
			$backend->initializeObject();
		}

		$cache = t3lib_div::makeInstance($cacheObjectName, $cacheIdentifier, $backend);
		if (!$cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_exception_InvalidCache(
				'"' . $cacheObjectName . '" is not a valid cache frontend object.',
				1216304300
			);
		}
		if (is_callable(array($cache, 'initializeObject'))) {
			$cache->initializeObject();
		}

		$this->cacheManager->registerCache($cache);
		return $cache;
	}
}
?>
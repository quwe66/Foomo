<?php

/*
 * bestbytes-copyright-placeholder
 */

namespace Foomo;

/**
 * translate stuff
 * @internal
 * @todo implement plural, dual etc.
 */
class Translation {

	protected $_table = array();
	protected $localeChain;
	protected static $_DEFAULT_LOCALE_CHAIN;

	/**
	 *
	 *
	 * @param string[] $localeRoots an array of folders where to look for localization resources
	 * @param string $namespace name of the resource /root/de_DE/<resourceName>.yml
	 * @param string[] $localeChain your language preferences
	 *
	 */
	public function __construct($localeRoots, $namespace, $localeChain = null)
	{
		if (is_null($localeChain)) {
			if (!isset(self::$_DEFAULT_LOCALE_CHAIN)) {
				// do not pull the default chain into getDefaultChainFromEnv(), because that will break testablity
				self::$_DEFAULT_LOCALE_CHAIN = self::getDefaultChainFromEnv();
			}
			$localeChain = self::$_DEFAULT_LOCALE_CHAIN;
		}
		$this->localeChain = $localeChain;
		//$this->_table = $this->getLocaleTable($localeRoots, $localeChain, $resourceName);
		//$this->_table = self::cachedGetLocaleTable($localeRoots, $localeChain, $namespace);
		$this->_table = \Foomo\Cache\Proxy::call(__CLASS__, 'cachedGetLocaleTable', array($localeRoots, $localeChain, $namespace));
	}

	public static function setDefaultLocaleChain($localeChain)
	{
		self::$_DEFAULT_LOCALE_CHAIN = $localeChain;
	}

	public static function getDefaultLocaleChain()
	{
		return self::$_DEFAULT_LOCALE_CHAIN;
	}

	/**
	 * get the default localeChain from the environment
	 *
	 * @todo implement locale detection for the command line
	 * @todo find a good configurable solution for the fallbacks
	 *
	 * @return string[] array of locales
	 */
	public static function getDefaultChainFromEnv()
	{
		$fallbacks = array('en', 'de');
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$parts = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$localeParts = explode(',', $parts[0]);
			$localeChain = array();
			foreach ($localeParts as $part) {
				$part = trim($part);
				$dashPos = strpos($part, '-');
				if ($dashPos !== false) {
					$firstPart = substr($part, 0, $dashPos);
					$part = $firstPart . '_' . strtoupper(substr($part, $dashPos + 1));
					$localeChain[] = $part;
					$localeChain[] = $firstPart;
				} else {
					$localeChain[] = $part;
				}
			}
			foreach ($fallbacks as $fallback) {
				if (!in_array($fallback, $localeChain)) {
					$localeChain[] = $fallback;
				}
			}
			return $localeChain;
		} else {
			return $fallbacks;
		}
	}

	public function _($msgId, $count = null)
	{
		if (is_array($msgId)) {
			$msgId = array_reverse($msgId);
			foreach ($msgId as $id => $minCount) {
				if ($count >= $minCount) {
					$msgId = $id;
					break;
				}
			}
		}
		return isset($this->_table[$msgId]) ? $this->_table[$msgId] : $msgId;
	}

	public static function getMessage($localeRoots, $resourceName, $localeChain, $msgId, $msgIdPlural = null, $count = null)
	{
		$locale = new self($localeRoots, $resourceName, $localeChain);
		return $locale->_($msgId, $msgIdPlural, $count);
	}

	/**
	 *
	 *
	 * @internal
	 *
	 * @Foomo\Cache\CacheResourceDescription
	 *
	 * @param array $localeRoots
	 * @param array $localeChain
	 * @param string $namespace
	 *
	 * @return array
	 */
	public static function cachedGetLocaleTable($localeRoots, $localeChain, $namespace)
	{
		$ret = array();
		$localeRoots = array_reverse($localeRoots);
		$localeChain = array_reverse($localeChain);
		foreach ($localeRoots as $localeRoot) {
			foreach ($localeChain as $locale) {
				$fileName = self::getResourceFileName($localeRoot, $locale, $namespace);
				if (file_exists($fileName)) {
					$ret = array_merge($ret, \Foomo\Yaml::parse(file_get_contents($fileName)));
				}
			}
		}
		return $ret;
	}
	/*
	public function setLocaleTable($localeRoot, $locale, $namespace, $table = array())
	{
		$fileName = self::getResourceFileName($localeRoot, $locale, $namespace);
		$dirName = dirname($fileName);
		if (!file_exists($dirName)) {
			mkdir($dirName);
		}
		$yaml = \Foomo\Yaml::dump($able);
		file_put_contents($fileName, $yaml);
	}
	*/
	private static function getResourceFileName($localeRoot, $locale, $namespace)
	{
		$fileName = $localeRoot . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR . $locale. '.yml';
		return $fileName;
	}
	/**
	 * get a translation for a module
	 * 
	 * @param string $moduleName
	 * @param string $namespace
	 * @param string $localeChain
	 * 
	 * @return Foomo\Translation
	 */
	public static function getModuleTranslation($moduleName, $namespace, $localeChain = null)
	{
		$rootBase = \Foomo\CORE_CONFIG_DIR_MODULES;
		return new self(
			array($rootBase . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'locale'),
			$namespace,
			$localeChain
		);
	}
}

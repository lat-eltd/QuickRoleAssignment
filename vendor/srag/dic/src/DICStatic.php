<?php

namespace srag\DIC\QuickRoleAssignment;

use ilLogLevel;
use ilPlugin;
use League\Flysystem\PluginInterface;
use srag\DIC\QuickRoleAssignment\DIC\DICInterface;
use srag\DIC\QuickRoleAssignment\DIC\LegacyDIC;
use srag\DIC\QuickRoleAssignment\DIC\NewDIC;
use srag\DIC\QuickRoleAssignment\Exception\DICException;
use srag\DIC\QuickRoleAssignment\Plugin\Plugin;
use srag\DIC\QuickRoleAssignment\Version\Version;
use srag\DIC\QuickRoleAssignment\Version\VersionInterface;

/**
 * Class DICStatic
 *
 * @package srag\DIC\QuickRoleAssignment
 *
 * @author  studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
final class DICStatic implements DICStaticInterface {

	/**
	 * @var DICInterface|null
	 */
	private static $dic = NULL;
	/**
	 * @var PluginInterface[]
	 */
	private static $plugins = [];
	/**
	 * @var VersionInterface|null
	 */
	private static $version = NULL;


	/**
	 * @inheritdoc
	 */
	public static function dic()/*: DICInterface*/ {
		if (self::$dic === NULL) {
			if (self::version()->is52()) {
				global $DIC;
				self::$dic = new NewDIC($DIC);
			} else {
				global $GLOBALS;
				self::$dic = new LegacyDIC($GLOBALS);
			}
		}

		return self::$dic;
	}


	/**
	 * @inheritdoc
	 */
	public static function plugin(/*string*/
		$plugin_class_name)/*: PluginInterface*/ {
		if (!isset(self::$plugins[$plugin_class_name])) {
			if (!class_exists($plugin_class_name)) {
				throw new DICException("Class $plugin_class_name not exists!");
			}

			if (method_exists($plugin_class_name, "getInstance")) {
				$plugin_object = $plugin_class_name::getInstance();
			} else {
				$plugin_object = new $plugin_class_name();

				self::dic()->log()->write("DICLog: Please implement $plugin_class_name::getInstance()!", ilLogLevel::DEBUG);
			}

			if (!$plugin_object instanceof ilPlugin) {
				throw new DICException("Class $plugin_class_name not extends ilPlugin!");
			}

			self::$plugins[$plugin_class_name] = new Plugin($plugin_object);
		}

		return self::$plugins[$plugin_class_name];
	}


	/**
	 * @inheritdoc
	 */
	public static function version()/*: VersionInterface*/ {
		if (self::$version === NULL) {
			self::$version = new Version();
		}

		return self::$version;
	}


	/**
	 * DICStatic constructor
	 */
	private function __construct() {

	}
}

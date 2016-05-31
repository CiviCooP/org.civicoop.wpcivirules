<?php

/**
 * Class CRM_WPCivi_Config
 * Contains general configuration and shared options/checks
 */
class CRM_WPCivi_Config
{
    /**
     * @var \CRM_Core_Config Cached CRM_Core_Config object
     */
    private static $civiconfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private static $logger;

    /**
     * Get and cache CiviCRM config object
     * @return \CRM_Core_Config Cached CRM_Core_Config object
     */
    public static function getCiviConfig()
    {
        if(empty(static::$civiconfig)) {
            static::$civiconfig = CRM_Core_Config::singleton();
        }
        return static::$civiconfig;
    }

    /**
     * Get and cache CiviRules database logger
     * @return \Psr\Log\LoggerInterface Logger Interface
     */
    public static function getLogger() {
        if(empty(static::$logger)) {
            static::$logger = new \CRM_Civiruleslogger_DatabaseLogger;
        }
        return static::$logger;
    }

    /**
     * Check if we're running CiviCRM on WordPress
     * @return bool Is WordPress?
     */
    public static function isWordPress() {
        $civiconfig = static::getCiviConfig();
        return ($civiconfig->userFramework == 'WordPress');
    }
}
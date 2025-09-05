<?php

require_once 'wpcivirules.civix.php';

/**
 * Implements hook_civicrm_tokens().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_tokens
 * @param array $tokens
 */
function wpcivirules_civicrm_tokens(&$tokens) {
  \CRM_WPCivi_CiviRulesActions_WPCreateUserTokens::addTokens($tokens);
}

/**
 * Implements hook_civicrm_tokenValues().
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_tokenValues
 * @param array $values
 * @param array $cids
 * @param mixed $job
 * @param array $tokens
 * @param mixed $context
 */
function wpcivirules_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = [], $context = null) {
  \CRM_WPCivi_CiviRulesActions_WPCreateUserTokens::addTokenValues($values, $cids, $tokens);
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function wpcivirules_civicrm_config(&$config) {
  _wpcivirules_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function wpcivirules_civicrm_install() {
  _wpcivirules_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function wpcivirules_civicrm_enable() {
  _wpcivirules_civix_civicrm_enable();
}

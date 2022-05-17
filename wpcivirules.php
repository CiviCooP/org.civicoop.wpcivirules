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
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function wpcivirules_civicrm_uninstall() {
  _wpcivirules_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function wpcivirules_civicrm_enable() {
  _wpcivirules_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function wpcivirules_civicrm_disable() {
  _wpcivirules_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function wpcivirules_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _wpcivirules_civix_civicrm_upgrade($op, $queue);
}


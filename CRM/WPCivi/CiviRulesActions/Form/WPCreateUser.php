<?php

require_once 'CRM/Core/Form.php';

/**
 * Class CRM_WPCivi_CiviRulesActions_Form_WPCreateUser
 * Form controller class to set options for the WPCreateUser CiviRules action.
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/How+to+Create+Your+Own+Action
 */
class CRM_WPCivi_CiviRulesActions_Form_WPCreateUser extends \CRM_CivirulesActions_Form_Form
{

  /**
   * Build form
   */
  public function buildQuickForm()
  {
    $this->add('hidden', 'rule_action_id');

    $this->add('select', 'wp_role', 'Assign WordPress Role', $this->getWPRoleOptions(), true);
    $this->addButtons([['type' => 'submit', 'name' => ts('Submit'), 'isDefault' => true ]]);

    parent::buildQuickForm();
  }

  /**
   * Save form submission
   */
  public function postProcess()
  {
    $values = $this->exportValues();
    foreach($values as $k => $v) {
      if(!in_array($k, ['wp_role'])) {
        unset($values[$k]);
      }
    }

    $this->ruleAction->action_params = serialize($values);
    $this->ruleAction->save();

    parent::postProcess();
  }

  /**
   * Get an array of available WordPress Roles
   * @return array Roles
   */
  private function getWPRoleOptions()
  {
    $editable_roles = array_reverse(get_editable_roles());
    $options = [];

    foreach ( $editable_roles as $role => $details ) {
      $options[$role] = $details['name'];
    }

    return $options;
  }

  /**
   * Set default values from rule action params
   * @return array $defaultValues
   * @access public
   */
  public function setDefaultValues() {
    $defaultValues = parent::setDefaultValues();
    $data = unserialize($this->ruleAction->action_params);
    if (!empty($data['wp_role'])) {
      $defaultValues['wp_role'] = $data['wp_role'];
    }
    return $defaultValues;
  }

}

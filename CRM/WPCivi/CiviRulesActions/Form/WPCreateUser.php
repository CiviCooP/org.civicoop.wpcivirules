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
   * @var array Array of field names used in checks below
   */
  private $_myFields = ['wp_role', 'activity_contact_id', 'activity_type_id'];

  /**
   * Build form
   */
  public function buildQuickForm()
  {
    $this->add('select', 'wp_role', ts('Assign WordPress Role'), $this->getWPRoleOptions(), true);

    $this->addEntityRef('activity_type_id', ts('Activity Type'),
        ['entity' => 'option_value',
         'api' => ['params' => ['option_group_id' => 'activity_type']],
         'select' => ['minimumInputLength' => 0],
        ]);
    $this->addEntityRef('activity_contact_id', ts('Activity Source Contact'));

    $this->add('hidden', 'rule_action_id');
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
      if(!in_array($k, $this->_myFields)) {
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

    foreach ($editable_roles as $role => $details) {
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

    foreach($this->_myFields as $field) {
      if (!empty($data[$field])) {
        $defaultValues[$field] = $data[$field];
      }
    }
    return $defaultValues;
  }

}

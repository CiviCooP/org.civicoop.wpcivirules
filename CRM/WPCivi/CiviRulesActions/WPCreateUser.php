<?php

/**
 * Class CRM_WPCivi_CiviRulesActions_WPCreateUser (...namespaces are overrated)
 * CiviRules action to create a WordPress user account for a contact, if it doesn't exist yet.
 */
class CRM_WPCivi_CiviRulesActions_WPCreateUser extends \CRM_Civirules_Action
{

    /**
     * Method to execute the action when it is triggered
     * @param \CRM_Civirules_TriggerData_TriggerData $triggerData
     * @return bool Success
     */
    public function processAction(\CRM_Civirules_TriggerData_TriggerData $triggerData)
    {
        $logger = \CRM_WPCivi_Config::getLogger(); // Hmm, we could have used $this->logAction... oh well
        $ruleId = $this->ruleAction['rule_id'];

        if(!\CRM_WPCivi_Config::isWordPress()) {
            $logger->error('WPCreateUser: could not execute action, not running on WordPress!');
            return false;
        }

        $contactId = $triggerData->getContactId();
        $actionParams = $this->getActionParameters(); // contains [wp_role]
        // $groupData = $triggerData->getEntityData('GroupContact'); // [group_id|contact_id|status] - not needed here

        // Check for existing WP user
        try {
            $ufmatch = civicrm_api3('UFMatch', 'getsingle', ['contact_id' => $contactId]);
            if($ufmatch && isset($ufmatch['uf_id'])) {
                $logger->info('WPCreateUser: no user created, contact already linked with WP user ' . $ufmatch['uf_id'] . ' (' . $ufmatch['uf_name'] . ')', ['contact_id' => $contactId, 'rule_id' => $ruleId]);
                return true;
            }
        } catch(\CiviCRM_API3_Exception $e) {
            // No match, continuing
        }

        // Create new WP user - TODO discuss what's the best approach here (passwords, welcome mail, etc)

        // Fetch contact data
        try {
            $contact = civicrm_api3('Contact', 'getsingle', ['id' => $contactId]);

            if(!$contact || empty($contact['email'])) {
                $logger->warning('WPCreateUser: contact has no primary email address, action cancelled.', ['contact_id' => $contactId, 'rule_id' => $ruleId]);
                return false;
            }
        } catch(\CiviCRM_API3_Exception $e) {
            $logger->warning('WPCreateUser: could not fetch contact record (' . $e->getMessage() . ').', ['contact_id' => $contactId, 'rule_id' => $ruleId]);
            return false;
        }

        // Check if username (email) exists
        if(username_exists($contact['email']) != false) {
            $logger->warning('WPCreateUser: a WP account with email address ' . $contact['email'] . ' already exists, but it is not matched to contact.', ['contact_id' => $contactId, 'rule_id' => $ruleId]);
            return false;
        }

        // Try to create user and set name, email, role
        $userParams = [
            'user_login' => $contact['email'],
            'user_pass' => md5(mt_rand()),
            'user_email' => $contact['email'],
            'first_name' => $contact['first_name'],
            'last_name' => $contact['last_name'],
            'display_name' => $contact['display_name'],
            'role' => $actionParams['wp_role'],
        ];
        $uf_id = wp_insert_user($userParams);

        if(empty($uf_id) || $uf_id instanceof \WP_Error) {
            $logger->warning('WPCreateUser: calling wp_insert_user failed for contact ' . $contactId . ' (parameters: ' . json_encode($userParams) . ').', ['contact_id' => $contactId, 'rule_id' => $ruleId]);
            return false;
        }

        // Create UFMatch record (double check if this is not done automatically)
        try {
            $ufmatch = civicrm_api3('UFMatch', 'create', [
                'contact_id' => $contactId,
                'uf_id' => $uf_id,
                'uf_name' => $contact['email'],
            ]);
        } catch(\CiviCRM_API3_Exception $e) {
            $logger->warning('WPCreateUser: WordPress account was created, but creating UFMatch record failed for contact_id ' . $contactId . ' and uf_id ' . $uf_id . ' (' . $e->getMessage() . ').',
                ['contact_id' => $contactId, 'rule_id' => $ruleId]);
            return false;
        }

        // That should be all for now
        $logger->info('WPCreateUser: created WP user ' . $uf_id . ' (' . $contact['email'] . ') with role "' . ucfirst($actionParams['wp_role']) . '".', ['contact_id' => $contactId, 'rule_id' => $ruleId]);
        return true;
    }

    /**
     * Method to return the url for additional form processing for action (return false if none is needed)
     * @param int $ruleActionId
     * @return string|bool
     */
    public function getExtraDataInputUrl($ruleActionId)
    {
        return CRM_Utils_System::url('civicrm/civirule/form/action/wpcreateuser', 'rule_action_id='.$ruleActionId);
    }

    /**
     * Returns a user friendly text explaining the action parameters
     * @return string
     */
    public function userFriendlyConditionParams() {
        $params = $this->getActionParameters();
        $label = ts('Create WordPress account');
        if(isset($params['wp_role'])) {
            $label .= ' with role: "' . ucfirst($params['wp_role']) . '"';
        }
        return $label;
    }
}
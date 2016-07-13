<?php

use Psr\Log\LogLevel;

/**
 * Class CRM_WPCivi_CiviRulesActions_WPCreateUser (...namespaces are overrated)
 * CiviRules action to create a WordPress user account for a contact, if it doesn't exist yet.
 */
class CRM_WPCivi_CiviRulesActions_WPCreateUser extends \CRM_Civirules_Action
{

    /**
     * Possible action status results. Used for logging and activity creation (see the logWPAction method).
     */
    const ACTION_COMPLETED = 'Completed';
    const ACTION_CANCELLED = 'Cancelled';
    const ACTION_NOT_REQUIRED = 'Not Required';

    /**
     * @var \CRM_Civirules_TriggerData_TriggerData $triggerData Trigger data (made available to all methods)
     */
    private $triggerData;

    /**
     * Method to execute the action when it is triggered
     * @param \CRM_Civirules_TriggerData_TriggerData $triggerData
     * @return bool Success
     */
    public function processAction(\CRM_Civirules_TriggerData_TriggerData $triggerData)
    {
        $this->triggerData = $triggerData;
        $ruleId = $this->ruleAction['rule_id'];

        if(!\CRM_WPCivi_Config::isWordPress()) {
            $this->logWPAction('WPCreateUser: could not execute action, not running on WordPress!', 'error');
            return false;
        }

        $contactId = $triggerData->getContactId();
        $actionParams = $this->getActionParameters(); // contains [wp_role]
        // $groupData = $triggerData->getEntityData('GroupContact'); // [group_id|contact_id|status] - not needed here

        // ---------------------
        // Check for existing WP user
        try {
            $ufmatch = civicrm_api3('UFMatch', 'getsingle', ['contact_id' => $contactId]);
            if($ufmatch && isset($ufmatch['uf_id'])) {
                $this->logWPAction('WPCreateUser: no user created, contact already linked with WP user ' . $ufmatch['uf_id'] . ' (' . $ufmatch['uf_name'] . ')', LogLevel::INFO, static::ACTION_NOT_REQUIRED);
                return true;
            }
        } catch(\CiviCRM_API3_Exception $e) {
            // No match, continuing
        }

        // ---------------------
        // Create new WP user

        // Fetch contact data
        try {
            $contact = civicrm_api3('Contact', 'getsingle', ['id' => $contactId]);

            if(!$contact || empty($contact['email'])) {
                return $this->logWPAction('WPCreateUser: contact has no primary email address, action cancelled.', LogLevel::WARNING, static::ACTION_CANCELLED);
            }
        } catch(\CiviCRM_API3_Exception $e) {
            return $this->logWPAction('WPCreateUser: could not fetch contact record (' . $e->getMessage() . ').', LogLevel::WARNING, static::ACTION_CANCELLED);
        }

        // Check if username (email) exists
        if(username_exists($contact['email']) != false) {
            return $this->logWPAction('WPCreateUser: a WP account with email address ' . $contact['email'] . ' already exists, but it is not linked with this contact.', LogLevel::WARNING, static::ACTION_CANCELLED);
        }

        // Try to create WP user and set name, email, role
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
            return $this->logWPAction('WPCreateUser: calling wp_insert_user failed for contact ' . $contactId . ' (parameters: ' . json_encode($userParams) . ').', LogLevel::ERROR, static::ACTION_CANCELLED);
        }

        // Create UFMatch record (double check if this is not done automatically)
        try {
            $ufmatch = civicrm_api3('UFMatch', 'create', [
                'contact_id' => $contactId,
                'uf_id' => $uf_id,
                'uf_name' => $contact['email'],
            ]);
        } catch(\CiviCRM_API3_Exception $e) {
            return $this->logWPAction('WPCreateUser: WordPress account was created, but creating UFMatch record failed for contact_id ' . $contactId . ' and uf_id ' . $uf_id . ' (' . $e->getMessage() . ').', LogLevel::WARNING, static::ACTION_CANCELLED);
        }

        // That should be all for now
        return $this->logWPAction('WPCreateUser: created WP user ' . $uf_id . ' (' . $contact['email'] . ') with role "' . ucfirst($actionParams['wp_role']) . '".', LogLevel::INFO, static::ACTION_COMPLETED);
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

    /**
     * Log an action using the internal logAction method (database logging should be enabled),
     * and try to create an activity based on the result of running this action.
     * @param string $message Log message
     * @param string $logLevel Log level (constant from \Psr\Log\LogLevel)
     * @param string $actionResult Action result (constant from this class)
     * @return bool Success
     */
    public function logWPAction($message, $logLevel = LogLevel::INFO, $actionResult = self::ACTION_CANCELLED)
    {
        // Log to CiviRulesLogger
        $this->logAction($message, $this->triggerData, $logLevel);

        // Create activity for contact, if possible
        // (-> activity type and activity source contact currently configurable, could probably just as well be automatically added)
        $params = $this->getActionParameters();
        if(isset($params['activity_type_id']) && isset($params['activity_contact_id'])) {
            try {
                switch ($actionResult) {
                    case self::ACTION_COMPLETED:
                        $subject = 'Succesfully created a WordPress account';
                        break;
                    case self::ACTION_NOT_REQUIRED:
                        $subject = 'Contact already has a WordPress account';
                        break;
                    case self::ACTION_CANCELLED:
                        $subject = 'An error occurred creating a WordPress account';
                        break;
                    default:
                        $subject = 'Unknown action result for WPCreateUser';
                        break;
                }

                // Ah, when triggered in the background we need a source_contact_id...
                $session = \CRM_Core_Session::singleton();
                $sourceContactId = $session->getLoggedInContactID();
                if(!$sourceContactId && isset($params['activity_contact_id'])) {
                    $sourceContactId = $params['activity_contact_id'];
                } else {
                    throw new \CiviCRM_API3_Exception('WPCreateUser: running in the background, activity type is configured but activity source contact isn\'t.', 500);
                }

                // Create activity
                civicrm_api3('Activity', 'create', [
                    'activity_type_id'  => $params['activity_type_id'],
                    'status_id'         => $actionResult,
                    'source_contact_id' => $sourceContactId,
                    'target_id'         => $this->triggerData->getContactId(),
                    'subject'           => $subject,
                    'details'           => $message,
                ]);

            } catch (\CiviCRM_API3_Exception $e) {
                if ($actionResult == self::ACTION_CANCELLED) {
                    $this->logAction('WPCreateUser: an error occurred while creating an activity for an error that occurred (' . $e->getMessage() . ').', $this->triggerData, LogLevel::WARNING);
                } else {
                    $this->logAction('WPCreateUser: action has succesfully run, but an error occurred while creating an activity (' . $e->getMessage() . ').', $this->triggerData, LogLevel::WARNING);
                }
            }
        }

        return ($actionResult != self::ACTION_CANCELLED);
    }
}
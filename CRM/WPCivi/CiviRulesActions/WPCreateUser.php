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
        $contactId = $triggerData->getContactId();
        $actionParams = $this->getActionParameters(); // should contain [wp_role, activity_type_id]
        // $ruleId = $this->ruleAction['rule_id'];

        if (!\CRM_WPCivi_Config::isWordPress()) {
            return $this->logWPAction('WPCreateUser: could not execute action, not running on WordPress!', LogLevel::ERROR, self::ACTION_CANCELLED);
        }

        // Fetch contact data
        try {
            $contact = civicrm_api3('Contact', 'getsingle', ['id' => $contactId]);

            if (!$contact || empty($contact['email'])) {
                return $this->logWPAction('WPCreateUser: contact has no primary email address, action cancelled.', LogLevel::WARNING, self::ACTION_CANCELLED);
            }
        } catch (\CiviCRM_API3_Exception $e) {
            return $this->logWPAction('WPCreateUser: could not fetch contact record (' . $e->getMessage() . ').', LogLevel::WARNING, self::ACTION_CANCELLED);
        }

        // Check for existing WP user
        try {
            $ufmatch = civicrm_api3('UFMatch', 'getsingle', ['contact_id' => $contactId]);
            if ($ufmatch && !empty($ufmatch['uf_id'])) {
                return $this->logWPAction("WPCreateUser: no user created, contact already linked with WP user {$ufmatch['uf_id']} ({$ufmatch['uf_name']}).", LogLevel::WARNING, self::ACTION_NOT_REQUIRED);
            }

            $ufmatch2 = civicrm_api3('UFMatch', 'getsingle', ['uf_name' => $contact['email']]);
            if ($ufmatch2 && !empty($ufmatch2['uf_id'])) {
                return $this->logWPAction("WPCreateUser: no user created, contact email already used by WP user {$ufmatch['uf_id']} ({$ufmatch['uf_name']}).", LogLevel::WARNING, self::ACTION_CANCELLED);
            }
        } catch (\CiviCRM_API3_Exception $e) {
            // No match, proceeding to create new user
        }

        // ---------------------
        // Create new WP user

        // Set default password for new users: predictable hash that we'll also use in email templates
        $defaultPassword = CRM_WPCivi_CiviRulesActions_WPCreateUserTokens::getDefaultPassword($contact['contact_id'], $contact['email']);

        // Try to create WP user and set name, email, role
        $userParams = [
            'user_login'   => $contact['email'],
            'user_email'   => $contact['email'],
            'user_pass'    => $defaultPassword,
            'nickname'     => $contact['email'],
            'first_name'   => $contact['first_name'],
            'last_name'    => $contact['last_name'],
            'display_name' => $contact['display_name'],
            'role'         => $actionParams['wp_role'],
        ];

        // Check if WP user exists, and if true update instead of insert
        $existingUserID = username_exists($contact['email']);
        if ($existingUserID != false) {
            $this->logAction("WPCreateUser: updating existing Wordpress user {$existingUserID} for email {$contact['email']}.", $this->triggerData, LogLevel::WARNING);
            $userParams['ID'] = $existingUserID;
        }

        // Quick hack: empty $_POST, because wp_insert_user() triggers the SynchronizeUFMatch functions, which seem to decide if this an interactive login/registration based on $_POST - TODO Report?
        $_POST = [];

        // Insert/update user (with try/catch because CiviCRM might throw fatal errors)
        // This should also add a correct UFMatch record, so we don't have to do that manually
        try {
            $this->logAction("WPCreateUser: Calling wp_insert_user for contact {$contactId} (parameters: " . print_r($userParams, true) . ").", $triggerData, LogLevel::INFO);
            $uf_id = wp_insert_user($userParams);

            if (empty($uf_id) || $uf_id instanceof \WP_Error) {
                return $this->logWPAction("WPCreateUser: calling wp_insert_user failed for contact {$contactId}. Result: " . print_r($uf_id, true) . ".", LogLevel::WARNING, self::ACTION_CANCELLED);
            }
        } catch (\Exception $e) {
            return $this->logWPAction("WPCreateUser: exception occurred calling wp_insert_user for contact {$contactId}. Message: " . $e->getMessage() . ".", LogLevel::WARNING, self::ACTION_CANCELLED);
        }

        // ---------------------
        // That should be all!

        return $this->logWPAction("WPCreateUser: succesfully created/updated WP user {$uf_id} (contact {$contactId} / {$contact['email']}) with role {$actionParams['wp_role']}.", LogLevel::INFO, self::ACTION_COMPLETED);
    }

    /**
     * Method to return the url for additional form processing for action (return false if none is needed)
     * @param int $ruleActionId
     * @return string|bool
     */
    public function getExtraDataInputUrl($ruleActionId)
    {
        return CRM_Utils_System::url('civicrm/civirule/form/action/wpcreateuser', 'rule_action_id=' . $ruleActionId);
    }

    /**
     * Returns a user friendly text explaining the action parameters
     * @return string
     */
    public function userFriendlyConditionParams()
    {
        $params = $this->getActionParameters();
        $label = ts('Create WordPress account');
        if (isset($params['wp_role'])) {
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
        // (-> activity type currently configurable, could probably just as well be automatically added)
        $params = $this->getActionParameters();

        if (isset($params['activity_type_id'])) {
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
                if (!$sourceContactId) {
                    $sourceContactId = 1; // Meer gedoe dan nut met configureerbare cid hiervoor
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
                    $this->logAction('WPCreateUser: an error occurred while creating an activity for an error that occurred (contact id ' . $this->triggerData->getContactId() . ' - ' . $e->getMessage() . ').', $this->triggerData, LogLevel::ERROR);
                } else {
                    $this->logAction('WPCreateUser: action has succesfully run, but an error occurred while creating an activity (contact id ' . $this->triggerData->getContactId() . ' - ' . $e->getMessage() . ').', $this->triggerData, LogLevel::ERROR);
                }
            }
        }

        return ($actionResult != self::ACTION_CANCELLED);
    }

}
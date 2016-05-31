<?php
/**
 * CiviRules action entity to create a WordPress user account.
 * @see \CRM_CivirulesActions_WordPress_WPCreateUser
 */

return [
        0 =>
            [
                'name'   => 'Civirules:Action.WPCreateUser',
                'entity' => 'CiviRuleAction',
                'params' =>
                    [
                        'version'    => 3,
                        'name'       => 'wp_create_user',
                        'label'      => 'WordPress: Create User',
                        'class_name' => 'CRM_WPCivi_CiviRulesActions_WPCreateUser',
                        'is_active'  => 1,
                    ],
            ],
    ];
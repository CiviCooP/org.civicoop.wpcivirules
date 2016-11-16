<?php

/**
 * Class CRM_WPCivi_CiviRulesActions_WPCreateUserTokens
 * Functions to provide Wordpress/UF-tokens for new member welcome emails.
 */

class CRM_WPCivi_CiviRulesActions_WPCreateUserTokens {

  public static function addTokens(&$tokens) {
    if(!isset($tokens['jourcoop'])) {
      $tokens['jourcoop'] = [];
    }
    $tokens['jourcoop']['jourcoop.uf_username'] = ts('WordPress Username');
    $tokens['jourcoop']['jourcoop.uf_password'] = ts('WordPress Default Password');
  }

  public static function addTokenValues(&$values, $cids, $tokens = []) {

    if(!empty($tokens['jourcoop'])) {

      // Get UFMatch records (esp. WP usernames) for all contact ids
      $ufmatch = civicrm_api3('UFMatch', 'get', [
        'contact_id' => ['IN' => $cids],
      ]);
      if($ufmatch['is_error']) {
        throw new \Exception('Could not load UFMatch records in WPCivi_CiviRulesActions_WPCreateUserTokens!');
      }

      $uf_usernames = [];
      foreach($ufmatch['values'] as $m) {
        $uf_usernames[$m['contact_id']] = $m['uf_name'];
      }

      // Walk all contacts and add tokens to $values[$cid]
      foreach($cids as $cid) {

        // No UFMatch record found
        if(!isset($uf_usernames[$cid])) {
          $values[$cid]['jourcoop.uf_username'] = '[ERROR:NO-ACCOUNT]';
          $values[$cid]['jourcoop.uf_password'] = '-';
          continue;
        }

        // UFMatch found, set tokens
        $values[$cid]['jourcoop.uf_username'] = $uf_usernames[$cid];
        $values[$cid]['jourcoop.uf_password'] = self::getDefaultPassword($cid, $uf_usernames[$cid]);
      }
    }
  }

  public static function getDefaultPassword($cid, $email) {
    $string = CIVICRM_SITE_KEY . '_' . $cid . '_' . $email;
    return substr(hash('sha256', $string), 0, 12);
  }
}
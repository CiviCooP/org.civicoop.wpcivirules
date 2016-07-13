<h3>{$ruleActionHeader}</h3>

<div class='status'><div class="icon inform-icon"></div>
  {ts}Select which WordPress role you want to assign to the users created by this action.{/ts}
  <em>({ts}If a contact already has a user account, his/her account and role will not be overwritten.{/ts})</em><br>
  {ts}Also, please choose the type of activity you want this action to add and the source contact to be used when run in the background.{/ts}
</div>

<div class="crm-block crm-form-block crm-civirule-rule_action-block-group-contact">
  <div class="crm-section">
    <div class="label">{$form.wp_role.label}</div>
    <div class="content">{$form.wp_role.html}</div>
    <div class="clear"></div>
  </div>


  <div class="crm-section">
    <div class="label">{$form.activity_type_id.label}</div>
    <div class="content">{$form.activity_type_id.html}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{$form.activity_contact_id.label}</div>
    <div class="content">{$form.activity_contact_id.html}</div>
    <div class="clear"></div>
  </div>
</div>

<div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
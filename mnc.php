<?php

require_once 'mnc.civix.php';

define('EVENT_TYPE_ID', 7);
define('PLAYER_PROFILE_ID', 13);
define('FOURSOME_FIELD_ID', 4);
define('FOURSOME_FIELD_VALUE', 8);


/**
 * Implementation of hook_civicrm_config
 */
function mnc_civicrm_config(&$config) {
  _mnc_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function mnc_civicrm_xmlMenu(&$files) {
  _mnc_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function mnc_civicrm_install() {
  return _mnc_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function mnc_civicrm_uninstall() {
  return _mnc_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function mnc_civicrm_enable() {
  return _mnc_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function mnc_civicrm_disable() {
  return _mnc_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function mnc_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mnc_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function mnc_civicrm_managed(&$entities) {
  return _mnc_civix_civicrm_managed($entities);
}

function mnc_civicrm_buildForm($formName, &$form) {
  if (substr($formName, 0, 27) == 'CRM_Event_Form_Registration' 
    && $form->_values['event']['event_type_id'] == EVENT_TYPE_ID) {
    if ($formName == 'CRM_Event_Form_Registration_Register') {
      $contants = mnc_getConstants();
      $form->assign('playerProfileID', PLAYER_PROFILE_ID);
      CRM_Core_Region::instance('page-body')->add(array(
        'template' => 'CRM/Extra.tpl',
      ));
      $form->assign('foursome', array('field' => 'price_' . FOURSOME_FIELD_ID,
        'value' => FOURSOME_FIELD_VALUE));
    }
    else {
      $formValues = $form->getVar('_params');
      $formValues = $formValues[0];
      if (!empty($formValues['price_' . FOURSOME_FIELD_ID]) && !CRM_Utils_Array::value(FOURSOME_FIELD_VALUE, $formValues['price_' . FOURSOME_FIELD_ID])) {
        $customPost = & CRM_Core_Smarty::singleton()->get_template_vars('primaryParticipantProfile');
        unset($customPost['CustomPost'][22]);
      }
    }
  }
}

function mnc_civicrm_postProcess($formName, &$form) {
  if (substr($formName, 0, 27) == 'CRM_Event_Form_Registration' 
      && $form->_values['event']['event_type_id'] == EVENT_TYPE_ID) {
    $contants = mnc_getConstants();
    if ($formName == 'CRM_Event_Form_Registration_Confirm') {
      $formValues = $form->getVar('_params');
      // create player participant
      if (!empty($formValues['price_' . FOURSOME_FIELD_ID]) && CRM_Utils_Array::value(FOURSOME_FIELD_VALUE, $formValues['price_' . FOURSOME_FIELD_ID])) {
        foreach ($contants as $key => $customFields) {
          if (!empty($formValues['custom_' . $customFields['first_name']]) 
            || !empty($formValues['custom_' . $customFields['last_name']]) 
            || !empty($formValues['custom_' . $customFields['email']])) {
            
            // create/check contact
            //check dupe
            $params = array(
              'last_name' => $formValues['custom_' . $customFields['last_name']],
              'first_name' => $formValues['custom_' . $customFields['first_name']],
              'email' => $formValues['custom_' . $customFields['email']],
            );
            $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');
            $dupes = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'Unsupervised');
            if (empty($dupes)) {
              $params += array(
                'contact_type' => 'Individual',
                'version' => 3,
              );
              $result = civicrm_api('Contact', 'create', $params);
              $contactId = $result['id'];
            }
            else {
              $contactId = current($dupes);
            }
            if (!$contactId) {
              continue;
            }
            //create participant
            $params = array(
              'contact_id' => $contactId,
              'event_id' => $form->_eventId,
              'status_id' => 1,
              'role_id' => 1,
              'registered_by_id' => $form->getVar('_participantId'),
              'register_date' => date('YmdHis'),
              'check_permissions' => false,
              'version' => 3,
            );
            civicrm_api('Participant', 'create', $params);
          }
        }
      }
    }
  }
}


function mnc_getConstants() {
  //FIXME: remove hardcoded values, use machine names
  return array(
    'player_1' => array(
      'first_name' => 1,
      'last_name' => 2,
      'email' => 3,
    ), 
    'player_2' => array(
      'first_name' => 4,
      'last_name' => 5,
      'email' => 6,
    ),
    'player_3' => array(
      'first_name' => 7,
      'last_name' => 8,
      'email' => 9,
    ),
    'player_4' => array(
      'first_name' => 10,
      'last_name' => 11,
      'email' => 12,
    ),
  );
}


function mnc_civicrm_validate($formName, &$fields, &$files, &$form) {
  if ($formName == 'CRM_Event_Form_Registration_Register'
    && $form->_values['event']['event_type_id'] == EVENT_TYPE_ID) {
    
    if (CRM_Utils_Array::value('price_' . FOURSOME_FIELD_ID, $fields) == FOURSOME_FIELD_VALUE) {
      $errors = array();
      $contants = mnc_getConstants();
      foreach ($contants as $key => $customFields) {
        foreach ($customFields as $customFieldId) {
          if (empty($fields['custom_' . $customFieldId])) {
            $errors['custom_' . $customFieldId] = $form->_fields['custom_' . $customFieldId]['title'] . ts(' is required.');
          }
        }
      }
      return $errors;
    }
  }
}
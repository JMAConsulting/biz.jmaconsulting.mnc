<?php

require_once 'mnc.civix.php';

define('GOLF_EVENT_TYPE_ID', 7);
define('PLAYER_PROFILE_ID', 13);
define('FOURSOME_FIELD_ID', 5);
define('FOURSOME_FIELD_VALUE', 11);
define('MSG_TEMPALTE_ID', 58);
define('MSG_LUNCH_VALUE', 14);
define('CANOPY_PRICE_NONE', 9);
define('CANOPY_PRICE_TEXT', 7);
define('CANOPY_TEXT_PRICE_FIELD', 15);
define('CANOPY_TEXT', ' Donation in support of canopy purchase');


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
  if ($formName == 'CRM_Event_Form_Registration_Register' 
    && array_key_exists('price_' . CANOPY_PRICE_NONE, $form->_elementIndex)
    && array_key_exists($form->_elementIndex['price_' . CANOPY_PRICE_NONE], $form->_elements)) {
    $form->_elements[$form->_elementIndex['price_' . CANOPY_PRICE_NONE]]->_elements[2]->_text = CANOPY_TEXT;
    $form->_elements[$form->_elementIndex['price_' . CANOPY_PRICE_TEXT]]->_label = '';
    $form->assign('otherOption', array(CANOPY_PRICE_NONE, CANOPY_PRICE_TEXT));
  }
  
  if (substr($formName, 0, 27) == 'CRM_Event_Form_Registration' || substr($formName, 0, 32) == 'CRM_Contribute_Form_Contribution') {
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Extra.tpl',
    ));
  }
  if (substr($formName, 0, 27) == 'CRM_Event_Form_Registration' 
    && $form->_values['event']['event_type_id'] == GOLF_EVENT_TYPE_ID) {
    if ($formName == 'CRM_Event_Form_Registration_Register') {
      $contants = mnc_getConstants();
      $form->assign('playerProfileID', PLAYER_PROFILE_ID);
      $form->assign('foursome', array('field' => 'price_' . FOURSOME_FIELD_ID,
        'value' => FOURSOME_FIELD_VALUE));
    }
    else {
      $formValues = $form->getVar('_params');
      $formValues = $formValues[0];
      
      $foreSome = FALSE;
      if (!CRM_Utils_Array::value(MSG_LUNCH_VALUE, $formValues['price_' . FOURSOME_FIELD_ID])) {
        $foreSome = TRUE;        
      }
      CRM_Core_Smarty::singleton()->assign('foresome', $foreSome);
      if (!empty($formValues['price_' . FOURSOME_FIELD_ID]) && !CRM_Utils_Array::value(FOURSOME_FIELD_VALUE, $formValues['price_' . FOURSOME_FIELD_ID])) {
        $customPost = & CRM_Core_Smarty::singleton()->get_template_vars('primaryParticipantProfile');
        unset($customPost['CustomPost'][PLAYER_PROFILE_ID]);
      }
    }
  }
}

function mnc_civicrm_pre( $op, $objectName, $id, &$params ) {
  if ($op == 'create' && $objectName == 'Contribution') {
    $lineItem = CRM_Core_Smarty::singleton()->get_template_vars('lineItem');
    if (is_array($lineItem) && array_key_exists(CANOPY_TEXT_PRICE_FIELD, $lineItem[0])) {
      $params['skipLineItem'] = FALSE;
      $params['line_item'][1] = $lineItem[0];
    }
  }
}

function mnc_civicrm_postProcess($formName, &$form) {
  if (($formName == 'CRM_Event_Form_Registration_Confirm' 
    && $form->_values['event']['event_type_id'] == GOLF_EVENT_TYPE_ID)
    || ($formName == 'CRM_Event_Form_Participant' 
    && $form->getVar('_eventTypeId') == GOLF_EVENT_TYPE_ID && $form->_action & CRM_Core_Action::ADD)) {
    $contants = mnc_getConstants();
    $formValues = $form->getVar('_params');
    $extraString = '';
    if ($formName == 'CRM_Event_Form_Participant') {
      $extraString = '_-1';
      $participantId = $form->getVar('_id');
    }
    else {
      $participantId = $form->getVar('_participantId');
    }
    // create player participant
    if (!empty($formValues['price_' . FOURSOME_FIELD_ID]) && CRM_Utils_Array::value(FOURSOME_FIELD_VALUE, $formValues['price_' . FOURSOME_FIELD_ID])) {
      foreach ($contants as $key => $customFields) {
        if (!empty($formValues['custom_' . $customFields['first_name'] . $extraString]) 
          || !empty($formValues['custom_' . $customFields['last_name'] . $extraString]) 
          || !empty($formValues['custom_' . $customFields['email'] . $extraString])) {
          
          // create/check contact
          //check dupe
          $params = array(
            'last_name' => $formValues['custom_' . $customFields['last_name'] . $extraString],
            'first_name' => $formValues['custom_' . $customFields['first_name'] . $extraString],
            'email' => $formValues['custom_' . $customFields['email'] . $extraString],
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
            'registered_by_id' => $participantId,
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


function mnc_getConstants() {
  //FIXME: remove hardcoded values, use machine names
  return array(
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

/** Joe commented this out as MNC doesn't want the player 2-4 fields to be mandatory
function mnc_civicrm_validate($formName, &$fields, &$files, &$form) {
  if ($formName == 'CRM_Event_Form_Registration_Register') {
    $errors = array();
    if (!array_key_exists('price_' . CANOPY_PRICE_NONE, $fields)) {
      $errors['price_' . CANOPY_PRICE_NONE] = 'Please select atleast one option';
    } 
    elseif ($fields['price_' . CANOPY_PRICE_NONE] == 0 && empty($fields['price_' . CANOPY_PRICE_TEXT])) {
      $errors['price_' . CANOPY_PRICE_TEXT] = CANOPY_TEXT . ts(' is required.');
    }
    return $errors;
  }
}
*/

function mnc_civicrm_pageRun(&$page) {
  if ($page->getVar('_name') == 'CRM_Event_Page_EventInfo') {
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Extra.tpl',
    ));
  }
}

function mnc_civicrm_alterMailParams(&$params, $context) {
  if (CRM_Utils_Array::value('valueName', $params) == 'event_online_receipt') {
    $smarty = CRM_Core_Smarty::singleton();
    $additionalCount = $smarty->get_template_vars('additionalCount');
    if (array_key_exists('tplParams', $params) && CRM_Utils_Array::value('additional_participants', $params['tplParams']) && !$additionalCount) {
      $allEmails = array();
      foreach($params['tplParams']['params'] as $value) {
        if (is_array($value) && !empty($value['email-Primary']) && !$value['is_primary']) {
          $allEmails[$value['email-Primary']] = $value['email-Primary'];
        }
      }
      if (!empty($params['bcc'])) {
        $params['bcc'] .= ' , ';
      }
      $params['bcc'] .= implode(' , ', $allEmails);
      $smarty->assign('additionalCount', $params['tplParams']['additional_participants']);
    }
    elseif ($additionalCount) {
      $params['toEmail'] = NULL;
      $smarty->assign('additionalCount', $additionalCount--);
    }
  }
}
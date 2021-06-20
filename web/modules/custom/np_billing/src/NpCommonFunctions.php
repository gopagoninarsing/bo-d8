<?php

namespace Drupal\np_billing;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

class NpCommonFunctions {

  static public function centeGuests($cid = NULL) {
    return ['Guest 1', 'Guest 2'];
  }

  static public function getIDfrom_autocomplete($name) {
    $it_data1 = explode('(', $name);
    return $item_id = (int) str_replace(')','',end($it_data1));
  }

  static public function guestDetails($uid) {
    $helper = \Drupal::service('np.helpers');
    $userCenter = $helper->getUserCenter($uid);
    $rateplan_id = NpCommonFunctions::guestRateplan($uid);
    $rateplan_name = $helper->getNodeTitle($rateplan_id);

    $preauth_status =  NpCommonFunctions::guestPreauthStatus($uid, $rateplan_id);
    if ($preauth_status == 'Yes') {
      $preauths = NpCommonFunctions::guestPreauths($uid, $rateplan_id);
    }

    return [
      'uid' => $uid,
      'name' => 'Guest 2',
      'type' => 'OP',
      'center_id' => $userCenter,
      'rateplan_id' => $rateplan_id,
      'rateplan_name' => $rateplan_name,
      'rateplan_items' => NpCommonFunctions::rateplanItems($rateplan_id),
      'preauth_status' => $preauth_status,
      'preauths' => $preauths,
    ];
  }

  static public function guestPreauthStatus($uid, $rateplan_id) {
    $sql = 'select field_preauth_status_value
            from {node__field_preauth_status}
            where entity_id = :rateplan_id and bundle = :bundle';

    $database = \Drupal::service('database');
    return $query = $database->query($sql, [
      ':bundle' => 'rate_plan',
      ':rateplan_id' => $rateplan_id])
      ->fetchField();

  }

  static public function guestPreauths($uid, $rateplan_id) {
    $query = \Drupal::entityQuery('node')
     ->condition('type', 'insurance_pre_auth')
     ->condition('field_insurance_status', 'open')
     ->condition('field_guest', $uid)
     ->condition('field_additional_rate_plans', $rateplan_id);
    $results = $query->execute();

    $preauths = [];
    foreach($results as $result) {
      $preauths[$result] = NpCommonFunctions::preauthDetails($result);
    }
    return $preauths;
  }

  static public function preauthDetails($preauth_id) {
    $preauth_node = Node::load($preauth_id);
    return [
      'general_insurance_id' => $preauth_node->get('field_general_insurance')->target_id,
      'preauth_no' => $preauth_node->get('field_pre_auth_no')->value,
      'sessions_left' => $preauth_node->get('field_sessions_left')->value,
      'amount_left' =>$preauth_node->get('field_amount_left')->value,
    ];
  }

  static public function guestRateplan($uid) {
    $userCenter = \Drupal::service('np.helpers')->getUserCenter();
    $database = \Drupal::service('database');
    $sql = 'select field_default_rateplan_target_id
            from {node__field_default_rateplan}
            where bundle = :bundle and delta = 0 and entity_id = :cid';
    $query = $database->query($sql, [':bundle' => 'center', ':cid' => $userCenter]);
    $result = $query->fetchField();
    return $result;
  }

  static public function rateplanItems($rateplan_id) {
    $rateplan_items[] = ' -- Select Bill Item --';
    if ($rateplan_id) {

      $helper = \Drupal::service('np.helpers');
      $rateplan = Node::load($rateplan_id);
      $items = $rateplan->field_rate_plan->getValue();

      foreach ($items as $item ) {
        $p = Paragraph::load($item['target_id']);
        $text = $p->field_service_item->getValue()[0]['target_id'];
        if (!empty($p->field_service_item->getValue()[0]['target_id'])){
          $id = $p->field_service_item->getValue()[0]['target_id'];
          $rateplan_items[$item['target_id']] = $helper->getNodeTitle($id);
        }
      }

    }

    return $rateplan_items;
  }
}

<?php

namespace Drupal\np_billing\Services;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime;



/**
 * Nephroplus Helper Service
 */
class NPHelper {

  protected $currentuser;

  protected $database;

  public function __construct(AccountInterface $user, Connection $connection) {
    $this->database = $connection;
    $this->currentuser = $user;
  }

  public function getUsername() {
    //$query = $this->database->query('SELECT nid FROM {node}');
    //$result = $query->fetchAssoc();
    return $this->currentuser->getDisplayName();
  }

  public function getNodeTitle($nid) {
    $sql = 'SELECT title FROM {node_field_data} where nid = :nid';
    $query = $this->database->query($sql, [':nid' => $nid]);
    return $query->fetchField();
  }

  public function getUserCenter($uid = NULL) {
    $roles = $this->currentuser->getRoles();
    $uid = $uid?$uid:$this->currentuser->id();

    $type = 'nephroplus_staff';
    $sql = 'select field_staff_center_target_id as center
            from {profile__field_staff_center} fsc
              INNER JOIN {profile} p on p.profile_id = fsc.entity_id
            where p.type = :type and p.uid = :uid';
    if (in_array('guest', $roles)) {
      $type = 'guest';
    }
    $query = $this->database->query($sql,
      array(
        ':type' => $type,
        ':uid' =>$uid,
      )
    );
    $result = $query->fetchField();
    return $result;
  }

  public function getAge($date) {
    $today = date("Y-m-d");
    $diff = date_diff(date_create($date), date_create($today));
    return $diff->format('%y');
  }

}

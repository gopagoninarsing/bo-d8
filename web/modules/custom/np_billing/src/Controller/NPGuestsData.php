<?php

namespace Drupal\np_billing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\np_billing\Services\NPHelper;

class NPGuestsData extends ControllerBase {

  public function centerGuests(Request $request, $cid) {
    $results = ['Guest 1 (3)', 'Guest 2 (4)'];
    return new JsonResponse($results);
  }

  public function guestBillData(Request $request, $uid) {
    $age = \Drupal::service('np.helpers')->getAge('1999-03-19');
    $results = [
      'uid' => $uid,
      'username' => 'Guest 1 (3)',
      'type' => 'OP',
      'dob' => '1999-03-19',
      'age' => $age>0?$age:0,
      'gender' => 'Male',
      'mobile' => '9098776765',
      'primary_nephro' => 'Nephro 1',
      'deposit'=> 10000,
      'insurance' => 'Insureance 1',
    ];
    return new JsonResponse($results);
  }
}

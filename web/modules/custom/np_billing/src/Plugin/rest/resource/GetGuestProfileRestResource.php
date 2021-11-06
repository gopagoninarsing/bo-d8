<?php

namespace Drupal\np_billing\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource to get the guest Details
 *
 * @RestResource(
 *    id = "get_guest_mobile_profile",
 *    label = @Translation("Mobile Guest Profile"),
 *    uri_paths = {
 *      "canonical" = "/rest/api/get/guestdetails/{user_id}"
 *    }
 * )
*/

class GetGuestProfileRestResource extends ResourceBase {

  public function get($user_id = NULL) {
    $data = ['name' => 'Am Here', 'type' => 'testing'];

    $response= new ResourceResponse($data);
    $response->addCacheableDependency($data);
    return $response;
  }
}

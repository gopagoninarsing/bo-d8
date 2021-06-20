<?php

namespace Drupal\np_billing\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a bill Saves i
 */
class BillSaveEvent extends Event {

  const SAVE_BILL = 'save_np_custom_bill';

  /**
   * The Form Data.
   *
   */
  public $formData;

  public function __construct($formData) {
    $this->formData = $formData;
  }
}

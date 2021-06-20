<?php

namespace Drupal\np_billing\EventSubscriber;

use Drupal\np_billing\Event\BillSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\np_billing\NpCommonFunctions;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\np_billing\EventSubscriber
 */
class BillingEventsSubscriber implements EventSubscriberInterface {

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * AnotherConfigEventsSubscriber constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service injected during the static create() method.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

 /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return[
      BillSaveEvent::SAVE_BILL => 'onBillFormSave',
    ];
  }

  /**
   * Subscribe to the bill Save event dispatched.
   *
   * @param \Drupal\np_billing\Event\BillSaveEvent $event
   *   Dat event object yo.
   */
  public function onBillFormSave(BillSaveEvent $event) {
    $formData = $event->formData;

    $guest_id = NpCommonFunctions::getIDfrom_autocomplete($formData['guest']);
    if ($guest_id) {
      $guest_data = NpCommonFunctions::guestDetails($guest_id);
      $bill_data = [];

      $bill_data['guest_uid'] = $guest_id;
      $bill_data['guest_type'] = $guest_data['type'];
      $bill_data['guest_name'] = $guest_data['name'];
      $bill_data['center_id'] = $guest_data['center_id'];
      $bill_data['rateplan_name'] = $guest_data['rateplan_name'];

      $bill_data['service_type'] = 'Dialysis';
      $bill_data['bill_status'] = 'Closed';
      $bill_data['cycle'] = $formData['bill_cycle'];
      $bill_data['session_date'] = !empty($formData['session_date'])?$formData['session_date']:date('Y-m-d H:i:s');

      $bill_data['bill_date'] = !empty($formData['bill_date'])?$formData['bill_date']: date('Y-m-d');
      $bill_data['session_date'] = !empty($formData['session_date'])?$formData['session_date']:date('Y-m-d');

      $bill_data['items'] = [];
      $bill_data['items'][] = ['item_id' => 2, 'qty' => 1, 'discount' => 0, 'price' => 1000];

      $bill_data['payments'] = [];
      $bill_data['payments'][] = ['date' => date('Y-m-d'), 'amount' => 1000,  'type' => $formData['payment_type'], 'comment' => ''];

      $bill_data['total'] = 1000;
      $bill_data['paid'] = 1000;
      $bill_data['balance'] = 0;

      return $this->saveCustomBill($bill_data);
    }
    /**
     *  TODO:
     *   1. Generate Bill Number
     *   2. Save The Bill Entity
     *   2. Save/use the Deposit Sale (EVENT)
     *   3. Save Preauth (EVENT)
     *   4. Save/use Package Sale (EVENT)
     *   5. Deduct Stock (EVENT)
     */
  }

  protected function saveCustomBill($bill_data) {
    $bill_items = Paragraph::create([
      'type' => 'bill_services',
      'field_service_item' => ['target_id' => $bill_data['items'][0]['item_id']],
      'field_discount' => ['value' => $bill_data['items'][0]['discount']],
      'field_price' => ['value' => $bill_data['items'][0]['price']],
      'field_quantity' => ['value' => $bill_data['items'][0]['qty']],
      'field_total' => ['value' => ($bill_data['items'][0]['qty'] * $bill_data['items'][0]['price']) ],
    ]);
    $bill_items->save();

    $payments = Paragraph::create([
      'type' => 'bill_payments',
      'field_bill_amount' => ['value' => $bill_data['payments'][0]['amount']],
      'field_comments' => ['value' => $bill_data['payments'][0]['comment']],
      'field_payment_date' => ['value' => $bill_data['payments'][0]['date']],
      'field_payment_type' => ['value' => $bill_data['payments'][0]['type']],
    ]);
    $payments->save();


    $bill = Node::create([
      'type' => 'bill',
      'title' =>'Bill for '.$bill_data['guest_name'],
      'field_service_line_item' => [
          [
            'target_id' => $bill_items->id(),
            'target_revision_id' => $bill_items->getRevisionId(),
          ]
        ],
      'field_bill_payments' => [
          [
            'target_id' => $payments->id(),
            'target_revision_id' => $payments->getRevisionId(),
          ]
        ]
    ]);

    $bill->set('field_guest', $bill_data['guest_uid']);
    $bill->set('field_bill_paid', $bill_data['paid']);
    $bill->set('field_balance', $bill_data['balance']);
    $bill->set('field_bill_cycle', $bill_data['cycle']);
    $bill->set('field_bill_service_type', $bill_data['service_type']);
    $bill->set('field_bill_status', $bill_data['bill_status']);
    $bill->set('field_center', $bill_data['center_id']);
    $bill->set('field_bill_rate_plan', $bill_data['rateplan_name']);

    $bill->set('field_bill_amount', $bill_data['total']);

    $session_date = \DateTime::createFromFormat('Y-m-d', $bill_data['session_date']);
    $sessionDate = $session_date->format('Y-m-d\TH:i:s');
    $bill->set('field_bill_date', $sessionDate);

    $bill_date = \DateTime::createFromFormat('Y-m-d', $bill_data['bill_date']);
    $BillDate = $bill_date->format('Y-m-d\TH:i:s');
    $bill->set('field_bill_created_date', $BillDate);

    $bill->enforceIsNew();
    $bill->save();

    $this->messenger->addStatus(t('Bill Saved Succssfully.' . $bill->id()) );
    return $bill;
    $url =  Url::fromRoute('entity.node.canonical', array('node' => $bill->id()));
    return new RedirectResponse($url->toString());
  }

}


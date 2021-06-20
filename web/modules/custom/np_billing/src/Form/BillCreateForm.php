<?php

namespace Drupal\np_billing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\np_billing\NpCommonFunctions;
use Drupal\np_billing\Event\BillSaveEvent;
use Drupal\np_billing\Services\NPHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AfterCommand;

use Symfony\Component\DependencyInjection\ContainerInterface;

/*
* Implements Bill Creation Form
*/
class BillCreateForm extends FormBase {

  protected $helper;

  /**
   *  Exmaple usgae of custom service
   *  using dependecy Injection
   */
  public function __construct(NPHelper $helper) {
    $this->helper = $helper;
  }

  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('np.helpers')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'bill_create_form';
  }

  public function defaultSessionDays($days = 3) {
    $daysList =[];
    for($i = $days; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $daysList[$date] = $date;
    }

    return array_reverse($daysList, TRUE);
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /**
     * GLOBAL SERVICE USAGE
     *  NOT Recommanded
     *
     * $data = \Drupal::service('np.helpers')->getUsername();
     * dump($data);
     *
     * Dependecy Injected Service Usage
     * dump($this->helper->getUsername());
     */
    //dump(NpCommonFunctions::guestPreauths(4, 5));
    //$this->helper->getUserCenter();


    //$guests = NpCommonFunctions::centeGuests();
    $form['#attached']['library'][] = 'np_billing/billing';
    $form['guest'] = [
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'np_billing.np_center_guests.autocomplete',
      '#autocomplete_route_parameters' => array('cid' => 2),
      '#title' => $this->t('Select Guest'),
      '#required' => TRUE,
      '#ajax' => [
        'callback'=> '::billGuestDetails',
        'event' => 'autocompleteclose',
        //'wrapper' => 'guest-rateplan',
        'progress' => [
            'type' => 'throbber',
        ],
      ],
    ];

    $form['rateplan'] = [
        '#type' => 'textfield',
        '#disabled' => TRUE,
        '#size' => 20,
        '#value' => 'Rateplan',
        '#attributes' => ['class' => ['guest-rateplan']],
        '#prefix' => '<div id="guest-rateplan">',
        '#suffix' => '</div>',
    ];

    $form['preauth'] = [
        '#type' => 'select',
        '#options' => [],
        '#ajax' => [
            'callback' => '::preauthDetails',
            'event' => 'change',
            'wrapper' => 'guest-preauth-validity',
            'progress' => [
                'type' => 'throbber'
            ]
        ],
        '#validated' => TRUE,
        '#prefix' => '<div id="guest-preauth">',
        '#suffix' => '</div>',
    ];

    $form['preauth_validity'] = [
        '#type' => 'item',
        '#markup' => "<div>N/A</div>",
        '#prefix' => '<div id="guest-preauth-validity">',
        '#suffix' => '</div>',
    ];

    $form['bill_date'] = [
        '#type' => 'date',
        '#title' => $this->t('Bill Date'),
        '#default_value' => date('Y-m-d'),
        '#attributes' => ['disabled' => TRUE],
    ];

    $form['session_date'] = [
        '#type' => 'select',
        '#title' => $this->t('Session Date'),
        '#options' =>$this->defaultSessionDays(),
    ];

    $cycles = ['Cycle 1', 'Cycle 2', 'Cycle 3', 'Cycle 4', 'Cycle 5'];
    $form['bill_cycle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bill Cycle'),
        '#options' =>$cycles,
        '#required' => TRUE,
    ];

    $form['bill_items'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Bill Item'),
        '#options' => [],
        '#ajax' => [
            'callback' => '::addBillitems',
            'event' => 'change',
            'progress' => [
                'type' => 'throbber'
            ]
        ],
        '#prefix' => '<div id="bill-items">',
        '#suffix' => '</div>',
        '#validated' => TRUE,
    ];

    $payments = [
        'Cash' => 'Cash',
        'Card' => 'Card',
        'Credit' => 'Credit',
        'Co-pay Cash' => 'Co-Pay Cash',
        'Co-pay Card' => 'Co-Pay Card',
        'Package' => 'Package',
        'paytm' => 'PAYTM',
        'CHEQUE' => 'CHEQUE',
        'NEFT' => 'NEFT/RTGS',
        'Deposit' => 'Deposit',
        'copay_depsoit' =>'Co-Pay Deposit'
    ];
    $form['payment_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Payment Type'),
        '#required' => TRUE,
        '#options' => $payments
    ];

    $form['#theme'] = 'np_bill_form';
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /**
     * Validate:
     *   1. Deposit Amount
     *   2. valdiate Preauth (Session/Amount)
     *   3. Validate Cycle
     *   4.
     */
    //if (strlen($form_state->getValue('phone_number')) < 3) {
     // $form_state->setErrorByName('phone_number', $this->t('The phone number is too short. Please enter a full phone number.'));
   // }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event = new BillSaveEvent($form_state->getValues());
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch(BillSaveEvent::SAVE_BILL, $event);
    $this->messenger()->addStatus($this->t('Your Name is @number', ['@number' => $form_state->getValue('guest')]));
  }

  public function billGuestDetails(array &$form, FormStateInterface $form_state) {
    $selectedValue = $form_state->getValue('guest');
    if ($selectedValue) {
      $guest_id = NpCommonFunctions::getIDfrom_autocomplete($selectedValue);
      $guest = NpCommonFunctions::guestDetails($guest_id);
      $selectedText = $guest['rateplan_name'];

      $form['rateplan']['#value'] = $selectedText;
      $form['bill_items']['#options'] = $guest['rateplan_items'];

      $preauths[0] = '--Select Preauth--';
      if (!empty($guest['preauths'])) {
        foreach($guest['preauths'] as $id => $preauth){
            $preauths[$id] = $preauth['preauth_no'];
        }
      }
      $form['preauth']['#options'] =$preauths;
      $form['preauth_validity']['#markup'] = "<div>N/A</div>";

      $rateplan_name = "<span class='uinsurence'>".$selectedText."</span>";

      $response = new AjaxResponse();
      $response->addCommand(new ReplaceCommand('#guest-rateplan', $form['rateplan']));

      $response->addCommand(new ReplaceCommand('.uinsurence', ['#markup' => $rateplan_name]));
      $response->addCommand(new ReplaceCommand('#bill-items', $form['bill_items']));
      $response->addCommand(new ReplaceCommand('#guest-preauth', $form['preauth']));
      $response->addCommand(new ReplaceCommand('#guest-preauth-validity', $form['preauth_validity']));
      return $response;
    }
    // Return the prepared textfield.
    return $form['rateplan'];
  }

  public function preauthDetails(array &$form, FormStateInterface $form_state) {
    $selectedValue = $form_state->getValue('preauth');

    if($selectedValue > 0) {
      $preauth = NpCommonFunctions::preauthDetails($selectedValue);
      $markup = "<div><b>Sessions Left</b>:  ". $preauth['sessions_left']."</div>";
      $markup .= "<div><b>Amount Left</b>:  ". $preauth['amount_left']."</div>";
    }
    else {
        $markup = 'N/A';
    }
    $output = "<div id='guest-preauth-validity'>$markup</div>";
    return ['#markup' => $output];
  }

  public function addBillitems(array &$form, FormStateInterface $form_state) {
    $selectedValue = $form_state->getValue('bill_items');
    $response = new AjaxResponse();
    $selector = '.bill-items-table  tr';
    $content = '<tr>
                    <td>FIRST ITEM</td>
                    <td>200</td>
                    <td>0</td>
                    <td>1</td>
                    <td>200</td>
                </tr>';
    $response->addCommand(new AfterCommand($selector, $content));
    return $response;
  }
}

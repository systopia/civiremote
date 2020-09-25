<?php


namespace Drupal\civiremote_event\Form;


use Drupal;
use Drupal\civiremote_event\CiviMRF;
use Drupal\civiremote_event\Form\RegisterForm\RegisterFormInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Exception;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use \Drupal\Core\Routing\CurrentRouteMatch;

class RegisterForm extends FormBase implements RegisterFormInterface {

  /**
   * @var \Drupal\Core\Session\AccountInterface $account
   */
  protected $account;

  /**
   * @var CiviMRF $cmrf_core
   */
  protected $cmrf;

  /**
   * @var stdClass $event
   */
  protected $event;

  /**
   * @var string $profile
   */
  protected $profile;

  /**
   * RegisterForm constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged-id user account service.
   * @param CiviMRF $cmrf
   *   The CiviMRF core service.
   * @param CurrentRouteMatch $routeMatch
   *   The current route match object.
   */
  public function __construct(AccountInterface $account, CiviMRF $cmrf, CurrentRouteMatch $routeMatch) {
    $this->account = $account;
    $this->cmrf = $cmrf;

    // Extract form parameters and set them here so that implementations do not
    // have to care about that.
    $this->event = $routeMatch->getParameter('event');
    $this->profile = $routeMatch->getRawParameter('profile');
    if (!isset($this->profile)) {
      $this->profile = $this->event->default_profile;
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * Inject dependencies to the current user account and CiviMRF.
     * @var CiviMRF $cmrf
     * @var AccountInterface $current_user
     * @var CurrentRouteMatch $route_match
     */
    $current_user = $container->get('current_user');
    $cmrf = $container->get('civiremote_event.cmrf');
    $route_match = $container->get('current_route_match');
    return new static(
      $current_user,
      $cmrf,
      $route_match
    );
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return 'civiremote_event_register_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prepare form steps.
    if (empty($form_state->get('steps'))) {
      $steps = [
        // Generic form step, can be replaced in implementations.
        'form',
        // Confirmation step, right before final submission.
        'confirm',
        // Thank you step, right after final submission.
        'thankyou',
      ];
      $form_state->set('steps', $steps);
      // Initialize with first step.
      $form_state->set('step', 0);
    }
    $step = $form_state->get('step');
    $steps = $form_state->get('steps');

    // Build form depending on current step.
    if ($step == array_search('confirm', $steps)) {
      $form = $this->buildConfirmForm($form, $form_state);
      $submit_label = $this->t('Submit');
    }
    elseif ($step == array_search('thankyou', $steps)) {
      $form = $this->buildThankyouPage($form, $form_state);
      $form_state->setSubmitted();
    }
    else {
      $form = $this->buildRegisterForm($form, $form_state);
      $submit_label = $this->t('Next');
    }

    // Add submit button.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    if ($step > 0 && $step != array_search('thankyou', $steps)) {
      $form['actions']['back'] = [
        '#type' => 'submit',
        '#value' => $this->t('Back'),
        // Do not validate when using this button. #submit is required for
        // #limit_validation_errors to work.
        '#limit_validation_errors' => [],
        '#submit' => [[$this, 'submitForm']],
      ];
    }
    if ($step < array_search('thankyou', $steps)) {
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $submit_label,
      ];
    }

    // Do not validate the confirm form, since it only contains "Item" fields.
    if ($step == array_search('confirm', $steps)) {
      $form['actions']['submit']['#limit_validation_errors'] = [];
      $form['actions']['submit']['#submit'] = [[$this, 'submitForm']];
    }

    return $form;
  }

  protected function buildRegisterForm(array $form, FormStateInterface $form_state) {
    // Add event form intro text.
    if (!empty($this->event->intro_text)) {
      $form[] = [
        '#markup' => $this->event->intro_text,
      ];
    }

    // Fetch fields from RemoteEvent.get_registration_form API when no specific
    // implementation is present.
    $fields = $this->cmrf->getRegistrationForm($this->event->id, $this->profile);
    $form_state->set('fields', $fields);
    $types = [
      'Text' => 'textfield',
      'Textarea' => 'textarea',
      'Select' => 'select',
      'Multi-Select' => 'select',
      'Checkbox' => 'checkbox',
    ];

    foreach ($fields as $field_name => $field) {
      // Create and reference the group to place the field into.
      if (!empty($field['group_name'])) {
        if (empty($form[$field['group_name']])) {
          $form[$field['group_name']] = [
            '#type' => 'fieldset',
            '#title' => $field['group_label'],
          ];
        }
        $group = &$form[$field['group_name']];
      }
      else {
        $group = &$form;
      }

      // Set correct field type.
      if ($field['validation'] == 'Email') {
        $type = 'email';
      }
      else {
        $type = $types[$field['type']];
      }

      // Build the field.
      $group[$field_name] = [
        '#type' => $type,
        '#title' => $field['label'],
        '#description' => $field['description'],
        '#required' => !empty($field['required']),
        '#options' => ($type == 'select' ? $field['options'] : NULL),
        '#multiple' => ($field['type'] == 'Multi-Select'),
        '#weight' => $field['weight'],
        '#default_value' => $form_state->getValue($field_name, NULL),
      ];
    }

    // Add event form footer text.
    if (!empty($this->event->footer_text)) {
      $form[] = [
        '#markup' => $this->event->footer_text,
      ];
    }

    return $form;
  }

  protected function buildConfirmForm(array $form, FormStateInterface $form_state) {
    // Set confirmation page title.
    if (!empty($this->event->confirm_title)) {
      $form['#title'] = $this->event->confirm_title;
    }

    // Add confirmation text.
    if (!empty($this->event->confirm_text)) {
      $form[] = [
        '#markup' => $this->event->confirm_text,
      ];
    }

    // Show summary.
    foreach ($form_state->get('fields') as $field_name => $field) {
      // Create and reference the group to place the field into.
      if (!empty($field['group_name'])) {
        if (empty($form[$field['group_name']])) {
          $form[$field['group_name']] = [
            '#type' => 'fieldset',
            '#title' => $field['group_label'],
          ];
        }
        $group = &$form[$field['group_name']];
      }
      else {
        $group = &$form;
      }

      // Build the field.
      $value = $form_state->get('values')[$field_name];
      $group[$field_name] = [
        '#type' => 'item',
        '#title' => $field['label'],
        '#description' => $field['description'],
        '#required' => !empty($field['required']),
        '#multiple' => ($field['type'] == 'Multi-Select'),
        '#weight' => $field['weight'],
        '#markup' => (!empty($field['options']) ? $field['options'][$value] : $value),
        '#value' => $form_state->getValue($field_name, NULL),
      ];
    }

    // Add confirmation footer text.
    if (!empty($this->event->confirm_footer_text)) {
      $form[] = [
        '#markup' => $this->event->confirm_footer_text,
      ];
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Do not validate backward navigation.
    if (
      isset($form['actions']['back'])
      && $form_state->getTriggeringElement()['#value'] == $form['actions']['back']['#value']
    ) {
      return;
    }

    if ($form_state->get('step') == array_search('confirm', $form_state->get('steps'))) {
      $values = $form_state->get('values');
    }
    else {
      $form_state->cleanValues();
      $values = $form_state->getValues();
    }
    $errors = $this->cmrf->validateEventRegistration(
      $this->event->id,
      $this->profile,
      $values
    );
    if (!empty($errors)) {
      $form_state->set('step', array_search('form', $form_state->get('steps')));
      foreach ($errors as $field => $message) {
        $form_state->setErrorByName($field, $message);
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->get('step');

    if (
      isset($form['actions']['back'])
      && $form_state->getTriggeringElement()['#value'] == $form['actions']['back']['#value']
    ) {
      // Handle backwards navigation.
      $form_state->set('step', --$step);
      $form_state->setValues($form_state->get('values'));
      $form_state->setRebuild();
    }
    else {
      // Handle forward navigation.
      if ($step == array_search('confirm', $form_state->get('steps'))) {
        // Submit the form values to the CiviRemote Event API.
        $values = $form_state->get('values');
        try {
          $result = $this->cmrf->submitEventRegistration(
            $this->event->id,
            $this->profile,
            $values
          );

          // Advance to "Thank you" step.
          $form_state->set(
            'step',
            array_search('thankyou', $form_state->get('steps'))
          );
          $form_state->setRebuild();
        }
        catch (Exception $exception) {
          $form_state->set('error', TRUE);
          Drupal::messenger()->addMessage(
            t('Registration failed, please try again later.'),
            MessengerInterface::TYPE_ERROR
          );
          $form_state->setRebuild();
        }
      }
      else {
        $form_state->cleanValues();
        $values = $form_state->getValues();
        // Advance to next step and rebuild the form.
        $form_state->set('values', $values);
        $form_state->set('step', ++$step);
        $form_state->setRebuild();
      }
    }
  }

  public function buildThankyouPage(array &$form, FormStateInterface $form_state) {
    // Set confirmation page title.
    if (!empty($this->event->thankyou_title)) {
      $form['#title'] = $this->event->thankyou_title;
    }

    // Add confirmation text.
    if (!empty($this->event->thankyou_text)) {
      $form[] = [
        '#markup' => $this->event->thankyou_text,
      ];
    }

    // TODO: Show summary?

    // Add confirmation footer text.
    if (!empty($this->event->thankyou_footer_text)) {
      $form[] = [
        '#markup' => $this->event->thankyou_footer_text,
      ];
    }

    return $form;
  }

  /**
   * Custom access callback for this form's route.
   *
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   * @param string $profile
   *   The remote event profile to use for displaying the form.
   *
   * @return AccessResult|AccessResultAllowed|AccessResultNeutral
   */
  public function access(stdClass $event, $profile) {
    // Grant access depending on flags on the remote event.
    return AccessResult::allowedIf(
      $event->can_register
      && (
        !isset($profile)
        || in_array($profile, explode(',', $event->enabled_profiles))
      )
    );
  }
}

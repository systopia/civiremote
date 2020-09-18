<?php


namespace Drupal\civiremote_event\Form;


use Drupal;
use Drupal\civiremote_event\CiviMRF;
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

class RegisterForm extends FormBase {

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
    // TODO: Fetch fields from API.

    // Add event description.
    if (!empty($this->event->event_description)) {
      $form[] = [
        '#markup' => $this->event->event_description,
      ];
    }

    // Add submit button.
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->event->registration_link_text,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $values = $form_state->getValues();
    $errors = $this->cmrf->validateEventRegistration(
      $this->event->id,
      $this->profile,
      $values
    );
    foreach ($errors as $field => $message) {
      $form_state->setErrorByName($field, $message);
    }
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $values = $form_state->getValues();
    try {
      $result = $this->cmrf->submitEventRegistration(
        $this->event->id,
        $this->profile,
        $values
      );
    }
    catch (Exception $exception) {
      $form_state->set('error', TRUE);
      Drupal::messenger()->addMessage(
        t('Registration failed, please try again later.'),
        MessengerInterface::TYPE_ERROR
      );
      $form_state->setRebuild();
    }
    Drupal::messenger()->addMessage($this->event->thankyou_title);
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

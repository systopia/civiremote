<?php


namespace Drupal\civiremote_event\Form;


use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Exception;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cmrf_core;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RegisterForm extends FormBase {

  /**
   * @var \Drupal\Core\Session\AccountInterface $account
   */
  protected $account;

  /**
   * @var cmrf_core\Core $cmrf_core
   */
  protected $cmrf_core;

  public function __construct(AccountInterface $account, cmrf_core\Core $cmrf_core) {
    $this->account = $account;
    $this->cmrf_core = $cmrf_core;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('cmrf_core.core')
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
   * @param \stdClass $event
   *   The CiviRemote event retrieved by the RemoteEvent.get API.
   * @param string $profile
   *   The event profile for displaying the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, stdClass $event = NULL, $profile = NULL) {
    // Use default profile if not provided.
    if (!isset($profile)) {
      $profile = $event->default_profile;
    }

    // Retrieve form ID for given profile ID from configuration.
    $form_id = NULL;
    $profile_form_mapping = Drupal::config('civiremote_event.settings')
      ->get('profile_form_mapping');
    if (!empty($profile_form_mapping)) {
      foreach ($profile_form_mapping as $mapping) {
        if ($mapping['profile_id'] == $profile) {
          $form_id = $mapping['form_id'];
          break;
        }
      }
    }
    // Default to preset profile form implementations.
    if (!isset($form_id) || $form_id == self::class) {
      $form_id = self::class . '\\' . $profile;
    }
    if (class_exists($form_id) && is_subclass_of($form_id, FormBase::class)) {
      // Use a form class provided through the configuration. It must have a
      // compatible signature.
      try {
        $form = Drupal::formBuilder()->getForm($form_id, $event, $profile);
      }
      catch (Exception $exception) {
        throw new AccessDeniedHttpException();
      }
    }
    else {
      throw new AccessDeniedHttpException();
    }

    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

  /**
   * Custom access callback for this form's route.
   *
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   * @param string $profile
   *   The remote event profile to use for displaying the form.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultNeutral
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

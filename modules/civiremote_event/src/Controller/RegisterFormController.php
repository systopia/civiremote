<?php
/*------------------------------------------------------------+
| CiviRemote - CiviCRM Remote Integration                     |
| Copyright (C) 2020 SYSTOPIA                                 |
| Author: J. Schuppe (schuppe@systopia.de)                    |
+-------------------------------------------------------------+
| This program is released as free software under the         |
| Affero GPL license. You can redistribute it and/or          |
| modify it under the terms of this license which you         |
| can read by viewing the included agpl.txt or online         |
| at www.gnu.org/licenses/agpl.html. Removal of this          |
| copyright header is strictly prohibited without             |
| written permission from the original author(s).             |
+-------------------------------------------------------------*/

namespace Drupal\civiremote_event\Controller;

use Drupal;
use Drupal\civiremote_event\CiviMRF;
use Drupal\civiremote_event\Form\RegisterForm\RegisterFormInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatch;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegisterFormController extends ControllerBase {

  const CONTEXT_CREATE = 'create';

  const CONTEXT_UPDATE = 'update';

  const CONTEXT_CANCEL = 'cancel';

  /**
   * @var CiviMRF $cmrf
   *   The CiviMRF service.
   */
  protected $cmrf;

  /**
   * @param CiviMRF $cmrf
   *   The CiviMRF service.
   */
  public function __construct(CiviMRF $cmrf) {
    $this->cmrf = $cmrf;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    /**
     * Inject dependencies.
     * @var CiviMRF $cmrf
     */
    $cmrf = $container->get('civiremote_event.cmrf');
    return new static(
      $cmrf
    );
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   * @param string $context
   * @param \stdClass $event_token
   *   A CiviCRM CiviRemote Event token converted into a CiviCRM Event.
   *
   * @return array
   *
   * @see \Drupal\civiremote_event\Routing\EventTokenConverter
   */
  public function formWithToken(RouteMatch $route_match, string $context, stdClass $event_token) {
    return self::form(
      $route_match,
      $context,
      $event_token,
      $route_match->getRawParameter('event_token')
    );
  }

  public function form(RouteMatch $route_match, string $context, stdClass $event, string $raw_event_token = NULL, string $profile = NULL) {
    // Retrieve the form definition.
    try {
      $form = $this->cmrf->getForm(
        (isset($event) ? $event->id : NULL),
        $profile,
        $raw_event_token,
        $context
      );
      $fields = $form['values'];
      $messages = $form['status_messages'] ?? [];
      if (!empty($fields['profile']['value'])) {
        $profile = $fields['profile']['value'];
      }
      else {
        switch ($context) {
          case self::CONTEXT_CREATE:
            $profile = $event->default_profile;
            break;
          case self::CONTEXT_UPDATE:
            $profile = $event->default_update_profile;
            break;
          case self::CONTEXT_CANCEL:
            $profile = NULL;
            break;
          default:
            throw new NotFoundHttpException('No profile found for CiviRemote event form.');
        }
      }
    }
    catch (Exception $exception) {
      Drupal::messenger()->addMessage(
        $exception->getMessage(),
        MessengerInterface::TYPE_ERROR
      );
    }

    // Retrieve implementation for building the form.
    $form_id = $this->getFormId($profile);

    // Build the form.
    return $this->formBuilder()->getForm(
      $form_id,
      $event,
      $profile,
      $context,
      $raw_event_token,
      $fields,
      $messages
    );
  }

  /**
   * @param \stdClass $event_token
   *    A CiviCRM CiviRemote Event token converted into a CiviCRM Event.
   *
   * @return mixed
   *
   * @see \Drupal\civiremote_event\Routing\EventTokenConverter
   */
  public function titleWithToken(stdClass $event_token) {
    return self::title($event_token);
  }

  public function title(stdClass $event) {
    return $event->event_title;
  }

  private function getFormId($profile = NULL) {
    // Use generic form ID.
    $form_id = '\Drupal\civiremote_event\Form\RegisterForm';

    // Try to find a profile-specific implementation.
    if ($profile) {
      $profile_form_id = '\Drupal\civiremote_event\Form\RegisterForm\\' . $profile;
      if (
        class_exists($profile_form_id)
        && in_array(RegisterFormInterface::class,class_implements($profile_form_id))
      ) {
        $form_id = $profile_form_id;
      }

      // Use form ID for given profile ID from configuration.
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
    }

    return $form_id;
  }

  /**
   * @param string $context
   * @param \stdClass $event_token
   *    A CiviCRM CiviRemote Event token converted into a CiviCRM Event.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultNeutral
   *
   * @see \Drupal\civiremote_event\Routing\EventTokenConverter
   */
  public function accessWithToken(string $context, stdClass $event_token) {
    return self::access($context, $event_token);
  }

  /**
   * Custom access callback for this form's route.
   *
   * @param stdClass $event
   *   The remote event retrieved by the RemoteEvent.get API.
   * @param string $profile
   *   The remote event profile to use for displaying the form.
   * @param stdClass $remote_token
   *   The remote token to use for retrieving the form.
   *
   * @return AccessResult|AccessResultAllowed|AccessResultNeutral
   */
  public function access(string $context, stdClass $event, string $profile = NULL) {
    $event ??= $event_token;
    // Grant access depending on flags on the remote event.
    return AccessResult::allowedIf(
      isset($event)
      && (
        $context == self::CONTEXT_CREATE && $event->can_register
        || $context == self::CONTEXT_UPDATE && $event->can_edit_registration
        || $context == self::CONTEXT_CANCEL && $this->event->is_registered && $this->event->can_cancel_registration
      )
      && (
        !isset($profile)
        || in_array(
          $profile,
          explode(',', $event->enabled_profiles)
        )
      )
    );
  }

}

<?php


namespace Drupal\civiremote_event\Controller;

use Drupal\civiremote_event\Form\RegisterForm\RegisterFormInterface;
use Drupal\Core\Controller\ControllerBase;
use stdClass;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RegisterFormController extends ControllerBase {

  public function form(stdClass $event = NULL, $profile = NULL) {
    // Use default profile if not given.
    $profile = (isset($profile) ? $profile : $event->default_profile);

    // Retrieve form ID for given profile ID from configuration.
    $form_id = '\Drupal\civiremote_event\Form\RegisterForm\\' . $profile;
    $profile_form_mapping = \Drupal::config('civiremote_event.settings')
      ->get('profile_form_mapping');
    if (!empty($profile_form_mapping)) {
      foreach ($profile_form_mapping as $mapping) {
        if ($mapping['profile_id'] == $profile) {
          $form_id = $mapping['form_id'];
          break;
        }
      }
    }
    if (
      class_exists($form_id)
      && in_array(RegisterFormInterface::class,class_implements($form_id))
    ) {
      // Build the form.
      $build = $this->formBuilder()->getForm($form_id);
    }
    else {
      throw new AccessDeniedHttpException();
    }

    return $build;
  }

  public function title(stdClass $event = NULL, $profile = NULL) {
    return $event->event_title;
  }

}

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

    // Try to find our own implementation.
    $form_id = $this->getFormId($profile);

    // Build the form.
    $build = $this->formBuilder()->getForm($form_id);

    return $build;
  }

  public function title(stdClass $event = NULL, $profile = NULL) {
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
    }

    return $form_id;
  }

}

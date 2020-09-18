<?php

namespace Drupal\civiremote_event\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CiviRemoteEventConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civiremote_event_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('civiremote_event.settings');

    $form['profile_form_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Profile to form mapping'),
      '#description' => t('Define which forms should handle which CiviRemote Events profiles'),
      '#description_display' => 'before',
    ];
    // Get current mapping from the form state or the config.
    if (
      empty($profile_form_mapping = $form_state->get('profile_form_mapping'))
      && empty($form_state->getTriggeringElement())
    ) {
      if (empty($profile_form_mapping = $config->get('profile_form_mapping'))) {
        // Add a first, empty mapping.
        $profile_form_mapping[] = [
          'profile_id' => NULL,
          'form_id' => NULL,
        ];
      }
      $form_state->set('profile_form_mapping', $profile_form_mapping);
    }
    $form['profile_form_mapping']['profile_form_mapping_table'] = [
      '#tree' => TRUE,
      '#theme' => 'table',
      '#header' => [
        $this->t('CiviRemote Events profile ID'),
        $this->t('Drupal form ID'),
        NULL,
      ],
      '#rows' => [],
      '#attributes' => [
        'id' => 'profile-form-mapping-table',
      ],
    ];
    foreach ($profile_form_mapping as $key => $mapping) {
      $profile_id_field = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $mapping['profile_id'],
      ];
      $form_id_field = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $mapping['form_id'],
      ];
      $remove_button = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'profile_form_mapping_' . $key . '_remove',
        '#submit' => ['::mappingRemove'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'profile-form-mapping-table',
        ],
        '#limit_validation_errors' => [],
        '#civiremote_profile_form_mapping_key' => $key,
      ];
      $form['profile_form_mapping']['profile_form_mapping_table'][$key] = [
        'profile_id' => &$profile_id_field,
        'form_id' => &$form_id_field,
        'remove_button' => &$remove_button,
      ];
      $form['profile_form_mapping']['profile_form_mapping_table']['#rows'][$key] = [
        ['data' => &$profile_id_field],
        ['data' => &$form_id_field],
        ['data' => &$remove_button],
      ];
      // Because we've used references we need to `unset()` our variables. If we
      // don't then every iteration of the loop will just overwrite the
      // variables we created the first time through leaving us with a form with
      // 3 copies of the same fields.
      unset($profile_id_field, $form_id_field, $remove_button);
    }
    $form['profile_form_mapping']['add_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add mapping'),
      '#name' => 'profile_form_mapping__add',
      '#submit' => ['::mappingAdd'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'profile-form-mapping-table',
      ],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $config = $this->config('civiremote_event.settings');
    $config->set('profile_form_mapping', $form_state->getValue('profile_form_mapping_table'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for the "remove mapping" button.
   *
   * Removes the mapping from the storage and causes a form rebuild.
   */
  public function mappingRemove(array &$form, FormStateInterface $form_state) {
    $key = $form_state->getTriggeringElement()['#civiremote_profile_form_mapping_key'];
    $profile_form_mapping = $form_state->get('profile_form_mapping');
    unset($profile_form_mapping[$key]);
    $form_state->set('profile_form_mapping', $profile_form_mapping);

    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "add one" button.
   *
   * Adds a new empty mapping to the storage and causes a form rebuild.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function mappingAdd(array &$form, FormStateInterface $form_state) {
    $profile_form_mapping = $form_state->get('profile_form_mapping');
    $profile_form_mapping[] = [
      'profile_id' => NULL,
      'form_id' => NULL,
    ];
    $form_state->set('profile_form_mapping', $profile_form_mapping);

    $form_state->setRebuild();
  }

  /**
   * Callback for both ajax-enabled buttons (remove, add).
   *
   * Selects and returns the mapping table.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    return $form['profile_form_mapping']['profile_form_mapping_table'];
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'civiremote_event.settings',
    ];
  }

}

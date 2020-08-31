<?php

namespace Drupal\CiviRemote\Form;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\cmrf_core;

class CiviRemoteConfigForm extends ConfigFormBase {

  /* @var cmrf_core\Core $cmrf_core */
  public $cmrf_core;

  /**
   * CiviRemoteConfigForm constructor.
   *
   * @param cmrf_core\Core $cmrf_core
   */
  public function __construct(cmrf_core\Core $cmrf_core) {
    $this->cmrf_core = $cmrf_core;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Load required services.
    return new static(
      $container->get('cmrf_core.core')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'civiremote_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);
    // Default settings.
    $config = $this->config('civiremote.settings');

    $form['cmrf_connector'] = [
      '#type' => 'select',
      '#title' => t('CiviMRF Connector'),
      '#description' => t('The CiviMRF connector to use for connecting to CiviCRM. @cmrf_connectors_link',
        [
          '@cmrf_connectors_link' => Link::fromTextAndUrl(
            t('Configure CiviMRF Connectors'),
            Url::fromRoute('entity.cmrf_connector.collection')
          )->toString(),
        ]),
      '#options' => $this->cmrf_core->getConnectors(),
      '#default_value' => $config->get('cmrf_connector'),
      '#required' => TRUE,
    ];

    $form['acquire_civiremote_id'] = [
      '#type' => 'checkbox',
      '#title' => t('Acquire CiviRemote ID'),
      '#description' => t('Whether to match a new user to a CiviCRM contact and store the CiviRemote ID returned by CiviCRM.'),
      '#default_value' => $config->get('acquire_civiremote_id'),
    ];

    $form['match_contact_mapping'] = [
      '#type' => 'fieldset',
      '#title' => t('Parameter mapping'),
      '#description' => t('Acquiring CiviRemote IDs involves matching CiviCRM contacts based on Drupal user data. Configure the mapping between those two.'),
      '#description_display' => 'before',
      '#states' => [
        'visible' => [':input[name="acquire_civiremote_id"]' => ['checked' => TRUE]],
      ],
    ];
    // Get current mapping from the form state or the config.
    if (
      empty($match_contact_mapping = $form_state->get('match_contact_mapping'))
      && empty($form_state->getTriggeringElement())
    ) {
      if (empty($match_contact_mapping = $config->get('match_contact_mapping'))) {
        // Add a first, empty mapping.
        $match_contact_mapping[] = [
          'user_field' => NULL,
          'contact_field' => NULL,
        ];
      }
      $form_state->set('match_contact_mapping', $match_contact_mapping);
    }
    $form['match_contact_mapping']['match_contact_mapping_table'] = [
      '#tree' => TRUE,
      '#theme' => 'table',
      '#header' => [
        t('Drupal user field'),
        t('CiviCRM contact field'),
        NULL,
      ],
      '#rows' => [],
      '#attributes' => [
        'id' => 'match-contact-mapping-table',
      ],
    ];
    foreach ($match_contact_mapping as $key => $mapping) {
      // Retrieve all user fields as select options.
      /* @var \Drupal\Core\Entity\EntityFieldManager $entityFieldManager */
      $entityFieldManager = \Drupal::service('entity_field.manager');
      $user_fields = $entityFieldManager->getFieldDefinitions('user', 'user');
      array_walk($user_fields, function (&$field) {
        /* @var BaseFieldDefinition | FieldConfig $field */
        $field = $field->getLabel();
      });
      $user_field = [
        '#type' => 'select',
        '#options' => $user_fields,
        '#required' => TRUE,
        '#default_value' => $mapping['user_field'],
      ];
      $contact_field = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $mapping['contact_field'],
      ];
      $remove_button = [
        '#type' => 'submit',
        '#value' => t('Remove'),
        '#name' => 'match_contact_mapping_' . $key . '_remove',
        '#submit' => ['::mappingRemove'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'match-contact-mapping-table',
        ],
        '#limit_validation_errors' => [],
        '#civiremote_match_contact_mapping_key' => $key,
      ];
      $form['match_contact_mapping']['match_contact_mapping_table'][$key] = [
        'user_field' => &$user_field,
        'contact_field' => &$contact_field,
        'remove_button' => &$remove_button,
      ];
      $form['match_contact_mapping']['match_contact_mapping_table']['#rows'][$key] = [
        ['data' => &$user_field],
        ['data' => &$contact_field],
        ['data' => &$remove_button],
      ];
      // Because we've used references we need to `unset()` our variables. If we
      // don't then every iteration of the loop will just overwrite the
      // variables we created the first time through leaving us with a form with
      // 3 copies of the same fields.
      unset($user_field, $contact_field, $remove_button);
    }
    $form['match_contact_mapping']['add_button'] = [
      '#type' => 'submit',
      '#value' => t('Add mapping'),
      '#name' => 'match_contact_mapping__add',
      '#submit' => ['::mappingAdd'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'match-contact-mapping-table',
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
    $config = $this->config('civiremote.settings');
    $config->set('cmrf_connector', $form_state->getValue('cmrf_connector'));
    $config->set('acquire_civiremote_id', $form_state->getValue('acquire_civiremote_id'));
    $config->set('match_contact_mapping', $form_state->getValue('match_contact_mapping_table'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for the "remove mapping" button.
   *
   * Removes the mapping from the storage and causes a form rebuild.
   */
  public function mappingRemove(array &$form, FormStateInterface $form_state) {
    $key = $form_state->getTriggeringElement()['#civiremote_match_contact_mapping_key'];
    $match_contact_mapping = $form_state->get('match_contact_mapping');
    unset($match_contact_mapping[$key]);
    $form_state->set('match_contact_mapping', $match_contact_mapping);

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
    $match_contact_mapping = $form_state->get('match_contact_mapping');
    $match_contact_mapping[] = [
      'user_field' => NULL,
      'contact_field' => NULL,
    ];
    $form_state->set('match_contact_mapping', $match_contact_mapping);

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
    return $form['match_contact_mapping']['match_contact_mapping_table'];
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'civiremote.settings',
    ];
  }

}

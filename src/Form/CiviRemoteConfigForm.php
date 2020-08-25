<?php

namespace Drupal\CiviRemote\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('civiremote.settings');
    $config->set('cmrf_connector', $form_state->getValue('cmrf_connector'));
    $config->set('acquire_civiremote_id', $form_state->getValue('acquire_civiremote_id'));
    $config->save();
    return parent::submitForm($form, $form_state);
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

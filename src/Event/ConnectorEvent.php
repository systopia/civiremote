<?php

namespace Drupal\civiremote\Event;

use Drupal\Component\EventDispatcher\Event;

class ConnectorEvent extends Event {

  const EVENT_NAME = 'civiremote_connector';

  /**
   * The CiviMRF Connector ID.
   *
   * @var string $connector_id
   */
  protected string $connector_id;

  /**
   * The context of the event.
   *
   * @var array $context
   */
  protected array $context;

  public function __construct(string $connector_id, array $context = []) {
    $this->connector_id = $connector_id;
    $this->context = $context;
  }

  /**
   * @return string
   */
  public function getConnectorId(): string {
    return $this->connector_id;
  }

  /**
   * @param string $connector_id
   *
   * @return void
   */
  public function setConnectorId(string $connector_id): void {
    $this->connector_id = $connector_id;
  }

}

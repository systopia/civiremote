<?php


namespace Drupal\civiremote;


use Drupal\cmrf_core\Core;

class CiviMRF {

  /** @var Core */
  public $core;

  public function __construct(Core $core) {
    $this->core = $core;
  }

  private function connector() {
    return \Drupal::config('civiremote.settings')->get('cmrf_connector');
  }

  /**
   * @param $params
   *
   * @return string
   *   The CiviRemote ID.
   */
  public function matchContact($params) {
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteContact',
      'match',
      $params
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();
    return !empty($reply['key']) ? $reply['key'] : FALSE;
  }

}

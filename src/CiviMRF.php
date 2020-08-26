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
   * @return string | false
   *   The CiviRemote ID, or FALSE when no contact could be matched.
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

  /**
   * @param $params
   *
   * @return array
   *   The CiviRemote roles.
   */
  public function getRoles($params) {
    $call = $this->core->createCall(
      $this->connector(),
      'RemoteContact',
      'get_roles',
      $params
    );
    $this->core->executeCall($call);
    $reply = $call->getReply();
    return $reply['values'];
  }

}

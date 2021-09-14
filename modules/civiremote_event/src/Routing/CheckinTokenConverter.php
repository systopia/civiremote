<?php
/*------------------------------------------------------------+
| CiviRemote - CiviCRM Remote Integration                     |
| Copyright (C) 2021 SYSTOPIA                                 |
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

namespace Drupal\civiremote_event\Routing;


use Drupal\civiremote\Utils;
use Drupal\civiremote_event\CiviMRF;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Route;

class CheckinTokenConverter implements ParamConverterInterface {

  /**
   * @var CiviMRF $cmrf
   *   The CiviMRF service.
   */
  protected CiviMRF $cmrf;

  /**
   * CheckinTokenConverter constructor.
   *
   * @param CiviMRF $cmrf
   *   The CiviMRF service.
   */
  public function __construct(CiviMRF $cmrf) {
    $this->cmrf = $cmrf;
  }

  /**
   * @inheritDoc
   */
  public function convert($value, $definition, $name, array $defaults) {
    try {
      return $this->cmrf->getCheckinInfo($value, TRUE);
    }
    catch (Exception $exception) {
      Utils::setMessages([['message' => $exception->getMessage(), 'severity' => 'error']]);
      throw new AccessDeniedHttpException($exception->getMessage());
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] == 'civiremote_checkin_token';
  }

}

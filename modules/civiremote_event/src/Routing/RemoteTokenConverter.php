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

namespace Drupal\civiremote_event\Routing;


use Drupal\civiremote_event\CiviMRF;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Route;

class RemoteTokenConverter implements ParamConverterInterface {

  /**
   * @var CiviMRF $cmrf
   *   The CiviMRF service.
   */
  protected $cmrf;

  /**
   * CiviRemoteEventConverter constructor.
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
      return $this->cmrf->getEvent(NULL, $value);
    }
    catch (Exception $exception) {
      // We don't care for the error and assume the user does not have access to
      // the event.
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] == 'civiremote_remote_token';
  }

}

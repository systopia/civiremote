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
use Drupal\Core\Session\AccountInterface;
use Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Route;

class CiviRemoteEventConverter implements ParamConverterInterface {

  /**
   * @var CiviMRF $cmrf
   */
  protected $cmrf;

  /**
   * @var \Drupal\Core\Session\AccountInterface $account
   */
  protected $account;

  /**
   * CiviRemoteEventConverter constructor.
   *
   * @param CiviMRF $cmrf
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public function __construct(CiviMRF $cmrf, AccountInterface $account) {
    $this->cmrf = $cmrf;
    $this->account = $account;
  }

  /**
   * @inheritDoc
   */
  public function convert($value, $definition, $name, array $defaults) {
    try {
      return (object) $this->cmrf->getEvent($value, $this->account);
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
    return !empty($definition['type']) && $definition['type'] == 'civiremote_event';
  }
}

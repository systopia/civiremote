<?php

/*
 * Copyright (C) 2024 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Drupal\civiremote_entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\civiremote_entity\CiviCRMPage\CiviCRMPageProxyInterface;
use Drupal\civiremote_entity\CiviCRMPage\CiviCRMUrlStorageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CiviCRMPageController extends ControllerBase {

  private CiviCRMPageProxyInterface $civiCRMPageProxy;

  private CiviCRMUrlStorageInterface $civiCRMUrlManager;

  public function __construct(
    CiviCRMPageProxyInterface $civiCRMPageProxy,
    CiviCRMUrlStorageInterface $civiCRMUrlManager
  ) {
    $this->civiCRMPageProxy = $civiCRMPageProxy;
    $this->civiCRMUrlManager = $civiCRMUrlManager;
  }

  public function get(string $identifier): Response {
    $remoteUrl = $this->civiCRMUrlManager->getRemoteUrl($identifier);
    if (NULL === $remoteUrl) {
      throw new NotFoundHttpException();
    }

    return $this->civiCRMPageProxy->get($remoteUrl);
  }

}

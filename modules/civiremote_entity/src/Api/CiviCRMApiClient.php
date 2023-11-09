<?php

/*
 * Copyright (C) 2023 SYSTOPIA GmbH
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

namespace Drupal\civiremote_entity\Api;

use Assert\Assertion;
use CMRF\Core\Call;
use CMRF\Core\Core;
use Drupal\civiremote_entity\Api\Exception\ApiCallFailedException;
use Drupal\Core\Config\ImmutableConfig;

final class CiviCRMApiClient implements CiviCRMApiClientInterface {

  private Core $cmrfCore;

  private string $connectorId;

  public static function create(
    Core $cmrfCore,
    ImmutableConfig $config,
    string $connectorConfigKey
  ): self {
    $connectorId = $config->get($connectorConfigKey);
    Assertion::string($connectorId);

    return new static($cmrfCore, $connectorId);
  }

  public function __construct(Core $cmrfCore, string $connectorId) {
    $this->cmrfCore = $cmrfCore;
    $this->connectorId = $connectorId;
  }

  public function executeV3(string $entity, string $action, array $parameters = [], array $options = []): array {
    $call = $this->cmrfCore->createCallV3($this->connectorId, $entity, $action, $parameters, $options);

    $result = $this->cmrfCore->executeCall($call);
    if (NULL === $result || Call::STATUS_FAILED === $call->getStatus()) {
      throw ApiCallFailedException::fromCall($call);
    }

    return $result;
  }

  public function executeV4(string $entity, string $action, array $parameters = []): array {
    $call = $this->cmrfCore->createCallV4($this->connectorId, $entity, $action, $parameters);

    $result = $this->cmrfCore->executeCall($call);
    if (NULL === $result || Call::STATUS_FAILED === $call->getStatus()) {
      throw ApiCallFailedException::fromCall($call);
    }

    return $result;
  }

}

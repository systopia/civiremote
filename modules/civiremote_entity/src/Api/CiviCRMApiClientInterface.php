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

interface CiviCRMApiClientInterface {

  /**
   * Execute a CiviCRM APIv3 call.
   *
   * @param string $entity
   * @param string $action
   * @param array<string, mixed> $parameters
   *   JSON encodable array.
   * @param array<string, mixed> $options
   *
   * @return array<int|string, mixed> JSON encodable array.
   *
   * @throws \Drupal\civiremote_entity\Api\Exception\ApiCallFailedException
   */
  public function executeV3(string $entity, string $action, array $parameters = [], array $options = []): array;

  /**
   * Execute a CiviCRM APIv4 call.
   *
   * @param string $entity
   * @param string $action
   * @param array<string, mixed> $parameters
   *   JSON encodable array.
   *
   * @return array<string, mixed>&array{values: array<string|int, mixed>} JSON encodable array.
   *
   * @throws \Drupal\civiremote_entity\Api\Exception\ApiCallFailedException
   */
  public function executeV4(string $entity, string $action, array $parameters = []): array;

}

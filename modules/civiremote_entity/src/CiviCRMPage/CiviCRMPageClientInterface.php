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

namespace Drupal\civiremote_entity\CiviCRMPage;

use Psr\Http\Message\ResponseInterface;

/**
 * Client for pages in CiviCRM.
 *
 * Authentication is done via AuthX headers. Additionally the remote contact ID
 * is delivered in the header X-Civi-Remote-Contact-Id. An empty string is used
 * if the current user has no remote contact ID.
 */
interface CiviCRMPageClientInterface {

  /**
   * Does not throw exceptions on HTTP status codes >= 400 by default.
   *
   * @phpstan-param array<string, mixed> $options
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function request(string $method, string $uri, array $options = []): ResponseInterface;

}

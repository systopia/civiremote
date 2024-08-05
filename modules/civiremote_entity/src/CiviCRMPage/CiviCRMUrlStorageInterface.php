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

use Drupal\Core\Url;

/**
 * Shall be used to avoid exposing CiviCRM URLs to users.
 */
interface CiviCRMUrlStorageInterface {

  /**
   * Stores the given URL in the current user's session.
   *
   * @param string|null $filename
   *   Used to generate a convenient URL (see return). Has no technical
   *   implication.
   *
   * @return \Drupal\Core\Url
   *   Contains the URL where the user can access the given remote URL.
   *
   * @see \Drupal\civiremote_entity\Controller\CiviCRMPageController
   */
  public function addRemoteUrl(string $url, ?string $filename = NULL): Url;

  /**
   * @param string $identifier
   *   Identifier that is part of the Url object returned by addRemoteUrl().
   *
   * @return string|null
   *   URL that has been added via addRemoteUrl() before, or NULL if the given
   *   identifier is unknown.
   */
  public function getRemoteUrl(string $identifier): ?string;

}

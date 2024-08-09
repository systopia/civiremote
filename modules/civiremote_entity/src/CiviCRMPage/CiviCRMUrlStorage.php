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

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class CiviCRMUrlStorage implements CiviCRMUrlStorageInterface {

  private SessionInterface $session;

  private UuidInterface $uuidGenerator;

  public function __construct(
    SessionInterface $session,
    UuidInterface $uuidGenerator
  ) {
    $this->session = $session;
    $this->uuidGenerator = $uuidGenerator;
  }

  /**
   * @{inheritDoc}
   */
  public function addRemoteUrl(string $url, ?string $filename = NULL): Url {
    $identifier = $this->uuidGenerator->generate();
    $this->session->set('civiremote_entity.remote_url:' . $identifier, $url);

    return Url::fromRoute('civiremote_entity.remote_page_get', ['identifier' => $identifier, 'filename' => $filename]);
  }

  /**
   * @{inheritDoc}
   */
  public function getRemoteUrl(string $identifier): ?string {
    // @phpstan-ignore return.type
    return $this->session->get('civiremote_entity.remote_url:' . $identifier);
  }

}

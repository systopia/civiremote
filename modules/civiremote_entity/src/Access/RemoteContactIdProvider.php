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

namespace Drupal\civiremote_entity\Access;

use Drupal\Core\Session\AccountProxyInterface;

final class RemoteContactIdProvider implements RemoteContactIdProviderInterface {

  private AccountProxyInterface $currentUser;

  public function __construct(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * @{inheritDoc}
   */
  public function getRemoteContactId(): string {
    if (!$this->hasRemoteContactId()) {
      throw new \RuntimeException(sprintf('User "%s" has no remote contact ID', $this->currentUser->getAccountName()));
    }

    // @phpstan-ignore property.notFound
    return $this->currentUser->getAccount()->civiremote_id;
  }

  /**
   * @{inheritDoc}
   */
  public function getRemoteContactIdOrNull(): ?string {
    return $this->hasRemoteContactId() ? $this->getRemoteContactId() : NULL;
  }

  /**
   * @{inheritDoc}
   */
  public function hasRemoteContactId(): bool {
    $account = $this->currentUser->getAccount();
    $remoteContactId = $account->civiremote_id ?? NULL;

    return is_string($remoteContactId) && '' !== $remoteContactId;
  }

}

<?php

/*
 * Copyright (C) 2022 SYSTOPIA GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Drupal\civiremote_activity\Form;

use Drupal\civiremote_activity\Form\RequestHandler\ActivityUpdateFormRequestHandler;
use Drupal\civiremote_entity\Form\AbstractEntityForm;
use Drupal\civiremote_entity\Form\ResponseHandler\FormResponseHandlerInterface;
use Drupal\json_forms\Form\FormArrayFactoryInterface;
use Drupal\json_forms\Form\Validation\FormValidationMapperInterface;
use Drupal\json_forms\Form\Validation\FormValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class ActivityUpdateForm extends AbstractEntityForm {

  /**
   * {@inheritDoc}
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get(FormArrayFactoryInterface::class),
      $container->get(FormValidatorInterface::class),
      $container->get(FormValidationMapperInterface::class),
      $container->get(ActivityUpdateFormRequestHandler::class),
      $container->get(FormResponseHandlerInterface::class),
    );
  }

  public function getFormId(): string {
    return 'civiremote_activity_update_form';
  }

}

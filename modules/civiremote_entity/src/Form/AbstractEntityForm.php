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

namespace Drupal\civiremote_entity\Form;

use Assert\Assertion;
use Drupal\civiremote_entity\Api\Exception\ApiCallFailedException;
use Drupal\civiremote_entity\Api\Form\EntityForm;
use Drupal\civiremote_entity\Form\RequestHandler\FormRequestHandlerInterface;
use Drupal\civiremote_entity\Form\ResponseHandler\FormResponseHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\json_forms\Form\AbstractJsonFormsForm;
use Drupal\json_forms\Form\FormArrayFactoryInterface;
use Drupal\json_forms\Form\Util\FieldNameUtil;
use Drupal\json_forms\Form\Validation\FormValidationMapperInterface;
use Drupal\json_forms\Form\Validation\FormValidatorInterface;
use Opis\JsonSchema\JsonPointer;

abstract class AbstractEntityForm extends AbstractJsonFormsForm {

  protected FormRequestHandlerInterface $formRequestHandler;

  protected FormResponseHandlerInterface $formResponseHandler;

  public function __construct(FormArrayFactoryInterface $formArrayFactory,
    FormValidatorInterface $formValidator,
    FormValidationMapperInterface $formValidationMapper,
    FormRequestHandlerInterface $formRequestHandler,
    FormResponseHandlerInterface $formResponseHandler
  ) {
    parent::__construct($formArrayFactory, $formValidator, $formValidationMapper);
    $this->formRequestHandler = $formRequestHandler;
    $this->formResponseHandler = $formResponseHandler;
  }

  /**
   * @phpstan-param array<int|string, mixed> $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Note: Using underscore case is enforced by Drupal's argument resolver.
   *
   * @phpstan-return array<int|string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (!$form_state->isCached()) {
      try {
        $entityForm = $this->formRequestHandler->getForm($this->getRequest());
      }
      catch (ApiCallFailedException $e) {
        $this->messenger()->addError($this->t('Loading form failed: @error', ['@error' => $e->getMessage()]));
        $entityForm = new EntityForm(new \stdClass(), new \stdClass());
      }
      $form_state->set('jsonSchema', $entityForm->getJsonSchema());
      $form_state->set('uiSchema', $entityForm->getUiSchema());
    }

    return $this->buildJsonFormsForm(
      $form,
      $form_state,
      // @phpstan-ignore-next-line
      $form_state->get('jsonSchema'),
      // @phpstan-ignore-next-line
      $form_state->get('uiSchema'),
    );
  }

  public function validateForm(array &$form, FormStateInterface $formState): void {
    parent::validateForm($form, $formState);
    if (!$formState->isSubmitted() && !$formState->isValidationEnforced()) {
      return;
    }

    if ([] === $formState->getErrors()) {
      $data = $this->getSubmittedData($formState);
      try {
        $validationResponse = $this->formRequestHandler->validateForm($this->getRequest(), $data);
      }
      catch (ApiCallFailedException $e) {
        $formState->setErrorByName(
          '',
          $this->t('Error validating form: @error', ['@error' => $e->getMessage()])->render()
        );

        return;
      }

      if (!$validationResponse->isValid()) {
        $this->mapResponseErrors($validationResponse->getErrors(), $formState);
      }
    }
  }

  /**
   * {@inheritDoc}
   *
   * @phpstan-param array<int|string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $formState): void {
    $data = $this->getSubmittedData($formState);
    try {
      $submitResponse = $this->formRequestHandler->submitForm($this->getRequest(), $data);
    }
    catch (ApiCallFailedException $e) {
      $this->messenger()->addError($this->t('Submitting form failed: @error', ['@error' => $e->getMessage()]));

      return;
    }

    $this->formResponseHandler->handleSubmitResponse($submitResponse, $formState);
  }

  /**
   * @phpstan-param array<string, non-empty-array<string>> $errors
   */
  private function mapResponseErrors(array $errors, FormStateInterface $formState): void {
    foreach ($errors as $field => $messages) {
      $pointer = JsonPointer::parse('/' . $field);
      Assertion::notNull($pointer);
      $absolutePath = $pointer->absolutePath();
      Assertion::notNull($absolutePath);
      $element = ['#parents' => FieldNameUtil::toFormParents($absolutePath)];
      $formState->setError($element, implode("\n", $messages));
    }
  }

  protected function doGetSubmittedData(FormStateInterface $formState): array {
    $data = parent::doGetSubmittedData($formState);
    unset($data['_submit']);

    return $data;
  }

}

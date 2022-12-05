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

namespace Drupal\civiremote_event;


use Drupal;
use Drupal\Core\Messenger\MessengerInterface;

class Utils {

  public static function fieldTypes() {
    return [
      'Text' => 'textfield',
      'Textarea' => 'textarea',
      'Select' => 'select', // Can be replaced with 'radios' in buildForm().
      'Multi-Select' => 'select', // Can be replaced with 'checkboxes' in buildForm().
      'Checkbox' => 'checkbox',
      'Radio' => 'radio',
      'Date' => 'date',
      'Datetime' => 'datetime',
      'Timestamp' => 'date',
      'Value' => 'value',
      'fieldset' => 'fieldset',
    ];
  }

  public static function fieldType($field, $step = 'form') {
    $types = Utils::fieldTypes();
    if (isset($field['validation']) && $field['validation'] == 'Email') {
      $type = 'email';
    }
    // Use radio buttons for select fields with up to 10 options.
    elseif (
      $types[$field['type']] == 'select'
      && count($field['options']) <= 10
    ) {
      $type = $field['type'] == 'Multi-Select' ? 'checkboxes' : 'radios';
    }
    // Use details element for session fieldsets.
    elseif (
      $types[$field['type']] == 'fieldset'
      && isset($field['parent'])
      && $field['parent'] == 'sessions'
      && $step == 'form'
    ) {
      $type = 'details';
    }
    // Use default field types from mapping.
    else {
      $type = $types[$field['type']];
    }

    return $type;
  }

}

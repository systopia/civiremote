CiviRemote
===========

The *CiviRemote* module, and included submodules, is a front-end implementation
of the [*CiviRemote*](https://github.com/systopia/de.systopia.remotetools)
framework, and specifically for the [*CiviRemote
Event* CiviCRM extension](https://github.com/systopia/de.systopia.remoteevent),
allowing for CiviCRM event registration (including update/cancellation) on a
Drupal website with connection to a CiviCRM instance via the [
*CiviMRF*](https://drupal.org/project/cmrf_core) framework.

## CiviRemote Entity

The submodule [*CiviRemote Entity*](modules/civiremote_entity) contains base
classes for remote entity forms to create and update CiviCRM entities via
remote API built with
[*CiviRemote*](https://github.com/systopia/de.systopia.remotetools). You can
find an implementation for the *Case* entity in
[modules/civiremote_case](modules/civiremote_case) that can be used as template
for other entities.

*CiviRemote Entity* contains a
[response handler](modules/civiremote_entity/src/Form/ResponseHandler/FormResponseHandlerRedirect.php)
that tries to redirect to the update form after entity creation. It should work
in most cases. If a concrete submodule uses non-standard paths, it is always
possible to use custom response handlers when creating the
[`AbstractEntityForm`](modules/civiremote_entity/src/Form/AbstractEntityForm.php).

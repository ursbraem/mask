<?php

use MASK\Mask\Controller\AjaxController;
use MASK\Mask\Controller\WizardController;

return [
    'mask_check_field_key' => [
        'path' => '/mask/checkFieldKey',
        'target' => WizardController::class . '::checkFieldKey'
    ],
    'mask_check_element_key' => [
        'path' => '/mask/checkElementKey',
        'target' => WizardController::class . '::checkElementKey'
    ],
    'mask_fieldtypes' => [
        'path' => '/mask/fieldTypes',
        'target' => AjaxController::class . '::fieldTypes'
    ],
    'mask_icons' => [
        'path' => '/mask/icons',
        'target' => AjaxController::class . '::icons'
    ],
    'mask_existing_tca' => [
        'path' => '/mask/existingTca',
        'target' => AjaxController::class . '::existingTca'
    ],
    'mask_tca_fields' => [
        'path' => '/mask/tcaFields',
        'target' => AjaxController::class . '::tcaFields'
    ],
    'mask_tabs' => [
        'path' => '/mask/tabs',
        'target' => AjaxController::class . '::tabs'
    ],
    'mask_language' => [
        'path' => '/mask/language',
        'target' => AjaxController::class . '::language'
    ],
    'mask_richtext_configuration' => [
        'path' => '/mask/richtextConfiguration',
        'target' => AjaxController::class . '::richtextConfiguration'
    ],
    'mask_ctypes' => [
        'path' => '/mask/ctypes',
        'target' => AjaxController::class . '::cTypes'
    ]
];

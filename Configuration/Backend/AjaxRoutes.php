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
    'mask_tca' => [
        'path' => '/mask/tca',
        'target' => AjaxController::class . '::tca'
    ]
];

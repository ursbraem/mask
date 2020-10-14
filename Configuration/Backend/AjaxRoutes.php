<?php

declare(strict_types=1);

return [
    'mask_check_field_key' => [
        'path' => '/mask/checkFieldKey',
        'target' => \MASK\Mask\Controller\WizardController::class . '::checkFieldKey'
    ],
    'mask_check_element_key' => [
        'path' => '/mask/checkElementKey',
        'target' => \MASK\Mask\Controller\WizardController::class . '::checkElementKey'
    ],
    'mask_fieldtypes' => [
        'path' => '/mask/fieldTypes',
        'target' => \MASK\Mask\Controller\AjaxController::class . '::fieldTypes'
    ],
    'mask_icons' => [
        'path' => '/mask/icons',
        'target' => \MASK\Mask\Controller\AjaxController::class . '::icons'
    ]
];

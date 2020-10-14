<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace MASK\Mask\Controller;

use MASK\Mask\DataStructure\FieldType;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class AjaxController extends ActionController
{
    public function fieldTypes(ServerRequestInterface $request): Response
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $json = [];
        foreach (FieldType::getConstants() as $type) {
            $json[] = [
                'name' => GeneralUtility::underscoredToUpperCamelCase($type),
                'icon' => $iconFactory->getIcon('mask-fieldtype-' . $type)->getMarkup(),
                'fields' => []
            ];
        }
        return new JsonResponse($json);
    }
}

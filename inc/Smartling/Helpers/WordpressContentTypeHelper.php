<?php

namespace Smartling\Helpers;

use Smartling\Bootstrap;
use Smartling\ContentTypes\ContentTypeInterface;
use Smartling\ContentTypes\ContentTypeManager;
use Smartling\Exception\SmartlingDirectRunRuntimeException;

/**
 * Class WordpressContentTypeHelper
 * @package Smartling\Helpers
 */
class WordpressContentTypeHelper
{
    /**
     * Checks if Wordpress i10n function __ is registered
     * if not - throws an SmartlingDirectRunRuntimeException exception
     * @throws SmartlingDirectRunRuntimeException
     */
    private static function checkRuntimeState()
    {
        if (!function_exists('__')) {
            $message = 'I10n Wordpress function not available on direct execution.';
            throw new SmartlingDirectRunRuntimeException($message);
        }
    }

    private static function getDynamicReverseMap()
    {
        /**
         * @var ContentTypeManager $contentTypeManager
         */
        $contentTypeManager = Bootstrap::getContainer()->get('content-type-descriptor-manager');

        $map = [];
        foreach ($contentTypeManager->getRegisteredContentTypes() as $type) {
            $map[$type] = $type;
        }

        return $map;
    }

    public static $internalTypes = [];

    /**
     * @return array
     * @throws SmartlingDirectRunRuntimeException
     */
    public static function getReverseMap()
    {
        self::checkRuntimeState();

        return array_merge(self::$internalTypes, self::getDynamicReverseMap());
    }

    private static function getDynamicLabelMap()
    {
        /**
         * @var ContentTypeManager $contentTypeManager
         */
        $contentTypeManager = Bootstrap::getContainer()->get('content-type-descriptor-manager');

        $map = [];
        foreach ($contentTypeManager->getRegisteredContentTypes() as $type) {
            $map[$type] = $contentTypeManager->getHandler($type)->getLabel();
        }

        return $map;
    }

    /**
     * @return array
     * @throws SmartlingDirectRunRuntimeException
     */
    public static function getLabelMap()
    {
        self::checkRuntimeState();

        return self::getDynamicLabelMap();
    }

    public static function getTypesRestrictedToBulkSubmit()
    {
        $mgr = Bootstrap::getContainer()->get('content-type-descriptor-manager');
        /**
         * @var ContentTypeManager $mgr
         */
        $descriptors = $mgr->getRestrictedForBulkSubmit();

        return [$descriptors];
    }

    /**
     * @return array
     */
    public static function getSupportedTaxonomyTypes()
    {
        $mgr = Bootstrap::getContainer()->get('content-type-descriptor-manager');
        /**
         * @var ContentTypeManager $mgr
         */
        $descriptors = $mgr->getDescriptorsByBaseType('taxonomy');

        $dynamicallyRegisteredTaxonomies = [];

        foreach ($descriptors as $descriptor) {
            $dynamicallyRegisteredTaxonomies[] = $descriptor->getSystemName();
        }

        return $dynamicallyRegisteredTaxonomies;
    }

    /**
     * @param $contentType
     *
     * @return string
     */
    public static function getLocalizedContentType($contentType)
    {
        $map = self::getLabelMap();
        if (array_key_exists($contentType, $map)) {
            return $map[$contentType];
        } else {
            $mgr = Bootstrap::getContainer()->get('content-type-descriptor-manager');
            $message = vsprintf('Content-type \'%s\' is not supported.', [$contentType]);
            /**
             * @var ContentTypeManager $mgr
             */
            try {
                $descriptor = $mgr->getDescriptorByType($contentType);

                if ($descriptor instanceof ContentTypeInterface) {
                    return $descriptor->getLabel();
                } else {
                    Bootstrap::getLogger()->warning($message);
                }
            } catch (\Exception $e) {
                Bootstrap::getLogger()->warning($e->getMessage());
                Bootstrap::getLogger()->warning($message);
            }

            return $contentType;
        }
    }
}
<?php

namespace Smartling\Helpers;

use Psr\Log\LoggerInterface;
use Smartling\Bootstrap;
use Smartling\Settings\ConfigurationProfileEntity;
use Smartling\Settings\SettingsManager;
use Smartling\Submissions\SubmissionEntity;

/**
 * Class ContentSerializationHelper
 * @package Smartling\Helpers
 */
class ContentSerializationHelper
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ContentHelper
     */
    private $contentHelper;

    /**
     * @var FieldsFilterHelper
     */
    private $fieldsFilter;

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return ContentHelper
     */
    public function getContentHelper()
    {
        return $this->contentHelper;
    }

    /**
     * @param ContentHelper $contentHelper
     */
    public function setContentHelper($contentHelper)
    {
        $this->contentHelper = $contentHelper;
    }

    /**
     * @return FieldsFilterHelper
     */
    public function getFieldsFilter()
    {
        return $this->fieldsFilter;
    }

    /**
     * @param FieldsFilterHelper $fieldsFilter
     */
    public function setFieldsFilter($fieldsFilter)
    {
        $this->fieldsFilter = $fieldsFilter;
    }

    /**
     * ContentSerializationHelper constructor.
     *
     * @param LoggerInterface    $logger
     * @param ContentHelper      $contentHelper
     * @param FieldsFilterHelper $fieldsFilter
     */
    public function __construct(LoggerInterface $logger, ContentHelper $contentHelper, FieldsFilterHelper $fieldsFilter)
    {
        $this->setLogger($logger);
        $this->setContentHelper($contentHelper);
        $this->setFieldsFilter($fieldsFilter);
    }

    private function cleanUpFields(array $fields)
    {
        $toRemove = [
            'entity' => [
                'hash',
                'post_author',
                'post_date',
                'post_date_gmt',
                'post_password',
                'post_modified',
                'post_modified_gmt',
            ],
            'meta'   => [
                '_edit_lock',
                '_edit_last',
                '_encloseme',
            ],
        ];

        foreach ($toRemove as $part => $keys) {
            foreach ($keys as $key) {
                if (array_key_exists($part, $fields) && array_key_exists($key, $fields[$part])) {
                    unset($fields[$part][$key]);
                }
            }
        }

        return $fields;
    }

    /**
     * @param SubmissionEntity $submission
     *
     * @return string
     */
    public function calculateHash(SubmissionEntity $submission)
    {
        $cache = RuntimeCacheHelper::getInstance();
        $key = implode(
            ':',
            [
                $submission->getSourceBlogId(),
                $submission->getContentType(),
                $submission->getSourceId(),
            ]
        );

        if (false === ($cached = $cache->get($key, 'hashCalculator'))) {
            $collectedContent = $this->collectSubmissionSourceContent($submission);
            $collectedContent = $this->cleanUpFields($collectedContent);
            $hash = md5(serialize($collectedContent));
            $cached = $hash;
            $cache->set($key, $hash, 'hashCalculator');
        }

        return $cached;
    }


    /**
     * @param SubmissionEntity $submission
     *
     * @return array
     */
    private function collectSubmissionSourceContent(SubmissionEntity $submission)
    {
        $source = [
            'entity' => $this->getContentHelper()->readSourceContent($submission)->toArray(false),
            'meta'   => $this->getContentHelper()->readSourceMetadata($submission),
        ];
        $source['meta'] = $source['meta'] ? : [];

        return $source;
    }

    /**
     * Sets filed processor rules depending on profile settings
     *
     * @param SettingsManager  $settingsManager
     * @param SubmissionEntity $submission
     *
     * @throws \Smartling\Exception\SmartlingConfigException
     */
    public static function prepareFieldProcessorValues(SettingsManager $settingsManager, SubmissionEntity $submission)
    {
        $profiles = $settingsManager->findEntityByMainLocale($submission->getSourceBlogId());

        $filter = [
            'ignore' => [],
            'key'    => [
                'seo' => [],
            ],
            'copy'   => [
                'name'   => [],
                'regexp' => [],
            ],
        ];

        if (0 < count($profiles)) {
            /**
             * @var ConfigurationProfileEntity $profile
             */
            $profile = ArrayHelper::first($profiles);

            $filter['ignore'] = array_map('trim', explode(PHP_EOL, $profile->getFilterSkip()));
            $filter['key']['seo'] = array_map('trim', explode(PHP_EOL, $profile->getFilterFlagSeo()));
            $filter['copy']['name'] = array_map('trim', explode(PHP_EOL, $profile->getFilterCopyByFieldName()));
            $filter['copy']['regexp'] = array_map('trim', explode(PHP_EOL, $profile->getFilterCopyByFieldValueRegex()));
        }

        Bootstrap::getContainer()->setParameter('field.processor', $filter);
    }
}
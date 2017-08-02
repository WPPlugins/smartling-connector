<?php

namespace Smartling\Helpers\MetaFieldProcessor;

use Smartling\Exception\SmartlingDbException;
use Smartling\Exception\SmartlingWpDataIntegrityException;

/**
 * Class ReferencedPostBasedContentProcessor
 * @package Smartling\Helpers\MetaFieldProcessor
 */
class ReferencedPostBasedContentProcessor extends ReferencedStdBasedContentProcessorAbstract
{
    /**
     * @param int $blogId
     * @param int $contentId
     *
     * @return string
     * @throws SmartlingWpDataIntegrityException
     */
    protected function detectRealContentType($blogId, $contentId)
    {
        try {
            $this->getContentHelper()->ensureBlog($blogId);
            $post = get_post($contentId);
            $this->getContentHelper()->ensureRestoredBlogId();

            if ($post instanceof \WP_Post) {
                return $post->post_type;
            } else {
                $message = vsprintf('The post-based content with id=\'%s\' not found in blog=\'%s\'', [$contentId,
                                                                                                       $blogId]);
                throw new SmartlingDbException($message);
            }
        } catch (\Exception $e) {
            $message = vsprintf('Error happened while detecting the real content type for content id=\'%s\' blog = \'%s\'', [$contentId,
                                                                                                                             $blogId]);
            throw new SmartlingWpDataIntegrityException($message, 0, $e);
        }
    }
}


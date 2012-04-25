<?php

namespace Parizz\CacheValidationBundle;

interface ValidationProcessorInterface
{
    /**
     * The process method generates and returns the eTag and/or 
     * lastModified values.
     *
     * ex: return array('etag' => azerto, 'last_modified' => new DateTime)
     *
     * @return array
     */
    function process();
}

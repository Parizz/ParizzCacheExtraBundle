<?php

namespace Parizz\CacheExtraBundle\Annotation;

use Parizz\CacheExtraBundle\Processor\ValidationProcessorInterface;

/**
 * The CacheValidation class handles the @CacheValidation annotation parts.
 *
 * @Annotation
 */
class CacheValidation
{
    /**
     * @var \Parizz\CacheExtraBundle\Processor\ValidationProcessorInterface
     */
    private $processor;
    
    /**
     * @var string
     */
    private $eTag;
    /**
     * @var \DateTime
     */
    private $lastModified;

    /**
     * Constructor.
     *
     * @param array $values Attributes from the annotation
     */
    public function __construct(array $values)
    {
        if (isset($values['value'])) {
            $values['processor'] = $values['value'];
        }

        if (!isset($values['processor'])) {
            throw new \InvalidArgumentException('No "processor" given for CacheValidation annotation');
        }

        $processor = new $values['processor'];

        if (!$processor instanceof ValidationProcessorInterface) {
            throw new \RuntimeException(sprintf('A cache validation processor has to implement the ValidationProcessorInterface, %s given.', get_class($processor)));
        }

        $this->processor = $processor;
    }

    /**
     * Gets the Validation headers provider.
     *
     * @return \Parizz\CacheBundle\Validation\ValidationProviderInterface
     */
    public function getProcessor()
    {
        return $this->processor;
    }

    /**
     * Gets the eTag value.
     *
     * @return string
     */
    public function getETag()
    {
        return $this->eTag;
    }

    /**
     * Sets the eTag value.
     *
     * @param string $eTag
     */
    public function setETag($eTag)
    {
        $this->eTag = $eTag;
    }

    /**
     * Gets the lastModified value.
     *
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * Sets the lastModified value.
     *
     * @param \DateTime $lastModified
     */
    public function setLastModified(\DateTime $lastModified)
    {
        $this->lastModified = $lastModified;
    }
}
<?php

namespace Parizz\CacheExtraBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpFoundation\Response;
use Parizz\CacheExtraBundle\Annotation\CacheValidation;
use Parizz\CacheExtraBundle\Processor\ValidationProcessorInterface;

class ResponseValidatorListener
{
    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    private $reader;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructor.
     *
     * @param Reader $reader An Reader instance
     * @param ContainerInterFace $container The container
     */
    public function __construct(Reader $reader, ContainerInterFace $container)
    {
        $this->reader = $reader;
        $this->container = $container;
    }

    /**
     * Modifies the Request object to apply cache validation configuration provider
     *
     * @param FilterControllerEvent $event A FilterControllerEvent instance
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        $object = new \ReflectionObject($controller[0]);
        $method = $object->getMethod($controller[1]);

        foreach ($this->reader->getMethodAnnotations($method) as $configuration) {
            if ($configuration instanceof CacheValidation) {
                $event->getRequest()->attributes->set('_cache_validation', $configuration);

                $response = $this->populateResponse($configuration);

                // if the response is valid, we return it
                if ($response->isNotModified($event->getRequest())) {
                    $returnNotModifiedResponse = function() use ($response) {
                        return $response;
                    };

                    $event->setController($returnNotModifiedResponse);
                }
            }
        }
    }

    /**
     * Modifies the response to apply HTTP expiration/validation header fields.
     *
     * @param FilterResponseEvent $event The notified event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$configuration = $event->getRequest()->attributes->get('_cache_validation')) {
            return;
        }

        $response = $event->getResponse();

        if (!$response->isSuccessful()) {
            return;
        }

        $this->populateResponse($configuration, $response);

        $event->setResponse($response);
    }

    /**
     * Modifies or create a response and apply HTTP validation header fields
     *
     * @param CacheValidation $configuration The annotation configuration
     * @param Response $response The response to populate
     */
    private function populateResponse($configuration, $response = null)
    {
        if (!$response) {
            $response = new Response;
            $processor = $configuration->getProcessor();

            if ($processor instanceof ContainerAwareInterface) {
                $processor->setContainer($this->container);
            }

            $validationHeaders = $processor->process();

            if (isset($validationHeaders['etag'])) {
                $configuration->setETag($validationHeaders['etag']);
            }
            if (isset($validationHeaders['last_modified'])) {
                $configuration->setLastModified($validationHeaders['last_modified']);
            }
        }

        if ($etag = $configuration->getETag()) {
            $response->setETag($etag);
        }
        if ($lastModified = $configuration->getLastModified()) {
            $response->setLastModified($lastModified);
        }

        return $response;
    }
}
<?php

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\RestBundle\Routing\Loader\Reader;

use Symfony\Component\Config\Resource\FileResource;
use Doctrine\Common\Annotations\Reader;
use FOS\RestBundle\Routing\RestRouteCollection;

/**
 * REST controller reader.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class RestControllerReader
{
    private $actionReader;
    private $annotationReader;

    /**
     * Initializes controller reader.
     *
     * @param RestActionReader $actionReader     action reader
     * @param Reader           $annotationReader annotation reader
     */
    public function __construct(RestActionReader $actionReader, Reader $annotationReader)
    {
        $this->actionReader     = $actionReader;
        $this->annotationReader = $annotationReader;
    }

    /**
     * Returns action reader.
     *
     * @return RestActionReader
     */
    public function getActionReader()
    {
        return $this->actionReader;
    }

    /**
     * Reads controller routes.
     *
     * @param ReflectionClass $reflection
     *
     * @return RestRouteCollection
     */
    public function read(\ReflectionClass $reflection, $type = 'rest')
    {
        $collection = new RestRouteCollection();
        $collection->addResource(new FileResource($reflection->getFileName()));

        // read prefix annotation
        if ($annotation = $this->readClassAnnotation($reflection, 'Prefix')) {
            $this->actionReader->setRoutePrefix($annotation->value);
        }

        // read name-prefix annotation
        if ($annotation = $this->readClassAnnotation($reflection, 'NamePrefix')) {
            $this->actionReader->setNamePrefix($annotation->value);
        }

        // trim '/' at the start of the prefix
        if ('/' === substr($prefix = $this->actionReader->getRoutePrefix(), 0, 1)) {
            $this->actionReader->setRoutePrefix(substr($prefix, 1));
        }

        $resource = null;
        if ('rest_class' === $type) {
            if (!preg_match('/([_a-zA-Z0-9]+)Controller/', $reflection->getShortName(), $matches)) {
                throw new \Exception('Cannot split off resource name from controller name ');
            }

            $resource = $matches[1];
        }

        // read action routes into collection
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $this->actionReader->read($collection, $method, $resource);
        }

        $this->actionReader->setRoutePrefix(null);
        $this->actionReader->setNamePrefix(null);
        $this->actionReader->setParents(array());

        return $collection;
    }

    /**
     * Reads
     *
     * @param ReflectionClass $reflection     controller class
     * @param string          $annotationName annotation name
     *
     * @return Annotation|null
     */
    private function readClassAnnotation(\ReflectionClass $reflection, $annotationName)
    {
        $annotationClass = "FOS\\RestBundle\\Controller\\Annotations\\$annotationName";

        if ($annotation = $this->annotationReader->getClassAnnotation($reflection, $annotationClass)) {
            return $annotation;
        }
    }
}

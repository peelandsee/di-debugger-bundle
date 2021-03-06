<?php
/**
 * Created by PhpStorm.
 * User: matteo
 * Date: 03/09/14
 * Time: 23.28
 */

namespace Cypress\DiDebuggerBundle\Checker\Checker;

use Cypress\DiDebuggerBundle\Checker\ServiceDescriptor;
use Cypress\DiDebuggerBundle\Exception\NonExistentFactoryMethodException;
use Cypress\DiDebuggerBundle\Exception\TooFewConstructorCountArguments;
use Cypress\DiDebuggerBundle\Exception\TooFewParameters;
use Cypress\DiDebuggerBundle\Exception\TooManyConstructorCountArguments;
use Cypress\DiDebuggerBundle\Exception\TooManyParameters;

class ArgumentsCountChecker extends BaseChecker implements Checker
{
    /**
     * @param ServiceDescriptor $sd
     * @throws TooFewConstructorCountArguments
     * @throws TooManyConstructorCountArguments
     * @return void
     */
    public function check(ServiceDescriptor $sd)
    {
        if ($sd->isAlias()) {
            return;
        }
        $definition = $sd->getDefinition();
        $class = $definition->getClass();
        if ($this->isParameter($class)) {
            $class = $sd->getContainer()->getParameter($this->parameterName($class));
        }
        $reflection = new \ReflectionClass($class);
        if (($factoryClass = $definition->getFactoryClass()) !== null) {
            if ($this->isParameter($factoryClass)) {
                $factoryClass = $sd->getContainer()->getParameter($this->parameterName($factoryClass));
            }
            $this->checkFactoryClass($sd, $factoryClass);
            return;
        }
        if (($factoryService = $definition->getFactoryService()) !== null) {
            $factoryService = $sd->getContainer()->get($factoryService);
            $this->checkFactoryService($sd, $factoryService);
            return;
        }
        $constructor = $reflection->getConstructor();
        if (is_null($constructor) && 0 === count($definition->getArguments())) {
            return;
        }
        $this->compare($sd, $definition->getArguments(), $constructor->getParameters());
    }

    /**
     * @param ServiceDescriptor $sd
     * @param $factoryClass
     * @throws NonExistentFactoryMethodException
     */
    private function checkFactoryClass(ServiceDescriptor $sd, $factoryClass)
    {
        $definition = $sd->getDefinition();
        $reflection = new \ReflectionClass($factoryClass);
        $factoryMethod = $definition->getFactoryMethod();
        if (! $reflection->hasMethod($factoryMethod)) {
            throw new NonExistentFactoryMethodException('error, factory method do not esists');
        }
    }

    /**
     * @param ServiceDescriptor $sd
     * @param $factoryService
     * @throws NonExistentFactoryMethodException
     */
    private function checkFactoryService(ServiceDescriptor $sd, $factoryService)
    {
        $definition = $sd->getDefinition();
        $reflection = new \ReflectionClass($factoryService);
        $factoryMethod = $definition->getFactoryMethod();
        if (! $reflection->hasMethod($factoryMethod)) {
            throw new NonExistentFactoryMethodException('error, factory method do not esists');
        }
        $method = $reflection->getMethod($factoryMethod);
        $this->compare($sd, $definition->getArguments(), $method->getParameters());
    }

    /**
     * @param ServiceDescriptor $sd
     * @param array $definitionArguments
     * @param \ReflectionParameter[] $methodParameters
     */
    private function compare(ServiceDescriptor $sd, $definitionArguments, $methodParameters) {
        $min = array_reduce($methodParameters, function ($min, \ReflectionParameter $parameter) {
            return $parameter->isOptional() ? $min : $min + 1;
        });
        if (count($definitionArguments) > count($methodParameters)) {
            $e = new TooManyParameters();
            $e->setServiceDescriptor($sd);
            $e->setArguments($definitionArguments, $methodParameters);
            throw $e;
        }
        if (count($definitionArguments) < $min) {
            throw new TooFewParameters();
        }
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 20;
    }
}

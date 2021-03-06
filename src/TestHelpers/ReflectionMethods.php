<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/6/18
 * Time: 7:07 PM
 */

namespace Tfboe\FmLib\TestHelpers;

/**
 * Trait ReflectionMethods
 * @package Tfboe\FmLib\TestHelpers
 */
trait ReflectionMethods
{
//<editor-fold desc="Protected Methods">
  /** @noinspection PhpDocMissingThrowsInspection */ //ReflectionException
  /**
   * Calls the given protected method on the given object with the given arguments
   * @param mixed $object the object to call on
   * @param string $method the method name
   * @param mixed[] $args the arguments for the method
   * @return mixed the return value of the method
   */
  protected static function callProtectedMethod($object, string $method, array $args = [])
  {
    // ReflectionException => get_class is a valid class
    /** @noinspection PhpUnhandledExceptionInspection */
    return self::getMethod(get_class($object), $method)->invokeArgs($object, $args);
  }

  /**
   * Gets a protected or private method and makes it accessible
   * @param string $class the class name
   * @param string $name the method name
   * @return \ReflectionMethod the accessible method object
   * @throws \ReflectionException the given class does not exist
   */
  protected static function getMethod(string $class, string $name): \ReflectionMethod
  {
    $class = new \ReflectionClass($class);
    $method = $class->getMethod($name);
    $method->setAccessible(true);
    return $method;
  }

  /**
   * Gets a protected or private property and makes it accessible
   * @param string $class the class name
   * @param string $name the method name
   * @return \ReflectionProperty the accessible property object
   * @throws \ReflectionException the given class does not exist
   */
  protected static function getProperty(string $class, string $name): \ReflectionProperty
  {
    $class = new \ReflectionClass($class);
    /** @noinspection PhpStatementHasEmptyBodyInspection */
    while (!$class->hasProperty($name) && ($class = $class->getParentClass()) !== null) {
    }
    $property = $class->getProperty($name);
    $property->setAccessible(true);
    return $property;
  }
//</editor-fold desc="Protected Methods">
}
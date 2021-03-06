<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 9/18/17
 * Time: 10:24 PM
 */

namespace Tfboe\FmLib\Helpers;

use Tfboe\FmLib\Exceptions\ValueNotValid;

/**
 * Class BasicEnum
 * @package Tfboe\FmLib\Entity\Categories
 */
abstract class BasicEnum
{
//<editor-fold desc="Fields">
  /** @var null|mixed[][] */
  private static $constCacheArray = NULL;

  /** @var null|string[][] */
  private static $constCacheArrayCaseMapping = NULL;

  /** @var null|string[][] */
  private static $nameArray = NULL;
//</editor-fold desc="Fields">

//<editor-fold desc="Public Methods">
  /**
   * Ensures that the given value is valid by throwing an exception if it is not valid
   * @param mixed $value the value to check for validity
   * @param bool $strict if yes type checks are performed
   * @throws ValueNotValid if the value is not valid
   */
  public static function ensureValidValue($value, bool $strict = true): void
  {
    if (!self::isValidValue($value, $strict)) {
      throw new ValueNotValid($value, get_called_class());
    }
  }

  /**
   * Gets the name of the given value, throwing an exception if the value is not valid
   * @param mixed $value the value to get the name for
   * @return string the name of the value
   * @throws ValueNotValid if the value is not valid
   */
  public static function getName($value): string
  {
    $names = self::getNamesArray();
    if (array_key_exists($value, $names)) {
      return $names[$value];
    }

    throw new ValueNotValid($value, get_called_class());
  }

  /**
   * Gets a list of all names in this enum
   * @return mixed[]
   */
  public static function getNames(): array
  {
    return array_keys(self::getConstants());
  }

  /**
   * Gets the value corresponding to the given name
   * @param string $name the name for which to get the value
   * @param bool $strict if yes retrieval is done case sensitive and otherwise case insensitive
   * @return mixed the corresponding value
   * @throws ValueNotValid if the name is not valid
   */
  public static function getValue(string $name, bool $strict = false)
  {
    $constants = self::getConstants();
    if ($strict) {
      if (array_key_exists($name, $constants)) {
        return $constants[$name];
      }
    } else {
      $mapping = self::getCaseMapping();
      $key = strtolower($name);
      if (array_key_exists($key, $mapping)) {
        return $constants[$mapping[$key]];
      }
    }

    throw new ValueNotValid($name, get_called_class(), "getValues");
  }

  /**
   * Gets a list of all values in this enum
   * @return mixed[]
   */
  public static function getValues(): array
  {
    return array_values(self::getConstants());
  }

  /**
   * Checks if a given name is part of this enum
   * @param string $name the name to check for validity
   * @param bool $strict if yes check is done case sensitive and otherwise case insensitive
   * @return bool true if the name is part of the enum and false otherwise
   */
  public static function isValidName(string $name, bool $strict = false): bool
  {
    $constants = self::getConstants();

    if ($strict) {
      return array_key_exists($name, $constants);
    }

    $keys = array_map('strtolower', array_keys($constants));
    return in_array(strtolower($name), $keys);
  }

  /**
   * Checks if a given value is part of this enum
   * @param mixed $value the value to check for validity
   * @param bool $strict if yes type checks are performed
   * @return bool true if the value is part of the enum and false otherwise
   */
  public static function isValidValue($value, bool $strict = true): bool
  {
    $values = self::getValues();
    return in_array($value, $values, $strict);
  }
//</editor-fold desc="Public Methods">

//<editor-fold desc="Private Methods">

  /**
   * Gets a case mapping which maps a lower case names to the real enum names
   * @return mixed[]
   */
  private static function getCaseMapping(): array
  {
    if (self::$constCacheArrayCaseMapping == NULL) {
      self::$constCacheArrayCaseMapping = [];
    }
    $calledClass = get_called_class();
    if (!array_key_exists($calledClass, self::$constCacheArrayCaseMapping)) {
      self::$constCacheArrayCaseMapping[$calledClass] = [];
      foreach (self::getNames() as $name) {
        self::$constCacheArrayCaseMapping[$calledClass][strtolower($name)] = $name;
      }
    }
    return self::$constCacheArrayCaseMapping[$calledClass];
  }

  /** @noinspection PhpDocMissingThrowsInspection */ //ReflectionException
  /**
   * Gets a dictionary of all constants in this enum
   * @param string|null $calledClass
   * @return mixed[]
   */
  private static function getConstants($calledClass = null): array
  {
    if (self::$constCacheArray == NULL) {
      self::$constCacheArray = [];
    }
    if ($calledClass === null) {
      $calledClass = get_called_class();
    }
    if (!array_key_exists($calledClass, self::$constCacheArray)) {
      // ReflectionException => whe know that calledClass is a valid class since it is the result of get_called_class
      /** @noinspection PhpUnhandledExceptionInspection */
      $reflect = new \ReflectionClass($calledClass);
      $array = $reflect->getConstants();
      asort($array);
      self::$constCacheArray[$calledClass] = $array;
    }
    return self::$constCacheArray[$calledClass];
  }

  /**
   * Gets a dictionary mapping values to names in this enum
   * @param string|null $calledClass
   * @return string[]
   */
  private static function getNamesArray($calledClass = null): array
  {
    if (self::$nameArray == NULL) {
      self::$nameArray = [];
    }
    if ($calledClass === null) {
      $calledClass = get_called_class();
    }
    if (!array_key_exists($calledClass, self::$nameArray)) {
      self::$nameArray[$calledClass] = array_flip(self::getConstants($calledClass));
    }
    return self::$nameArray[$calledClass];
  }
//</editor-fold desc="Private Methods">
}
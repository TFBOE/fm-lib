<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 10/14/18
 * Time: 12:16 PM
 */

namespace Tfboe\FmLib\Helpers;


use Doctrine\ORM\EntityManagerInterface;
use Tfboe\FmLib\Entity\Helpers\BaseEntityInterface;

/**
 * Class Tools
 * @package Tfboe\FmLib\Helpers
 */
class Tools
{
//<editor-fold desc="Fields">
  /**
   * @var string
   */
  private static $datetimetzFormat = 'Y-m-d H:i:s e';
//</editor-fold desc="Fields">

//<editor-fold desc="Public Methods">
  /**
   * Gets a transformation function which transforms a string in datetime format into a datetime with the given timezone
   * @return \Closure the function which transforms a string into a datetime
   */
  public static function datetimetzTransformer(): \Closure
  {
    return function ($dateString) {
      return \DateTime::createFromFormat(self::$datetimetzFormat, $dateString);
    };
  }

  /**
   * Gets a transformation function which transforms an enum name into the corresponding value
   * @param string $enumName the name of the enum
   * @return \Closure the function which transforms a name into the enum value
   */
  public static function enumTransformer(string $enumName): \Closure
  {
    return function ($name) use ($enumName) {
      return call_user_func([$enumName, "getValue"], $name);
    };
  }

  /**
   * @return string
   */
  public static function getDatetimetzFormat(): string
  {
    return self::$datetimetzFormat;
  }

  /**
   * Fills an object with the information of inputArray
   * @param BaseEntityInterface $object the object to fill
   * @param array $specification the specification how to fill the object
   * @param array $inputArray the input array
   * @return mixed the object
   */
  public static function setFromSpecification(BaseEntityInterface $object, array $specification, array $inputArray,
                                              EntityManagerInterface $entityManager)
  {
    foreach ($specification as $key => $values) {
      if (!array_key_exists('ignore', $values) || $values['ignore'] != true) {
        $matches = [];
        preg_match('/[^\.]*$/', $key, $matches);
        $arrKey = $matches[0];
        if (array_key_exists('property', $values)) {
          $property = $values['property'];
        } else {
          $property = $arrKey;
        }
        $setter = 'set' . ucfirst($property);
        if (array_key_exists($arrKey, $inputArray)) {
          $value = $inputArray[$arrKey];
          self::transformValue($value, $values, $entityManager);
          $object->$setter($value);
        } else if (array_key_exists('default', $values) && $object->methodExists($setter)) {
          $object->$setter($values['default']);
        }
      }
    }
    return $object;
  }
//</editor-fold desc="Public Methods">

//<editor-fold desc="Private Methods">
//</editor-fold desc="Protected Methods">
  /**
   * Transforms a value from a standard json communication format to its original php format. Counter part of
   * valueToJson().
   * @param string $value the json representation of the value
   * @param string $type the type of the value
   * @return mixed the real php representation of the value
   */
  private static function transformByType($value, $type)
  {
    if (strtolower($type) === 'date' || strtolower($type) === 'datetime') {
      return new \DateTime($value);
    }
    return $value;
  }

  /**
   * Transforms the given value based on different configurations in specification.
   * @param mixed $value the value to optionally transform
   * @param array $specification the specification for this value
   */
  private static function transformValue(&$value, array $specification, EntityManagerInterface $entityManager)
  {
    if (array_key_exists('reference', $specification)) {
      $value = $entityManager->find($specification['reference'], $value);
    }
    if (array_key_exists('type', $specification)) {
      $value = self::transformByType($value, $specification['type']);
    }
    if (array_key_exists('transformer', $specification)) {
      $value = $specification['transformer']($value);
    }
  }
}
<?php
declare(strict_types=1);


namespace Tfboe\FmLib\Helpers;


use Closure;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tfboe\FmLib\Entity\Helpers\BaseEntityInterface;

/**
 * Trait SpecificationHandler
 * @package Tfboe\FmLib\Helpers
 */
trait SpecificationHandler
{
//<editor-fold desc="Fields">
  /**
   * @var string
   */
  private $datetimetzFormat = 'Y-m-d H:i:s e';
//</editor-fold desc="Fields">

//<editor-fold desc="Protected Final Methods">
  /**
   * @return string
   */
  final protected function getDatetimetzFormat(): string
  {
    return $this->datetimetzFormat;
  }
//</editor-fold desc="Protected Final Methods">

//<editor-fold desc="Protected Methods">
  /**
   * Gets a transformation function which transforms a string in datetime format into a datetime with the given timezone
   * @return Closure the function which transforms a string into a datetime
   */
  protected function datetimetzTransformer(): Closure
  {
    return TransformerFactory::datetimetzTransformer($this->datetimetzFormat);
  }

  /**
   * Gets a transformation function which transforms an enum name into the corresponding value
   * @param string $enumName the name of the enum
   * @return Closure the function which transforms a name into the enum value
   */
  protected function enumTransformer(string $enumName): Closure
  {
    return TransformerFactory::enumTransformer($enumName);
  }

  /**
   * @param $class
   * @param $id
   * @return mixed
   */
  abstract protected function getReference($class, $id);

  /**
   * Fills an object with the information of inputArray
   * @param BaseEntityInterface $object the object to fill
   * @param array $specification the specification how to fill the object
   * @param array $inputArray the input array
   * @param bool $useDefaults if false defaults are never used
   * @return mixed the object
   */
  protected function setFromSpecification(BaseEntityInterface $object, array $specification, array $inputArray,
                                          bool $useDefaults = true)
  {
    foreach ($specification as $key => $values) {
      if (!array_key_exists('ignore', $values) || $values['ignore'] != true) {
        $matches = [];
        preg_match('/[^.]*$/', $key, $matches);
        $arrKey = $matches[0];

        $setterExists = true;
        if (array_key_exists('setter', $values)) {
          $setter = $values['setter'];
        } else {
          if (array_key_exists('property', $values)) {
            $property = $values['property'];
          } else {
            $property = $arrKey;
          }
          $setterName = 'set' . ucfirst($property);
          $setterExists = $object->methodExists($setterName);
          $setter = function ($entity, $value) use ($setterName) {
            $entity->$setterName($value);
          };
        }

        if (array_key_exists($arrKey, $inputArray)) {
          $value = $inputArray[$arrKey];
          $this->transformValue($value, $values);
          $setter($object, $value);
        } elseif ($useDefaults && array_key_exists('default', $values) && $setterExists) {
          $setter($object, $values['default']);
        }
      }
    }
    return $object;
  }

  /**
   * Transforms the given value based on different configurations in specification.
   * @param mixed $value the value to optionally transform
   * @param array $specification the specification for this value
   */
  protected function transformValue(&$value, array $specification)
  {
    if (array_key_exists('nullValue', $specification) && $value === null) {
      $value = $specification['nullValue'];
    }
    if (array_key_exists('reference', $specification)) {
      $value = $this->getReference($specification['reference'], $value);
    }
    if (array_key_exists('type', $specification)) {
      $value = self::transformByType($value, $specification['type']);
    }
    if (array_key_exists('transformer', $specification)) {
      $value = $specification['transformer']($value);
    }
  }
  //bug???
  /**
   * Validates the parameters of a request by the validate fields of the given specification
   * @param Request $request the request
   * @param array $specification the specification
   * @throws ValidationException
   */
  protected function validateBySpecification(Request $request, array $specification): void
  {
    $arr = [];
    foreach ($specification as $key => $values) {
      if (array_key_exists('validation', $values)) {
        $arr[$key] = $values['validation'];
      }
      if (array_key_exists('transformBeforeValidation', $values) && $request->has($key)) {
        $request->merge([$key => $values['transformBeforeValidation']($request->get($key))]);
      }
    }
    $this->validateSpec($request, $arr);
  }

  /**
   * @param Request $request
   * @param array $spec
   * @return mixed
   * @throws ValidationException
   */
  abstract protected function validateSpec(Request $request, array $spec);
//</editor-fold desc="Protected Methods">
//</editor-fold desc="Protected Methods">

//<editor-fold desc="Private Methods">
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
      try {
        return new DateTime($value);
      } catch (Exception $e) {
        //we return the value itself if it is not parsable by DateTime
      }
    }
    return $value;
  }
//</editor-fold desc="Private Methods">
}
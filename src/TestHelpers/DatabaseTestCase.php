<?php
declare(strict_types=1);

/**
 * Class DatabaseTestCase
 */

namespace Tfboe\FmLib\TestHelpers;

use Faker\Factory;
use LaravelDoctrine\ORM\Facades\EntityManager;
use Tfboe\FmLib\Entity\PlayerInterface;
use Tfboe\FmLib\Entity\TeamInterface;
use Tfboe\FmLib\Entity\TeamMembershipInterface;
use Tfboe\FmLib\Entity\UserInterface;

/**
 * Class DatabaseTestCase
 * @package Tfboe\FmLib\TestHelpers
 */
abstract class DatabaseTestCase extends LumenTestCase
{
//<editor-fold desc="Fields">
  /**
   * @var \Faker\Generator
   */
  protected $faker;

  /**
   * @var bool
   */
  private $clear;
//</editor-fold desc="Fields">

//<editor-fold desc="Constructor">
  /**
   * DatabaseTestCase constructor.
   * @param string|null $name test name
   * @param array $data test data
   * @param string $dataName test data name
   * @param bool $clear
   */
  public function __construct($name = null, array $data = [], $dataName = '', $clear = false)
  {
    parent::__construct($name, $data, $dataName);
    srand(3); //always use the same faker values to get reproducibility
    $this->faker = Factory::create();
    $this->clear = $clear;
  }
//</editor-fold desc="Constructor">

//<editor-fold desc="Protected Methods">

  /**
   * Clears the database by truncating all tables (very time consuming)
   * @throws \Doctrine\DBAL\DBALException
   */
  protected function clearDatabase()
  {
    /** @var \Doctrine\DBAL\Connection $connection */
    /** @noinspection PhpUndefinedMethodInspection */
    $connection = EntityManager::getConnection();
    $connection->query(sprintf('SET FOREIGN_KEY_CHECKS = 0;'));
    $tables = $connection->getSchemaManager()->listTables();
    foreach ($tables as $table) {
      $sql = sprintf('TRUNCATE TABLE %s', $table->getName());
      $connection->query($sql);
    }
    $connection->query(sprintf('SET FOREIGN_KEY_CHECKS = 1;'));
  }

  /**
   * Creates an array of players.
   * @param int $number the number of players
   * @return PlayerInterface[] the created player array
   */
  protected function createPlayers(int $number = 1): array
  {
    $result = [];
    for ($i = 0; $i < $number; $i++) {
      $result[] = entity($this->resolveEntity(PlayerInterface::class))->create();
    }
    return $result;
  }

  /**
   * Creates an array of teams with ranks and start numbers
   * @param int $number the number of teams to create
   * @param int $playerPerTeam the number of players per team
   * @return TeamInterface[] the created team array
   */
  protected function createTeams(int $number, $playerPerTeam = 1): array
  {
    $result = [];
    for ($i = 0; $i < $number; $i++) {
      /** @var TeamInterface $team */
      $team = entity($this->resolveEntity(TeamInterface::class))->create(
        ['startNumber' => $i + 1, 'rank' => $number - $i]);
      foreach ($this->createPlayers($playerPerTeam) as $player) {
        /** @var TeamMembershipInterface $teamMembership */
        $teamMembership = entity($this->resolveEntity(TeamMembershipInterface::class))->create(
          ['team' => $team, 'player' => $player]);
        $team->getMemberships()->set($teamMembership->getId(), $teamMembership);
      }
      $result[] = $team;
    }
    return $result;
  }

  /**
   * Creates a new user
   * @return array containing the password and the user object
   */
  protected function createUser()
  {
    $password = $this->newPassword();
    /** @var UserInterface $user */
    $attributes = ['originalPassword' => $password];
    $this->addAdditionalNewUserAttributes($attributes);
    $user = entity($this->resolveEntity(UserInterface::class))->create($attributes);
    return [
      'password' => $password,
      'user' => $user
    ];
  }

  /**
   * Adds additional attributes given to the create entity method
   * @param mixed[] $attributes
   */
  protected function addAdditionalNewUserAttributes(array &$attributes)
  {
  }

  /**
   * Uses faker to generate a new password
   * @return string the new password
   */
  protected function newPassword()
  {
    return $this->faker->password(8, 30);
  }

  /**
   * Boot the testing helper traits.
   *
   * @return void
   * @throws \Doctrine\DBAL\DBALException
   */
  protected function setUpTraits()
  {
    srand(3); //always use the same faker values to get reproducibility
    $clear = $this->clear;
    parent::setUpTraits();
    if ($clear) {
      $this->clearDatabase();
      $this->workOnDatabaseSetUp();
    } else {
      $this->workOnDatabaseSetUp();
      /** @noinspection PhpUndefinedMethodInspection */
      EntityManager::beginTransaction();
    }

    $this->beforeApplicationDestroyed(function () use ($clear) {
      if ($clear) {
        $this->workOnDatabaseDestroy();
        $this->clearDatabase();
      } else {
        /** @noinspection PhpUndefinedMethodInspection */
        EntityManager::rollback();
        $this->workOnDatabaseDestroy();
      }
    });
  }

  protected function workOnDatabaseDestroy()
  {

  }

  protected function workOnDatabaseSetUp()
  {

  }

  /**
   * Resolve className according to fm-lib config
   * @param string $className
   * @return string
   */
  protected final function resolveEntity(string $className): string
  {
    //resolve class name according to fm-lib config
    if (config()->has('fm-lib')) {
      $config = config('fm-lib');
      if (array_key_exists('entityMaps', $config)) {
        $classMap = $config['entityMaps'];
        if (array_key_exists($className, $classMap)) {
          return $classMap[$className];
        }
      }
    }
    return $className;
  }
//</editor-fold desc="Protected Methods">
}
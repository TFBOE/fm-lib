<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/3/18
 * Time: 3:54 PM
 */

namespace Tfboe\FmLib\Tests\Unit\Service\RankingSystem;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Tfboe\FmLib\Entity\Helpers\AutomaticInstanceGeneration;
use Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity;
use Tfboe\FmLib\Entity\Helpers\TournamentHierarchyInterface;
use Tfboe\FmLib\Entity\RankingSystemChangeInterface;
use Tfboe\FmLib\Entity\RankingSystemInterface;
use Tfboe\FmLib\Entity\RankingSystemListEntryInterface;
use Tfboe\FmLib\Entity\RankingSystemListInterface;
use Tfboe\FmLib\Exceptions\PreconditionFailedException;
use Tfboe\FmLib\Helpers\Level;
use Tfboe\FmLib\Service\ObjectCreatorServiceInterface;
use Tfboe\FmLib\Service\RankingSystem\EntityComparerInterface;
use Tfboe\FmLib\Service\RankingSystem\RankingSystemService;
use Tfboe\FmLib\Service\RankingSystem\TimeServiceInterface;
use Tfboe\FmLib\Tests\Entity\Competition;
use Tfboe\FmLib\Tests\Entity\Game;
use Tfboe\FmLib\Tests\Entity\Match;
use Tfboe\FmLib\Tests\Entity\Phase;
use Tfboe\FmLib\Tests\Entity\Player;
use Tfboe\FmLib\Tests\Entity\RankingSystem;
use Tfboe\FmLib\Tests\Entity\RankingSystemChange;
use Tfboe\FmLib\Tests\Entity\RankingSystemList;
use Tfboe\FmLib\Tests\Entity\RankingSystemListEntry;
use Tfboe\FmLib\Tests\Entity\Tournament;
use Tfboe\FmLib\Tests\Helpers\UnitTestCase;


/**
 * Class RankingSystemServiceTest
 * @packageTfboe\FmLib\Tests\Unit\Service\RankingSystemService
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class RankingSystemServiceTest extends UnitTestCase
{
//<editor-fold desc="Public Methods">

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   */
  public function testConstruct()
  {
    $entityManager = $this->createMock(EntityManagerInterface::class);
    $timeService = $this->createMock(TimeServiceInterface::class);
    $entityComparer = $this->createMock(EntityComparerInterface::class);
    $objectCreator = $this->createMock(ObjectCreatorServiceInterface::class);
    $system = $this->getMockForAbstractClass(RankingSystemService::class,
      [$entityManager, $timeService, $entityComparer, $objectCreator]);
    self::assertInstanceOf(RankingSystemService::class, $system);
    /** @noinspection PhpUnhandledExceptionInspection */
    self::assertEquals($entityManager, self::getProperty(get_class($system), 'entityManager')->getValue($system));
    /** @noinspection PhpUnhandledExceptionInspection */
    self::assertEquals($timeService, self::getProperty(get_class($system), 'timeService')->getValue($system));
    /** @noinspection PhpUnhandledExceptionInspection */
    self::assertEquals($entityComparer, self::getProperty(get_class($system), 'entityComparer')->getValue($system));
    /** @noinspection PhpUnhandledExceptionInspection */
    self::assertEquals($objectCreator, self::getProperty(get_class($system), 'objectCreatorService')
      ->getValue($system));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getAverage
   */
  public function testGetAverage()
  {
    /** @var $service RankingSystemService */
    $service = $this->getMockForAbstractClass(RankingSystemService::class, [], '', false);

    $entry1 = $this->createMock(RankingSystemListEntry::class);
    $entry1->method('getPoints')->willReturn(1.0);
    $entry2 = $this->createMock(RankingSystemListEntry::class);
    $entry2->method('getPoints')->willReturn(2.0);

    self::assertEquals(1.5, static::callProtectedMethod($service, 'getAverage', [[$entry1, $entry2]]));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestInfluence
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestEntityInfluence
   * @uses   \Tfboe\FmLib\Entity\Traits\Competition
   * @uses   \Tfboe\FmLib\Entity\Traits\Competition
   * @uses   \Tfboe\FmLib\Entity\Traits\Game
   * @uses   \Tfboe\FmLib\Entity\Traits\Game
   * @uses   \Tfboe\FmLib\Entity\Helpers\NameEntity
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimeEntity
   * @uses   \Tfboe\FmLib\Entity\Traits\Match
   * @uses   \Tfboe\FmLib\Entity\Traits\Match
   * @uses   \Tfboe\FmLib\Entity\Traits\Phase
   * @uses   \Tfboe\FmLib\Entity\Traits\Phase
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testGetEarliestInfluenceGameLevel()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    $timeService = $this->createMock(TimeServiceInterface::class);
    $timeService->expects(self::atLeastOnce())->method('clearTimes')->id('clearTimes');
    $timeService->method('getTime')->willReturnCallback(function (TournamentHierarchyInterface $entity) {
      return $entity->getEndTime();
    })->after('clearTimes');
    /** @var RankingSystemInterface $ranking */
    $service = $this->getMockForAbstractClass(RankingSystemService::class,
      [$this->createMock(EntityManagerInterface::class),
        $timeService,
        $this->createMock(EntityComparerInterface::class),
        $this->createMock(ObjectCreatorServiceInterface::class)]);
    $service->method("getLevel")->willReturn(Level::GAME);
    /** @var RankingSystemService $service */
    $tournament = new Tournament();
    $competition = new Competition();
    $competition->setName("TestCompetition")->setTournament($tournament);
    $phase = new Phase();
    $phase->setPhaseNumber(1);
    $phase->setCompetition($competition);
    $match = new Match();
    $match->setMatchNumber(1);
    $match->setPhase($phase);
    self::assertNull($service->getEarliestInfluence($ranking, $tournament));

    $tournament->getRankingSystems()->set($ranking->getId(), $ranking);
    self::assertNull($service->getEarliestInfluence($ranking, $tournament));

    $game = new Game();
    $game->setGameNumber(1);
    $game->setMatch($match);
    $gameEndTime = new \DateTime("2017-06-01 00:00:00");
    $game->setEndTime($gameEndTime);
    self::assertEquals($gameEndTime, $service->getEarliestInfluence($ranking, $tournament));

    $game2 = new Game();
    $game2->setGameNumber(2);
    $game2->setMatch($match);
    $game2EndTime = new \DateTime("2017-05-01 00:00:00");
    $game2->setEndTime($game2EndTime);
    self::assertEquals($game2EndTime, $service->getEarliestInfluence($ranking, $tournament));

    $game3 = new Game();
    $game3->setGameNumber(3);
    $game3->setMatch($match);
    $game3EndTime = new \DateTime("2017-07-01 00:00:00");
    $game3->setEndTime($game3EndTime);
    self::assertEquals($game2EndTime, $service->getEarliestInfluence($ranking, $tournament));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestInfluence
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestEntityInfluence
   * @uses   \Tfboe\FmLib\Entity\Traits\Competition
   * @uses   \Tfboe\FmLib\Entity\Traits\Competition
   * @uses   \Tfboe\FmLib\Entity\Traits\Game
   * @uses   \Tfboe\FmLib\Entity\Traits\Game
   * @uses   \Tfboe\FmLib\Entity\Helpers\NameEntity
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimeEntity
   * @uses   \Tfboe\FmLib\Entity\Traits\Match
   * @uses   \Tfboe\FmLib\Entity\Traits\Match
   * @uses   \Tfboe\FmLib\Entity\Traits\Phase
   * @uses   \Tfboe\FmLib\Entity\Traits\Phase
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public function testGetEarliestInfluenceGameLevelWithDifferentImpactLevels()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    $timeService = $this->createMock(TimeServiceInterface::class);
    $timeService->expects(self::atLeastOnce())->method('clearTimes')->id('clearTimes');
    $timeService->method('getTime')->willReturnCallback(function (TournamentHierarchyInterface $entity) {
      return $entity->getEndTime();
    })->after('clearTimes');
    /** @var RankingSystemInterface $ranking */
    $service = $this->getMockForAbstractClass(RankingSystemService::class,
      [$this->createMock(EntityManagerInterface::class),
        $timeService,
        $this->createMock(EntityComparerInterface::class),
        $this->createMock(ObjectCreatorServiceInterface::class)]);
    $service->method("getLevel")->willReturn(Level::GAME);
    /** @var RankingSystemService $service */
    $tournament = new Tournament();
    $competition = new Competition();
    $competition->setName("TestCompetition")->setTournament($tournament);
    $phase = new Phase();
    $phase->setPhaseNumber(1);
    $phase->setCompetition($competition);
    $match = new Match();
    $match->setMatchNumber(1);
    $match->setPhase($phase);
    $game = new Game();
    $game->setGameNumber(1);
    $game->setMatch($match);
    $endTime1 = new \DateTime("2017-12-01 00:00:00");
    $game->setEndTime($endTime1);
    $game->getRankingSystems()->set($ranking->getId(), $ranking);
    self::assertEquals($endTime1, $service->getEarliestInfluence($ranking, $tournament));

    $game2 = new Game();
    $game2->setGameNumber(2);
    $game2->setMatch($match);
    $endTime2 = new \DateTime("2017-11-01 00:00:00");
    $game2->setEndTime($endTime2);
    self::assertEquals($endTime1, $service->getEarliestInfluence($ranking, $tournament));

    $match->getRankingSystems()->set($ranking->getId(), $ranking);
    self::assertEquals($endTime2, $service->getEarliestInfluence($ranking, $tournament));

    $match2 = new Match();
    $match2->setMatchNumber(2);
    $match2->setPhase($phase);
    $game3 = new Game();
    $game3->setGameNumber(1);
    $game3->setMatch($match2);
    $endTime3 = new \DateTime("2017-10-01 00:00:00");
    $game3->setEndTime($endTime3);
    self::assertEquals($endTime2, $service->getEarliestInfluence($ranking, $tournament));

    $phase->getRankingSystems()->set($ranking->getId(), $ranking);
    self::assertEquals($endTime3, $service->getEarliestInfluence($ranking, $tournament));

    $phase2 = new Phase();
    $phase2->setPhaseNumber(2);
    $phase2->setCompetition($competition);
    $match3 = new Match();
    $match3->setMatchNumber(1);
    $match3->setPhase($phase2);
    $game4 = new Game();
    $game4->setGameNumber(1);
    $game4->setMatch($match3);
    $endTime4 = new \DateTime("2017-09-01 00:00:00");
    $game4->setEndTime($endTime4);
    self::assertEquals($endTime3, $service->getEarliestInfluence($ranking, $tournament));

    $competition->getRankingSystems()->set($ranking->getId(), $ranking);
    self::assertEquals($endTime4, $service->getEarliestInfluence($ranking, $tournament));

    $competition2 = new Competition();
    $competition2->setName("TestCompetition2")->setTournament($tournament);
    $phase3 = new Phase();
    $phase3->setPhaseNumber(1);
    $phase3->setCompetition($competition2);
    $match4 = new Match();
    $match4->setMatchNumber(1);
    $match4->setPhase($phase3);
    $game5 = new Game();
    $game5->setGameNumber(1);
    $game5->setMatch($match4);
    $endTime5 = new \DateTime("2017-01-01 00:00:00");
    $game5->setEndTime($endTime5);
    self::assertEquals($endTime4, $service->getEarliestInfluence($ranking, $tournament));

    $game6 = new Game();
    $game6->setGameNumber(2);
    $game6->setMatch($match4);
    $endTime6 = new \DateTime("2017-10-01 00:00:00");
    $game6->setEndTime($endTime6);
    $game6->getRankingSystems()->set($ranking->getId(), $ranking);
    self::assertEquals($endTime4, $service->getEarliestInfluence($ranking, $tournament));

    $game7 = new Game();
    $game7->setGameNumber(3);
    $game7->setMatch($match4);
    $endTime7 = new \DateTime("2017-08-01 00:00:00");
    $game7->setEndTime($endTime7);
    $game7->getRankingSystems()->set($ranking->getId(), $ranking);
    self::assertEquals($endTime7, $service->getEarliestInfluence($ranking, $tournament));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestInfluence
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestEntityInfluence
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimeEntity
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimestampableEntity
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testGetEarliestInfluenceTournamentLevel()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    $timeService = $this->createMock(TimeServiceInterface::class);
    $timeService->expects(self::atLeastOnce())->method('clearTimes')->id('clearTimes');
    $timeService->method('getTime')->willReturnCallback(function (TournamentHierarchyInterface $entity) {
      return $entity->getEndTime();
    })->after('clearTimes');
    /** @var RankingSystemInterface $ranking */
    $service = $this->getMockForAbstractClass(RankingSystemService::class,
      [$this->createMock(EntityManagerInterface::class),
        $timeService,
        $this->createMock(EntityComparerInterface::class),
        $this->createMock(ObjectCreatorServiceInterface::class)]);
    $service->method("getLevel")->willReturn(Level::TOURNAMENT);
    /** @var RankingSystemService $service */
    $tournament = new Tournament();
    $tournament->getRankingSystems()->set($ranking->getId(), $ranking);
    $endTime = new \DateTime("2017-03-01 00:00:00");
    $tournament->setEndTime($endTime);
    self::assertEquals($endTime, $service->getEarliestInfluence($ranking, $tournament));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getAverage
   */
  public function testGetEmptyAverage()
  {
    /** @var $service RankingSystemService */
    $service = $this->getMockForAbstractClass(RankingSystemService::class, [], '', false);

    self::assertEquals(0.0, static::callProtectedMethod($service, 'getAverage', [[]]));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   */
  public function testGetEntities()
  {
    //create mock for input
    $ranking = $this->createMock(RankingSystem::class);

    //create service mock
    $service = $this->getMockForAbstractClass(RankingSystemService::class,
      [$this->createMock(EntityManagerInterface::class), $this->createMock(TimeServiceInterface::class),
        $this->createMock(EntityComparerInterface::class),
        $this->createMock(ObjectCreatorServiceInterface::class)]);

    //create mock for queryBuilder
    $entityList = ['e1', 'e2'];
    $query = $this->createMock(AbstractQuery::class);
    $query->expects(static::once())->method('getResult')->willReturn($entityList);
    //create query builder mock for getEntities
    $queryBuilder = $this->createMock(QueryBuilder::class);
    $queryBuilder->expects(static::once())->method('getQuery')->willReturn($query);
    $service->expects(static::once())->method('getEntitiesQueryBuilder')
      ->with($ranking, new \DateTime("2017-01-01"))->willReturn($queryBuilder);

    /** @var $service RankingSystemService */
    /** @noinspection PhpUnhandledExceptionInspection */
    self::assertEquals($entityList, static::getMethod(get_class($service), 'getEntities')
      ->invokeArgs($service, [$ranking, new \DateTime("2017-01-01"), new \DateTime("2018-01-01")]));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntityManager
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   */
  public function testGetEntityManager()
  {
    $entityManager = $this->createMock(EntityManagerInterface::class);
    $service = $this->getMockForAbstractClass(RankingSystemService::class, [$entityManager,
      $this->createMock(TimeServiceInterface::class), $this->createMock(EntityComparerInterface::class),
      $this->createMock(ObjectCreatorServiceInterface::class)]);
    $em = static::callProtectedMethod($service, 'getEntityManager');
    self::assertEquals($entityManager, $em);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntriesOfPlayers
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getOrCreateRankingSystemListEntry
   */
  public function testGetEntriesOfPlayers()
  {

    /** @var $service RankingSystemService */
    $service = $this->getMockForAbstractClass(RankingSystemService::class, [], '', false);

    $entry1 = $this->createMock(RankingSystemListEntry::class);
    $entry2 = $this->createMock(RankingSystemListEntry::class);
    $entry3 = $this->createMock(RankingSystemListEntry::class);

    $entries = new ArrayCollection([1 => $entry1, 2 => $entry2, 3 => $entry3]);
    $list = $this->createStub(RankingSystemList::class, ['getEntries' => $entries]);

    $player1 = $this->createStub(Player::class, ['getId' => 1]);
    $player3 = $this->createStub(Player::class, ['getId' => 3]);

    $returnedEntries = static::callProtectedMethod($service, 'getEntriesOfPlayers',
      [new ArrayCollection([$player1, $player3]), $list]);
    self::assertEquals([$entry1, $entry3], $returnedEntries);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getOrCreateChange
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::initSubClassData
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemChange
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::setProperty
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getAdditionalChangeFields()
   */
  public function testGetOrCreateChangeCreateNewOne()
  {
    $persisted = null;

    [$entity, $ranking, $player] = $this->createEntities();
    [$service, $entityManager] = $this->prepareCreateChange();
    $entityManager->expects(self::once())->method('persist')->willReturnCallback(
      function (RankingSystemChangeInterface $change) use (&$persisted, $entity, $ranking, $player) {
        $persisted = $change;
        self::assertInstanceOf(RankingSystemChange::class, $change);
        self::assertEquals($entity, $change->getHierarchyEntity());
        self::assertEquals($ranking, $change->getRankingSystem());
        self::assertEquals($player, $change->getPlayer());
      });
    $service->method('getAdditionalFields')->willReturn(['additional' => 0.0]);

    $change = static::callProtectedMethod($service, 'getOrCreateChange', [$entity, $ranking, $player]);
    self::assertEquals($persisted, $change);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getOrCreateChange
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::initSubClassData
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemChange
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getAdditionalChangeFields()
   */
  public function testGetOrCreateChangeCreateTwice()
  {
    [$entity, $ranking, $player] = $this->createEntities();
    [$service, $entityManager] = $this->prepareCreateChange();
    $entityManager->expects(self::once())->method('persist');

    $change = static::callProtectedMethod($service, 'getOrCreateChange', [$entity, $ranking, $player]);
    $change2 = static::callProtectedMethod($service, 'getOrCreateChange', [$entity, $ranking, $player]);
    self::assertEquals($change, $change2);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getOrCreateChange
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::deleteOldChanges
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::markOldChangesAsDeleted
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::flushAndForgetEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getMaxDate
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::initSubClassData
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemChange::init
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemChange
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemList
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::cloneInto
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::recomputeBasedOn
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingFrom
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextGenerationTime
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextEntities
   */
  public function testGetOrCreateGetDeletedChange()
  {
    [$entity, $ranking, $player] = $this->createEntities();
    $change = $this->createStub(RankingSystemChange::class,
      ['getRankingSystem' => $ranking, 'getPlayer' => $player, 'getHierarchyEntity' => $entity, 'getId' => "c1"]);

    $entityManager = $this->getEntityManagerMockForQuery([$change], null, ['persist', 'flush', 'detach', 'remove',
      'getRepository']);
    $entityManager->expects(self::once())->method('flush');
    $service = $this->prepareUpdateRankingFrom($ranking, $entityManager, null, 1, ['getChanges'], [$entity]);
    $service->expects(self::once())->method('getChanges')->willReturnCallback(
      function ($e) use ($service, $ranking, $player, $change) {
        $foundChange = static::callProtectedMethod($service, 'getOrCreateChange', [$e, $ranking, $player]);
        self::assertEquals($foundChange->getId(), $change->getId());
        return [];
      });
    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2017-02-28'));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getOrCreateChange
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::deleteOldChanges
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::markOldChangesAsDeleted
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::flushAndForgetEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getMaxDate
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::initSubClassData
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemChange::init
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemChange
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemList
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::cloneInto
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::recomputeBasedOn
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingFrom
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextGenerationTime
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextEntities
   */
  public function testDontUseDeletedChange()
  {
    [$entity, $ranking, $player] = $this->createEntities();
    $change = $this->createStub(RankingSystemChange::class,
      ['getRankingSystem' => $ranking, 'getPlayer' => $player, 'getHierarchyEntity' => $entity, 'getId' => "c1"]);

    $entityManager = $this->getEntityManagerMockForQuery([$change], null, ['persist', 'flush', 'detach', 'remove',
      'getRepository']);
    $entityManager->expects(self::once())->method('flush');
    $entityManager->expects(self::once())->method('remove')->with($change);
    $service = $this->prepareUpdateRankingFrom($ranking, $entityManager, null, 1, [], [$entity]);
    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2017-02-28'));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getOrCreateChange
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::markOldChangesAsDeleted
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::deleteOldChanges
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::initSubClassData
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemChange::init
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemChange
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemList
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::cloneInto
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::recomputeBasedOn
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingFrom
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextGenerationTime
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::flushAndForgetEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getMaxDate
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextEntities
   */
  public function testDoubleOldChange()
  {
    [$entity, $ranking, $player] = $this->createEntities();
    $change1 = $this->createStub(RankingSystemChange::class,
      ['getRankingSystem' => $ranking, 'getPlayer' => $player, 'getHierarchyEntity' => $entity, 'getId' => "c1"]);
    $change2 = $this->createStub(RankingSystemChange::class,
      ['getRankingSystem' => $ranking, 'getPlayer' => $player, 'getHierarchyEntity' => $entity, 'getId' => "c2"]);
    self::assertNotEquals($change1->getId(), $change2->getId());

    $entityManager = $this->getEntityManagerMockForQuery([$change1, $change2], null, ['persist', 'flush', 'remove',
      'getRepository']);
    $entityManager->expects(self::once())->method('flush');
    $entityManager->expects(self::exactly(2))->method('remove')->withConsecutive($change2, $change1);
    $service = $this->prepareUpdateRankingFrom($ranking, $entityManager, null, 1, ['deleteOldChanges']);
    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2017-02-28'));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getOrCreateRankingSystemListEntry
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::startPoints
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::initSubClassData
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemListEntry
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   */
  public function testGetOrCreateRankingSystemListEntryExistingEntry()
  {
    $player = $this->createStubWithId(Player::class, 1, 'getId');
    $entries = new ArrayCollection([]);
    $list = $this->createStub(RankingSystemList::class, ['getEntries' => $entries]);
    $entry = $this->createStub(RankingSystemListEntry::class,
      ['getPlayer' => $player, 'getRankingSystemList' => $list]);
    $entries->set(1, $entry);

    /** @var RankingSystemService $service */
    $service = $this->getMockForAbstractClass(RankingSystemService::class, [], '', false);
    $foundEntry = static::callProtectedMethod($service, 'getOrCreateRankingSystemListEntry', [$list, $player]);
    self::assertEquals($entry, $foundEntry);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getOrCreateRankingSystemListEntry
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::startPoints
   * @covers   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::resetListEntry
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::initSubClassData
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemListEntry
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::setProperty
   */
  public function testGetOrCreateRankingSystemListEntryNewEntry()
  {
    $player = $this->createStubWithId(Player::class, 1, 'getId');
    $entries = new ArrayCollection([]);
    $list = $this->createStub(RankingSystemList::class, ['getEntries' => $entries]);

    /** @var RankingSystemListEntryInterface $createdEntry */
    $createdEntry = null;
    $entityManager = $this->createMock(EntityManager::class);
    $entityManager->expects(self::once())->method('persist')->willReturnCallback(
      function (RankingSystemListEntryInterface $entry) use (&$createdEntry, $player, $list) {
        $createdEntry = $entry;
      });


    $service = $this->getMockForAbstractClass(RankingSystemService::class, [$entityManager,
      $this->createMock(TimeServiceInterface::class), $this->createMock(EntityComparerInterface::class),
      $this->getObjectCreator()]);
    $service->method('getAdditionalFields')->willReturn(['additional' => 0.0]);
    /** @var RankingSystemService $service */

    $entry = static::callProtectedMethod($service, 'getOrCreateRankingSystemListEntry', [$list, $player]);
    self::assertEquals($createdEntry, $entry);
    self::assertInstanceOf(RankingSystemListEntry::class, $entry);
    self::assertEquals($player, $entry->getPlayer());
    self::assertEquals($list, $entry->getRankingSystemList());
    self::assertEquals(1, $entries->count());
    self::assertEquals($entry, $entries[1]);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingForTournament
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimeEntity
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimestampableEntity
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestEntityInfluence
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestInfluence
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testUpdateRankingForTournamentOldEarliestIsEarlier()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    $timeService = $this->createMock(TimeServiceInterface::class);
    $timeService->expects(self::atLeastOnce())->method('clearTimes')->id('clearTimes');
    $timeService->method('getTime')->willReturnCallback(function (TournamentHierarchyInterface $entity) {
      return $entity->getEndTime();
    })->after('clearTimes');
    $service = $this->getMockWithMockedArguments(RankingSystemService::class,
      [$this->createMock(EntityManagerInterface::class),
        $timeService], ['updateRankingFrom']);
    $service->method("getLevel")->willReturn(Level::TOURNAMENT);
    /** @var RankingSystemInterface $ranking */
    $tournament = new Tournament();
    $endedAt = new \DateTime("2017-02-01 00:00:00");
    $tournament->setUpdatedAt($endedAt);
    $tournament->getRankingSystems()->set($ranking->getId(), $ranking);
    $oldInfluence = new \DateTime("2017-01-01 00:00:00");
    $service->expects(static::once())
      ->method('updateRankingFrom')
      ->with($ranking, new \DateTime("2017-01-01 00:00:00"));

    /** @var RankingSystemService $service */
    $service->updateRankingForTournament($ranking, $tournament, $oldInfluence);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingForTournament
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimestampableEntity
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestEntityInfluence
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestInfluence
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testUpdateRankingForTournamentOldEarliestIsNotNullAndTournamentNotRanked()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    /** @var RankingSystemInterface $ranking */
    $tournament = new Tournament();
    $endedAt = new \DateTime("2017-01-01 00:00:00");
    $tournament->setUpdatedAt($endedAt);
    $service = $this->getMockWithMockedArguments(RankingSystemService::class, [], ['updateRankingFrom']);
    $service->method("getLevel")->willReturn(Level::TOURNAMENT);
    $oldInfluence = new \DateTime("2017-02-01 00:00:00");
    $service->expects(static::once())
      ->method('updateRankingFrom')
      ->with($ranking, new \DateTime("2017-02-01 00:00:00"));

    /** @var RankingSystemService $service */
    $service->updateRankingForTournament($ranking, $tournament, $oldInfluence);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingForTournament
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimeEntity
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimestampableEntity
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestEntityInfluence
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestInfluence
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testUpdateRankingForTournamentOldEarliestIsNull()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    /** @var RankingSystemInterface $ranking */
    $tournament = new Tournament();
    $endedAt = new \DateTime("2017-01-01 00:00:00");
    $tournament->setEndTime($endedAt);
    $tournament->getRankingSystems()->set($ranking->getId(), $ranking);
    $timeService = $this->createMock(TimeServiceInterface::class);
    $timeService->expects(self::atLeastOnce())->method('clearTimes')->id('clearTimes');
    $timeService->method('getTime')->willReturnCallback(function (TournamentHierarchyInterface $entity) {
      return $entity->getEndTime();
    })->after('clearTimes');
    $service = $this->getMockWithMockedArguments(RankingSystemService::class,
      [$this->createMock(EntityManagerInterface::class),
        $timeService], ['updateRankingFrom']);
    $service->method("getLevel")->willReturn(Level::TOURNAMENT);
    $service->expects(static::once())
      ->method('updateRankingFrom')
      ->with($ranking, new \DateTime("2017-01-01 00:00:00"));

    /** @var RankingSystemService $service */
    $service->updateRankingForTournament($ranking, $tournament, null);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingForTournament
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimestampableEntity
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestEntityInfluence
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestInfluence
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testUpdateRankingForTournamentOldEarliestIsNullAndTournamentNotRanked()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    /** @var RankingSystemInterface $ranking */
    $tournament = new Tournament();
    $endedAt = new \DateTime("2017-01-01 00:00:00");
    $tournament->setUpdatedAt($endedAt);
    $service = $this->getMockWithMockedArguments(RankingSystemService::class,
      [], ['updateRankingFrom']);
    $service->method("getLevel")->willReturn(Level::TOURNAMENT);
    $service->expects(self::never())
      ->method('updateRankingFrom');

    /** @var RankingSystemService $service */
    $service->updateRankingForTournament($ranking, $tournament, null);
  }

  //TODO split this up in multiple unit tests!!!

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingForTournament
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimeEntity
   * @uses   \Tfboe\FmLib\Entity\Helpers\TimestampableEntity
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestEntityInfluence
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEarliestInfluence
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testUpdateRankingForTournamentTournamentIsEarlier()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    /** @var RankingSystemInterface $ranking */
    $tournament = new Tournament();
    $endedAt = new \DateTime("2017-01-01");
    $tournament->setEndTime($endedAt);
    $tournament->getRankingSystems()->set($ranking->getId(), $ranking);
    $timeService = $this->createMock(TimeServiceInterface::class);
    $timeService->expects(self::atLeastOnce())->method('clearTimes')->id('clearTimes');
    $timeService->method('getTime')->willReturnCallback(function (TournamentHierarchyInterface $entity) {
      return $entity->getEndTime();
    })->after('clearTimes');
    $service = $this->getMockWithMockedArguments(RankingSystemService::class,
      [$this->createMock(EntityManagerInterface::class), $timeService], ['updateRankingFrom']);
    $service->method("getLevel")->willReturn(Level::TOURNAMENT);
    $oldInfluence = new \DateTime("2017-02-01");
    $service->expects(static::once())
      ->method('updateRankingFrom')
      ->with($ranking, new \DateTime("2017-01-01"));

    /** @var RankingSystemService $service */
    $service->updateRankingForTournament($ranking, $tournament, $oldInfluence);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingFrom
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::recomputeBasedOn
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::cloneInto
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextGenerationTime
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::resetListEntry
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getMaxDate
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::flushAndForgetEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::markOldChangesAsDeleted
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::deleteOldChanges
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemListEntry
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::cloneSubClassDataFrom
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::initSubClassData
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getOrCreateRankingSystemListEntry
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::getProperty
   * @uses   \Tfboe\FmLib\Entity\Helpers\SubClassData::setProperty
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemChange
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::startPoints
   */
  public function testUpdateRankingFrom()
  {
    //create mock for input
    $ranking = $this->createStubWithId(RankingSystem::class);
    $ranking->method('getGenerationInterval')->willReturn(-1);

    //create mocks for ranking lists
    $list1 = $this->createMock(RankingSystemList::class);
    $list1->method('isCurrent')->willReturn(false);
    $list1->method('getLastEntryTime')->willReturn(new \DateTime("2017-01-01"));
    $list1->method('getRankingSystem')->willReturn($ranking);

    $entry1 = $this->createEmptyEntry();
    $entry2 = $this->createEmptyEntry();
    $entry3 = $this->createEmptyEntry();

    $list2 = $this->createMock(RankingSystemList::class);
    $list2->method('isCurrent')->willReturn(false);
    $list2->method('getLastEntryTime')->willReturn(new \DateTime("2017-02-01"));
    $list2->method('getEntries')->willReturn(new ArrayCollection([1 => $entry1, 3 => $entry3]));
    $list2->method('getRankingSystem')->willReturn($ranking);

    $list3 = $this->createMock(RankingSystemList::class);
    $list3->method('isCurrent')->willReturn(false);
    $list3->method('getLastEntryTime')->willReturn(new \DateTime("2017-03-01"));
    $list3->method('getEntries')->willReturn(new ArrayCollection([1 => $entry1, 2 => $entry2]));
    $list3->method('getRankingSystem')->willReturn($ranking);

    $list4 = $this->createMock(RankingSystemList::class);
    $list4->method('isCurrent')->willReturn(false);
    $list4->method('getLastEntryTime')->willReturn(new \DateTime("2017-04-01"));
    $list4->method('getEntries')->willReturn(new ArrayCollection());
    $list4->method('getRankingSystem')->willReturn($ranking);

    $list5 = $this->createMock(RankingSystemList::class);
    $list5->method('isCurrent')->willReturn(true);
    $list5->method('getLastEntryTime')->willReturn(new \DateTime("2017-05-01"));
    $list5->method('getEntries')->willReturn(new ArrayCollection());
    $list5->method('getRankingSystem')->willReturn($ranking);

    $lists = $this->createMock(Collection::class);
    $lists->expects(static::once())->method('toArray')->willReturn([$list1, $list2, $list3, $list4, $list5]);

    //finish mock for input
    $ranking->expects(static::once())->method('getLists')->willReturn($lists);

    //create time service, entity comparer and ranking service mock
    $timeService = $this->createMock(TimeServiceInterface::class);
    $timeService->expects(self::atLeastOnce())->method('clearTimes')->id('clearTimes');
    $timeService->method('getTime')->willReturnCallback(function (TournamentHierarchyInterface $entity) {
      return $entity->getEndTime();
    })->after('clearTimes');
    $entityComparer = $this->createMock(EntityComparerInterface::class);
    $entityComparer->method('compareEntities')->willReturnCallback(
      function (TournamentHierarchyInterface $entity1, TournamentHierarchyInterface $entity2) {
        return $entity1->getEndTime() <=> $entity2->getEndTime();
      });
    $entityManager = $this->getEntityManagerMockForQuery([],
      'SELECT c FROM Tfboe\FmLib\Entity\RankingSystemChangeInterface c WHERE c.rankingSystem = :ranking' .
      ' AND c.hierarchyEntity IN(:entities)', ['persist', 'remove', 'flush', 'detach'], 3);
    $service = $this->getMockWithMockedArguments(RankingSystemService::class,
      [$entityManager,
        $timeService,
        $this->createMock(EntityComparerInterface::class),
        $this->getObjectCreator()]);

    //create entities mocks
    $entity1 = $this->createStubWithId(TournamentHierarchyEntity::class, "e1");
    $entity1->method('getEndTime')->willReturn(new \DateTime("2017-03-01"));

    $entity2 = $this->createStubWithId(TournamentHierarchyEntity::class, "e2");
    $entity2->method('getEndTime')->willReturn(new \DateTime("2017-02-01 00:00:01"));

    $entity3 = $this->createStubWithId(TournamentHierarchyEntity::class, "e3");
    $entity3->method('getEndTime')->willReturn(new \DateTime("2017-05-02"));

    $entity4 = $this->createStubWithId(TournamentHierarchyEntity::class, "e4");
    $entity4->method('getEndTime')->willReturn(new \DateTime("2017-03-02"));

    $parent = $this->createStubWithId(TournamentHierarchyEntity::class, "e4");
    $parent->method('getEndTime')->willReturn(new \DateTime("2017-12-02"));
    $entity4->method('getParent')->willReturn($parent);

    //create query mock for getEntities
    $query3 = $this->createMock(AbstractQuery::class);
    $query3->expects(static::once())->method('getResult')->willReturn([$entity1, $entity2, $entity3, $entity4]);
    $queryBuilder3 = $this->createMock(QueryBuilder::class);
    $queryBuilder3->expects(static::once())->method('getQuery')->willReturn($query3);

    $query4 = $this->createMock(AbstractQuery::class);
    $query4->expects(static::once())->method('getResult')->willReturn([$entity1, $entity2, $entity3, $entity4]);
    $queryBuilder4 = $this->createMock(QueryBuilder::class);
    $queryBuilder4->expects(static::once())->method('getQuery')->willReturn($query4);

    $query5 = $this->createMock(AbstractQuery::class);
    $query5->expects(static::once())->method('getResult')->willReturn([$entity1, $entity2, $entity3, $entity4]);
    $queryBuilder5 = $this->createMock(QueryBuilder::class);
    $queryBuilder5->expects(static::once())->method('getQuery')->willReturn($query5);

    $service->method('getEntitiesQueryBuilder')
      ->withConsecutive([$ranking, new \DateTime("2017-02-01"), new \DateTime("2017-03-01")],
        [$ranking, new \DateTime("2017-03-01"), new \DateTime("2017-04-01")],
        [$ranking, new \DateTime("2017-04-01")])
      ->willReturnOnConsecutiveCalls($queryBuilder3, $queryBuilder4, $queryBuilder5);
    $changes = [
      $this->createEmptyChange(),
      $this->createEmptyChange(),
    ];
    $service->method('getChanges')->willReturn($changes);
    $service->method('getAdditionalFields')->willReturn(['additional' => 0.0]);

    /** @var RankingSystemService $service */
    /** @var RankingSystemInterface $ranking */
    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2017-02-28'));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingFrom
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::recomputeBasedOn
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::cloneInto
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextGenerationTime
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::flushAndForgetEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::markOldChangesAsDeleted
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemList
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::deleteOldChanges
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getMaxDate
   */
  public function testUpdateRankingFromCalledTwice()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    $service = $this->prepareUpdateRankingFrom($ranking, $this->getEntityManagerMockForQuery([], null, ['flush']));

    /** @var RankingSystemInterface $ranking */

    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2017-02-28'));

    $this->expectException(PreconditionFailedException::class);

    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2017-02-28'));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingFrom
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::recomputeBasedOn
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::cloneInto
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextGenerationTime
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::flushAndForgetEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::markOldChangesAsDeleted
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemList
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Entity\Helpers\UUIDEntity::getId
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::deleteOldChanges
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getMaxDate
   */
  public function testUpdateRankingFromNoCurrent()
  {
    //create mock for input
    $ranking = $this->createStubWithId(RankingSystem::class, 'r1');
    $ranking->method('getGenerationInterval')->willReturn(AutomaticInstanceGeneration::MONTHLY);

    //create mocks for ranking lists
    $list = $this->createMock(RankingSystemList::class);
    $list->method('isCurrent')->willReturn(false);
    $list->method('getLastEntryTime')->willReturn(new \DateTime("2017-12-01"));
    $list->method('getEntries')->willReturn(new ArrayCollection());
    $list->method('getRankingSystem')->willReturn($ranking);

    $lists = $this->createMock(Collection::class);
    $lists->expects(static::once())->method('toArray')->willReturn([$list]);
    $lists->expects(static::once())->method('set')->with('new')->willReturnSelf();

    //finish mock for input
    $ranking->expects(static::exactly(2))->method('getLists')->willReturn($lists);

    //create service mock
    $entityManager = $this->getEntityManagerMockForQuery([],
      'SELECT c FROM Tfboe\FmLib\Entity\RankingSystemChangeInterface c WHERE c.rankingSystem = :ranking' .
      ' AND c.hierarchyEntity IN(:entities)', ['persist', 'flush']);
    $entityManager->expects(static::once())->method('persist')->willReturnCallback(
      function (RankingSystemListInterface $entity) {
        self::assertInstanceOf(RankingSystemList::class, $entity);
        self::assertTrue($entity->isCurrent());
        static::getProperty(get_class($entity), 'id')->setValue($entity, 'new');
      });
    $service = $this->getMockWithMockedArguments(RankingSystemService::class, [$entityManager,
      $this->createMock(TimeServiceInterface::class), $this->createMock(EntityComparerInterface::class),
      $this->getObjectCreator()]);

    //create query mock for getEntities
    $query = $this->createMock(AbstractQuery::class);
    $query->expects(static::once())->method('getResult')->willReturn([]);
    //create query builder mock for getEntities
    $queryBuilder = $this->createMock(QueryBuilder::class);
    $queryBuilder->expects(static::once())->method('getQuery')->willReturn($query);
    $service->expects(static::once())->method('getEntitiesQueryBuilder')
      ->with($ranking, new \DateTime("2017-12-01"))->willReturn($queryBuilder);

    /** @var RankingSystemService $service */
    /** @var RankingSystemInterface $ranking */
    /** @noinspection PhpUnhandledExceptionInspection */
    /** @noinspection PhpUnhandledExceptionInspection */
    /** @noinspection PhpUnhandledExceptionInspection */
    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2018-02-28'));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingFrom
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::recomputeBasedOn
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::cloneInto
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextGenerationTime
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::flushAndForgetEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::markOldChangesAsDeleted
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemList
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Entity\Helpers\UUIDEntity::getId
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::deleteOldChanges
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getMaxDate
   */
  public function testUpdateRankingCreateMonthlyLists()
  {
    //create mock for input
    $ranking = $this->createStubWithId(RankingSystem::class, 'r1');
    $ranking->method('getGenerationInterval')->willReturn(AutomaticInstanceGeneration::MONTHLY);

    //create mocks for ranking lists
    $list = $this->createMock(RankingSystemList::class);
    $list->method('isCurrent')->willReturn(true);
    $list->method('getLastEntryTime')->willReturn(new \DateTime("2017-12-01"));
    $list->method('getEntries')->willReturn(new ArrayCollection());
    $list->method('getRankingSystem')->willReturn($ranking);

    $lists = $this->createMock(Collection::class);
    $lists->expects(static::once())->method('toArray')->willReturn([$list]);
    $lists->expects(static::once())->method('set')->with('new')->willReturnSelf();

    //create entities mocks
    $entity1 = $this->createStubWithId(TournamentHierarchyEntity::class, "e1");
    $entity1->method('getEndTime')->willReturn(new \DateTime("2018-03-01"));

    $entity2 = $this->createStubWithId(TournamentHierarchyEntity::class, "e2");
    $entity2->method('getEndTime')->willReturn(new \DateTime("2018-04-01 00:00:01"));

    //finish mock for input
    $ranking->expects(static::exactly(2))->method('getLists')->willReturn($lists);

    //create service mock
    $entityManager = $this->getEntityManagerMockForQuery([],
      'SELECT c FROM Tfboe\FmLib\Entity\RankingSystemChangeInterface c WHERE c.rankingSystem = :ranking' .
      ' AND c.hierarchyEntity IN(:entities)', ['persist', 'flush', 'detach']);
    $entityManager->expects(static::once())->method('persist')->willReturnCallback(
      function (RankingSystemListInterface $entity) {
        self::assertInstanceOf(RankingSystemList::class, $entity);
        static::getProperty(get_class($entity), 'id')->setValue($entity, 'new');
      });
    $timeService = $this->createMock(TimeServiceInterface::class);
    $timeService->method('getTime')->willReturnCallback(function (TournamentHierarchyInterface $entity) {
      return $entity->getEndTime();
    });
    $service = $this->getMockWithMockedArguments(RankingSystemService::class, [$entityManager,
      $timeService, $this->createMock(EntityComparerInterface::class),
      $this->getObjectCreator()]);

    //create query mock for getEntities
    $query = $this->createMock(AbstractQuery::class);
    $query->expects(static::once())->method('getResult')->willReturn([$entity1, $entity2]);
    //create query builder mock for getEntities
    $queryBuilder = $this->createMock(QueryBuilder::class);
    $queryBuilder->expects(static::once())->method('getQuery')->willReturn($query);
    $service->expects(static::once())->method('getEntitiesQueryBuilder')
      ->with($ranking, new \DateTime("2017-12-01"))->willReturn($queryBuilder);

    /** @var RankingSystemService $service */
    /** @var RankingSystemInterface $ranking */
    /** @noinspection PhpUnhandledExceptionInspection */
    /** @noinspection PhpUnhandledExceptionInspection */
    /** @noinspection PhpUnhandledExceptionInspection */
    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2018-02-28'));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingFrom
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::recomputeBasedOn
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::cloneInto
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextGenerationTime
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::flushAndForgetEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::markOldChangesAsDeleted
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemList
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::deleteOldChanges
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getMaxDate
   */
  public function testUpdateRankingFromNoReusable()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);
    $service = $this->prepareUpdateRankingFrom($ranking, $this->getEntityManagerMockForQuery([], null, ['flush']));

    /** @var RankingSystemInterface $ranking */

    /** @var RankingSystemService $service */
    /** @var RankingSystemInterface $ranking */
    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2017-02-28'));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::updateRankingFrom
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::recomputeBasedOn
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::cloneInto
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextGenerationTime
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getNextEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::flushAndForgetEntities
   * @covers \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::markOldChangesAsDeleted
   * @uses   \Tfboe\FmLib\Entity\Traits\RankingSystemList
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getEntities
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::deleteOldChanges
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::getMaxDate
   */
  public function testUpdateRankingFromNoEntities()
  {
    $ranking = $this->createStubWithId(RankingSystem::class);

    //create mocks for ranking lists
    $list = $this->createMock(RankingSystemList::class);
    $list->method('isCurrent')->willReturn(false);
    $list->method('getLastEntryTime')->willReturn(new \DateTime("2017-12-01"));
    $list->method('getEntries')->willReturn(new ArrayCollection());
    $list->method('getRankingSystem')->willReturn($ranking);

    $current = $this->createMock(RankingSystemList::class);
    $current->method('isCurrent')->willReturn(true);
    $current->method('getLastEntryTime')->willReturn(new \DateTime("2017-12-01"));
    $current->method('getEntries')->willReturn(new ArrayCollection());
    $current->method('getRankingSystem')->willReturn($ranking);

    $service = $this->prepareUpdateRankingFrom($ranking, $this->getEntityManagerMockForQuery([], null,
      ['flush', 'persist'], 2), [$list, $current], 2);
    /** @var RankingSystemInterface $ranking */

    /** @var RankingSystemService $service */
    /** @var RankingSystemInterface $ranking */
    /** @noinspection PhpUnhandledExceptionInspection */
    $service->updateRankingFrom($ranking, new \DateTime('2017-02-28'));
  }
//</editor-fold desc="Public Methods">

//<editor-fold desc="Private Methods">
  /**
   * Creates an empty RankingSystemChange
   * @return MockObject|RankingSystemChangeInterface
   */
  private function createEmptyChange(): MockObject
  {
    $change = $this->getMockForAbstractClass(RankingSystemChange::class, [['additional']], '', true, true, true,
      ['getPlayer', 'getPointsChange']);
    return $change;
  }

  /**
   * Creates an empty RankingSystemListEntry
   * @return MockObject|RankingSystemListEntryInterface
   */
  private function createEmptyEntry(): MockObject
  {
    $entry = $this->getMockForAbstractClass(RankingSystemListEntry::class, [['additional']], '', true, true, true,
      ['getPlayer', 'getPoints']);
    return $entry;
  }

  /**
   * Creates different entities used for create change
   * @return array, a hierarchy entity, a ranking system and a player
   */
  private function createEntities()
  {
    $entity = $this->createStubWithId(TournamentHierarchyEntity::class, 'h1');
    $ranking = $this->createStubWithId(RankingSystem::class, 'r1');
    $player = $this->createStubWithId(Player::class, 1, 'getId');
    return [$entity, $ranking, $player];
  }

  /**
   * Prepares a ranking system service for creating a change
   * @return array, the service entity and its corresponding entity manager
   */
  private function prepareCreateChange()
  {
    $entityManager = $this->createStub(EntityManager::class, []);
    /** @var $service RankingSystemService */
    $service = $this->getMockForAbstractClass(RankingSystemService::class, [
      $entityManager, $this->createMock(TimeServiceInterface::class),
      $this->createMock(EntityComparerInterface::class),
      $this->getObjectCreator()
    ]);
    return [$service, $entityManager];
  }

  /**
   * prepares a new ranking system service for update ranking from
   * @param MockObject $ranking the ranking entity
   * @param EntityManagerInterface|null $entityManager the entity manager or null if a mock should be used
   * @return RankingSystemService
   */
  private function prepareUpdateRankingFrom(MockObject $ranking, ?EntityManagerInterface $entityManager = null,
                                            $listsArray = null, $numListsToUpdate = 1, $mockedMethods = [],
                                            $entities = [])
  {
    if ($entityManager === null) {
      $entityManager = $this->getEntityManagerMockForQuery([]);
    }
    $service = $this->getMockForAbstractClass(RankingSystemService::class,
      [$entityManager, $this->createMock(TimeServiceInterface::class),
        $this->createMock(EntityComparerInterface::class),
        $this->getObjectCreator()], '', true, true, true, $mockedMethods);

    if ($listsArray == null) {
      //create mocks for current lists
      $list = $this->createMock(RankingSystemList::class);
      $list->method('isCurrent')->willReturn(true);
      $list->method('getLastEntryTime')->willReturn(new \DateTime("2017-06-01"));
      $list->method('getEntries')->willReturn(new ArrayCollection());
      $listsArray = [$list];
    }

    $lists = $this->createMock(Collection::class);
    $lists->expects(static::once())->method('toArray')->willReturn($listsArray);

    //finish mock for input
    $ranking->method('getLists')->willReturn($lists);

    //create query mock for getEntities
    $query = $this->createMock(AbstractQuery::class);
    $query->expects(static::exactly($numListsToUpdate))->method('getResult')->willReturn($entities);
    //create query builder mock for getEntities
    $queryBuilder = $this->createMock(QueryBuilder::class);
    $queryBuilder->expects(static::exactly($numListsToUpdate))->method('getQuery')->willReturn($query);
    $service->expects(static::exactly($numListsToUpdate))->method('getEntitiesQueryBuilder')
      ->with($ranking)->willReturn($queryBuilder);
    /** @var $service RankingSystemService */

    return $service;
  }
//</editor-fold desc="Private Methods">
}
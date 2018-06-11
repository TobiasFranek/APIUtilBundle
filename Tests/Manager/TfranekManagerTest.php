<?php

namespace Tfranek\APIUtilBundle\Tests;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Tfranek\APIUtilBundle\Manager\TfranekManager;
use Tfranek\APIUtilBundle\Tests\Manager\TestManager;
use Tfranek\APIUtilBundle\Exception\ResourceNotFoundException;


class TfranekManagerTest extends TestCase
{

    /**
     * @var EntityManager
     */
    private $objectManager;

    /**
     * @var Entity
     */
    private $entity;

    /**
     * @var TfranekManager
     */
    private $manager;

    protected function setUp()
    {
        $this->objectManager = $this->createMock(EntityManager::class);
        $this->entity = '\stdClass';


        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
                             ->setConstructorArgs([$this->objectManager])
                             ->setMethods(array('getQuery', 'getResult'))
                             ->getMock();

        // $queryBuilder = new QueryBuilder($this->objectManager);

        $repository = $this->createMock(ServiceEntityRepository::class);

        $metadata = $this->createMock(ClassMetadata::class);


        $repository->expects($this->any())
                   ->method('findAll')
                   ->will($this->returnValue([new \stdClass, new \stdClass]));

        $repository->expects($this->any())
                   ->method('findBy')
                   ->withConsecutive($this->equalTo([]), $this->equalTo(['test' => 'test']))
                   ->will($this->returnCallback([$this, 'findByCallback']));

        $this->objectManager->expects($this->any())
                            ->method('find')
                            ->withConsecutive(
                                [$this->equalTo($this->entity), $this->equalTo(1)],
                                [$this->equalTo($this->entity), $this->equalTo(2)]

                            )
                            ->will($this->returnCallback([$this, 'findCallback']));

        $this->objectManager->expects($this->any())
                            ->method('getRepository')
                            ->will($this->returnValue($repository));


        $metadata->expects($this->any())
                            ->method('getAssociationMappings')
                            ->willReturn([]);

        $metadata->expects($this->any())
                 ->method('getTypeOfField')
                 ->willReturn('integer');

        $queryBuilder->expects($this->any())
                     ->method('getQuery')
                     ->willReturn($queryBuilder);

        $queryBuilder->expects($this->any())
                     ->method('getResult')
                     ->willReturn([]);

        $this->objectManager->expects($this->any())
                            ->method('getClassMetadata')
                            ->willReturn($metadata);
        
        $this->objectManager->expects($this->any())
                            ->method('createQueryBuilder')
                            ->willReturn($queryBuilder);
    }
    public function testRead()
    {
        $manager = new TestManager($this->objectManager, $this->entity);

        $entity = $manager->read(1);

        $this->assertNotEmpty($entity);
        $this->assertInstanceOf(\stdClass::class,$entity);

        $this->expectException(ResourceNotFoundException::class);

        $manager->read(2);

        $this->assertTrue(true);
    }

    public function testReadAll() 
    {
        $manager = new TestManager($this->objectManager, $this->entity);

        $entities = $manager->readAll();

        $this->assertInternalType('array', $entities);

        $this->assertNotEmpty($entities);

        $this->assertInstanceOf(\stdClass::class, $entities[0]);
    }

    public function testReadBy()
    {
        $manager = new TestManager($this->objectManager, $this->entity);

        $entities = $manager->readBy([]);

        $this->assertInternalType('array', $entities);

        $this->assertNotEmpty($entities);

        $this->assertInstanceOf(\stdClass::class, $entities[0]);

        $this->assertEquals($entities, $manager->readAll());

        $entities = $manager->readBy(['test' => 'test']);

        $this->assertInternalType('array', $entities);

        $this->assertInstanceOf(\stdClass::class, $entities[0]);

        $this->assertNotEquals($entities, $manager->readAll());
    }

    public function testCreate()
    {
        $manager = new TestManager($this->objectManager, $this->entity);

        $entity = $manager->create(['test' => 'test']);

        $this->assertInstanceOf($this->entity, $entity);
    }

    public function testUpdate() 
    {
        $manager = new TestManager($this->objectManager, $this->entity);

        $entity = $manager->update(1, ['test' => 'test']);

        $this->assertInstanceOf($this->entity, $entity);

        $this->expectException(ResourceNotFoundException::class);

        $entity = $manager->update(2, ['test' => 'test']);
    }

    public function testDelete() 
    {
        $manager = new TestManager($this->objectManager, $this->entity);

        $id = $manager->delete(1);

        $this->assertEquals($id, 1);

        $this->expectException(ResourceNotFoundException::class);

        $manager->delete(2);
    }

    public function testReadByRecursivly()
    {
        $manager = new TestManager($this->objectManager, $this->entity);

        var_dump($manager->readByRecursively(['test' => '1']));

        var_dump($manager->getQuery()->getDQL());
        $this->assertTrue(true);
    }

    public function findCallback($entity, $id) 
    {

        return $id == 1 ? new \stdClass() : null;
    }

    public function findByCallback($parameters) 
    {
        return $parameters ? [new \stdClass()] : [new \stdClass(), new \stdClass()];
    }
}


<?php

declare(strict_types=1);

namespace Tfranek\APIUtilBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Tfranek\APIUtilBundle\Exception\MethodNotDeclaredException;
use Tfranek\APIUtilBundle\Exception\NotValidDateformatException;
use Tfranek\APIUtilBundle\Exception\ResourceNotFoundException;
use Tfranek\APIUtilBundle\Manager\Interfaces\ManagerInterface;

/**
 * Abstract class for the Managers to extend from
 * @author Tobias Franek <tobias.franek@gmail.com>
 * @license MIT
 */
abstract class TfranekManager implements ManagerInterface 
{
    /**
     * @var EntityManager
     */
    protected $entityManager;
    /**
     * @var string
     */
    protected $class;
    /**
     * @var QueryBuilder
     */
    protected $query;

    /**
     * @param EntityManager $entityManager
     * @param object $entity
     */
    public function __construct(EntityManager $entityManager, $entity)
    {
        $this->entityManager = $entityManager;
        $this->class = $entity;
        $this->query = $this->generateJoins();
    }
    /**
     * Creates a new Entity Element and set the data which are passed
     * @param array $data
     * @return Entity
     */
    public function create(array $data) : object
    {
        $entity = $this->bind(new $this->class(), $data);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }
    /**
     * Returns Elements with given id.
     * @param int $id
     * @throws ResourceNotFoundException
     * @return Entity
     */
    public function read(int $id) : object
    {
        $entity = $this->entityManager->find($this->class, $id);
        if($entity) {
            return $entity;
        } else {
            throw new ResourceNotFoundException('The Resource you are looking for could not be found');
        }
    }
    /**
     * Returns all Elements
     * @return array
     */
    public function readAll() : array
    {
        return $this->entityManager->getRepository($this->class)->findAll();
    }
    /**
     * Returns all Elements that where search for by a readBy
     * @param array $parameter
     * @return array
     */
    public function readBy(array $parameter) : array
    {
        return $this->entityManager->getRepository($this->class)->findBy($parameter);
    }
    /**
     * Return all Elements that where also searched by in joint Tables
     * @param array $parameter
     * @return array
     */
    public function readByRecursively(array $parameter) : array
    {
        $this->generateWheres($parameter);
        return $this->query->getQuery()->getResult();
    }
    /**
     * Updates the Element with the given id and data
     * @param $id
     * @param array $data
     * @throws ResourceNotFoundException
     * @return Entity
     */
    public function update($id, array $data) : object
    {
        $entity = $this->read($id);

        if(!$entity) {
            throw new ResourceNotFoundException('The Resource you are looking for could not be found');
        }

        $changedEntity = $this->bind($entity, $data);
        $this->entityManager->flush();
        return $changedEntity;
    }
    /**
     * Deletes the Element with the given id and data
     * @param int $id
     * @throws ResourceNotFoundException
     * @return int
     */
    public function delete(int $id) : int 
    {
        $entity = $this->read($id);
        if(!$entity) {
            throw new ResourceNotFoundException('The Resource you want to delete could not be found');
        }
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
        return $id;
    }

    /**
     * return the Repository of the Entity
     * @return ServiceEntityRepository
     */
    public function getRepository() : ServiceEntityRepository
    {
        return $this->entityManager->getRepository($this->class);
    }

    public function getQuery() : QueryBuilder
    {
        return $this->query;
    }

    /**
     * Returns property of given data array.
     * If the key not exists default value will be returned.
     * @param array $data
     * @param string $property
     * @param string $default
     * @return string
     */
    protected function getValue(array $data, string $property, string $default = '') : string
    {
        return array_key_exists($property, $data) ? $data[$property] : $default;
    }
    /**
     * generates a query with all tables that need to be joined
     * @return QueryBuilder
     */
    protected function generateJoins() : QueryBuilder
    {
        $associations = $this->entityManager->getClassMetadata($this->class)->getAssociationMappings();
        $query = $this->entityManager->createQueryBuilder();
        $explodedClassName = explode('\\', $this->class);
        $parentName = strtolower($explodedClassName[count($explodedClassName) - 1]);
        $selectString = $parentName . ', ';
        $query->from($this->class, $parentName);
        foreach ($associations as $key => $value) {
            $selectString .= $value['fieldName'] . ', ';
            $query->leftJoin($parentName . '.' . $value['fieldName'], $value['fieldName']);
        }
        $query->select(substr($selectString, 0, strlen($selectString) - 2));
        return $query;
    }
    /**
     * generates the wheres from the given parameters
     * @param array $parameters
     * @throws NotValidDateformatException
     */
    protected function generateWheres(array $parameters)
    {
        $i = 0;
        $explodedClassName = explode('\\', $this->class);
        $parentName = strtolower($explodedClassName[count($explodedClassName) - 1]);
        $parametersWithoutKeys = [];
        foreach ($parameters as $key => $value) {
            $or = false;
            $firstLetter = substr($value, 0, 1);
            if ($firstLetter == '|') {
                $or = true;
                $value = substr($value, 1);
            }
            $key = str_replace('_', '.', $key);
            if (count(explode('.', $key)) < 2 && $key != 'orderBy' && $key != 'limit') {
                $key = $parentName . '.' . $key;
            }
            if ($key == 'orderBy') {
                $orderByField = array_keys($value)[0];
                $direction = $value[$orderByField];
                if (count(explode('.', $orderByField)) < 2) {
                    $orderByField = $parentName . '.' . $orderByField;
                }
                $this->query->orderBy($orderByField, $direction);
                continue;
            }
            if ($key == 'limit') {
                $this->query->setMaxResults(intval($value));
                continue;
            }
            $type = $this->getType($key);
            $operator = '';
            if ($type == 'integer') {
                $operator = '=';
            } else if ($type == 'float' || $type == 'decimal') {
                $operator = '=';
            } else if ($type == 'datetime') {
                if (!isset($value['startDate'])) {
                    $this->query->andWhere($key . ' <= ?' . $i);
                } else if (!isset($value['endDate'])) {
                    $this->query->andWhere($key . ' >= ?' . $i);
                } else if (isset($value['startDate']) && isset($value['endDate'])) {
                    $this->query->andWhere($key . ' >= ?' . $i . ' and ' . $key . ' <= ?' . ($i + 1));
                } else {
                    throw new NotValidDateformatException('Date format is not valid');
                }
            } else {
                $operator = 'LIKE';
            }
            if ($type !== 'datetime') {
                if ($i == 0) {
                    $this->query->where($key . ' ' . $operator . ' ?' . $i);
                } else {
                    if ($or) {
                        $this->query->orWhere($key . ' ' . $operator . ' ?' . $i);
                    } else {
                        $this->query->andWhere($key . ' ' . $operator . ' ?' . $i);
                    }
                }
            }
            if ($type == 'datetime') {
                if (isset($value['startDate'])) {
                    $parametersWithoutKeys[$i] = new \DateTime($value['startDate']);
                }
                if (isset($value['endDate']) && isset($value['startDate'])) {
                    $i++;
                }
                if (isset($value['endDate'])) {
                    $parametersWithoutKeys[$i] = new \DateTime($value['endDate']);
                }
            } else {
                $parametersWithoutKeys[] = $value;
            }
            $i++;
        }
        $this->query->setParameters($parametersWithoutKeys);
    }
    /**
     * return the doctrine type of a given field
     * @param string $var
     * @throws ResourceNotFoundException
     * @return string
     */
    protected function getType(string $var) : string 
     {
        $var = str_replace('_', '.', $var);
        $parts = explode('.', $var);
        $explodedClassName = explode('\\', $this->class);
        $parentName = strtolower($explodedClassName[count($explodedClassName) - 1]);
        if ($parts[0] == $parentName) {
            return $this->entityManager->getClassMetadata($this->class)->getTypeOfField($parts[1]);
        } else {
            $mapping = $this->entityManager->getClassMetadata($this->class)->getAssociationMappings();
            $targetEntity = '';
            foreach ($mapping as $value) {
                if ($value['fieldName'] == $parts[0]) {
                    $targetEntity = $value['targetEntity'];
                    break;
                }
            }
            if ($targetEntity) {
                return $this->entityManager->getClassMetadata($targetEntity)->getTypeOfField($parts[1]);
            } else {
                throw new ResourceNotFoundException('Table does not exist');
            }
        }
    }
}
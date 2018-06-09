<?php
namespace Tfranek\APIUtilBundle\Manager\Interfaces;

use Doctrine\ORM\Mapping\Entity;

/**
 * Manager Interface
 * @author Tobias Franek <tobias.franek@gmail.com>
 * @license MIT
 */
interface ManagerInterface {

    /**
     * Creates a new Element and sets the data which are passed
     * @param array $data
     * @return Entity
     */
    public function create(array $data) : object;

    /**
     * Returns a Element with given id.
     * @param $id
     * @return Entity
     */
    public function read(int $id) : object;

    /**
     * Returns all Elements
     * @return array
     */
    public function readAll() : array;

    /**
     * Updates the Element with the given id and data
     * @param int $id
     * @param array $data
     * @return Entity
     */
    public function update(int $id, array $data) : object;

    /**
     * Delete the Element with the given id and data
     * @param int $id
     */
    public function delete(int $id);

    /**
     * Bind data array to the given entity.
     * @param Entity $entity
     * @param array $data
     * @return Entity
     */
    public function bind(object $entity, array $data) : object;
}
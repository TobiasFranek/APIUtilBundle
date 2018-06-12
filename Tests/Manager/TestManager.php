<?php
namespace Tfranek\APIUtilBundle\Tests\Manager;

use Doctrine\ORM\EntityManager;
use Tfranek\APIUtilBundle\Manager\TfranekManager;

class TestManager extends TfranekManager {

    /**
     * {@inheritdoc}
     */
    public function bind($entity, array $data)
     {
        return $entity;
    }
}
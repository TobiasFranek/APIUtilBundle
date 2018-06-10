<?php
namespace Tfranek\APIUtilBundle\Tests\Manager;

use Doctrine\ORM\EntityManager;
use Tfranek\APIUtilBundle\Manager\TfranekManager;

class TestManager extends TfranekManager {

    /**
     * {@inheritdoc}
     */
    public function bind(object $entity, array $data): object
     {
        return $entity;
    }
}
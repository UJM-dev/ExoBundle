<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace UJM\ExoBundle\Installation\Updater;

use Claroline\InstallationBundle\Updater\Updater;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Updater050000 extends Updater
{
    private $container;
    private $om;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->om = $container->get('doctrine.orm.entity_manager');
    }

    public function preUpdate($currentVersion)
    {
        $this->log('Updating migration versions...');
        $conn = $this->om->getConnection();
        $stmt = $conn->query("SELECT * from doctrine_ujmexobundle_versions where version=20150416104535");
        $found = false;

        while ($row = $stmt->fetch()) {
            $found = true;
        }

        if (!$found) {
            $this->log('Inserting migration 20150416104535.');
            $conn->query("INSERT INTO doctrine_clarolinecorebundle_versions (version) VALUES (20150416104535)");
        } else {
            $this->log('Migrations found.');
        }

    }
}

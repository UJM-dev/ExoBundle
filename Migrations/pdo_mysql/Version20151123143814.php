<?php

namespace UJM\ExoBundle\Migrations\pdo_mysql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated migration based on mapping information: modify it with caution
 *
 * Generation date: 2015/11/23 02:38:15
 */
class Version20151123143814 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE ujm_paper 
            DROP FOREIGN KEY FK_82972E4BE934951A
        ");
        $this->addSql("
            ALTER TABLE ujm_paper 
            ADD CONSTRAINT FK_82972E4BE934951A FOREIGN KEY (exercise_id) 
            REFERENCES ujm_exercise (id) 
            ON DELETE SET NULL
        ");
    }

    public function down(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE ujm_paper 
            DROP FOREIGN KEY FK_82972E4BE934951A
        ");
        $this->addSql("
            ALTER TABLE ujm_paper 
            ADD CONSTRAINT FK_82972E4BE934951A FOREIGN KEY (exercise_id) 
            REFERENCES ujm_exercise (id)
        ");
    }
}
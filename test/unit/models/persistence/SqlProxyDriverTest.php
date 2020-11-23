<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2020 (original work) Open Assessment Technologies SA;
 */

namespace oat\taoDevTools\test\unit\models\persistence;

use oat\generis\test\TestCase;
use oat\generis\test\SqlMockTrait;
use oat\taoDevTools\models\persistence\SqlProxyDriver;
use oat\generis\persistence\PersistenceManager;

class SqlProxyDriverTest extends TestCase
{
    use SqlMockTrait;

    public function testLogQueries()
    {
        $persistenceManager = $this->getSqlMock('memory');
        $driver = new SqlProxyDriver();
        $driver->setServiceLocator($this->getServiceLocatorMock([
            PersistenceManager::class => $persistenceManager
        ]));
        $persistence = $driver->connect('proxy', ['persistenceId' => 'memory']);
        $this->assertInstanceOf(\common_persistence_SqlPersistence::class, $persistence);

        $counter = $driver->getCounter();
        $this->assertEquals(0, $counter->getCount());
        $persistence->query('SELECT 1;');
        $this->assertEquals(1, $counter->getCount());
        $persistence->exec('SELECT 1;');
        $this->assertEquals(2, $counter->getCount());
        // generate SQL only
        $queryBuilder = $persistence->getPlatForm()->getQueryBuilder();
        $queryBuilder->select('1');
        $this->assertEquals(2, $counter->getCount());
        $persistence->query($queryBuilder->getSQL())->fetchAll();
        $this->assertEquals(3, $counter->getCount());
        // execute query builder
        $queryBuilder = $persistence->getPlatForm()->getQueryBuilder();
        $queryBuilder->select('1');
        $this->assertEquals(3, $counter->getCount());
        $queryBuilder->execute();
        $this->assertEquals(4, $counter->getCount());
    }

}

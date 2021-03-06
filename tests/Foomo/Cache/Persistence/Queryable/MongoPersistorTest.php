<?php

/*
 * This file is part of the foomo Opensource Framework.
 *
 * The foomo Opensource Framework is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General Public License as
 * published  by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * The foomo Opensource Framework is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with
 * the foomo Opensource Framework. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Foomo\Cache\Persistence\Queryable;

use Foomo\Cache\Persistence\Expr;

/**
 * @link www.foomo.org
 * @license www.gnu.org/licenses/lgpl.txt
 * @author jan <jan@bestbytes.de>
 */
class MongoPersistorTest extends AbstractTest {

	private $mongoPersistor;
	private $resource;
	private $arguments;
	private $method;
	private $className;
	private $object;
	private $config;


	public function setUp() {
		$domainConfig = \Foomo\Config::getConf(\Foomo\Module::NAME, \Foomo\Cache\Test\DomainConfig::NAME);
		if ($domainConfig && !empty($domainConfig->queryablePersistors['mongo'])) {
			$fastPersistorConf = $domainConfig->fastPersistors['memcached'];
			$queryablePersistorConf = $domainConfig->queryablePersistors['mongo'];
			$fastPersistor = \Foomo\Cache\Manager::getPersistorFromConf($fastPersistorConf, false);
			$mongoPersistor = \Foomo\Cache\Manager::getPersistorFromConf($queryablePersistorConf, true);


			$this->className = 'Foomo\Cache\MockObjects\SampleResources';
			$this->object = new $this->className;
			$this->method = 'getHoroscopeData';
			$this->arguments = array(0, 'myLocation');
			$this->resource = \Foomo\Cache\Proxy::getEmptyResource($this->className, $this->method, $this->arguments);
			$this->resource->value = call_user_func_array(array($this->object, $this->method), $this->arguments);
			$this->mongoPersistor = $mongoPersistor;

			$this->mongoPersistor->reset(null, true);
			$this->saveManagerSettings();
			\Foomo\Cache\Manager::initialize($this->mongoPersistor);
		} else {
			$this->markTestSkipped(
					'missing test config ' . \Foomo\Cache\Test\DomainConfig::NAME .
					' for module ' . \Foomo\Module::NAME . ' respectively the mongo config on it is empty'
			);
		}
	}

	public function tearDown() {
		//set the mamager back
		$this->restoreManagerSettings();
	}

	public function testConnect() {
		$this->assertNotNull($this->mongoPersistor->mongo);
	}

	public function testLoadSaveDelete() {
		$this->mongoPersistor->save($this->resource);

		//var_dump($this->resource);
		$loadedResource = $this->mongoPersistor->load($this->resource);
		//var_dump($loadedResource);
		$this->assertEquals($this->resource, $loadedResource);
		$success = $this->mongoPersistor->delete($this->resource);
		$this->assertEquals(true, $success);
		$loadedResource = $this->mongoPersistor->load($this->resource);
		$this->assertEquals(null, $loadedResource, 'Resources were not deleted after call to delete.');
	}

	public function testqueryWithExpression() {
		$this->storeTestResources();

		$expr = Expr::idEq($this->resource->id);
		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(1, count($iterator));



		$expr = Expr::groupAnd(Expr::idEq($this->resource->id), Expr::isExpired()
		);


		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(0, count($iterator));


		$expr = Expr::groupAnd(Expr::idEq($this->resource->id), Expr::isExpired(), Expr::statusValid()
		);

		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(0, count($iterator));


		$expr = Expr::groupAnd(
						Expr::isNotExpired(), Expr::statusValid()
		);

		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(4, count($iterator));



		$expr = Expr::groupOr(
						Expr::idNe($this->resource->id), Expr::groupAnd(
								Expr::isNotExpired(), Expr::isExpired(), Expr::statusValid()
						)
		);

		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(3, count($iterator));



		$expr = Expr::groupAnd(
						Expr::createdBefore(\time() + 1)
		);

		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(4, count($iterator));
	}

	public function testPropertiesQueries() {
		$this->storeTestResources();
		$expr = Expr::propEq('timestamp', 0);
		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(1, count($iterator));

		$expr = Expr::propNe('timestamp', 0);
		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(3, count($iterator));

		$expr = Expr::groupOr(Expr::propEq('timestamp', 0), Expr::propNe('timestamp', 0)
		);
		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(4, count($iterator));

		$expr = Expr::propsEq($this->resource->properties);
		$iterator = $this->mongoPersistor->query($this->resource->name, $expr, 0, 0);
		$this->assertEquals(1, count($iterator));
	}

	public function testDelete() {
		$this->storeTestResources();
		$this->resource = \Foomo\Cache\Proxy::getEmptyResource($this->className, $this->method, $this->arguments);

		$resources = \Foomo\Cache\Manager::query($this->resource->name);
		//now delete all
		foreach ($resources as $resource) {
			$this->mongoPersistor->delete($resource);
		}
		$resources = \Foomo\Cache\Manager::query($this->resource->name);
		$this->assertEquals(0, count($resources));
	}

	private function storeTestResources() {


		$argumentCombinations = array(array(0, 'myLocation'), array(1, 'myLocation1'), array(2, 'myLocation2'), array(3, 'myLocation3'));
		foreach ($argumentCombinations as $arguments) {
			$this->resource = \Foomo\Cache\Proxy::getEmptyResource($this->className, $this->method, $arguments);
			$this->resource->value = call_user_func_array(array($this->object, $this->method), $arguments);
			$this->mongoPersistor->save($this->resource);
		}
	}

	private function deleteTestResources() {
		$this->mongoPersistor->reset();
	}

}
<?php

namespace App\Model\Connection;

use App\Model\Connection\Transaction;

use App\Model\Connection\InsertArray;

class Pgsql{

	use Transaction;

	private $_connection;

	public $pdo;

	public $query;

	public function __construct(){

		$this->_connection = new \stdClass;

		$this->query = new InsertArray;
	}

	public function connect(array $config) :object|array|null {

		try{

			$dsn = $config['DB_CONNECTION']??'pgsql'.':host='.$config['DB_HOST'].';port='.$config['DB_PORT'].';dbname='.$config['DB_DATABASE'];

			$this->_connection->config = $config;
			$this->pdo = new \PDO($dsn, $config['DB_USERNAME'], $config['DB_PASSWORD']);

			return $this;

		}catch(\PDOException $e){

			echo 'Fail at connect to '.$config['DB_DATABASE'].': '.$e->getMessage();

			exit;
		}

		return null;
	}
}
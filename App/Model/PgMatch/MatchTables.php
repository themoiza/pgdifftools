<?php

namespace App\Model\PgMatch;

use App\Model\Connection\Pgsql;

use App\Model\PgMatch\MatchColumns;

class MatchTables extends MatchColumns{

	public $pdoOrigin;

	public $pdoTarget;

	public $source = [];
	public $target = [];

	public $sourceTables = [];

	public $targetTables = [];

	public $threshold = 11;

	public $tableNameWeight = 1.5;

	public $columnsCountWeight = 1.1;

	public $columnSimilarWeight = 1.5;


	public $points = 0;
	public $best = '?';

	public function config(array $source, array $target) :void{

		$Pgsql = new Pgsql;
		$this->pdoOrigin = $Pgsql->connect($source);
		$Pgsql = null;

		$Pgsql = new Pgsql;
		$this->pdoTarget = $Pgsql->connect($target);
		$Pgsql = null;
	}

	public function setThreshold(float $threshold) :void{
		$this->threshold = $threshold;
	}

	public function setTableNameWeight(float $tableNameWeight) :void{
		$this->tableNameWeight = $tableNameWeight;
	}

	public function setColumnsCountWeight(float $columnsCountWeight) :void{
		$this->columnsCountWeight = $columnsCountWeight;
	}

	public function setColumnSimilarWeight(float $columnSimilarWeight) :void{
		$this->columnSimilarWeight = $columnSimilarWeight;
	}

	private function _orderString(string $string){

		$arr = str_split($string, 1);

		$sort = [];
		foreach($arr as $letter){
			$sort[$letter] = $letter;
		}

		natsort($sort);

		$string = implode('', $sort);

		return $string;
	}

	protected function _getColumns(object $conn, string $table, string $direction) :void{

		$columns = [];
		if(!isset($this->sourceTables[$table]) and !isset($this->targetTables[$table])){

			$query = $conn->pdo->prepare(
				"SELECT 
					cols.column_name AS field,
					cols.data_type AS type,
					cols.is_nullable AS null,
					cols.column_default AS default,
					cols.character_maximum_length AS column_size,
					cols.numeric_precision,
					cols.numeric_scale
				FROM information_schema.columns as cols
				WHERE cols.table_name = :table_name
				ORDER BY cols.data_type asc, cols.column_name ASC");

			$query->bindParam(':table_name', $table);
			$query->execute();
			$temp = $query->fetchAll(\PDO::FETCH_OBJ);
			$query = null;

			foreach ($temp as $a => $b) {

				/*$nullable = 'NOTNULL';

				if($b->null === 'YES'){
					$nullable = 'NULL';
				}*/

				$columns[$this->_orderString($b->field)] = $b->type/*.'|'.$nullable*/;
			}

			if($direction == 'source'){

				$this->sourceTables[$table] = $columns;
			}

			if($direction == 'target'){

				$this->targetTables[$table] = $columns;
			}
		}
	}

	protected function _getBestMatch(string $tableToCompare, array $target) :array{

		$first = $this->sourceTables[$tableToCompare] ?? [];

		$this->points = 0;
		$this->best = '?';
		foreach($target as $table){

			$thisPoints = 1;

			// Table Name Weight
			similar_text($tableToCompare, $table, $percent);
			$thisPoints = $thisPoints + pow(($percent / 10), $this->tableNameWeight);

			// Columns Count Weight
			$second = $this->targetTables[$table] ?? [];
			$thisPoints = $thisPoints + $this->_getColumnsMatch($first, $second);


			// Column Similar Weight
			$table1 = implode('-', $first).'-'.$tableToCompare;
			$table2 = implode('-', $second).'-'.$table;
			similar_text($table1, $table2, $percent2);
			$thisPoints = $thisPoints + pow(($percent2 / 10), $this->columnSimilarWeight);

			if($this->points < $thisPoints and $thisPoints > $this->threshold){
				$this->points = $thisPoints;
				$this->best = $table;
			}
		}

		return [
			'best' => $this->best,
			'points' => number_format($this->points, '2', ',', '.') 
		];
	}

	public function setTables(array $source, array $target) :void{

		$this->source = $source;
		$this->target = $target;

		foreach($this->source as $table){

			$this->_getColumns($this->pdoOrigin, $table, 'source');
		}

		foreach($this->target as $table){

			$this->_getColumns($this->pdoTarget, $table, 'target');
		}
	}

	public function do() :array{

		$result = [];
		foreach($this->source as $tableToCompare){
		
			$best = $this->_getBestMatch($tableToCompare, $this->target);

			$result[] = [
				$tableToCompare,
				$best['points'],
				$best['best']
			];
		}

		return $result;
	}
}
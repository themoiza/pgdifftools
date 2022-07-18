<?php

namespace App\Model\Connection;

class InsertArray{

	public function insertArray($table, $schema, $arr, $conn){

		$cols = [];
		$vals = [];

		foreach($arr as $col => $val){

			$cols[] = $col;
			$vals[] = ':'.$col;

		}

		$cols = implode(', ', $cols);
		$vals = implode(', ', $vals);

		$query = $conn->prepare("INSERT INTO $schema.$table ($cols) VALUES ($vals)");

		foreach($arr as $col => &$val){

			$query->bindParam(':'.$col, $val);
		}

		$query->execute();
	}
}
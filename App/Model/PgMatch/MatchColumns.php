<?php

namespace App\Model\PgMatch;

class MatchColumns{

	protected function _getColumnsMatch(array $first, array $second) :float{

		$thisPoints = 0;

		if(count($first) == count($second)){

			$thisPoints = $thisPoints + pow(10, $this->columnsCountWeight);

		}else{

			$pow = pow(abs(count($first) - count($second)), $this->columnsCountWeight);

			$thisPoints = $thisPoints - pow($pow, $this->columnsCountWeight);
		}

		return $thisPoints;
	}
}
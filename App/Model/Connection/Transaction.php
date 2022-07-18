<?php

namespace App\Model\Connection;

trait Transaction{

	protected $_commit = true;

	protected $_sqlErrors = [];

	// BLOCK COMMIT AND ADD AN ERROR
	public function blockCommit(array $error){

		$this->_commit = false;

		$this->addError($error);
		return $this;
	}

	// START TRANSACTION
	public function startTransaction(){

		if($this->pdo->inTransaction() === false){
			$this->pdo->beginTransaction();
		}
	}

	// VERIFY IF COMMIT IS POSSIBLE AND MAKE IT
	public function makeCommit(){

		if($this->_commit === true and $this->pdo->inTransaction() === true){

			$this->pdo->commit();
		}
	}

	// VERIFY IF ROLLBACK IS POSSIBLE AND MAKE IT
	public function makeRollback(){

		if($this->pdo->inTransaction() === true){

			$this->pdo->rollBack();
		}
	}

	public function addError(array $error){

		$this->_sqlErrors = array_merge($this->_sqlErrors, $error);

		return $this;
	}

	public function getErrors(){

		return $this->_sqlErrors;
	}

	public function canCommit(){

		return $this->_commit;
	}
}
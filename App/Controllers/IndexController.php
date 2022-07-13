<?php

namespace App\Controller;

use TheMoiza\MvcCore\Core\View;

use TheMoiza\MvcCore\Core\Response;

class IndexController{

	public function index(){

		Response::send(View::load('index', []));
	}
}
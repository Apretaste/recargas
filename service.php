<?php

use Apretaste\Request;
use Apretaste\Response;

class Service
{
	public function _main(Request $request, Response $response)
	{
		$response->setCache('year');
		$response->setTemplate('main.ejs');
	}
}

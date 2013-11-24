<?php

use Parvula\Core\View;
use Parvula\Core\Config;
use Parvula\Core\Parvula;

// Admin pages
$router->any('/_admin/', function() {
	return require ADMIN . 'admin.php';
});

// Api
$router->space('/_api', function($router) {
	return require APP . 'api.php';
});

// Front
$router->get('/?:pagename?', function($req) use($config) {
	$pagename = isSet($req->params->pagename) ? $req->params->pagename : Config::homePage();

	// Check if template exists (must have index.html)
	$baseTemplate = TMPL . Config::get('template');
	if(!is_readable($baseTemplate . '/index.html')) {
		die("Error - Template is not readable");
	}

	Asset::setBasePath(Parvula::getRelativeURIToRoot() . $baseTemplate);

	$parvula = new Parvula;
	$page = $parvula->getPage($pagename);

	// 404
	if(false === $page) {
		$page = $parvula->getPage(Config::errorPage());

		if(false === $page) {
			// Juste print simple 404 if there is no 404 page
			die('404 - Page ' . htmlspecialchars($page) . ' not found');
		}
	}

	try {
		$view = new View(TMPL . Config::get('template'));

		// Assign some variables
		$view->assign('baseUrl', Parvula::getRelativeURIToRoot());
		$view->assign('templateUrl', Asset::getBasePath());

		// Register alias for secure echo
		$view->assign(array(
			'_e' => function(&$str) {
				return HTML::sEcho($str);
			},
			'_et' => function(&$str, $str2) {
				return HTML::sEchoThen($str, $str2);
			}
		));

		$pages = $parvula->getPages();
		$view->assign(array(
			'site' => $config,
			'pages' => $pages,
			'meta' => $page,
			'content' => $page->content
		));

		// Show index template
		echo $view('index');

	} catch(Exception $e) {
		exceptionHandler($e);
	}
});

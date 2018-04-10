<?php

use App\AnimeCharades;
use App\AnimeListManager;
use Slim\App;
use Slim\Exception\NotFoundException;

session_start();

require_once __DIR__ . '/../vendor/autoload.php';


$slim = new App();

$container = $slim->getContainer();

// Register Twig View helper
$container['view'] = function (\Slim\Container $container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../templates', [
//        'cache' => 'path/to/cache',
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new \Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

$slim->get('[/]', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {

    $app = new AnimeCharades();
    $lists = AnimeListManager::loadAll();

    if (!$lists) {
        throw new NotFoundException($request, $response);
    }

    $app->generateList($lists);
    $anime = $app->roll();

    return $this->view->render($response, 'game.twig', [
        'anime' => $anime,
    ]);
});

$slim->get('/fetch', function (\Slim\Http\Request $request, \Slim\Http\Response $response) {
    $nicknames = [
    ];
    foreach ($nicknames as $nickname) {
        $mal = new \App\Mal();
        $list = $mal->fetch($nickname);
        AnimeListManager::save($list);
    }

    return $response->write('Done');
});


$slim->run();


<?php

use App\AnimeCharades;
use App\AnimeListManager;
use App\Database\Anime;
use App\Database\SQLiteConnection;
use App\Mal;
use Slim\App;
use Slim\Container;
use Slim\Exception\NotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Views\TwigExtension;

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

$slim = new App();

$container = $slim->getContainer();

// Register Twig View helper
$container['view'] = function (Container $container) {
    $view = new \Slim\Views\Twig(__DIR__ . '/../templates', [
//        'cache' => 'path/to/cache',
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new TwigExtension($container['router'], $basePath));

    return $view;
};

$container['anime'] = function () {
    return new Anime(new SQLiteConnection());
};

$slim->get('[/]', function (Request $request, Response $response) {

    $app = new AnimeCharades();
    $lists = AnimeListManager::loadAll();

    if (!$lists) {
        throw new NotFoundException($request, $response);
    }

    $app->generateList($lists);
    $anime = $app->roll();

    return $this->view->render($response, 'game.twig', [
        'anime' => $anime,
        'list' => $this->anime->related($anime->getName()),
    ]);
});

$slim->get('/anime', function (Request $request, Response $response) {
    $page = $request->getParam('p', 1);
    $search = $request->getParam('s', '');
    $items = $search ? $this->anime->search($search, $page) : $this->anime->list($page);

    return $this->view->render($response, 'list.twig', ['items' => $items, 'page' => $page]);
})->setName('anime.index');

$slim->post('/anime/update/{id:\d+}', function (Request $request, Response $response) {
    $id = (int) $request->getAttribute('id');
    $name = (string) trim($request->getParam('name', ''));
    if (!$name) {
        throw new NotFoundException($request, $response);
    }

    $this->anime->update($id, $name);

    return $this->view->render($response, '_delete-form.twig', ['id' => $id, 'main' => $name]);
})->setName('anime.update');

$slim->post('/anime/delete/{id:\d+}', function (Request $request, Response $response) {
    $id = (int) $request->getAttribute('id');
    $this->anime->delete($id);

    return $this->view->render($response, '_update-form.twig', ['id' => $id]);
})->setName('anime.delete');

$slim->get('/anime/hint', function (Request $request, Response $response) {
    if (!$name = $request->getParam('query')) {
        throw new NotFoundException($request, $response);
    }

    $payload = $this->anime->hint($name);

    return $response->withJson($payload);
})->setName('anime.hint');

$slim->post('/fetch', function (Request $request, Response $response) {
    $nicknames = $request->getParam('nicknames');

    foreach ($nicknames as $nickname) {
        $mal = new Mal();
        $list = $mal->fetch($nickname);
        AnimeListManager::save($list);
    }

    return $response->write('Done');
});


$slim->run();

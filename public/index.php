<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\RoomController;
use App\Controllers\SongController;
use App\Controllers\QueueController;
use App\Controllers\ScoringController;
use App\Controllers\AdminController;

$request = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Route handling
switch (true) {
    // Auth routes
    case preg_match('/^\/login$/', $request):
        $controller = new AuthController();
        if ($method === 'GET') {
            $controller->showLogin();
        } elseif ($method === 'POST') {
            $controller->login();
        }
        break;
        
    case preg_match('/^\/register$/', $request):
        $controller = new AuthController();
        if ($method === 'GET') {
            $controller->showRegister();
        } elseif ($method === 'POST') {
            $controller->register();
        }
        break;
        
    case preg_match('/^\/logout$/', $request):
        $controller = new AuthController();
        $controller->logout();
        break;

    // Room routes
    case preg_match('/^\/$/', $request):
        $controller = new RoomController();
        $controller->index();
        break;
        
    case preg_match('/^\/solo$/', $request):
        $controller = new RoomController();
        $controller->soloMode();
        break;
        
    case preg_match('/^\/room\/create$/', $request):
        $controller = new RoomController();
        if ($method === 'POST') {
            $controller->create();
        }
        break;
        
    case preg_match('/^\/room\/join\/([^\/]+)$/', $request, $matches):
        $controller = new RoomController();
        $controller->join($matches[1]);
        break;
        
    case preg_match('/^\/room\/([^\/]+)$/', $request, $matches):
        $controller = new RoomController();
        $controller->show($matches[1]);
        break;

    // Song routes
    case preg_match('/^\/api\/songs$/', $request):
        $controller = new SongController();
        $controller->getSongs();
        break;
        
    case preg_match('/^\/api\/songs\/search$/', $request):
        $controller = new SongController();
        $controller->search();
        break;
        
    case preg_match('/^\/api\/songs\/([^\/]+)$/', $request, $matches):
        $controller = new SongController();
        $controller->getSong($matches[1]);
        break;

    // Queue routes
    case preg_match('/^\/api\/queue\/([^\/]+)$/', $request, $matches):
        $controller = new QueueController();
        $controller->getQueue($matches[1]);
        break;
        
    case preg_match('/^\/api\/queue\/([^\/]+)\/add$/', $request, $matches):
        $controller = new QueueController();
        if ($method === 'POST') {
            $controller->addToQueue($matches[1]);
        }
        break;
        
    case preg_match('/^\/api\/queue\/([^\/]+)\/next$/', $request, $matches):
        $controller = new QueueController();
        if ($method === 'POST') {
            $controller->nextSong($matches[1]);
        }
        break;
        
    case preg_match('/^\/api\/queue\/([^\/]+)\/reorder$/', $request, $matches):
        $controller = new QueueController();
        if ($method === 'POST') {
            $controller->reorder($matches[1]);
        }
        break;

    // Scoring routes
    case preg_match('/^\/api\/scoring\/start$/', $request):
        $controller = new ScoringController();
        if ($method === 'POST') {
            $controller->startScoring();
        }
        break;
        
    case preg_match('/^\/api\/scoring\/update$/', $request):
        $controller = new ScoringController();
        if ($method === 'POST') {
            $controller->updateScore();
        }
        break;
        
    case preg_match('/^\/api\/scoring\/end$/', $request):
        $controller = new ScoringController();
        if ($method === 'POST') {
            $controller->endScoring();
        }
        break;
        
    case preg_match('/^\/api\/leaderboard$/', $request):
        $controller = new ScoringController();
        $controller->getLeaderboard();
        break;

    // Admin routes
    case preg_match('/^\/admin$/', $request):
        $controller = new AdminController();
        $controller->dashboard();
        break;
        
    case preg_match('/^\/admin\/songs$/', $request):
        $controller = new AdminController();
        $controller->manageSongs();
        break;
        
    case preg_match('/^\/admin\/songs\/add$/', $request):
        $controller = new AdminController();
        if ($method === 'POST') {
            $controller->addSong();
        }
        break;
        
    case preg_match('/^\/admin\/songs\/edit\/([^\/]+)$/', $request, $matches):
        $controller = new AdminController();
        if ($method === 'POST') {
            $controller->editSong($matches[1]);
        }
        break;
        
    case preg_match('/^\/admin\/songs\/delete\/([^\/]+)$/', $request, $matches):
        $controller = new AdminController();
        if ($method === 'POST') {
            $controller->deleteSong($matches[1]);
        }
        break;
        
    case preg_match('/^\/admin\/youtube\/search$/', $request):
        $controller = new AdminController();
        $controller->searchYouTube();
        break;
        
    case preg_match('/^\/admin\/youtube\/import$/', $request):
        $controller = new AdminController();
        if ($method === 'POST') {
            $controller->importYouTube();
        }
        break;
        
    case preg_match('/^\/admin\/upload$/', $request):
        $controller = new AdminController();
        if ($method === 'POST') {
            $controller->uploadVideo();
        }
        break;
        
    case preg_match('/^\/admin\/categories$/', $request):
        $controller = new AdminController();
        $controller->manageCategories();
        break;
        
    case preg_match('/^\/admin\/playlists$/', $request):
        $controller = new AdminController();
        $controller->managePlaylists();
        break;

    // Assets
    case preg_match('/^\/assets\/.*/', $request):
        // Serve static assets
        $file = __DIR__ . $request;
        if (file_exists($file)) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $mimeTypes = [
                'css' => 'text/css',
                'js' => 'application/javascript',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml',
                'woff' => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf' => 'font/ttf',
                'eot' => 'application/vnd.ms-fontobject'
            ];
            if (isset($mimeTypes[$ext])) {
                header("Content-Type: {$mimeTypes[$ext]}");
            }
            readfile($file);
        } else {
            http_response_code(404);
            echo 'Asset not found';
        }
        break;

    default:
        http_response_code(404);
        echo 'Page not found';
        break;
}
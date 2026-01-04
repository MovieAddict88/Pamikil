<?php
/**
 * Web Routes Definition
 */

if (!isset($router)) {
    throw new Exception("Router must be initialized before loading routes");
}

// Public routes
$router->get('/', function() {
    if (AuthController::isLoggedIn()) {
        redirect('dashboard');
    } else {
        redirect('login');
    }
});

$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@register');
$router->post('/register', 'AuthController@register');
$router->get('/logout', 'AuthController@logout');

// Protected routes (require authentication)
$router->get('/dashboard', 'DashboardController@index');
$router->post('/dashboard/create-room', 'DashboardController@createRoom');
$router->get('/dashboard/create-room', 'DashboardController@createRoom');
$router->get('/dashboard/rooms', 'DashboardController@myRooms');
$router->get('/room/{roomCode}', 'DashboardController@room');
$router->post('/room/{roomCode}/join', 'DashboardController@joinRoom');

// API routes for real-time features
$router->get('/api/room/{roomCode}/queue', 'DashboardController@apiQueue');
$router->post('/api/room/{roomCode}/queue', 'DashboardController@apiQueue');
$router->delete('/api/room/{roomCode}/queue', 'DashboardController@apiQueue');

// Songs API routes
$router->get('/api/songs/search', 'SongsController@search');
$router->get('/api/songs/popular', 'SongsController@popular');
$router->get('/api/songs/recent', 'SongsController@recent');
$router->get('/api/songs/random', 'SongsController@random');
$router->get('/api/songs/by-number', 'SongsController@byNumber');
$router->get('/api/songs/categories', 'SongsController@categories');
$router->get('/api/songs/category/{categoryId}', 'SongsController@byCategory');

// Admin routes
$router->get('/admin', 'AdminController@dashboard');
$router->get('/admin/dashboard', 'AdminController@dashboard');
$router->get('/admin/songs', 'AdminController@songs');
$router->post('/admin/songs', 'AdminController@songs');
$router->get('/admin/songs/new', 'AdminController@songForm');
$router->get('/admin/songs/{id}', 'AdminController@songForm');
$router->post('/admin/songs/{id}', 'AdminController@songForm');
$router->post('/admin/songs/{id}/delete', 'AdminController@songs');
$router->post('/admin/songs/{id}/toggle', 'AdminController@songs');

// YouTube integration routes
$router->get('/admin/youtube/search', 'AdminController@youtubeSearch');
$router->post('/admin/youtube/import', 'AdminController@youtubeImport');

// Media upload routes
$router->get('/admin/upload', 'AdminController@uploadMedia');
$router->post('/admin/upload', 'AdminController@uploadMedia');

// Catch-all route for 404
$router->get('/{path}', function($path) {
    http_response_code(404);
    include __DIR__ . '/Views/errors/404.php';
});
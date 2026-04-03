<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Play2TV API Routes
 *
 * API Structure:
 *   /api/*       → JWT protected (except register/login/logout)
 *   /admin/*     → Session protected (except login)
 *
 * Android Retrofit base URL: https://api.play2tv.nl/
 *
 * @var RouteCollection $routes
 */

// ─────────────────────────────────────────────────────────────────────────────
// API ROUTES
// ─────────────────────────────────────────────────────────────────────────────

// Public (no JWT required)
$routes->post('api/register', 'Api\AuthController::register');
$routes->post('api/login',    'Api\AuthController::login');
$routes->post('api/refresh',  'Api\AuthController::refresh');
$routes->post('api/logout',   'Api\AuthController::logout');

// Protected (JWT required — JwtFilter applied in Filters.php)
$routes->group('api', ['filter' => 'jwt'], function ($routes) {
    // Auth
    $routes->get('user', 'Api\AuthController::user');

    // Category Preferences
    $routes->get('category-prefs',  'Api\CategoryPrefsController::index');
    $routes->post('category-prefs', 'Api\CategoryPrefsController::save');

    // Settings
    $routes->post('settings', 'Api\SettingsController::save');
    $routes->get('settings',  'Api\SettingsController::get');

    // Watch History
    $routes->post('history', 'Api\HistoryController::save');
    $routes->get('history',  'Api\HistoryController::index');

    // Store Points
    $routes->post('store-points', 'Api\StorePointsController::add');
    $routes->get('store-points',  'Api\StorePointsController::index');

    // Playlist (premium only — checked inside controller)
    $routes->get('playlist', 'Api\PlaylistController::index');
});

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN ROUTES
// ─────────────────────────────────────────────────────────────────────────────

// Public admin routes (no session required)
$routes->get('admin',         'Admin\AdminAuthController::loginForm');
$routes->get('admin/login',   'Admin\AdminAuthController::loginForm');
$routes->post('admin/login',  'Admin\AdminAuthController::loginProcess');
$routes->get('admin/logout',  'Admin\AdminAuthController::logout');

// Protected admin routes (session required — AdminAuthFilter applied)
$routes->group('admin', ['filter' => 'adminauth'], function ($routes) {
    // Dashboard
    $routes->get('dashboard', 'Admin\DashboardController::index');

    // Users
    $routes->get('users',                'Admin\UserController::index');
    $routes->get('users/create',         'Admin\UserController::create');
    $routes->post('users/create',        'Admin\UserController::store');
    $routes->get('users/(:num)',         'Admin\UserController::view/$1');
    $routes->get('users/(:num)/edit',    'Admin\UserController::edit/$1');
    $routes->post('users/(:num)/edit',   'Admin\UserController::update/$1');
    $routes->get('users/(:num)/delete',  'Admin\UserController::delete/$1');
    $routes->post('users/(:num)/points', 'Admin\UserController::addPoints/$1');

    // Playlists
    $routes->get('playlists',                    'Admin\PlaylistController::index');
    $routes->get('playlists/add',                'Admin\PlaylistController::addForm');
    $routes->post('playlists/add',               'Admin\PlaylistController::add');
    $routes->get('playlists/(:num)/edit',        'Admin\PlaylistController::editForm/$1');
    $routes->post('playlists/(:num)/edit',       'Admin\PlaylistController::edit/$1');
    $routes->get('playlists/(:num)/activate',    'Admin\PlaylistController::activate/$1');
    $routes->get('playlists/(:num)/delete',      'Admin\PlaylistController::delete/$1');
});

// ─────────────────────────────────────────────────────────────────────────────
// DEFAULT (redirect /admin to login)
// ─────────────────────────────────────────────────────────────────────────────
$routes->get('/', function () {
    return redirect()->to(base_url('admin/login'));
});

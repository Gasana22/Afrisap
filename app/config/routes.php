<?php

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\FarmController;
use App\Controllers\BlockController;
use App\Controllers\PlotController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\RoleController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\AuditLogController;

/** @var \App\Core\Router $router */

// Auth
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/mfa', [AuthController::class, 'showMfa']);
$router->post('/mfa', [AuthController::class, 'verifyMfa']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'sendResetLink']);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

// Dashboard
$router->get('/', [DashboardController::class, 'index']);
$router->get('/profile', [DashboardController::class, 'profile']);
$router->post('/profile', [DashboardController::class, 'updateProfile']);

// Farm structure
$router->get('/farms', [FarmController::class, 'index'], 'farms.view');
$router->get('/farms/create', [FarmController::class, 'create'], 'farms.create');
$router->post('/farms', [FarmController::class, 'store'], 'farms.create');
$router->get('/farms/{id}', [FarmController::class, 'show'], 'farms.view');
$router->get('/farms/{id}/edit', [FarmController::class, 'edit'], 'farms.edit');
$router->post('/farms/{id}', [FarmController::class, 'update'], 'farms.edit');
$router->post('/farms/{id}/delete', [FarmController::class, 'destroy'], 'farms.delete');

$router->post('/farms/{farmId}/blocks', [BlockController::class, 'store'], 'farms.edit');
$router->post('/blocks/{id}/delete', [BlockController::class, 'destroy'], 'farms.edit');

$router->post('/blocks/{blockId}/plots', [PlotController::class, 'store'], 'farms.edit');
$router->post('/plots/{id}/delete', [PlotController::class, 'destroy'], 'farms.edit');

// Admin panel
$router->get('/admin/users', [UserController::class, 'index'], 'users.view');
$router->get('/admin/users/create', [UserController::class, 'create'], 'users.create');
$router->post('/admin/users', [UserController::class, 'store'], 'users.create');
$router->get('/admin/users/{id}/edit', [UserController::class, 'edit'], 'users.edit');
$router->post('/admin/users/{id}', [UserController::class, 'update'], 'users.edit');
$router->post('/admin/users/{id}/delete', [UserController::class, 'destroy'], 'users.delete');

$router->get('/admin/roles', [RoleController::class, 'index'], 'roles.view');
$router->get('/admin/roles/{id}/edit', [RoleController::class, 'edit'], 'roles.edit');
$router->post('/admin/roles/{id}', [RoleController::class, 'update'], 'roles.edit');

$router->get('/admin/settings', [SettingsController::class, 'index'], 'settings.view');
$router->post('/admin/settings', [SettingsController::class, 'update'], 'settings.edit');

$router->get('/admin/audit-logs', [AuditLogController::class, 'index'], 'audit.view');

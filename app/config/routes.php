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
use App\Controllers\CropSetupController;
use App\Controllers\CropCycleController;
use App\Controllers\AnimalController;
use App\Controllers\WorkerController;
use App\Controllers\FinanceController;
use App\Controllers\SupplierController;
use App\Controllers\PurchaseOrderController;
use App\Controllers\InventoryController;

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

// Crop setup (types & seasons)
$router->get('/crops/setup', [CropSetupController::class, 'index'], 'crops.edit');
$router->post('/crops/setup/types', [CropSetupController::class, 'storeType'], 'crops.edit');
$router->post('/crop-types/{id}/delete', [CropSetupController::class, 'destroyType'], 'crops.edit');
$router->post('/crops/setup/seasons', [CropSetupController::class, 'storeSeason'], 'crops.edit');
$router->post('/seasons/{id}/delete', [CropSetupController::class, 'destroySeason'], 'crops.edit');

// Crop lifecycle
$router->get('/crops', [CropCycleController::class, 'index'], 'crops.view');
$router->get('/crops/create', [CropCycleController::class, 'create'], 'crops.create');
$router->post('/crops', [CropCycleController::class, 'store'], 'crops.create');
$router->get('/crops/{id}', [CropCycleController::class, 'show'], 'crops.view');
$router->get('/crops/{id}/edit', [CropCycleController::class, 'edit'], 'crops.edit');
$router->post('/crops/{id}', [CropCycleController::class, 'update'], 'crops.edit');
$router->post('/crops/{id}/delete', [CropCycleController::class, 'destroy'], 'crops.delete');

$router->post('/crops/{id}/inputs', [CropCycleController::class, 'addInput'], 'crops.edit');
$router->post('/crop-inputs/{id}/delete', [CropCycleController::class, 'deleteInput'], 'crops.edit');

$router->post('/crops/{id}/nursery', [CropCycleController::class, 'addNursery'], 'crops.edit');
$router->post('/nursery/{id}/delete', [CropCycleController::class, 'deleteNursery'], 'crops.edit');

$router->post('/crops/{id}/activities', [CropCycleController::class, 'addActivity'], 'crops.edit');
$router->post('/activities/{id}/status', [CropCycleController::class, 'updateActivityStatus'], 'crops.edit');
$router->post('/activities/{id}/delete', [CropCycleController::class, 'deleteActivity'], 'crops.edit');

$router->post('/crops/{id}/monitoring', [CropCycleController::class, 'addMonitoring'], 'crops.edit');
$router->post('/monitoring/{id}/delete', [CropCycleController::class, 'deleteMonitoring'], 'crops.edit');

$router->post('/crops/{id}/yield-forecast', [CropCycleController::class, 'addForecast'], 'crops.edit');
$router->post('/yield-forecasts/{id}/delete', [CropCycleController::class, 'deleteForecast'], 'crops.edit');

$router->post('/crops/{id}/harvest', [CropCycleController::class, 'addHarvest'], 'crops.edit');
$router->post('/harvests/{id}/delete', [CropCycleController::class, 'deleteHarvest'], 'crops.edit');

$router->post('/harvests/{harvestId}/sales', [CropCycleController::class, 'addSale'], 'crops.edit');
$router->post('/crop-sales/{id}/delete', [CropCycleController::class, 'deleteSale'], 'crops.edit');

// Livestock
$router->get('/livestock', [AnimalController::class, 'index'], 'livestock.view');
$router->get('/livestock/create', [AnimalController::class, 'create'], 'livestock.create');
$router->post('/livestock', [AnimalController::class, 'store'], 'livestock.create');
$router->get('/livestock/{id}', [AnimalController::class, 'show'], 'livestock.view');
$router->get('/livestock/{id}/edit', [AnimalController::class, 'edit'], 'livestock.edit');
$router->post('/livestock/{id}', [AnimalController::class, 'update'], 'livestock.edit');
$router->post('/livestock/{id}/delete', [AnimalController::class, 'destroy'], 'livestock.delete');

$router->post('/livestock/{id}/vaccinations', [AnimalController::class, 'addVaccination'], 'livestock.edit');
$router->post('/livestock/{id}/feedings', [AnimalController::class, 'addFeeding'], 'livestock.edit');
$router->post('/livestock/{id}/weights', [AnimalController::class, 'addWeight'], 'livestock.edit');
$router->post('/livestock/{id}/treatments', [AnimalController::class, 'addTreatment'], 'livestock.edit');
$router->post('/livestock/{id}/breeding', [AnimalController::class, 'addBreeding'], 'livestock.edit');
$router->post('/livestock/{id}/production', [AnimalController::class, 'addProduction'], 'livestock.edit');
$router->post('/livestock/{id}/mortality', [AnimalController::class, 'recordMortality'], 'livestock.edit');
$router->post('/livestock/{id}/sale', [AnimalController::class, 'recordSale'], 'livestock.edit');

// Workers
$router->get('/workers', [WorkerController::class, 'index'], 'workers.view');
$router->get('/workers/create', [WorkerController::class, 'create'], 'workers.create');
$router->post('/workers', [WorkerController::class, 'store'], 'workers.create');
$router->get('/workers/{id}', [WorkerController::class, 'show'], 'workers.view');
$router->get('/workers/{id}/edit', [WorkerController::class, 'edit'], 'workers.edit');
$router->post('/workers/{id}', [WorkerController::class, 'update'], 'workers.edit');
$router->post('/workers/{id}/toggle-status', [WorkerController::class, 'toggleStatus'], 'workers.delete');

$router->post('/workers/{id}/attendance', [WorkerController::class, 'addAttendance'], 'workers.edit');
$router->post('/attendance/{id}/approve', [WorkerController::class, 'approveAttendance'], 'workers.edit');

$router->post('/workers/{id}/tasks', [WorkerController::class, 'addTask'], 'workers.edit');
$router->post('/tasks/{id}/status', [WorkerController::class, 'updateTaskStatus'], 'workers.edit');
$router->post('/tasks/{id}/verify', [WorkerController::class, 'verifyTask'], 'workers.edit');

$router->post('/workers/{id}/payroll', [WorkerController::class, 'addPayroll'], 'workers.edit');
$router->post('/payroll/{id}/status', [WorkerController::class, 'updatePayrollStatus'], 'workers.edit');

// Finance
$router->get('/finance', [FinanceController::class, 'report'], 'finance.view');
$router->get('/finance/income', [FinanceController::class, 'incomeIndex'], 'finance.view');
$router->post('/finance/income', [FinanceController::class, 'storeIncome'], 'finance.create');
$router->post('/income/{id}/delete', [FinanceController::class, 'destroyIncome'], 'finance.delete');
$router->get('/finance/expenses', [FinanceController::class, 'expenseIndex'], 'finance.view');
$router->post('/finance/expenses', [FinanceController::class, 'storeExpense'], 'finance.create');
$router->post('/expenses/{id}/delete', [FinanceController::class, 'destroyExpense'], 'finance.delete');

// Procurement
$router->get('/suppliers', [SupplierController::class, 'index'], 'procurement.view');
$router->get('/suppliers/create', [SupplierController::class, 'create'], 'procurement.create');
$router->post('/suppliers', [SupplierController::class, 'store'], 'procurement.create');
$router->get('/suppliers/{id}/edit', [SupplierController::class, 'edit'], 'procurement.edit');
$router->post('/suppliers/{id}', [SupplierController::class, 'update'], 'procurement.edit');
$router->post('/suppliers/{id}/delete', [SupplierController::class, 'destroy'], 'procurement.delete');

$router->get('/purchase-orders', [PurchaseOrderController::class, 'index'], 'procurement.view');
$router->get('/purchase-orders/create', [PurchaseOrderController::class, 'create'], 'procurement.create');
$router->post('/purchase-orders', [PurchaseOrderController::class, 'store'], 'procurement.create');
$router->get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show'], 'procurement.view');
$router->post('/purchase-orders/{id}/status', [PurchaseOrderController::class, 'updateStatus'], 'procurement.edit');
$router->post('/purchase-orders/{id}/delete', [PurchaseOrderController::class, 'destroy'], 'procurement.delete');
$router->post('/purchase-orders/{id}/deliveries', [PurchaseOrderController::class, 'addDelivery'], 'procurement.edit');
$router->post('/purchase-orders/{id}/payments', [PurchaseOrderController::class, 'addPayment'], 'procurement.edit');

// Inventory
$router->get('/inventory', [InventoryController::class, 'index'], 'inventory.view');
$router->get('/inventory/create', [InventoryController::class, 'create'], 'inventory.create');
$router->post('/inventory', [InventoryController::class, 'store'], 'inventory.create');
$router->get('/inventory/{id}', [InventoryController::class, 'show'], 'inventory.view');
$router->get('/inventory/{id}/edit', [InventoryController::class, 'edit'], 'inventory.edit');
$router->post('/inventory/{id}', [InventoryController::class, 'update'], 'inventory.edit');
$router->post('/inventory/{id}/delete', [InventoryController::class, 'destroy'], 'inventory.delete');
$router->post('/inventory/{id}/stock-in', [InventoryController::class, 'stockIn'], 'inventory.edit');
$router->post('/inventory/{id}/stock-out', [InventoryController::class, 'stockOut'], 'inventory.edit');
$router->post('/inventory/{id}/transfer', [InventoryController::class, 'transfer'], 'inventory.edit');

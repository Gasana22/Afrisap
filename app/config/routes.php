<?php

// Public app -- the farm-tenant product (login-gated per organization, plus
// the no-login QR trace page). See app/controllers/Public/.
use App\Controllers\Public\AuthController;
use App\Controllers\Public\RegistrationController;
use App\Controllers\Public\DashboardController;
use App\Controllers\Public\FarmController;
use App\Controllers\Public\BlockController;
use App\Controllers\Public\PlotController;
use App\Controllers\Public\TeamController;
use App\Controllers\Public\CropSetupController;
use App\Controllers\Public\CropCycleController;
use App\Controllers\Public\AnimalController;
use App\Controllers\Public\WorkerController;
use App\Controllers\Public\FinanceController;
use App\Controllers\Public\SupplierController;
use App\Controllers\Public\PurchaseOrderController;
use App\Controllers\Public\InventoryController;
use App\Controllers\Public\AssetController;
use App\Controllers\Public\TraceabilityController;
use App\Controllers\Public\PublicTraceController;
use App\Controllers\Public\ReportsController;
use App\Controllers\Public\MapController;
use App\Controllers\Public\NotificationController;
use App\Controllers\Public\MediaController;
use App\Controllers\Public\AlertsController;
use App\Controllers\Public\ComplianceController;

// Admin app -- Afrisap's own staff-only platform portal (still served at
// /platform/* URLs). See app/controllers/Admin/.
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\OrganizationController;
use App\Controllers\Admin\UserController as AdminUserController;
use App\Controllers\Admin\RoleController as AdminRoleController;
use App\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Controllers\Admin\AuditLogController as AdminAuditLogController;

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
$router->get('/signup', [RegistrationController::class, 'showSignup']);
$router->post('/signup', [RegistrationController::class, 'signup']);

// Dashboard
$router->get('/', [DashboardController::class, 'index']);
$router->get('/profile', [DashboardController::class, 'profile']);
$router->post('/profile', [DashboardController::class, 'updateProfile']);

// Team (tenant-side: Farm Owner manages their own organization's staff)
$router->get('/team', [TeamController::class, 'index'], 'team.view');
$router->get('/team/create', [TeamController::class, 'create'], 'team.create');
$router->post('/team', [TeamController::class, 'store'], 'team.create');
$router->get('/team/{id}/edit', [TeamController::class, 'edit'], 'team.edit');
$router->post('/team/{id}', [TeamController::class, 'update'], 'team.edit');
$router->post('/team/{id}/delete', [TeamController::class, 'destroy'], 'team.delete');

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

// Platform admin portal -- separate login, separate UI shell, only ever
// reachable by platform-scope roles (Super Admin, Platform Manager,
// Platform Accountant). Supersedes the old /admin/* namespace.
$router->get('/platform/login', [AdminAuthController::class, 'showLogin']);
$router->post('/platform/login', [AdminAuthController::class, 'login']);
$router->get('/platform/mfa', [AdminAuthController::class, 'showMfa']);
$router->post('/platform/mfa', [AdminAuthController::class, 'verifyMfa']);
$router->post('/platform/logout', [AdminAuthController::class, 'logout']);

$router->get('/platform', [AdminDashboardController::class, 'index']);

$router->get('/platform/organizations', [OrganizationController::class, 'index'], 'organizations.view');
$router->get('/platform/organizations/{id}', [OrganizationController::class, 'show'], 'organizations.view');
$router->post('/platform/organizations/{id}/status', [OrganizationController::class, 'toggleStatus'], 'organizations.edit');

$router->get('/platform/users', [AdminUserController::class, 'index'], 'users.view');
$router->get('/platform/users/create', [AdminUserController::class, 'create'], 'users.create');
$router->post('/platform/users', [AdminUserController::class, 'store'], 'users.create');
$router->get('/platform/users/{id}/edit', [AdminUserController::class, 'edit'], 'users.edit');
$router->post('/platform/users/{id}', [AdminUserController::class, 'update'], 'users.edit');
$router->post('/platform/users/{id}/delete', [AdminUserController::class, 'destroy'], 'users.delete');

$router->get('/platform/roles', [AdminRoleController::class, 'index'], 'roles.view');
$router->get('/platform/roles/{id}/edit', [AdminRoleController::class, 'edit'], 'roles.edit');
$router->post('/platform/roles/{id}', [AdminRoleController::class, 'update'], 'roles.edit');

$router->get('/platform/settings', [AdminSettingsController::class, 'index'], 'settings.view');
$router->post('/platform/settings', [AdminSettingsController::class, 'update'], 'settings.edit');

$router->get('/platform/audit-logs', [AdminAuditLogController::class, 'index'], 'audit.view');

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

// Assets
$router->get('/farm-assets', [AssetController::class, 'index'], 'assets.view');
$router->get('/farm-assets/create', [AssetController::class, 'create'], 'assets.create');
$router->post('/farm-assets', [AssetController::class, 'store'], 'assets.create');
$router->get('/farm-assets/{id}', [AssetController::class, 'show'], 'assets.view');
$router->get('/farm-assets/{id}/edit', [AssetController::class, 'edit'], 'assets.edit');
$router->post('/farm-assets/{id}', [AssetController::class, 'update'], 'assets.edit');
$router->post('/farm-assets/{id}/delete', [AssetController::class, 'destroy'], 'assets.delete');
$router->post('/farm-assets/{id}/maintenance', [AssetController::class, 'addMaintenance'], 'assets.edit');

// Traceability
$router->get('/traceability', [TraceabilityController::class, 'index'], 'traceability.view');
$router->get('/traceability/{id}', [TraceabilityController::class, 'show'], 'traceability.view');
$router->post('/traceability/{id}/status', [TraceabilityController::class, 'updateStatus'], 'traceability.edit');
$router->post('/traceability/{id}/qr', [TraceabilityController::class, 'generateQr'], 'traceability.edit');
$router->post('/traceability/{id}/documents', [TraceabilityController::class, 'addDocument'], 'traceability.edit');
$router->post('/traceability/{id}/approvals', [TraceabilityController::class, 'addApproval'], 'traceability.edit');
$router->post('/approvals/{id}/status', [TraceabilityController::class, 'updateApprovalStatus'], 'traceability.edit');
$router->post('/traceability/{id}/journey', [TraceabilityController::class, 'addJourneyStage'], 'traceability.edit');

$router->get('/crops/{id}/traceability', [TraceabilityController::class, 'forCropCycle'], 'crops.view');
$router->get('/livestock/{id}/traceability', [TraceabilityController::class, 'forAnimal'], 'livestock.view');

// Public QR scan page (no login required)
$router->get('/trace/{token}', [PublicTraceController::class, 'show']);

// Reports
$router->get('/reports', [ReportsController::class, 'index'], 'reports.view');
$router->get('/reports/crop-yield', [ReportsController::class, 'cropYield'], 'reports.view');
$router->get('/reports/livestock-production', [ReportsController::class, 'livestockProduction'], 'reports.view');
$router->get('/reports/worker-productivity', [ReportsController::class, 'workerProductivity'], 'reports.view');
$router->get('/reports/daily-activities', [ReportsController::class, 'dailyActivities'], 'reports.view');

// Maps
$router->get('/maps', [MapController::class, 'index'], 'farms.view');

// Notifications
$router->get('/notifications', [NotificationController::class, 'index']);
$router->post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
$router->post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

// Media & documents
$router->get('/media', [MediaController::class, 'index'], 'media.view');
$router->get('/media/create', [MediaController::class, 'create'], 'media.create');
$router->post('/media', [MediaController::class, 'store'], 'media.create');
$router->post('/media/{id}/delete', [MediaController::class, 'destroy'], 'media.delete');

// Alerts
$router->get('/alerts', [AlertsController::class, 'index'], 'reports.view');

// Compliance reports
$router->get('/traceability/{id}/compliance/{type}', [ComplianceController::class, 'generate'], 'traceability.view');

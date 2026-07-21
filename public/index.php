<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\EmployeeController;
use App\Controllers\AdminController;
use App\Controllers\SettingsController;
use App\Controllers\DebugController;
use App\Controllers\ProviderController;

$router = new Router();

// Auth
$router->get('/login', fn() => AuthController::showLogin());
$router->post('/login', fn() => AuthController::login());
$router->get('/logout', fn() => AuthController::logout());

// Home redirect
$router->get('/', fn() => \redirect('/timecards'));
$router->get('/home', fn() => EmployeeController::home());
$router->get('/timecards', fn() => AdminController::timecards());
// Employee
$router->get('/dashboard', fn() => EmployeeController::dashboard());
$router->get('/timecard', fn() => EmployeeController::timecard());
$router->post('/timecard/save', fn() => EmployeeController::saveTimecard());
$router->post('/timecard/submit', fn() => EmployeeController::submitTimecard());

// Admin
$router->get('/admin', fn() => AdminController::dashboard());
$router->get('/admin/employees', fn() => AdminController::employees());
$router->get('/admin/employees/new', fn($p=[]) => AdminController::employeeEdit(['id'=>0]));
$router->get('/admin/employees/{id}', fn($p) => AdminController::employeeEdit($p));
$router->post('/admin/employees/save', fn() => AdminController::employeeSave());
$router->post('/admin/employees/reset-password', fn() => AdminController::employeeResetPassword());
$router->post('/admin/employees/delete', fn() => AdminController::employeeDelete());

$router->get('/admin/timecards', fn() => AdminController::timecards());
$router->get('/admin/timecards/{id}', fn($p) => AdminController::timecardEdit($p));
$router->post('/admin/timecards/save', fn() => AdminController::timecardSave());
$router->post('/admin/timecards/lock', fn() => AdminController::timecardLock());
$router->post('/admin/timecards/unlock', fn() => AdminController::timecardUnlock());

$router->post('/admin/payperiod/lock', fn() => AdminController::lockGenerateReport());
$router->post('/admin/payperiod/regenerate', fn() => AdminController::regeneratePayPeriodReport());
// Providers (admin-only)
$router->get('/admin/providers', fn() => ProviderController::providers());
$router->get('/admin/providers/new', fn($p=[]) => ProviderController::providerEdit(['id'=>0]));
$router->get('/admin/providers/{id}', fn($p) => ProviderController::providerEdit($p));
$router->post('/admin/providers/save', fn() => ProviderController::providerSave());

$router->get('/admin/appointment-types', fn() => ProviderController::appointmentTypes());
$router->post('/admin/appointment-types/save', fn() => ProviderController::appointmentTypeSave());
$router->post('/admin/appointment-types/toggle', fn() => ProviderController::appointmentTypeToggle());
$router->post('/admin/appointment-types/delete', fn() => ProviderController::appointmentTypeDelete());

$router->get('/admin/providers/{id}/rates', fn($p) => ProviderController::providerRates($p));
$router->post('/admin/providers/{id}/rates/add', fn($p) => ProviderController::providerRateAdd($p));
$router->post('/admin/providers/{id}/rates/end', fn($p) => ProviderController::providerRateEnd($p));
$router->post('/admin/providers/{id}/rates/update', fn($p) => ProviderController::providerRateUpdate($p));
$router->post('/admin/providers/{id}/rates/delete', fn($p) => ProviderController::providerRateDelete($p));

$router->get('/admin/provider-production', fn() => ProviderController::production());
$router->get('/admin/provider-production/{id}', fn($p) => ProviderController::productionEdit($p));
$router->post('/admin/provider-production/save', fn() => ProviderController::productionSave());
$router->post('/admin/provider-production/lock', fn() => ProviderController::productionLock());
$router->post('/admin/provider-production/unlock', fn() => ProviderController::productionUnlock());

// Provider production working routes
$router->get('/provider-production/{id}', fn($p) => ProviderController::productionEdit($p));
$router->post('/provider-production/save', fn() => ProviderController::productionSave());
$router->post('/provider-production/lock', fn() => ProviderController::productionLock());
$router->post('/provider-production/unlock', fn() => ProviderController::productionUnlock());


// Settings
$router->get('/admin/settings', fn() => SettingsController::settings());
$router->post('/admin/settings/save', fn() => SettingsController::save());

// Debug (client-side error capture)
$router->post('/debug/client-error', fn() => DebugController::clientError());

// Dispatch
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($base !== '' && $base !== '/' && strpos($uri, $base) === 0) {
  $uri = substr($uri, strlen($base));
  if ($uri === '') $uri = '/';
}
$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);

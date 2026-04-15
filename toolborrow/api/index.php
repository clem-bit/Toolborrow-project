<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/middleware/Auth.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/ToolController.php';
require_once __DIR__ . '/controllers/LoanController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/controllers/MaintenanceController.php';
require_once __DIR__ . '/controllers/ReservationController.php';
require_once __DIR__ . '/controllers/NotificationController.php';


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


$method = $_SERVER['REQUEST_METHOD'];
$uri    = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$parts  = explode('/', $uri);


$strip = ['toolborrow', 'api'];
foreach ($strip as $seg) {
    if (!empty($parts) && $parts[0] === $seg) {
        array_shift($parts);
    }
}

$resource = $parts[0] ?? '';
$id       = (isset($parts[1]) && $parts[1] !== '') ? $parts[1] : null;
$action   = (isset($parts[2]) && $parts[2] !== '') ? $parts[2] : null;

$body = json_decode(file_get_contents('php://input'), true) ?? [];


match ($resource) {
    'auth'          => (new AuthController())->handle($method, $id, $body),
    'categories'    => (new CategoryController())->handle($method, $id, $body),
    'tools'         => (new ToolController())->handle($method, $id, $body),
    'loans'         => (new LoanController())->handle($method, $id, $action, $body),
    'users'         => (new UserController())->handle($method, $id, $body),
    'maintenance'   => (new MaintenanceController())->handle($method, $id, $action, $body),
    'reservations'  => (new ReservationController())->handle($method, $id, $action, $body),
    'notifications' => (new NotificationController())->handle($method, $id, $action, $body),
    default         => Response::error('API endpoint not found', 404),
};
?>

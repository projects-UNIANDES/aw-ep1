<?php

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Guayaquil');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Destino.php';
require_once __DIR__ . '/models/Turista.php';
require_once __DIR__ . '/models/Reserva.php';
require_once __DIR__ . '/controllers/Controller.php';
require_once __DIR__ . '/controllers/DestinoController.php';
require_once __DIR__ . '/controllers/TuristaController.php';
require_once __DIR__ . '/controllers/ReservaController.php';

const CONTROLADORES = [
    'destinos' => DestinoController::class,
    'turistas' => TuristaController::class,
    'reservas' => ReservaController::class,
];


const ACCIONES = [
    'todos' => 'GET',
    'uno' => 'GET',
    'guardar' => 'POST',
    'actualizar' => 'POST',
    'eliminar' => 'POST',
];

$modulo = $_GET['modulo'] ?? '';
$accion = $_GET['accion'] ?? 'todos';

if (!is_string($modulo) || !isset(CONTROLADORES[$modulo])) {
    Controller::responder(404, ['error' => 'Módulo no encontrado.']);
    exit;
}

if (!is_string($accion) || !isset(ACCIONES[$accion])) {
    Controller::responder(404, ['error' => 'Acción no encontrada.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== ACCIONES[$accion]) {
    Controller::responder(405, ['error' => 'El método no es permitido.']);
    exit;
}

try {
    $conexion = (new Database())->conectar();
    $clase = CONTROLADORES[$modulo];
    $controlador = new $clase($conexion);

    match ($accion) {
        'todos' => $controlador->todos(),
        'uno' => $controlador->uno(),
        'guardar' => $controlador->guardar(),
        'actualizar' => $controlador->actualizar(),
        'eliminar' => $controlador->eliminar(),
    };
} catch (mysqli_sql_exception $error) {
    error_log($error->getMessage());
    $mensaje = $error->getCode() === 1146
        ? 'Las tablas no existen. Ejecuta el script database.sql.'
        : 'No se pudo completar la operación en la base de datos.';
    Controller::responder(500, ['error' => $mensaje]);
} catch (RuntimeException $error) {
    error_log($error->getMessage());
    Controller::responder(500, ['error' => $error->getMessage()]);
}

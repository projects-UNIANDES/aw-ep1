<?php

class ReservaController extends Controller
{
    private const NO_EXISTE = 'La reserva solicitada no existe.';
    private const MAXIMO_PERSONAS = 50;

    private Reserva $reservas;
    private Turista $turistas;
    private Destino $destinos;

    public function __construct(mysqli $conexion)
    {
        $this->reservas = new Reserva($conexion);
        $this->turistas = new Turista($conexion);
        $this->destinos = new Destino($conexion);
    }

    public function todos(): void
    {
        self::responder(200, $this->reservas->todos());
    }

    public function uno(): void
    {
        $id = $this->leerId($_GET);
        $reserva = $id === null ? null : $this->reservas->buscar($id);

        if ($reserva === null) {
            self::responder(404, ['error' => self::NO_EXISTE]);
            return;
        }

        self::responder(200, $reserva);
    }

    public function guardar(): void
    {
        $datos = $this->leerFormulario();
        $errores = $this->validar($datos);

        if ($errores !== []) {
            $this->responderErrores($errores);
            return;
        }

        $datos['total'] = $this->calcularTotal($datos['destino_id'], $datos['cantidad_personas']);
        $id = $this->reservas->guardar($datos);

        self::responder(201, ['mensaje' => 'Reserva registrada correctamente.', 'id' => $id]);
    }

    public function actualizar(): void
    {
        $id = $this->leerId($_POST);

        if ($id === null || $this->reservas->buscar($id) === null) {
            self::responder(404, ['error' => self::NO_EXISTE]);
            return;
        }

        $datos = $this->leerFormulario();
        $errores = $this->validar($datos);

        if ($errores !== []) {
            $this->responderErrores($errores);
            return;
        }

        $datos['total'] = $this->calcularTotal($datos['destino_id'], $datos['cantidad_personas']);
        $this->reservas->actualizar($id, $datos);

        self::responder(200, ['mensaje' => 'Reserva actualizada correctamente.']);
    }

    public function eliminar(): void
    {
        $id = $this->leerId($_POST);

        if ($id === null || !$this->reservas->eliminar($id)) {
            self::responder(404, ['error' => self::NO_EXISTE]);
            return;
        }

        self::responder(200, ['mensaje' => 'Reserva eliminada correctamente.']);
    }

    private function leerFormulario(): array
    {
        return [
            'turista_id' => $this->leerId($_POST, 'turista_id'),
            'destino_id' => $this->leerId($_POST, 'destino_id'),
            'fecha_viaje' => $this->leerFechaViaje(),
            'cantidad_personas' => $this->leerEntero('cantidad_personas', 1, self::MAXIMO_PERSONAS),
        ];
    }


    private function leerFechaViaje(): ?string
    {
        $valor = $this->leerTexto('fecha_viaje');
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

        if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
            return null;
        }

        return $valor >= date('Y-m-d') ? $valor : null;
    }

    private function validar(array $datos): array
    {
        $errores = [];

        if ($datos['turista_id'] === null || $this->turistas->buscar($datos['turista_id']) === null) {
            $errores[] = 'Selecciona un turista válido.';
        }

        if ($datos['destino_id'] === null || $this->destinos->buscar($datos['destino_id']) === null) {
            $errores[] = 'Selecciona un destino válido.';
        }

        if ($datos['fecha_viaje'] === null) {
            $errores[] = 'La fecha de viaje debe ser válida y no puede ser anterior a hoy.';
        }

        if ($datos['cantidad_personas'] === null) {
            $errores[] = 'La cantidad de personas debe ser un número entero entre 1 y ' . self::MAXIMO_PERSONAS . '.';
        }

        return $errores;
    }


    private function calcularTotal(int $destinoId, int $personas): float
    {
        $destino = $this->destinos->buscar($destinoId);

        return round((float) $destino['costo'] * $personas, 2);
    }
}

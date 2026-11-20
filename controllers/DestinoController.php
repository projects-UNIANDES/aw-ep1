<?php

class DestinoController extends Controller
{
    private const NO_EXISTE = 'El destino solicitado no existe.';

    private Destino $destinos;

    public function __construct(mysqli $conexion)
    {
        $this->destinos = new Destino($conexion);
    }

    public function todos(): void
    {
        self::responder(200, $this->destinos->todos());
    }

    public function uno(): void
    {
        $id = $this->leerId($_GET);
        $destino = $id === null ? null : $this->destinos->buscar($id);

        if ($destino === null) {
            self::responder(404, ['error' => self::NO_EXISTE]);
            return;
        }

        self::responder(200, $destino);
    }

    public function guardar(): void
    {
        $datos = $this->leerFormulario();
        $errores = $this->validar($datos);

        if ($errores !== []) {
            $this->responderErrores($errores);
            return;
        }

        $id = $this->destinos->guardar($datos);
        self::responder(201, ['mensaje' => 'Destino registrado correctamente.', 'id' => $id]);
    }

    public function actualizar(): void
    {
        $id = $this->leerId($_POST);

        if ($id === null || $this->destinos->buscar($id) === null) {
            self::responder(404, ['error' => self::NO_EXISTE]);
            return;
        }

        $datos = $this->leerFormulario();
        $errores = $this->validar($datos);

        if ($errores !== []) {
            $this->responderErrores($errores);
            return;
        }

        $this->destinos->actualizar($id, $datos);
        self::responder(200, ['mensaje' => 'Destino actualizado correctamente.']);
    }

    public function eliminar(): void
    {
        $id = $this->leerId($_POST);

        try {
            $eliminado = $id !== null && $this->destinos->eliminar($id);
        } catch (mysqli_sql_exception $error) {
            if ($error->getCode() !== self::ERROR_TIENE_DEPENDENCIAS) {
                throw $error;
            }
            self::responder(409, ['error' => 'No se puede eliminar el destino porque tiene reservas registradas.']);
            return;
        }

        if (!$eliminado) {
            self::responder(404, ['error' => self::NO_EXISTE]);
            return;
        }

        self::responder(200, ['mensaje' => 'Destino eliminado correctamente.']);
    }

    private function leerFormulario(): array
    {
        $descripcion = $this->leerTexto('descripcion');
        $costo = filter_var($this->leerTexto('costo'), FILTER_VALIDATE_FLOAT, [
            'options' => ['min_range' => 0, 'max_range' => 99999999.99],
        ]);

        return [
            'nombre' => $this->leerTexto('nombre'),
            'pais' => $this->leerTexto('pais'),
            'descripcion' => $descripcion === '' ? null : $descripcion,
            'costo' => $costo === false ? null : round($costo, 2),
        ];
    }

    private function validar(array $datos): array
    {
        $errores = [];

        if (!$this->textoValido($datos['nombre'], 100)) {
            $errores[] = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
        }

        if (!$this->textoValido($datos['pais'], 80)) {
            $errores[] = 'El país es obligatorio y debe tener máximo 80 caracteres.';
        }

        if ($datos['descripcion'] !== null && $this->longitud($datos['descripcion']) > 1000) {
            $errores[] = 'La descripción debe tener máximo 1000 caracteres.';
        }

        if ($datos['costo'] === null) {
            $errores[] = 'El costo debe ser un número entre 0 y 99999999.99.';
        }

        return $errores;
    }
}

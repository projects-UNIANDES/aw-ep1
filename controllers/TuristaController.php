<?php

class TuristaController extends Controller
{
    private const NO_EXISTE = 'El turista solicitado no existe.';
    private const EMAIL_DUPLICADO = 'Ya existe un turista registrado con ese email.';

    private Turista $turistas;

    public function __construct(mysqli $conexion)
    {
        $this->turistas = new Turista($conexion);
    }

    public function todos(): void
    {
        self::responder(200, $this->turistas->todos());
    }

    public function uno(): void
    {
        $id = $this->leerId($_GET);
        $turista = $id === null ? null : $this->turistas->buscar($id);

        if ($turista === null) {
            self::responder(404, ['error' => self::NO_EXISTE]);
            return;
        }

        self::responder(200, $turista);
    }

    public function guardar(): void
    {
        $datos = $this->leerFormulario();
        $errores = $this->validar($datos);

        if ($errores !== []) {
            $this->responderErrores($errores);
            return;
        }

        try {
            $id = $this->turistas->guardar($datos);
        } catch (mysqli_sql_exception $error) {
            $this->manejarEmailDuplicado($error);
            return;
        }

        self::responder(201, ['mensaje' => 'Turista registrado correctamente.', 'id' => $id]);
    }

    public function actualizar(): void
    {
        $id = $this->leerId($_POST);

        if ($id === null || $this->turistas->buscar($id) === null) {
            self::responder(404, ['error' => self::NO_EXISTE]);
            return;
        }

        $datos = $this->leerFormulario();
        $errores = $this->validar($datos);

        if ($errores !== []) {
            $this->responderErrores($errores);
            return;
        }

        try {
            $this->turistas->actualizar($id, $datos);
        } catch (mysqli_sql_exception $error) {
            $this->manejarEmailDuplicado($error);
            return;
        }

        self::responder(200, ['mensaje' => 'Turista actualizado correctamente.']);
    }

    public function eliminar(): void
    {
        $id = $this->leerId($_POST);

        try {
            $eliminado = $id !== null && $this->turistas->eliminar($id);
        } catch (mysqli_sql_exception $error) {
            if ($error->getCode() !== self::ERROR_TIENE_DEPENDENCIAS) {
                throw $error;
            }
            self::responder(409, ['error' => 'No se puede eliminar el turista porque tiene reservas registradas.']);
            return;
        }

        if (!$eliminado) {
            self::responder(404, ['error' => self::NO_EXISTE]);
            return;
        }

        self::responder(200, ['mensaje' => 'Turista eliminado correctamente.']);
    }

    private function manejarEmailDuplicado(mysqli_sql_exception $error): void
    {
        if ($error->getCode() !== self::ERROR_DUPLICADO) {
            throw $error;
        }

        self::responder(409, ['error' => self::EMAIL_DUPLICADO]);
    }

    private function leerFormulario(): array
    {
        return [
            'nombre' => $this->leerTexto('nombre'),
            'apellido' => $this->leerTexto('apellido'),
            'email' => $this->leerTexto('email'),
            'telefono' => $this->leerTexto('telefono'),
        ];
    }

    private function validar(array $datos): array
    {
        $errores = [];

        if (!$this->textoValido($datos['nombre'], 80)) {
            $errores[] = 'El nombre es obligatorio y debe tener máximo 80 caracteres.';
        }

        if (!$this->textoValido($datos['apellido'], 80)) {
            $errores[] = 'El apellido es obligatorio y debe tener máximo 80 caracteres.';
        }

        if (strlen($datos['email']) > 150 || filter_var($datos['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errores[] = 'El email no es válido.';
        }

        if (preg_match('/^\+?\d{7,15}$/', $datos['telefono']) !== 1) {
            $errores[] = 'El teléfono debe tener entre 7 y 15 dígitos (puede iniciar con +).';
        }

        return $errores;
    }
}

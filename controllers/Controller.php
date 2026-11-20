<?php

abstract class Controller
{
    protected const ERROR_DUPLICADO = 1062;
    protected const ERROR_TIENE_DEPENDENCIAS = 1451;

    abstract public function todos(): void;

    abstract public function uno(): void;

    abstract public function guardar(): void;

    abstract public function actualizar(): void;

    abstract public function eliminar(): void;

    public static function responder(int $codigo, array $datos): void
    {
        http_response_code($codigo);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    }

    protected function responderErrores(array $errores): void
    {
        self::responder(422, ['error' => 'Revisa los datos ingresados.', 'errores' => $errores]);
    }

    protected function leerId(array $origen, string $campo = 'id'): ?int
    {
        $id = filter_var($origen[$campo] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    protected function leerEntero(string $campo, int $minimo, int $maximo): ?int
    {
        $valor = filter_var($this->leerTexto($campo), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => $minimo, 'max_range' => $maximo],
        ]);

        return $valor === false ? null : $valor;
    }

    protected function leerTexto(string $campo): string
    {
        $valor = $_POST[$campo] ?? '';

        return is_string($valor) ? trim($valor) : '';
    }

    protected function textoValido(string $valor, int $maximo): bool
    {
        return $valor !== '' && $this->longitud($valor) <= $maximo;
    }

    protected function longitud(string $valor): int
    {
        $caracteres = preg_match_all('/./su', $valor);

        return $caracteres === false ? PHP_INT_MAX : $caracteres;
    }
}

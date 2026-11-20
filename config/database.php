<?php

class Database
{
    private const HOST = 'localhost';
    private const USUARIO = 'root';
    private const CLAVE = 'root';
    private const BASE_DATOS = 'gestion_viajes';
    private const TABLAS = ['destinos', 'turistas', 'reservas'];
    private const SCRIPT = __DIR__ . '/../database.sql';
    private const BLOQUEO = 'gestion_viajes_instalacion';

    public function conectar(): mysqli
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {

            $conexion = new mysqli(self::HOST, self::USUARIO, self::CLAVE);
        } catch (mysqli_sql_exception $error) {
            throw new RuntimeException('No se pudo conectar con la base de datos.', 0, $error);
        }

        $conexion->set_charset('utf8mb4');
        $this->instalarSiFalta($conexion);
        $conexion->select_db(self::BASE_DATOS);

        return $conexion;
    }


    private function instalarSiFalta(mysqli $conexion): void
    {
        if ($this->tablasCreadas($conexion)) {
            return;
        }

        $conexion->query("SELECT GET_LOCK('" . self::BLOQUEO . "', 10)");

        try {

            if (!$this->tablasCreadas($conexion)) {
                $this->ejecutarScript($conexion);
            }
        } finally {
            $conexion->query("SELECT RELEASE_LOCK('" . self::BLOQUEO . "')");
        }
    }

    private function tablasCreadas(mysqli $conexion): bool
    {
        $stmt = $conexion->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME IN (?, ?, ?)'
        );
        $base = self::BASE_DATOS;
        [$destinos, $turistas, $reservas] = self::TABLAS;
        $stmt->bind_param('ssss', $base, $destinos, $turistas, $reservas);
        $stmt->execute();
        $total = (int) $stmt->get_result()->fetch_row()[0];
        $stmt->close();

        return $total === count(self::TABLAS);
    }

    private function ejecutarScript(mysqli $conexion): void
    {
        $script = file_get_contents(self::SCRIPT);

        if ($script === false) {
            throw new RuntimeException('No se encontró el archivo database.sql.');
        }


        $conexion->multi_query($script);
        do {
            $resultado = $conexion->store_result();
            if ($resultado instanceof mysqli_result) {
                $resultado->free();
            }
        } while ($conexion->more_results() && $conexion->next_result());
    }
}

<?php

class Turista
{
    private const COLUMNAS = 'turista_id, nombre, apellido, email, telefono';

    public function __construct(private mysqli $conexion) {}

    public function todos(): array
    {
        $stmt = $this->conexion->prepare('SELECT ' . self::COLUMNAS . ' FROM turistas ORDER BY apellido, nombre');
        $stmt->execute();
        $turistas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $turistas;
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT ' . self::COLUMNAS . ' FROM turistas WHERE turista_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $turista = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $turista ?: null;
    }

    public function guardar(array $datos): int
    {
        $stmt = $this->conexion->prepare(
            'INSERT INTO turistas (nombre, apellido, email, telefono) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $datos['nombre'], $datos['apellido'], $datos['email'], $datos['telefono']);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        return $id;
    }

    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->conexion->prepare(
            'UPDATE turistas SET nombre = ?, apellido = ?, email = ?, telefono = ? WHERE turista_id = ?'
        );
        $stmt->bind_param('ssssi', $datos['nombre'], $datos['apellido'], $datos['email'], $datos['telefono'], $id);
        $stmt->execute();
        $stmt->close();
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->conexion->prepare('DELETE FROM turistas WHERE turista_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $eliminado = $stmt->affected_rows > 0;
        $stmt->close();

        return $eliminado;
    }
}

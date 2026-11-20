<?php

class Destino
{
    private const COLUMNAS = 'destino_id, nombre, pais, descripcion, costo';

    public function __construct(private mysqli $conexion) {}

    public function todos(): array
    {
        $stmt = $this->conexion->prepare('SELECT ' . self::COLUMNAS . ' FROM destinos ORDER BY nombre');
        $stmt->execute();
        $destinos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $destinos;
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare('SELECT ' . self::COLUMNAS . ' FROM destinos WHERE destino_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $destino = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $destino ?: null;
    }

    public function guardar(array $datos): int
    {
        $stmt = $this->conexion->prepare(
            'INSERT INTO destinos (nombre, pais, descripcion, costo) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('sssd', $datos['nombre'], $datos['pais'], $datos['descripcion'], $datos['costo']);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        return $id;
    }

    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->conexion->prepare(
            'UPDATE destinos SET nombre = ?, pais = ?, descripcion = ?, costo = ? WHERE destino_id = ?'
        );
        $stmt->bind_param('sssdi', $datos['nombre'], $datos['pais'], $datos['descripcion'], $datos['costo'], $id);
        $stmt->execute();
        $stmt->close();
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->conexion->prepare('DELETE FROM destinos WHERE destino_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $eliminado = $stmt->affected_rows > 0;
        $stmt->close();

        return $eliminado;
    }
}

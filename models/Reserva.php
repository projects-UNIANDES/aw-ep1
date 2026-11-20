<?php

class Reserva
{
    public function __construct(private mysqli $conexion) {}

    public function todos(): array
    {
        $stmt = $this->conexion->prepare(
            "SELECT r.reserva_id, r.turista_id, r.destino_id, r.fecha_viaje, r.cantidad_personas, r.total,
                    CONCAT(t.nombre, ' ', t.apellido) AS turista, t.email,
                    d.nombre AS destino, d.pais
             FROM reservas r
             INNER JOIN turistas t ON t.turista_id = r.turista_id
             INNER JOIN destinos d ON d.destino_id = r.destino_id
             ORDER BY r.fecha_viaje, r.reserva_id"
        );
        $stmt->execute();
        $reservas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $reservas;
    }

    public function buscar(int $id): ?array
    {
        $stmt = $this->conexion->prepare(
            'SELECT reserva_id, turista_id, destino_id, fecha_viaje, cantidad_personas, total
             FROM reservas WHERE reserva_id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $reserva = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $reserva ?: null;
    }

    public function guardar(array $datos): int
    {
        $stmt = $this->conexion->prepare(
            'INSERT INTO reservas (turista_id, destino_id, fecha_viaje, cantidad_personas, total) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'iisid',
            $datos['turista_id'],
            $datos['destino_id'],
            $datos['fecha_viaje'],
            $datos['cantidad_personas'],
            $datos['total']
        );
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        return $id;
    }

    public function actualizar(int $id, array $datos): void
    {
        $stmt = $this->conexion->prepare(
            'UPDATE reservas
             SET turista_id = ?, destino_id = ?, fecha_viaje = ?, cantidad_personas = ?, total = ?
             WHERE reserva_id = ?'
        );
        $stmt->bind_param(
            'iisidi',
            $datos['turista_id'],
            $datos['destino_id'],
            $datos['fecha_viaje'],
            $datos['cantidad_personas'],
            $datos['total'],
            $id
        );
        $stmt->execute();
        $stmt->close();
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->conexion->prepare('DELETE FROM reservas WHERE reserva_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $eliminado = $stmt->affected_rows > 0;
        $stmt->close();

        return $eliminado;
    }
}

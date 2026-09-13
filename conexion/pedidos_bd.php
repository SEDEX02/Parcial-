<?php
/**
 * =========================================================================
 *  MÓDULO BACKEND: Guardar pedidos en la base de datos
 * -------------------------------------------------------------------------
 *  Ajustado a la estructura real que entregó el compañero de BD:
 *    - pedidos(id_pedido, numero_pedido, cedula_cliente, fecha_pedido, valor_total)
 *    - menu(id_producto, nombre_producto, precio)
 *    - detalle_pedido(id_detalle, id_pedido, id_producto, cantidad, subtotal)
 *
 *  Nota: numero_pedido no es autoincremental por sí solo, así que aquí
 *  se genera automáticamente usando el mismo id_pedido (que sí es único
 *  y autoincremental), garantizando que nunca se repita.
 * =========================================================================
 */

require_once __DIR__ . '/conexion.php'; // este archivo va dentro de la carpeta conexion/, junto al conexion.php de tu compañero

function guardarPedido(string $cedula, array $productos): array
{
    $conexionObj = new conexion();
    $link = $conexionObj->conectar();

    if (empty($productos)) {
        throw new InvalidArgumentException('El pedido debe tener al menos un producto.');
    }

    // Calcular el valor total del pedido
    $valor_total = 0;
    foreach ($productos as $producto) {
        $valor_total += $producto['cantidad'] * $producto['precio_unitario'];
    }

    $link->begin_transaction();

    try {
        // 1. Insertar el pedido (numero_pedido temporal en 0, se actualiza después)
        $stmt = $link->prepare(
            'INSERT INTO pedidos (numero_pedido, cedula_cliente, valor_total) VALUES (0, ?, ?)'
        );
        $stmt->bind_param('id', $cedula, $valor_total);
        $stmt->execute();
        $stmt->close();

        $id_pedido = $link->insert_id;

        // 2. Usar el mismo id_pedido como numero_pedido (así siempre es único)
        $stmt = $link->prepare('UPDATE pedidos SET numero_pedido = ? WHERE id_pedido = ?');
        $stmt->bind_param('ii', $id_pedido, $id_pedido);
        $stmt->execute();
        $stmt->close();

        // 3. Insertar cada producto en detalle_pedido, validando que exista en el menú
        $stmtBuscar = $link->prepare('SELECT id_producto, precio FROM menu WHERE nombre_producto = ? LIMIT 1');
        $stmtDetalle = $link->prepare(
            'INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, subtotal) VALUES (?, ?, ?, ?)'
        );

        foreach ($productos as $producto) {
            $stmtBuscar->bind_param('s', $producto['nombre']);
            $stmtBuscar->execute();
            $resultado = $stmtBuscar->get_result()->fetch_assoc();

            if (!$resultado) {
                throw new RuntimeException("El producto '{$producto['nombre']}' no existe en el menú.");
            }

            $id_producto = $resultado['id_producto'];
            $subtotal = $producto['cantidad'] * $producto['precio_unitario'];

            $stmtDetalle->bind_param('iiid', $id_pedido, $id_producto, $producto['cantidad'], $subtotal);
            $stmtDetalle->execute();
        }

        $stmtBuscar->close();
        $stmtDetalle->close();

        $link->commit();
        $conexionObj->desconectar();

        return ['numero_pedido' => $id_pedido];

    } catch (Exception $e) {
        $link->rollback();
        $conexionObj->desconectar();
        throw $e;
    }
}

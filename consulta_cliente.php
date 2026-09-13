<?php
/**
 * =========================================================================
 *  MÓDULO: Consulta de pedidos por cédula del cliente
 * =========================================================================
 */

require 'conexion/conexion.php';

$conexionObj = new conexion();
$link = $conexionObj->conectar();

$cedula = trim($_GET['cedula'] ?? '');
$pedidos = [];
$buscado = false;

if ($cedula !== '') {
    $buscado = true;

    // 1. Traer los pedidos de esa cédula
    $stmt = $link->prepare('SELECT id_pedido, numero_pedido, fecha_pedido, valor_total FROM pedidos WHERE cedula_cliente = ? ORDER BY fecha_pedido DESC');
    $stmt->bind_param('i', $cedula);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        // 2. Para cada pedido, traer sus productos
        $stmtDetalle = $link->prepare(
            'SELECT m.nombre_producto, d.cantidad, d.subtotal
             FROM detalle_pedido d
             JOIN menu m ON d.id_producto = m.id_producto
             WHERE d.id_pedido = ?'
        );
        $stmtDetalle->bind_param('i', $fila['id_pedido']);
        $stmtDetalle->execute();
        $productos = $stmtDetalle->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtDetalle->close();

        $fila['productos'] = $productos;
        $pedidos[] = $fila;
    }
    $stmt->close();
}

$conexionObj->desconectar();

function formatearPesos($valor): string
{
    return number_format((float) $valor, 0, ',', '.') . ' pesos';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consulta por Cliente - Petrona Burger</title>
<link rel="stylesheet" href="style.css">
<style>
  .tabla-consulta { width: 100%; border-collapse: collapse; margin-top: 20px; }
  .tabla-consulta th, .tabla-consulta td { border: 1px solid #ddd; padding: 10px; text-align: left; }
  .tabla-consulta th { background: #f5f5f5; }
  .pedido-bloque { margin-bottom: 30px; border: 1px solid #ddd; border-radius: 8px; padding: 15px; }
  .pedido-bloque h4 { margin-top: 0; }
  .form-busqueda { display: flex; gap: 10px; margin-bottom: 20px; }
  .form-busqueda input { padding: 8px; flex: 1; max-width: 250px; }
  .form-busqueda button { padding: 8px 16px; }
</style>
</head>
<body>

<header>
  <h1>PETRONA BURGER</h1>
  <span class="etiqueta-proyecto">PROYECTO ACADÉMICO</span>
</header>

<nav class="navegacion">
  <a href="creacion_pedidos.php">[Crear Pedido]</a>
  <a href="pedidos_ordenados.php">[Todos los Pedidos]</a>
  <a href="consulta_cliente.php" class="seccion-activa">[Consulta por Cliente]</a>
</nav>

<main>
  <h2>Consulta de pedidos por cédula</h2>

  <form method="get" action="consulta_cliente.php" class="form-busqueda">
    <input type="text" name="cedula" placeholder="Ej: 79123456" value="<?php echo htmlspecialchars($cedula); ?>" required>
    <button type="submit">Buscar</button>
  </form>

  <?php if ($buscado && empty($pedidos)): ?>
    <p>No se encontraron pedidos para la cédula <?php echo htmlspecialchars($cedula); ?>.</p>
  <?php endif; ?>

  <?php foreach ($pedidos as $pedido): ?>
    <div class="pedido-bloque">
      <h4>Pedido #<?php echo $pedido['numero_pedido']; ?> — <?php echo $pedido['fecha_pedido']; ?></h4>
      <table class="tabla-consulta">
        <thead>
          <tr><th>Producto</th><th>Cantidad</th><th>Subtotal</th></tr>
        </thead>
        <tbody>
          <?php foreach ($pedido['productos'] as $prod): ?>
            <tr>
              <td><?php echo htmlspecialchars($prod['nombre_producto']); ?></td>
              <td><?php echo $prod['cantidad']; ?></td>
              <td><?php echo formatearPesos($prod['subtotal']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p><strong>Total del pedido: <?php echo formatearPesos($pedido['valor_total']); ?></strong></p>
    </div>
  <?php endforeach; ?>
</main>

</body>
</html>

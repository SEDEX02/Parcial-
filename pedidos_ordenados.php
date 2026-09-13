<?php
/**
 * =========================================================================
 *  MÓDULO: Listado de todos los pedidos, ordenados de menor a mayor
 *          según la cantidad total de productos.
 *          Incluye botón para eliminar pedidos.
 * =========================================================================
 */

require 'conexion/conexion.php';

$conexionObj = new conexion();
$link = $conexionObj->conectar();

// Eliminar pedido si se solicitó
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_pedido'])) {
    $id_pedido = (int) $_POST['eliminar_pedido'];
    $stmt = $link->prepare('DELETE FROM pedidos WHERE id_pedido = ?');
    $stmt->bind_param('i', $id_pedido);
    $stmt->execute();
    $stmt->close();
    // El detalle_pedido se borra solo por el ON DELETE CASCADE configurado en la base de datos
    header('Location: pedidos_ordenados.php');
    exit;
}

// Traer todos los pedidos con su cantidad total de productos
$sql = "
    SELECT p.id_pedido, p.numero_pedido, p.cedula_cliente, p.fecha_pedido, p.valor_total,
           COALESCE(SUM(d.cantidad), 0) AS total_productos
    FROM pedidos p
    LEFT JOIN detalle_pedido d ON d.id_pedido = p.id_pedido
    GROUP BY p.id_pedido, p.numero_pedido, p.cedula_cliente, p.fecha_pedido, p.valor_total
    ORDER BY total_productos ASC
";
$resultado = $link->query($sql);
$pedidos = $resultado->fetch_all(MYSQLI_ASSOC);

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
<title>Todos los Pedidos - Petrona Burger</title>
<link rel="stylesheet" href="style.css">
<style>
  .tabla-pedidos { width: 100%; border-collapse: collapse; margin-top: 20px; }
  .tabla-pedidos th, .tabla-pedidos td { border: 1px solid #ddd; padding: 10px; text-align: left; }
  .tabla-pedidos th { background: #f5f5f5; }
  .boton-eliminar { background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
  .boton-eliminar:hover { background: #c0392b; }
</style>
</head>
<body>

<header>
  <h1>PETRONA BURGER</h1>
  <span class="etiqueta-proyecto">PROYECTO ACADÉMICO</span>
</header>

<nav class="navegacion">
  <a href="creacion_pedidos.php">[Crear Pedido]</a>
  <a href="pedidos_ordenados.php" class="seccion-activa">[Todos los Pedidos]</a>
  <a href="consulta_cliente.php">[Consulta por Cliente]</a>
</nav>

<main>
  <h2>Todos los pedidos (ordenados de menor a mayor cantidad de productos)</h2>

  <?php if (empty($pedidos)): ?>
    <p>Todavía no hay pedidos registrados.</p>
  <?php else: ?>
    <table class="tabla-pedidos">
      <thead>
        <tr>
          <th>N° Pedido</th>
          <th>Cédula</th>
          <th>Fecha</th>
          <th>Cantidad de productos</th>
          <th>Valor total</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pedidos as $pedido): ?>
          <tr>
            <td><?php echo $pedido['numero_pedido']; ?></td>
            <td><?php echo htmlspecialchars($pedido['cedula_cliente']); ?></td>
            <td><?php echo $pedido['fecha_pedido']; ?></td>
            <td><?php echo $pedido['total_productos']; ?></td>
            <td><?php echo formatearPesos($pedido['valor_total']); ?></td>
            <td>
              <form method="post" action="pedidos_ordenados.php" onsubmit="return confirm('¿Seguro que quieres eliminar este pedido?');">
                <input type="hidden" name="eliminar_pedido" value="<?php echo $pedido['id_pedido']; ?>">
                <button type="submit" class="boton-eliminar">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</main>

</body>
</html>

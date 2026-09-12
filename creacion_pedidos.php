<?php
/**
 * =========================================================================
 *  MÓDULO: Creación de Pedidos
 *  Desarrollador: Santiago
 * -------------------------------------------------------------------------
 *  Esta parte es TUYA:
 *    - Mostrar el menú (Costillas / Malteadas).
 *    - Manejar los botones + / - (sin JS, recargando la página).
 *    - Armar el resumen del pedido y calcular el total en pantalla.
 *    - Validar que el pedido no esté vacío antes de enviarlo.
 *
 *  Esta parte es de DUBAN (BD/Backend):
 *    - La función guardarPedido() de más abajo es un PLACEHOLDER (una
 *      versión falsa/temporal) para que tu módulo funcione solo, mientras
 *      él no entregue su código.
 *    - Cuando Duban te pase su archivo (algo como includes/pedidos_bd.php
 *      con la conexión PDO y el INSERT real), solo tienes que:
 *        1. Borrar la función guardarPedido() de aquí abajo.
 *        2. Agregar arriba del todo: require 'includes/pedidos_bd.php';
 *      Todo lo demás de este archivo queda exactamente igual.
 * =========================================================================
 */

// ---- Menú (esto normalmente vendrá de la tabla `menu` de Duban) ----
$precios = [
    'Costillas' => 15000,
    'Malteadas' => 5000,
];

/**
 * PLACEHOLDER temporal — Duban la va a reemplazar por la función real que
 * inserta en MySQL con PDO y devuelve el numero_pedido único generado por
 * la base de datos.
 */
function guardarPedido(string $cedula, array $productos): array
{
    // Simula un número de pedido autoincremental mientras no hay BD real.
    $numero_pedido = rand(1000, 9999);
    return ['numero_pedido' => $numero_pedido];
}

function formatearPesos(int $valor): string
{
    return number_format($valor, 0, ',', '.') . ' pesos';
}

// ---- Estado del formulario ----
$errores = [];
$exito = null;

$cedula = trim($_POST['cedula'] ?? '');
$cant_costillas = max(0, (int) ($_POST['cant_costillas'] ?? 0));
$cant_malteadas = max(0, (int) ($_POST['cant_malteadas'] ?? 0));
$accion = $_POST['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($accion) {
        case 'sumar_costillas':
            $cant_costillas++;
            break;
        case 'restar_costillas':
            $cant_costillas = max(0, $cant_costillas - 1);
            break;
        case 'sumar_malteadas':
            $cant_malteadas++;
            break;
        case 'restar_malteadas':
            $cant_malteadas = max(0, $cant_malteadas - 1);
            break;
        case 'confirmar':
            // Validación: pedido no puede estar vacío
            if ($cedula === '') {
                $errores[] = 'Debes ingresar la cédula del cliente.';
            }
            if ($cant_costillas === 0 && $cant_malteadas === 0) {
                $errores[] = 'El pedido no puede estar vacío: agrega al menos un producto.';
            }

            if (empty($errores)) {
                $productos = [];
                if ($cant_costillas > 0) {
                    $productos[] = ['nombre' => 'Costillas', 'cantidad' => $cant_costillas, 'precio_unitario' => $precios['Costillas']];
                }
                if ($cant_malteadas > 0) {
                    $productos[] = ['nombre' => 'Malteadas', 'cantidad' => $cant_malteadas, 'precio_unitario' => $precios['Malteadas']];
                }

                // ---- Aquí se llama a la parte de Duban ----
                $resultado = guardarPedido($cedula, $productos);

                $total = ($cant_costillas * $precios['Costillas']) + ($cant_malteadas * $precios['Malteadas']);
                $exito = ['numero_pedido' => $resultado['numero_pedido'], 'total' => $total];

                $cedula = '';
                $cant_costillas = 0;
                $cant_malteadas = 0;
            }
            break;
    }
}

$total = ($cant_costillas * $precios['Costillas']) + ($cant_malteadas * $precios['Malteadas']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear Pedido - Petrona Burger</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <h1>PETRONA BURGER</h1>
  <span class="etiqueta-proyecto">PROYECTO ACADÉMICO</span>
</header>

<nav class="navegacion">
  <a href="index.html" class="seccion-activa">[Crear Pedido]</a>
  <a href="#">[Todos los Pedidos]</a>
  <a href="#">[Consulta por Cliente]</a>
</nav>

<main>

  <?php if ($exito): ?>
    <div class="alert alert-success">
      Pedido #<?php echo $exito['numero_pedido']; ?> creado con éxito. Valor total: <?php echo formatearPesos($exito['total']); ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errores)): ?>
    <div class="alert alert-error">
      <?php foreach ($errores as $err): ?>
        <div><?php echo htmlspecialchars($err); ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="creacion_pedidos.php">
    <section class="seccion-crear-pedido">

      <!-- Producto: Costillas -->
      <div class="producto-menu">
        <img src="https://images.unsplash.com/photo-1544025162-d76694265947?w=400&h=300&fit=crop" alt="Costillas">
        <div class="producto-info">
          <h3>Costillas</h3>
          <div class="producto-precio"><?php echo formatearPesos($precios['Costillas']); ?></div>
        </div>
        <div class="control-cantidad">
          <button type="submit" name="accion" value="restar_costillas" class="boton-cantidad boton-restar">−</button>
          <span class="cantidad-valor"><?php echo $cant_costillas; ?></span>
          <button type="submit" name="accion" value="sumar_costillas" class="boton-cantidad boton-sumar">+</button>
        </div>
      </div>

      <!-- Producto: Malteadas -->
      <div class="producto-menu">
        <img src="https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=400&h=300&fit=crop" alt="Malteadas">
        <div class="producto-info">
          <h3>Malteadas</h3>
          <div class="producto-precio"><?php echo formatearPesos($precios['Malteadas']); ?></div>
        </div>
        <div class="control-cantidad">
          <button type="submit" name="accion" value="restar_malteadas" class="boton-cantidad boton-restar">−</button>
          <span class="cantidad-valor"><?php echo $cant_malteadas; ?></span>
          <button type="submit" name="accion" value="sumar_malteadas" class="boton-cantidad boton-sumar">+</button>
        </div>
      </div>

      <!-- Resumen del pedido -->
      <div class="resumen-pedido">
        <h3>Tu pedido</h3>

        <input type="hidden" name="cant_costillas" value="<?php echo $cant_costillas; ?>">
        <input type="hidden" name="cant_malteadas" value="<?php echo $cant_malteadas; ?>">

        <div class="campo">
          <label>Cédula del cliente</label>
          <input type="text" name="cedula" value="<?php echo htmlspecialchars($cedula); ?>" placeholder="Ej: 79123456" required>
        </div>

        <?php if ($cant_costillas > 0 || $cant_malteadas > 0): ?>
          <div class="lista-productos">
            <span class="lista-productos-titulo">Productos:</span>
            <ul>
              <?php if ($cant_costillas > 0): ?>
                <li>
                  <span>Costillas x <?php echo $cant_costillas; ?></span>
                  <span><?php echo number_format($cant_costillas * $precios['Costillas'], 0, ',', '.'); ?></span>
                </li>
              <?php endif; ?>
              <?php if ($cant_malteadas > 0): ?>
                <li>
                  <span>Malteadas x <?php echo $cant_malteadas; ?></span>
                  <span><?php echo number_format($cant_malteadas * $precios['Malteadas'], 0, ',', '.'); ?></span>
                </li>
              <?php endif; ?>
            </ul>
          </div>

          <div class="caja-total">
            <span class="total-etiqueta">Valor total final:</span>
            <span class="total-valor"><?php echo formatearPesos($total); ?></span>
          </div>
        <?php else: ?>
          <p class="nota-verificacion">⚠ Agrega al menos un producto con los botones + / −</p>
        <?php endif; ?>

        <button type="submit" name="accion" value="confirmar" class="boton-finalizar">Finalizar pedido</button>
      </div>
    </section>
  </form>
</main>

</body>
</html>
<?php
require_once "config.php";

// Consulta de proveedores
$sql = "
    SELECT 
        pp.id,
        u.nombre AS nombre_usuario,
        pp.nombre_empresa,
        pp.direccion,
        pp.nombre_contacto,
        pp.telefono_contacto,
        pp.sitio_web,
        pp.disponibilidad,
        pp.tipo_proveedor,
        c.nombre AS categoria
    FROM provider_profiles pp
    -- CAMBIAMOS INNER POR LEFT PARA QUE NO BLOQUEE SI NO HAY USUARIO
    LEFT JOIN users u ON pp.user_id = u.id 
    LEFT JOIN categories c ON pp.categoria_id = c.id
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Proveedores - FastContact</title>

    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f2f5;
            margin: 0;
            padding: 40px;
        }

        .title {
            text-align: center;
            font-size: 32px;
            font-weight: 800;
            color: #333;
            margin-bottom: 25px;
        }

        .card {
            background: #fff;
            max-width: 1200px;
            margin: auto;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
            margin-top: 10px;
        }

        th {
            background: #ff7f32;
            color: white;
            padding: 14px;
            text-align: left;
            font-size: 14px;
        }

        td {
            padding: 12px;
            background: #fff;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        tr:hover td {
            background: #fff6ef;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: bold;
            color: #fff;
        }

        .badge-bebidas { background: #0d6efd; }
        .badge-lácteos { background: #198754; }
        .badge-panificados { background: #ffc107; color: #000; }
        .badge-otros { background: #6c757d; }

        a.btn {
            padding: 6px 12px;
            background: #ff7f32;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: 0.2s ease;
        }

        a.btn:hover {
            background: #e76b1f;
        }

        .empty {
            text-align: center;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            font-size: 16px;
            color: #777;
        }
    </style>
</head>

<body>

<h1 class="title">Proveedores registrados – FastContact</h1>

<div class="card">

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Empresa</th>
            <th>Categoría</th>
            <th>Tipo proveedor</th>
            <th>Contacto</th>
            <th>Teléfono</th>
            <th>Sitio web</th>
            <th>Disponibilidad</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>

                <?php
                $tipo = $row['tipo_proveedor'] ?: 'otros';
                $badgeClass = 'badge-otros';

                if ($tipo === 'bebidas') $badgeClass = 'badge-bebidas';
                elseif ($tipo === 'lácteos') $badgeClass = 'badge-lácteos';
                elseif ($tipo === 'panificados') $badgeClass = 'badge-panificados';
                ?>

                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['nombre_empresa']) ?></td>
                    <td><?= htmlspecialchars($row['categoria'] ?: 'Sin categoría') ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($tipo) ?></span></td>
                    <td><?= htmlspecialchars($row['nombre_contacto']) ?></td>
                    <td><?= htmlspecialchars($row['telefono_contacto']) ?></td>
                    <td>
                        <?php if (!empty($row['sitio_web'])): ?>
                            <a href="<?= htmlspecialchars($row['sitio_web']) ?>" target="_blank" class="btn">Visitar</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['disponibilidad']) ?></td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>
            <tr><td colspan="8" class="empty">No hay proveedores registrados.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</div>

</body>
</html>

<?php $conn->close(); ?>

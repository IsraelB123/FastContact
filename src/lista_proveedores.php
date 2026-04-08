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
    LEFT JOIN users u ON pp.user_id = u.id 
    LEFT JOIN categories c ON pp.categoria_id = c.id
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Directorio de Proveedores - FastContact</title>

    <style>
        * { box-sizing: border-box; transition: all 0.2s ease; }
        body {
            font-family: 'Inter', system-ui, Arial, sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0%, #0f172a 40%, #020617 100%);
            background-attachment: fixed;
            margin: 0;
            padding: 40px 20px;
            color: #f8fafc;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-actions {
            margin-bottom: 20px;
        }

        .back-link {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .back-link:hover {
            color: #7dd3fc;
            text-decoration: underline;
        }

        .title {
            text-align: center;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 30px;
            letter-spacing: 0.5px;
        }
        .title span {
            color: #38bdf8;
        }

        .card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.05);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            color: #38bdf8;
            padding: 15px 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 14px;
        }

        tr:hover td {
            background: rgba(255,255,255,0.02);
        }

        /* Insignias de categoría adaptadas para modo oscuro */
        .badge {
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .badge-bebidas { background: rgba(56, 189, 248, 0.15); color: #38bdf8; } /* Azul */
        .badge-lácteos { background: rgba(52, 211, 153, 0.15); color: #6ee7b7; } /* Verde */
        .badge-panificados { background: rgba(251, 191, 36, 0.15); color: #fcd34d; } /* Amarillo */
        .badge-otros { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; } /* Gris */

        a.btn {
            padding: 8px 14px;
            background: transparent;
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 10px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
        }

        a.btn:hover {
            background: rgba(56, 189, 248, 0.1);
            border-color: #38bdf8;
        }

        .empty {
            text-align: center;
            padding: 40px;
            font-size: 15px;
            color: #64748b;
        }
    </style>
</head>

<body>

<div class="container">
    <div class="header-actions">
        <a href="index.php" class="back-link">← Volver al inicio</a>
    </div>

    <h1 class="title">Directorio de <span>Proveedores</span></h1>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Categoría</th>
                    <th>Tipo</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Disponibilidad</th>
                    <th>Sitio web</th>
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
                            <td><strong><?= htmlspecialchars($row['nombre_empresa']) ?></strong></td>
                            <td><?= htmlspecialchars($row['categoria'] ?: 'Sin categoría') ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($tipo) ?></span></td>
                            <td><?= htmlspecialchars($row['nombre_contacto']) ?></td>
                            <td><?= htmlspecialchars($row['telefono_contacto']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($row['disponibilidad'])) ?></td>
                            <td>
                                <?php if (!empty($row['sitio_web'])): ?>
                                    <a href="<?= htmlspecialchars($row['sitio_web']) ?>" target="_blank" class="btn">Visitar web</a>
                                <?php else: ?>
                                    <span style="color: #64748b; font-size: 12px;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>
                    <tr><td colspan="7" class="empty">Aún no hay proveedores registrados en la plataforma.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
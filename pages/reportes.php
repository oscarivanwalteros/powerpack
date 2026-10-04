<?php
// Métricas analíticas generales
$total_contactos = (int)$db->querySingle("SELECT COUNT(*) FROM contactos");
$total_ganados = (int)$db->querySingle("SELECT COUNT(*) FROM contactos WHERE etapa = 'ganado'");
$total_perdidos = (int)$db->querySingle("SELECT COUNT(*) FROM contactos WHERE etapa = 'perdido'");
$total_cerrados = $total_ganados + $total_perdidos;
$win_rate = $total_cerrados > 0 ? round(($total_ganados / $total_cerrados) * 100, 1) : 0;

$monto_ganado = (float)$db->querySingle("SELECT COALESCE(SUM(monto), 0) FROM negocios WHERE etapa = 'ganado'");
$ticket_promedio = $total_ganados > 0 ? round($monto_ganado / $total_ganados, 0) : 0;

// Forecast ponderado (Monto * Probabilidad)
$forecast_monto = (float)$db->querySingle("SELECT COALESCE(SUM(monto * probabilidad / 100.0), 0) FROM negocios WHERE etapa NOT IN ('ganado', 'perdido')");
$pipeline_bruto = (float)$db->querySingle("SELECT COALESCE(SUM(monto), 0) FROM negocios WHERE etapa NOT IN ('ganado', 'perdido')");

// Métricas del embudo por etapas
$embudo = [];
$max_count = 1;
foreach ($pipeline_etapas as $e) {
    $c = (int)$db->querySingle("SELECT COUNT(*) FROM contactos WHERE etapa = '{$e['id']}'");
    $embudo[$e['id']] = ['nombre' => $e['nombre'], 'color' => $e['color'], 'count' => $c];
    if ($c > $max_count) $max_count = $c;
}

// Ventas y prospectos por fuente
$fuentes_sql = $db->query("SELECT fuente, COUNT(*) as total_leads, 
    SUM(CASE WHEN etapa = 'ganado' THEN 1 ELSE 0 END) as ganados 
    FROM contactos GROUP BY fuente ORDER BY total_leads DESC");

// Actividad comercial acumulada
$act_wa = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'whatsapp'");
$act_mail = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'email'");
$act_call = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'llamada'");
$act_task = (int)$db->querySingle("SELECT COUNT(*) FROM actividades WHERE tipo = 'tarea' AND completada = 1");
$act_cot = (int)$db->querySingle("SELECT COUNT(*) FROM cotizaciones");
?>

<div class="page-header">
    <div>
        <h1>Reportes & Analítica Comercial</h1>
        <p>Métricas de conversión, forecast de ventas ponderado y rendimiento de canales</p>
    </div>
</div>

<!-- TARJETAS ANALÍTICAS -->
<div class="cards-grid">
    <div class="metric-card" style="border-top:4px solid var(--success)">
        <div class="label">Tasa de Cierre (Win Rate)</div>
        <div class="value" style="color:var(--success)"><?= $win_rate ?>%</div>
        <div class="sub"><?= $total_ganados ?> ganados de <?= $total_cerrados ?> prospectos cerrados</div>
    </div>
    <div class="metric-card" style="border-top:4px solid var(--accent)">
        <div class="label">Forecast Ponderado</div>
        <div class="value">$<?= number_format($forecast_monto, 0) ?></div>
        <div class="sub">Valor esperado según probabilidad (bruto: $<?= number_format($pipeline_bruto, 0) ?>)</div>
    </div>
    <div class="metric-card" style="border-top:4px solid var(--blue)">
        <div class="label">Ticket Promedio</div>
        <div class="value">$<?= number_format($ticket_promedio, 0) ?></div>
        <div class="sub">Valor promedio por venta industrial ganada</div>
    </div>
    <div class="metric-card" style="border-top:4px solid #7c3aed">
        <div class="label">Cotizaciones Emitidas</div>
        <div class="value"><?= $act_cot ?></div>
        <div class="sub"><a href="?page=cotizaciones" style="color:var(--email);font-weight:600">Ver todas las propuestas →</a></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.2fr 0.8fr;gap:24px;margin-bottom:24px">

    <!-- EMBUDO DE CONVERSIÓN VISUAL (Funnel Chart) -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow-sm)">
        <h3 style="font-size:16px;font-weight:800;margin-bottom:6px">📊 Embudo de Conversión de Prospectos</h3>
        <p style="font-size:12px;color:var(--fg-secondary);margin-bottom:20px">Flujo de prospectos desde que ingresan como leads hasta el cierre de la venta</p>

        <div style="display:flex;flex-direction:column;gap:14px">
            <?php foreach($embudo as $k => $step): 
                $pct = $total_contactos > 0 ? round(($step['count'] / $total_contactos) * 100, 1) : 0;
                $bar_w = $max_count > 0 ? max(12, round(($step['count'] / $max_count) * 100)) : 12;
            ?>
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;font-size:13px;margin-bottom:5px">
                    <span style="font-weight:700"><?= $step['nombre'] ?></span>
                    <span><strong><?= $step['count'] ?></strong> (<?= $pct ?>%)</span>
                </div>
                <div style="background:#f1f5f9;border-radius:999px;height:12px;overflow:hidden">
                    <div style="background:<?= $step['color'] ?>;width:<?= $bar_w ?>%;height:100%;border-radius:999px;transition:width 0.4s ease"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ACTIVIDAD Y ENERGÍA COMERCIAL -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;box-shadow:var(--shadow-sm)">
        <h3 style="font-size:16px;font-weight:800;margin-bottom:6px">⚡ Actividad Comercial Acumulada</h3>
        <p style="font-size:12px;color:var(--fg-secondary);margin-bottom:18px">Esfuerzo del equipo de ventas en interacciones y seguimientos</p>

        <div style="display:flex;flex-direction:column;gap:12px">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fcfcfd">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:22px">💬</span>
                    <div><div style="font-weight:700;font-size:13px">Mensajes de WhatsApp</div><div style="font-size:11px;color:var(--fg-secondary)">Interacciones directas</div></div>
                </div>
                <span style="font-size:18px;font-weight:800;color:var(--whatsapp)"><?= $act_wa ?></span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fcfcfd">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:22px">✉️</span>
                    <div><div style="font-weight:700;font-size:13px">Correos Electrónicos</div><div style="font-size:11px;color:var(--fg-secondary)">Envíos por Hostinger SMTP</div></div>
                </div>
                <span style="font-size:18px;font-weight:800;color:var(--email)"><?= $act_mail ?></span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fcfcfd">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:22px">📞</span>
                    <div><div style="font-weight:700;font-size:13px">Llamadas Telefónicas</div><div style="font-size:11px;color:var(--fg-secondary)">Conversaciones registradas</div></div>
                </div>
                <span style="font-size:18px;font-weight:800;color:#7c3aed"><?= $act_call ?></span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);background:#fcfcfd">
                <div style="display:flex;align-items:center;gap:10px">
                    <span style="font-size:22px">✅</span>
                    <div><div style="font-weight:700;font-size:13px">Tareas Completadas</div><div style="font-size:11px;color:var(--fg-secondary)">Compromisos cumplidos</div></div>
                </div>
                <span style="font-size:18px;font-weight:800;color:var(--success)"><?= $act_task ?></span>
            </div>
        </div>
    </div>

</div>

<!-- EFECTIVIDAD POR CANAL DE CAPTACIÓN -->
<div class="table-wrap">
    <div style="padding:18px 20px;border-bottom:1px solid var(--border)">
        <h3 style="font-size:15px;font-weight:800">🎯 Rendimiento por Fuente de Captación (¿De dónde vienen tus mejores clientes?)</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Fuente / Canal</th>
                <th>Total Prospectos</th>
                <th>Clientes Ganados</th>
                <th>Tasa de Conversión</th>
            </tr>
        </thead>
        <tbody>
            <?php while($f = $fuentes_sql->fetchArray(SQLITE3_ASSOC)): 
                $c_pct = $f['total_leads'] > 0 ? round(($f['ganados'] / $f['total_leads']) * 100, 1) : 0;
            ?>
            <tr>
                <td><strong><?= h(ucwords($f['fuente'])) ?></strong></td>
                <td><?= (int)$f['total_leads'] ?> prospectos</td>
                <td><strong style="color:var(--success)"><?= (int)$f['ganados'] ?></strong> cerrados</td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div style="background:#f1f5f9;border-radius:999px;width:120px;height:8px;overflow:hidden">
                            <div style="background:var(--success);width:<?= min(100, $c_pct) ?>%;height:100%"></div>
                        </div>
                        <strong><?= $c_pct ?>%</strong>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

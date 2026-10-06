<?php
// pages/presentacion.php - Presentación de Diapositivas & Manual de Funcionamiento
?>

<div class="page-header" style="margin-bottom:20px">
    <div>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
            <span style="background:#2c60a4;color:#fff;font-size:10.5px;font-weight:800;padding:2px 8px;border-radius:4px;text-transform:uppercase;letter-spacing:0.04em">Inducción & Capacitación</span>
            <span style="font-size:12px;color:var(--fg-secondary)">• Power Pack Suite Comercial</span>
        </div>
        <h1 style="font-size:22px;font-weight:900">Manual de Funcionamiento & Presentación Comercial</h1>
        <p>Aprende de forma rápida y sencilla las ventajas y el uso de cada módulo de la plataforma para ponerlo en práctica hoy mismo.</p>
    </div>

    <!-- Pestañas de Vista: Presentación vs Manual -->
    <div style="display:flex;gap:8px;background:#f1f5f9;padding:4px;border-radius:var(--radius-sm);border:1px solid var(--border)">
        <button type="button" onclick="cambiarVista('presentacion')" id="btnTabPresentacion" class="btn btn-sm" style="background:#2c60a4;color:#fff;font-weight:800;border:none">
            📽️ Diapositivas Interactivas
        </button>
        <button type="button" onclick="cambiarVista('manual')" id="btnTabManual" class="btn btn-sm" style="background:transparent;color:var(--fg);font-weight:700;border:none">
            📖 Manual Detallado
        </button>
    </div>
</div>

<!-- ============================================================== -->
<!-- SECCIÓN 1: PRESENTACIÓN INTERACTIVA DE DIAPOSITIVAS (SLIDE DECK) -->
<!-- ============================================================== -->
<div id="seccionPresentacion">
    
    <!-- Barra de Control del Slide Deck -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius) var(--radius) 0 0;padding:14px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;border-bottom:none">
        <div style="display:flex;align-items:center;gap:12px">
            <span style="font-size:12px;font-weight:800;color:var(--fg-secondary);text-transform:uppercase">Diapositiva:</span>
            <span id="appSlideCounter" style="background:#eff6ff;color:#1e40af;font-weight:800;padding:3px 10px;border-radius:6px;font-size:13px;border:1px solid #bfdbfe">
                1 de 9
            </span>
            <span id="appSlideTitleBadge" style="font-size:13px;font-weight:700;color:var(--fg)">
                1. Portada & Propósito
            </span>
        </div>

        <div style="display:flex;align-items:center;gap:8px">
            <button type="button" onclick="appPrevSlide()" class="btn btn-secondary btn-sm" style="font-weight:800;padding:6px 14px" title="Anterior (←)">
                ◀ Anterior
            </button>
            <button type="button" onclick="appNextSlide()" class="btn btn-primary btn-sm" style="font-weight:800;padding:6px 16px;background:#2c60a4" title="Siguiente (→)">
                Siguiente ▶
            </button>
        </div>
    </div>

    <!-- Barra de Progreso Bicolor -->
    <div style="height:5px;background:#e2e8f0;width:100%;overflow:hidden">
        <div id="appProgressBar" style="height:100%;width:11.1%;background:linear-gradient(90deg, #2c60a4 70%, #ed1c29 30%);transition:width 0.3s ease"></div>
    </div>

    <!-- Contenedor del Slide Activo -->
    <div style="background:#fff;border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius) var(--radius);padding:36px 40px;min-height:480px;box-shadow:var(--shadow-sm);position:relative">
        
        <!-- SLIDE 1 -->
        <div class="deck-slide deck-active" id="dslide-0">
            <div style="max-width:820px">
                <span style="display:inline-block;padding:3px 10px;background:#eff6ff;color:#2c60a4;font-size:11px;font-weight:800;text-transform:uppercase;border-radius:999px;margin-bottom:12px">
                    🚀 Bienvenido a la Nueva Era de Ventas de Power Pack
                </span>
                <h2 style="font-size:28px;font-weight:900;color:#0f172a;line-height:1.2;margin-bottom:12px">
                    Power Pack <span style="color:#2c60a4">Suite Comercial</span>
                </h2>
                <p style="font-size:15px;color:#475569;line-height:1.6;margin-bottom:24px">
                    Diseñada para vender maquinaria de empaque, selladoras al vacío, dosificadoras y soluciones industriales en Colombia con la máxima velocidad, formalidad ejecutiva y sin perder nunca el rastro de un cliente potencial.
                </p>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;margin:24px 0">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #2c60a4;border-radius:6px;padding:16px">
                        <strong style="color:#0f172a;display:block;margin-bottom:4px;font-size:14px">⚡ Cotizaciones en 30s</strong>
                        <span style="font-size:12.5px;color:#64748b">Papelería membretada oficial lista para imprimir o enviar por WhatsApp.</span>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #ed1c29;border-radius:6px;padding:16px">
                        <strong style="color:#0f172a;display:block;margin-bottom:4px;font-size:14px">🎯 Prioridad en 1 Clic</strong>
                        <span style="font-size:12.5px;color:#64748b">Filtra clientes VIP (🔥 Alta) para llamar a los más calificados primero.</span>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-left:4px solid #7c3aed;border-radius:6px;padding:16px">
                        <strong style="color:#0f172a;display:block;margin-bottom:4px;font-size:14px">🤖 Copiloto IA con Gemini</strong>
                        <span style="font-size:12.5px;color:#64748b">Redacción persuasiva con el catálogo y políticas de Power Pack.</span>
                    </div>
                </div>

                <div style="font-size:12px;color:#94a3b8;display:flex;align-items:center;gap:6px">
                    <span>💡 Tip:</span> Usa las teclas de dirección <kbd style="background:#e2e8f0;padding:2px 6px;border-radius:4px;font-weight:700">←</kbd> y <kbd style="background:#e2e8f0;padding:2px 6px;border-radius:4px;font-weight:700">→</kbd> de tu teclado para cambiar de diapositiva.
                </div>
            </div>
        </div>

        <!-- SLIDE 2 -->
        <div class="deck-slide" id="dslide-1" style="display:none">
            <div style="max-width:820px">
                <span style="display:inline-block;padding:3px 10px;background:#fef2f2;color:#dc2626;font-size:11px;font-weight:800;text-transform:uppercase;border-radius:999px;margin-bottom:12px">
                    El Desafío Comercial de Maquinaria
                </span>
                <h2 style="font-size:26px;font-weight:900;color:#0f172a;margin-bottom:14px">
                    ¿Por qué se pierden ventas de maquinaria en Colombia?
                </h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:16px">
                    <div style="background:#fff5f5;border:1px solid #fecaca;border-radius:8px;padding:20px">
                        <h3 style="color:#991b1b;font-weight:800;font-size:15px;margin-bottom:10px">❌ El Método Antiguo (Lento & Caótico)</h3>
                        <ul style="font-size:13px;color:#7f1d1d;line-height:1.7;padding-left:18px">
                            <li>El cliente pide una cotización y recibe el archivo 2 días después.</li>
                            <li>Contactos de ferias (Andina Pack/Alimentec) archivados en tarjetas de papel o libretas.</li>
                            <li>No hay orden de prioridad: se pierde tiempo con clientes fríos.</li>
                            <li>Formatos en Word desordenados sin cálculo exacto de IVA.</li>
                        </ul>
                    </div>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:20px">
                        <h3 style="color:#166534;font-weight:800;font-size:15px;margin-bottom:10px">✅ Con Power Pack Suite Comercial</h3>
                        <ul style="font-size:13px;color:#14532d;line-height:1.7;padding-left:18px">
                            <li>Cotización formal en PDF enviada al WhatsApp del cliente en menos de 1 minuto.</li>
                            <li>Carga masiva de ferias con un solo clic.</li>
                            <li>Semáforo de abandono que avisa si un trato lleva más de 5 días congelado.</li>
                            <li>Papelería membretada institucional permanente con NIT y cuenta Bancolombia.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE 3 -->
        <div class="deck-slide" id="dslide-2" style="display:none">
            <div style="max-width:820px">
                <span style="display:inline-block;padding:3px 10px;background:#eff6ff;color:#2c60a4;font-size:11px;font-weight:800;text-transform:uppercase;border-radius:999px;margin-bottom:12px">
                    Metodología de Venta en 4 Pasos
                </span>
                <h2 style="font-size:26px;font-weight:900;color:#0f172a;margin-bottom:14px">
                    El Flujo de Trabajo que Duplica los Cierres
                </h2>
                <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:14px;margin-top:20px">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;text-align:center">
                        <div style="width:36px;height:36px;border-radius:50%;background:#2c60a4;color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-weight:900">1</div>
                        <strong style="display:block;font-size:13.5px;color:#0f172a">Captura</strong>
                        <p style="font-size:11.5px;color:#64748b;margin-top:4px">Feria, web o WhatsApp ingresan a la base de datos sin duplicados.</p>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;text-align:center">
                        <div style="width:36px;height:36px;border-radius:50%;background:#ed1c29;color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-weight:900">2</div>
                        <strong style="display:block;font-size:13.5px;color:#0f172a">Prioridad</strong>
                        <p style="font-size:11.5px;color:#64748b;margin-top:4px">Se marca en 🔥 Alta para llamar primero a quienes van a comprar hoy.</p>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;text-align:center">
                        <div style="width:36px;height:36px;border-radius:50%;background:#d97706;color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-weight:900">3</div>
                        <strong style="display:block;font-size:13.5px;color:#0f172a">Cotización</strong>
                        <p style="font-size:11.5px;color:#64748b;margin-top:4px">Oferta membretada formal con carta de presentación ejecutiva.</p>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px;text-align:center">
                        <div style="width:36px;height:36px;border-radius:50%;background:#16a34a;color:#fff;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;font-weight:900">4</div>
                        <strong style="display:block;font-size:13.5px;color:#0f172a">Cierre</strong>
                        <p style="font-size:11.5px;color:#64748b;margin-top:4px">Seguimiento en Kanban y puesta en marcha del equipo en planta.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE 4 -->
        <div class="deck-slide" id="dslide-3" style="display:none">
            <div style="max-width:820px">
                <span style="display:inline-block;padding:3px 10px;background:#fef2f2;color:#dc2626;font-size:11px;font-weight:800;text-transform:uppercase;border-radius:999px;margin-bottom:12px">
                    Gestión del Tiempo Comercial
                </span>
                <h2 style="font-size:26px;font-weight:900;color:#0f172a;margin-bottom:14px">
                    Prioridad Comercial 1-Clic: Dónde Enfocar las Llamadas
                </h2>
                <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin-top:20px">
                    <div style="background:#fff;border:1.5px solid #f87171;border-radius:8px;padding:18px">
                        <div style="font-size:24px;margin-bottom:6px">🔥</div>
                        <strong style="color:#b91c1c;font-size:15px;display:block">Alta Prioridad</strong>
                        <p style="font-size:12px;color:#64748b;line-height:1.5;margin-top:6px">
                            Clientes con presupuesto aprobado, urgencia operativa o decisión en menos de 15 días. <strong>Se contactan a primera hora.</strong>
                        </p>
                    </div>
                    <div style="background:#fff;border:1.5px solid #fbbf24;border-radius:8px;padding:18px">
                        <div style="font-size:24px;margin-bottom:6px">🟡</div>
                        <strong style="color:#b45309;font-size:15px;display:block">Media Prioridad</strong>
                        <p style="font-size:12px;color:#64748b;line-height:1.5;margin-top:6px">
                            En fase de evaluación técnica o comparando cotizaciones. Requieren llamadas y seguimiento semanal estructurado.
                        </p>
                    </div>
                    <div style="background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;padding:18px">
                        <div style="font-size:24px;margin-bottom:6px">⚪</div>
                        <strong style="color:#475569;font-size:15px;display:block">Baja Prioridad</strong>
                        <p style="font-size:12px;color:#64748b;line-height:1.5;margin-top:6px">
                            Proyectos a largo plazo (6+ meses) o prospectos fríos. Se conservan para campañas masivas de correo electrónico.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE 5 -->
        <div class="deck-slide" id="dslide-4" style="display:none">
            <div style="max-width:820px">
                <span style="display:inline-block;padding:3px 10px;background:#f3e8ff;color:#7c3aed;font-size:11px;font-weight:800;text-transform:uppercase;border-radius:999px;margin-bottom:12px">
                    Inteligencia Artificial Aplicada
                </span>
                <h2 style="font-size:26px;font-weight:900;color:#0f172a;margin-bottom:14px">
                    El Copiloto IA: Redacción Persuasiva en WhatsApp & Correo
                </h2>
                <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:12px;margin-top:16px;font-size:12px">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:6px">
                        <strong style="color:#2c60a4;display:block;margin-bottom:2px">🎯 Fórmula AIDA</strong>
                        <span style="color:#64748b">Seguimiento a contactos de ferias recordando el stand.</span>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:6px">
                        <strong style="color:#ed1c29;display:block;margin-bottom:2px">⚡ Fórmula PAS</strong>
                        <span style="color:#64748b">Identifica mermas y ofrece la máquina como solución.</span>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:6px">
                        <strong style="color:#16a34a;display:block;margin-bottom:2px">🏭 Fórmula BAB</strong>
                        <span style="color:#64748b">Muestra el ahorro de tiempo y el aumento de velocidad.</span>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:6px">
                        <strong style="color:#0284c7;display:block;margin-bottom:2px">🧊 Magic Email</strong>
                        <span style="color:#64748b">Reactivación cordial para cotizaciones frías.</span>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:6px">
                        <strong style="color:#9333ea;display:block;margin-bottom:2px">🏢 Showroom Bogotá</strong>
                        <span style="color:#64748b">Invita a probar la máquina en Calle 161 con muestras reales.</span>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:6px">
                        <strong style="color:#d97706;display:block;margin-bottom:2px">📱 WhatsApp Flash</strong>
                        <span style="color:#64748b">3 párrafos cortos y directos para gerentes de planta.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE 6 -->
        <div class="deck-slide" id="dslide-5" style="display:none">
            <div style="max-width:820px">
                <span style="display:inline-block;padding:3px 10px;background:#fef3c7;color:#b45309;font-size:11px;font-weight:800;text-transform:uppercase;border-radius:999px;margin-bottom:12px">
                    Estandarización Corporativa
                </span>
                <h2 style="font-size:26px;font-weight:900;color:#0f172a;margin-bottom:14px">
                    Cotizaciones Oficiales en Papelería Membretada
                </h2>
                <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;padding:18px;margin-top:14px">
                    <div style="display:flex;align-items:center;justify-content:space-between;border-bottom:2px solid #2c60a4;padding-bottom:10px;margin-bottom:12px">
                        <div>
                            <strong style="font-size:14px;color:#0f172a">POWER PACK • NIT: 901.452.889-1</strong>
                            <div style="font-size:11px;color:#64748b">Showroom: Calle 161 # 54 - 25, Bogotá • Tel: +57 300 467 0474</div>
                        </div>
                        <span style="background:#2c60a4;color:#fff;font-weight:800;font-size:11px;padding:4px 10px;border-radius:4px">
                            PROPUESTA TÉCNICO-COMERCIAL
                        </span>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;font-size:12px;color:#334155">
                        <div>
                            <strong>✓ Carta de Presentación Ejecutiva:</strong> Saludo protocolario institucional que destaca la ingeniería y respaldo nacional.
                        </div>
                        <div>
                            <strong>✓ Memoria Técnica en COP:</strong> Desglose por equipo, cálculo automático de IVA (19%) y total de la inversión.
                        </div>
                        <div>
                            <strong>✓ Términos Comerciales & Garantía:</strong> 12 meses de garantía directa, capacitación en planta y cuenta Bancolombia.
                        </div>
                        <div>
                            <strong>✓ Impresión & PDF en 1 Clic:</strong> Formato tamaño carta sin barras ni menús, y botón directo para WhatsApp.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE 7 -->
        <div class="deck-slide" id="dslide-6" style="display:none">
            <div style="max-width:820px">
                <span style="display:inline-block;padding:3px 10px;background:#ecfdf5;color:#059669;font-size:11px;font-weight:800;text-transform:uppercase;border-radius:999px;margin-bottom:12px">
                    Control Visual del Pipeline
                </span>
                <h2 style="font-size:26px;font-weight:900;color:#0f172a;margin-bottom:14px">
                    Embudo Kanban & Semáforo Anti-Abandono
                </h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:16px">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px">
                        <strong style="color:#0f172a;font-size:14px;display:block;margin-bottom:6px">✋ Arrastre Inteligente (Drag & Drop)</strong>
                        <p style="font-size:12.5px;color:#64748b;line-height:1.6">
                            Mueve tarjetas de prospectos entre etapas (Contacto Inicial, Calificado, Cotización, Negociación, Ganado) con el mouse o en tu celular. La base de datos se actualiza al instante.
                        </p>
                    </div>
                    <div style="background:#fff5f5;border:1.5px solid #fca5a5;border-radius:8px;padding:18px">
                        <strong style="color:#991b1b;font-size:14px;display:block;margin-bottom:6px">🚨 Semáforo de Abandono</strong>
                        <p style="font-size:12.5px;color:#7f1d1d;line-height:1.6">
                            Si un trato lleva **más de 5 días sin contacto**, la tarjeta se resalta automáticamente con borde rojo y advertencia visual para evitar que el cliente compre a la competencia.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE 8 -->
        <div class="deck-slide" id="dslide-7" style="display:none">
            <div style="max-width:820px">
                <span style="display:inline-block;padding:3px 10px;background:#f3e8ff;color:#7c3aed;font-size:11px;font-weight:800;text-transform:uppercase;border-radius:999px;margin-bottom:12px">
                    Eventos Masivos & Movilidad
                </span>
                <h2 style="font-size:26px;font-weight:900;color:#0f172a;margin-bottom:14px">
                    Captura en Ferias, Respaldo & App Móvil (PWA)
                </h2>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:16px">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px">
                        <strong style="color:#0f172a;font-size:14px;display:block;margin-bottom:6px">🎪 Carga de Ferias en 1 Clic</strong>
                        <p style="font-size:12.5px;color:#64748b;line-height:1.6">
                            Sube hojas de cálculo de eventos o pega filas directamente. El sistema crea contactos, empresas y tareas asociadas en 2 segundos. Incluye botón de prueba con 12 prospectos colombianos.
                        </p>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:18px">
                        <strong style="color:#0f172a;font-size:14px;display:block;margin-bottom:6px">📲 Instalación como App Móvil</strong>
                        <p style="font-size:12.5px;color:#64748b;line-height:1.6">
                            Instala Power Pack en tu celular Android (Chrome &gt; Instalar) o iPhone (Safari &gt; Agregar a inicio) para cotizar, llamar y registrar notas en vivo desde la planta del cliente.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLIDE 9 -->
        <div class="deck-slide" id="dslide-8" style="display:none">
            <div style="max-width:820px">
                <span style="display:inline-block;padding:3px 10px;background:#ecfdf5;color:#059669;font-size:11px;font-weight:800;text-transform:uppercase;border-radius:999px;margin-bottom:12px">
                    Plan de Acción Inmediato
                </span>
                <h2 style="font-size:26px;font-weight:900;color:#0f172a;margin-bottom:14px">
                    La Rutina Diaria de 15 Minutos para Vender Más
                </h2>
                <div style="display:flex;flex-direction:column;gap:10px;margin-top:16px">
                    <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;padding:12px 16px;border-radius:8px;border:1px solid #e2e8f0">
                        <span style="font-weight:900;background:#2c60a4;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px">8:00 AM</span>
                        <span style="font-size:13px;color:#1e293b"><strong>Abre el Dashboard:</strong> Revisa llamadas del día y tareas vencidas pendientes.</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;padding:12px 16px;border-radius:8px;border:1px solid #e2e8f0">
                        <span style="font-weight:900;background:#ed1c29;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px">8:15 AM</span>
                        <span style="font-size:13px;color:#1e293b"><strong>Filtra por Prioridad Alta (🔥):</strong> Llama a los clientes calificados listos para ordenar.</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;padding:12px 16px;border-radius:8px;border:1px solid #e2e8f0">
                        <span style="font-weight:900;background:#d97706;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px">10:00 AM</span>
                        <span style="font-size:13px;color:#1e293b"><strong>Pipeline Anti-Abandono:</strong> Reactiva tratos con más de 5 días sin contacto usando IA.</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;padding:12px 16px;border-radius:8px;border:1px solid #e2e8f0">
                        <span style="font-weight:900;background:#16a34a;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px">En el día</span>
                        <span style="font-size:13px;color:#1e293b"><strong>Cotizaciones en caliente:</strong> Emite la propuesta formal con papelería membretada en 30s.</span>
                    </div>
                </div>

                <div style="margin-top:24px;display:flex;justify-content:space-between;align-items:center">
                    <button type="button" onclick="appGoToSlide(0)" class="btn btn-secondary btn-sm">↺ Reiniciar Diapositivas</button>
                    <button type="button" onclick="cambiarVista('manual')" class="btn btn-primary btn-sm" style="background:#2c60a4">Ir al Manual Detallado →</button>
                </div>
            </div>
        </div>

    </div>

    <!-- Puntos de Navegación Rápida Inferiores -->
    <div style="display:flex;justify-content:center;gap:8px;margin-top:16px" id="appSlideDots">
        <!-- Generado dinámicamente -->
    </div>
</div>

<!-- ============================================================== -->
<!-- SECCIÓN 2: MANUAL DETALLADO PASO A PASO (GUÍA DE CONSULTA) -->
<!-- ============================================================== -->
<div id="seccionManual" style="display:none;background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:36px;box-shadow:var(--shadow-sm);max-width:100%">
    
    <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:2px solid #2c60a4;padding-bottom:16px;margin-bottom:24px">
        <div>
            <h2 style="font-size:22px;font-weight:900;color:#0f172a">Manual de Operación Comercial Paso a Paso</h2>
            <p style="font-size:13px;color:#64748b;margin-top:2px">Guía de referencia rápida y procedimientos operativos estándar para el equipo de ventas.</p>
        </div>
        <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">🖨️ Imprimir Manual</button>
    </div>

    <div style="display:grid;grid-template-columns:240px 1fr;gap:30px">
        
        <!-- Menú Lateral de Capítulos -->
        <div style="position:sticky;top:20px;height:fit-content;background:#f8fafc;padding:16px;border-radius:8px;border:1px solid #e2e8f0;font-size:12.5px">
            <strong style="display:block;margin-bottom:10px;color:#2c60a4;font-size:11.5px;text-transform:uppercase">Índice del Manual</strong>
            <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px">
                <li><a href="#m-intro" style="color:#334155;text-decoration:none">1. Propósito & Ventajas</a></li>
                <li><a href="#m-dashboard" style="color:#334155;text-decoration:none">2. Uso del Dashboard</a></li>
                <li><a href="#m-prioridad" style="color:#334155;text-decoration:none">3. Prioridades Comerciales</a></li>
                <li><a href="#m-ia" style="color:#334155;text-decoration:none">4. Copiloto IA & Skills</a></li>
                <li><a href="#m-cotizaciones" style="color:#334155;text-decoration:none">5. Cotizaciones Membretadas</a></li>
                <li><a href="#m-pipeline" style="color:#334155;text-decoration:none">6. Pipeline & Semáforo</a></li>
                <li><a href="#m-ferias" style="color:#334155;text-decoration:none">7. Ferias & Respaldos</a></li>
                <li><a href="#m-pwa" style="color:#334155;text-decoration:none">8. App Móvil (PWA)</a></li>
            </ul>
        </div>

        <!-- Contenido del Manual -->
        <div style="font-size:13.5px;color:#334155;line-height:1.7">
            
            <section id="m-intro" style="margin-bottom:30px">
                <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:8px">1. Propósito & Ventajas de la Plataforma</h3>
                <p>Power Pack Suite Comercial fue construida exclusivamente para la venta consultiva B2B de maquinaria industrial. A diferencia de un CRM genérico, integra cotizaciones con papelería propia en Pesos Colombianos (COP), cálculo exacto de IVA (19%), carta de presentación protocolaria, conexión SMTP oficial con Hostinger y WhatsApp con registro automático en el historial del cliente.</p>
            </section>

            <section id="m-dashboard" style="margin-bottom:30px">
                <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:8px">2. Uso Diario del Dashboard</h3>
                <p>Cada asesor debe iniciar su día a las 8:00 AM revisando el panel. Los 4 KPIs superiores muestran el estado del negocio. En la sección <strong>Tareas Prioritarias</strong>, haz clic en el círculo a la izquierda de cada tarea para marcarla como realizada al terminar la llamada.</p>
            </section>

            <section id="m-prioridad" style="margin-bottom:30px">
                <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:8px">3. Cómo Administrar Prioridades en 1 Clic</h3>
                <p>En el directorio de contactos, utiliza los botones superiores para filtrar:</p>
                <ul>
                    <li><strong>🔥 Alta:</strong> Clientes calificados listos para ordenar o con presupuesto activo.</li>
                    <li><strong>🟡 Media:</strong> Clientes en comparación técnica o esperando respuesta de cotización.</li>
                    <li><strong>⚪ Baja:</strong> Contactos fríos o proyectos a largo plazo.</li>
                </ul>
                <p>Para cambiar la prioridad de cualquier cliente, simplemente haz clic en el selector desplegable dentro de la tabla o en su cabecera; el cambio se sincroniza en vivo en el servidor.</p>
            </section>

            <section id="m-ia" style="margin-bottom:30px">
                <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:8px">4. El Copiloto IA (Gemini 3.8 Flash)</h3>
                <p>Dentro de cualquier contacto, haz clic en el botón <strong>✨ Copiloto IA</strong> o dentro de la pestaña de WhatsApp o Correo. Selecciona la fórmula comercial que mejor se adapte al caso (AIDA para ferias, PAS para problemas de empaque, Magic Email para prospectos fríos o Showroom para invitarlos a la Calle 161 en Bogotá). Haz clic en <em>Generar Mensaje</em> y luego cópialo o envíalo directamente.</p>
            </section>

            <section id="m-cotizaciones" style="margin-bottom:30px">
                <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:8px">5. Emisión de Cotizaciones en Papelería Membretada</h3>
                <p>Para generar una propuesta formal:</p>
                <ol>
                    <li>Ingresa a <strong>Cotizaciones &gt; + Nueva Cotización Formal</strong> o haz clic en <em>+ Cotizar</em> desde la ficha del cliente.</li>
                    <li>Verifica el cliente y revisa la <strong>Carta de Presentación Ejecutiva</strong>. Si deseas usar el texto corporativo predeterminado de Power Pack, haz clic en <em>🔄 Restablecer Texto Estándar</em>.</li>
                    <li>Agrega los equipos desde el catálogo o agrega líneas manuales con sus especificaciones técnicas.</li>
                    <li>Guarda la cotización. Aparecerá la <strong>hoja membretada oficial</strong> con franja bicolor, NIT, dirección y firmas.</li>
                    <li>Haz clic en <strong>🖨️ Imprimir / Guardar en PDF</strong> o en <strong>💬 Compartir por WhatsApp</strong> para enviarla de inmediato.</li>
                </ol>
            </section>

            <section id="m-pipeline" style="margin-bottom:30px">
                <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:8px">6. Embudo Kanban & Semáforo Anti-Abandono</h3>
                <p>En el Pipeline puedes visualizar todas las oportunidades en columnas. Arrastra las tarjetas para avanzar el trato de etapa. Si una tarjeta se ilumina en rojo con la advertencia <strong>⚠️ Semáforo de Abandono</strong>, significa que lleva más de 5 días sin contacto: ¡llámalo inmediatamente antes de que compre a otro proveedor!</p>
            </section>

            <section id="m-ferias" style="margin-bottom:30px">
                <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:8px">7. Carga de Ferias & Respaldos Mensuales</h3>
                <p>En el módulo <strong>Subir Excel / CSV</strong> puedes pegar los contactos obtenidos en ferias industriales y el sistema mapeará automáticamente sus datos. Además, el último día de cada mes el administrador puede descargar el respaldo completo de la base de datos en formato Excel y el archivo SQLite maestro.</p>
            </section>

            <section id="m-pwa" style="margin-bottom:30px">
                <h3 style="font-size:17px;font-weight:800;color:#0f172a;margin-bottom:8px">8. Instalación como Aplicación Móvil (PWA)</h3>
                <p>Para usar la plataforma en tu celular sin entrar cada vez al navegador:</p>
                <ul>
                    <li><strong>Android (Chrome):</strong> Toca los tres puntos (⋮) y selecciona <em>Instalar aplicación</em>.</li>
                    <li><strong>iPhone (Safari):</strong> Toca el botón Compartir y selecciona <em>Agregar a inicio</em>.</li>
                </ul>
            </section>

        </div>

    </div>

</div>

<script>
const appSlideTitles = [
    "1. Portada & Propósito",
    "2. El Reto B2B vs Solución",
    "3. Flujo Comercial en 4 Pasos",
    "4. Priorización 1-Clic",
    "5. Copiloto IA con Gemini",
    "6. Cotizaciones Membretadas",
    "7. Pipeline & Semáforo",
    "8. Ferias & App Móvil",
    "9. Rutina Diaria de Éxito"
];

let appCurrentSlide = 0;
const appTotalSlides = 9;

function appUpdateSlide() {
    for (let i = 0; i < appTotalSlides; i++) {
        const el = document.getElementById('dslide-' + i);
        if (el) {
            el.style.display = (i === appCurrentSlide) ? 'block' : 'none';
        }
    }
    
    // Contador y título
    document.getElementById('appSlideCounter').innerText = (appCurrentSlide + 1) + ' de ' + appTotalSlides;
    document.getElementById('appSlideTitleBadge').innerText = appSlideTitles[appCurrentSlide] || '';
    
    // Barra de progreso
    const pct = ((appCurrentSlide + 1) / appTotalSlides) * 100;
    document.getElementById('appProgressBar').style.width = pct + '%';
    
    // Puntos
    renderDots();
}

function appNextSlide() {
    if (appCurrentSlide < appTotalSlides - 1) {
        appCurrentSlide++;
        appUpdateSlide();
    }
}

function appPrevSlide() {
    if (appCurrentSlide > 0) {
        appCurrentSlide--;
        appUpdateSlide();
    }
}

function appGoToSlide(idx) {
    if (idx >= 0 && idx < appTotalSlides) {
        appCurrentSlide = idx;
        appUpdateSlide();
    }
}

function renderDots() {
    const cont = document.getElementById('appSlideDots');
    if (!cont) return;
    cont.innerHTML = '';
    for (let i = 0; i < appTotalSlides; i++) {
        const dot = document.createElement('button');
        dot.type = 'button';
        dot.style.width = (i === appCurrentSlide) ? '26px' : '9px';
        dot.style.height = '9px';
        dot.style.borderRadius = '999px';
        dot.style.border = 'none';
        dot.style.background = (i === appCurrentSlide) ? '#2c60a4' : '#cbd5e1';
        dot.style.cursor = 'pointer';
        dot.style.transition = 'all 0.2s ease';
        dot.title = appSlideTitles[i] || ('Diapositiva ' + (i+1));
        dot.onclick = () => appGoToSlide(i);
        cont.appendChild(dot);
    }
}

// Control por teclado en la pantalla
window.addEventListener('keydown', (e) => {
    const seccionP = document.getElementById('seccionPresentacion');
    if (seccionP && seccionP.style.display !== 'none') {
        if (e.key === 'ArrowRight' || e.key === ' ') {
            appNextSlide();
        } else if (e.key === 'ArrowLeft') {
            appPrevSlide();
        }
    }
});

function cambiarVista(vista) {
    const sPres = document.getElementById('seccionPresentacion');
    const sMan = document.getElementById('seccionManual');
    const bPres = document.getElementById('btnTabPresentacion');
    const bMan = document.getElementById('btnTabManual');
    
    if (vista === 'manual') {
        sPres.style.display = 'none';
        sMan.style.display = 'block';
        bPres.style.background = 'transparent';
        bPres.style.color = 'var(--fg)';
        bMan.style.background = '#2c60a4';
        bMan.style.color = '#fff';
    } else {
        sPres.style.display = 'block';
        sMan.style.display = 'none';
        bPres.style.background = '#2c60a4';
        bPres.style.color = '#fff';
        bMan.style.background = 'transparent';
        bMan.style.color = 'var(--fg)';
    }
}

// Iniciar al cargar
window.addEventListener('DOMContentLoaded', () => {
    appUpdateSlide();
});
</script>

# 💼 Power Pack - Suite Comercial (PHP + SQLite)

Plataforma comercial ágil, ligera y sin dependencias externas, optimizada para despliegue inmediato en **Hostinger** y servidores basados en Apache/LiteSpeed con soporte PHP 7.4 / 8.x.

---

## 🚀 Características
- **📊 Dashboard Comercial**: Métricas en tiempo real de leads, calificados, ganados, pipeline monetario y timeline de actividades.
- **👥 Gestión de Contactos**: Directorio con buscador en tiempo real, filtros por etapa comercial y nivel de interés (1 a 5 estrellas).
- **📋 Embudo de Negocios (Pipeline)**: Seguimiento de tratos y cotizaciones en etapas (*Lead*, *Contacto Inicial*, *Calificado*, *Cotización*, *Negociación*, *Ganado*, *Perdido*).
- **📝 Ficha de Detalle de Contacto**: Registro de llamadas, reuniones, notas de seguimiento y envío directo de cotizaciones/correos.
- **⚡ Base de Datos SQLite Autónoma (`powerpack.db`)**: No requiere configurar MySQL, usuarios ni contraseñas. Se inicializa y auto-configura en la primera visita.
- **🔒 Seguridad Integrada**: Reglas en `.htaccess` para proteger la base de datos contra accesos no autorizados.

---

## 📂 Estructura del Proyecto

```text
├── .htaccess            # Protección de base de datos y configuración del servidor
├── .gitignore           # Ignora base de datos local y temporales
├── db.php               # Conexión SQLite, esquemas de tablas y datos semilla
├── index.php            # Enrutador principal, layout y estilos
├── pages/
│   ├── dashboard.php    # Resumen general y métricas
│   ├── contactos.php    # Lista y búsqueda de contactos
│   ├── detalle.php      # Vista detallada de contacto y actividades
│   ├── nuevo.php        # Formulario de nuevo contacto
│   ├── pipeline.php     # Tablero de negocios
│   └── nuevo_negocio.php # Formulario de nueva oportunidad comercial
└── README.md            # Documentación del proyecto
```

---

## 🌐 Despliegue en Hostinger

### Opción 1: Conexión Automática con GitHub (Recomendada)
1. En tu panel de **Hostinger (hPanel)** ve a la sección **Avanzado > Git**.
2. Conecta la URL de este repositorio de GitHub.
3. Define la rama como `main` y el directorio de destino (`public_html` o la carpeta de tu subdominio).
4. Haz clic en **Crear**. Cada vez que hagas `git push`, Hostinger desplegará los cambios automáticamente.

### Opción 2: Subida por Administrador de Archivos / FTP
1. Sube todos los archivos directamente a la carpeta `public_html` de tu dominio o subdominio.
2. Abre tu dominio en el navegador (`https://tudominio.com`). El sistema creará la base de datos `powerpack.db` automáticamente en la primera carga.

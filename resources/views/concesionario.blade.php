<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Concesionario | Gestión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="app-shell">
        <header class="topbar">
            <a class="brand" href="/" aria-label="Concesionario, inicio">
                <span class="brand-mark">C</span>
                <span>CONCESIONARIO<small>Gestión automotriz</small></span>
            </a>
            <span class="api-status"><span class="status-dot"></span> Sistema de gestión</span>
        </header>

        <section class="page-heading">
            <div>
                <p class="eyebrow">PANEL DE ADMINISTRACIÓN</p>
                <h1>Tu taller, bajo control.</h1>
                <p class="intro">Administra clientes, vehículos y revisiones técnicas desde un solo lugar.</p>
            </div>
            <div class="heading-decoration" aria-hidden="true">01 <span>—</span> 03</div>
        </section>

        <section class="summary-grid" aria-label="Resumen">
            <article class="summary-card">
                <span class="summary-icon icon-client" aria-hidden="true">C</span>
                <div><p>Clientes</p><strong id="count-clientes">—</strong></div>
                <span class="summary-caption">registrados</span>
            </article>
            <article class="summary-card">
                <span class="summary-icon icon-car" aria-hidden="true">V</span>
                <div><p>Vehículos</p><strong id="count-coches">—</strong></div>
                <span class="summary-caption">en el concesionario</span>
            </article>
            <article class="summary-card">
                <span class="summary-icon icon-review" aria-hidden="true">R</span>
                <div><p>Revisiones</p><strong id="count-revisiones">—</strong></div>
                <span class="summary-caption">realizadas</span>
            </article>
        </section>

        <section class="workspace">
            <nav class="tabs" aria-label="Secciones de gestión">
                <button class="tab is-active" type="button" data-view="clientes">Clientes</button>
                <button class="tab" type="button" data-view="coches">Vehículos</button>
                <button class="tab" type="button" data-view="revisiones">Revisiones</button>
            </nav>

            <div class="toolbar">
                <div>
                    <h2 id="section-title">Clientes</h2>
                    <p id="section-description">Información de las personas y sus vehículos.</p>
                </div>
                <button class="button button-primary" id="create-record" type="button">
                    <span aria-hidden="true">＋</span> Nuevo cliente
                </button>
            </div>

            <form class="filters" id="filters" hidden></form>
            <div class="notice" id="notice" role="status" aria-live="polite" hidden></div>

            <div class="table-wrap">
                <table>
                    <thead id="table-head"></thead>
                    <tbody id="table-body"></tbody>
                </table>
                <div class="empty-state" id="empty-state" hidden>
                    <span class="empty-icon" aria-hidden="true">◇</span>
                    <strong>No hay registros todavía</strong>
                    <p>Crea un registro nuevo para empezar.</p>
                </div>
            </div>
            <footer class="table-footer"><span id="record-count">0 registros</span><span>API REST · Datos sincronizados</span></footer>
        </section>
        <p class="footer-note">CONCESIONARIO &amp; TALLER AUTOMOTRIZ <span>·</span> PANEL DE GESTIÓN</p>
    </main>

    <dialog id="record-dialog" class="record-dialog">
        <form id="record-form" class="record-form">
            <div class="dialog-heading">
                <div><p class="eyebrow" id="dialog-eyebrow">REGISTRO</p><h2 id="dialog-title">Nuevo registro</h2></div>
                <button class="close-button" id="close-dialog" type="button" aria-label="Cerrar">×</button>
            </div>
            <div class="form-fields" id="form-fields"></div>
            <p class="form-error" id="form-error" role="alert" hidden></p>
            <div class="dialog-actions">
                <button class="button button-secondary" id="cancel-dialog" type="button">Cancelar</button>
                <button class="button button-primary" id="save-record" type="submit">Guardar registro</button>
            </div>
        </form>
    </dialog>
</body>
</html>

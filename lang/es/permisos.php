<?php

declare(strict_types=1);

return [
    'admin-panel' => ['nombre' => 'Panel', 'permisos' => [
        'admin-panel.access' => 'Ingresar al panel',
    ]],
    'usuarios' => ['nombre' => 'Usuarios', 'permisos' => [
        'usuarios.gestionar' => 'Gestionar usuarios, roles y permisos',
    ]],
    'academico' => ['nombre' => 'Académico', 'permisos' => [
        'academico.ver' => 'Ver la oferta académica',
        'academico.gestionar' => 'Gestionar la oferta académica',
    ]],
    'personas' => ['nombre' => 'Personas', 'permisos' => [
        'personas.ver' => 'Ver personas',
        'personas.gestionar' => 'Gestionar personas',
    ]],
    'matricula' => ['nombre' => 'Matrícula', 'permisos' => [
        'matricula.ver' => 'Ver matrículas',
        'matricula.gestionar' => 'Gestionar matrículas, traslados y retiros',
    ]],
    'clasificacion' => ['nombre' => 'Clasificación', 'permisos' => [
        'clasificacion.ver' => 'Ver solicitudes de examen de clasificación',
        'clasificacion.evaluar' => 'Registrar resultados de clasificación',
    ]],
    'pagos' => ['nombre' => 'Pagos', 'permisos' => [
        'pagos.ver' => 'Ver pagos',
        'pagos.registrar' => 'Registrar pagos',
        'pagos.validar' => 'Validar pagos',
        'pagos.anular' => 'Anular pagos',
        'pagos.configurar' => 'Configurar conceptos y tarifas',
        'pagos.reportar' => 'Ver reportes de pagos',
    ]],
    'docencia' => ['nombre' => 'Docencia', 'permisos' => [
        'docencia.ver' => 'Ver docentes y carga académica',
        'docencia.gestionar' => 'Gestionar docentes y carga académica',
        'docencia.costos' => 'Gestionar costos por hora',
        'docencia.reportar' => 'Ver reportes de docencia',
    ]],
    'certificacion' => ['nombre' => 'Certificación', 'permisos' => [
        'certificacion.actas' => 'Gestionar actas',
        'certificacion.documentos' => 'Emitir y anular constancias y certificados',
    ]],
    'tramites' => ['nombre' => 'Trámites', 'permisos' => [
        'tramites.gestionar' => 'Atender trámites',
        'tramites.configurar' => 'Configurar tipos de trámite',
    ]],
    'tablero' => ['nombre' => 'Tablero gerencial', 'permisos' => [
        'tablero.ver' => 'Ver el tablero gerencial',
    ]],
];

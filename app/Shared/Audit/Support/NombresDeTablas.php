<?php

declare(strict_types=1);

namespace App\Shared\Audit\Support;

final class NombresDeTablas
{
    private const array NOMBRES = [
        'per_personas' => 'Personas',
        'per_tipos_vinculo' => 'Tipos de vínculo',
        'per_tipos_documento' => 'Tipos de documento',
        'aca_modalidades' => 'Modalidades',
        'doc_bancos' => 'Bancos',
        'tra_grados_fut' => 'Grados del FUT',
        'aca_idiomas' => 'Idiomas',
        'aca_ciclos' => 'Ciclos',
        'aca_periodos' => 'Periodos',
        'aca_aulas' => 'Aulas',
        'aca_programas' => 'Programas',
        'aca_grupos' => 'Grupos',
        'aca_horarios' => 'Horarios',
        'aca_esquemas_evaluacion' => 'Sistemas de evaluación',
        'aca_componentes_evaluacion' => 'Componentes de evaluación',
        'mat_matriculas' => 'Matrículas',
        'mat_movimientos' => 'Traslados y retiros',
        'mat_solicitudes_clasificacion' => 'Solicitudes de clasificación',
        'mat_habilitaciones' => 'Ciclos habilitados',
        'pag_pagos' => 'Pagos',
        'pag_conceptos' => 'Conceptos de pago',
        'pag_tarifas' => 'Tarifas',
        'pag_medios_pago' => 'Medios de pago',
        'pag_fuentes_concepto' => 'Fuentes TUPA/TUSNE',
        'pag_configuracion' => 'Configuración de pagos',
        'doc_docentes' => 'Docentes',
        'doc_docente_idiomas' => 'Idiomas del docente',
        'doc_disponibilidades' => 'Disponibilidad docente',
        'doc_cargas_academicas' => 'Carga académica',
        'doc_clases_dictadas' => 'Clases dictadas',
        'doc_asistencias' => 'Asistencias',
        'doc_delegados' => 'Delegados',
        'doc_notas_parciales' => 'Notas parciales',
        'doc_costos_hora' => 'Costos por hora',
        'doc_condiciones_laborales' => 'Condiciones laborales',
        'doc_grados_academicos' => 'Grados académicos',
        'cer_actas' => 'Actas finales',
        'cer_notas_finales' => 'Notas finales',
        'cer_rectificaciones' => 'Rectificaciones de actas',
        'cer_documentos' => 'Constancias y certificados',
        'cer_firmantes' => 'Firmantes',
        'cer_denominaciones' => 'Denominación del año',
        'cer_aprobaciones' => 'Aprobaciones sin acta',
        'cer_datos_institucionales' => 'Datos institucionales',
        'tra_tipos_tramite' => 'Tipos de trámite',
        'tra_etapas' => 'Etapas de trámite',
        'tra_tramites' => 'Trámites',
        'tra_tipo_tramite_programa' => 'Programas por tipo de trámite',
        'tra_adjuntos' => 'Adjuntos de trámites',
        'roles' => 'Roles',
        'model_has_roles' => 'Roles de las cuentas',
        'model_has_permissions' => 'Permisos de las cuentas',
        'role_has_permissions' => 'Permisos de los roles',
    ];

    /**
     * @return array<string, string> every audited table by its label, for the filter (no scan of the growing log)
     */
    public static function todas(): array
    {
        return collect(self::NOMBRES)->sort()->all();
    }

    public static function de(string $tabla): string
    {
        return self::NOMBRES[$tabla] ?? $tabla;
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Periodos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

final class PeriodoForm
{
    public const string PERIODO_REPETIDO = 'Ya existe un periodo con ese número en el año.';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('anio')->label('Año')->required()->integer()->minValue(2000)->maxValue(2100),
                TextInput::make('numero')
                    ->label('Número (mes o periodo)')
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(12)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('anio', $get('anio')))
                    ->validationMessages(['unique' => self::PERIODO_REPETIDO]),
                TextInput::make('nombre')->label('Nombre')->required()->maxLength(60)->columnSpanFull(),
                DatePicker::make('fecha_inicio')->label('Inicio de clases')->required(),
                DatePicker::make('fecha_fin')->label('Fin de clases')->required()->after('fecha_inicio'),
                DatePicker::make('matricula_inicio')->label('Matrícula desde')->required(),
                DatePicker::make('matricula_fin')
                    ->label('Matrícula hasta')
                    ->required()
                    ->afterOrEqual('matricula_inicio')
                    ->beforeOrEqual('fecha_fin')
                    ->helperText('La matrícula no puede terminar después del fin de clases.'),
                DatePicker::make('actas_inicio')
                    ->label('Actas finales desde')
                    ->requiredWith('actas_fin')
                    ->helperText('Días en que el docente puede validar y cerrar el acta final.'),
                DatePicker::make('actas_fin')
                    ->label('Actas finales hasta')
                    ->requiredWith('actas_inicio')
                    ->afterOrEqual('actas_inicio'),
            ]);
    }
}

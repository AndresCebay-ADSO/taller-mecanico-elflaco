<?php

namespace App\Services\DataImport;

/**
 * Hojas y columnas de la plantilla de carga inicial (resources/templates/plantilla_carga_inicial.xlsx).
 * Debe coincidir con resources/templates/generar_plantilla.py. Las columnas se reconocen por su
 * encabezado (sin el «*»), no por su posición.
 */
final class ImportSchema
{
    public const SUPPLIERS = 'Proveedores';
    public const PRODUCTS = 'Productos';
    public const MECHANICS = 'Mecánicos';
    public const JOB_TYPES = 'Tipos de trabajo';

    /** Hoja => [clave interna => encabezado]. El orden de las hojas es el orden de importación. */
    public const SHEETS = [
        self::SUPPLIERS => [
            'name' => 'Nombre',
            'phone' => 'Teléfono',
            'email' => 'Correo',
            'address' => 'Dirección',
        ],
        self::PRODUCTS => [
            'name' => 'Nombre',
            'category' => 'Categoría',
            'upc' => 'Código',
            'supplier' => 'Proveedor',
            'cost' => 'Costo unitario',
            'price' => 'Precio de venta',
            'stock' => 'Stock actual',
            'min_stock' => 'Stock mínimo',
        ],
        self::MECHANICS => [
            'name' => 'Nombre',
            'phone' => 'Teléfono',
            'hire_date' => 'Fecha de ingreso',
            'email' => 'Correo',
        ],
        self::JOB_TYPES => [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'calculation' => 'Cálculo',
            'mechanic_percentage' => '% mecánico',
            'fixed_mechanic' => 'Monto mecánico',
            'fixed_workshop' => 'Monto taller',
            'allow_products' => '¿Usa repuestos?',
        ],
    ];

    public static function template(): string
    {
        return resource_path('templates/plantilla_carga_inicial.xlsx');
    }
}

# Guía Técnica: Controladores y Rutas - Taller Mecanico

Esta guía explica la arquitectura implementada para el manejo de la lógica de negocio y la navegación del sistema.

## 1. Arquitectura: Resource Controllers

Hemos optado por usar **Resource Controllers** para asegurar la consistencia en todo el proyecto. Cada controlador maneja una entidad (ej: Proveedores, Productos) siguiendo el patrón RESTful.

### Métodos Estándar

| Método | Acción | Ruta | Propósito |
| :--- | :--- | :--- | :--- |
| `index` | GET | `/entidad` | Lista todos los elementos. |
| `create` | GET | `/entidad/create` | Muestra el formulario de creación. |
| `store` | POST | `/entidad` | Guarda un nuevo elemento en la DB. |
| `show` | GET | `/entidad/{id}` | Muestra un elemento específico. |
| `edit` | GET | `/entidad/{id}/edit` | Muestra el formulario de edición. |
| `update` | PUT/PATCH | `/entidad/{id}` | Actualiza un elemento existente. |
| `destroy` | DELETE | `/entidad/{id}` | Elimina un elemento de la DB. |

## 2. Definición de Rutas

Todas las rutas se encuentran centralizadas en `routes/web.php`. Al usar `Route::resource()`, Laravel genera automáticamente las 7 rutas mencionadas anteriormente.

```php
Route::resource('suppliers', SupplierController::class);
Route::resource('products', ProductController::class);
// ...
```

## 3. Lógica de los Controladores

### Inyección de Modelos (Model Binding)
Como Senior, he implementado **Route Model Binding**. Esto significa que Laravel busca automáticamente el registro en la base de datos basándose en el ID de la URL y lo inyecta directamente en el método del controlador.

Ejemplo en `SupplierController`:
```php
public function show(Supplier $supplier) // $supplier ya es el objeto cargado desde la DB
{
    return view('suppliers.show', compact('supplier'));
}
```

### Validación
Cada método `store` y `update` utiliza `$request->validate([...])` para asegurar la integridad de los datos antes de persistirlos.

## 4. Próximos Pasos

1. **Vistas (Blade)**: Crear los archivos `.blade.php` en `resources/views/`.
2. **Form Requests**: Mover las validaciones a clases separadas para mantener los controladores aún más limpios.
3. **SweetAlert/Toasts**: Implementar notificaciones visuales para el usuario.

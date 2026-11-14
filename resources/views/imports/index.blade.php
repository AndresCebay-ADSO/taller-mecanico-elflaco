<x-app-layout>
<div class="ds mx-auto max-w-3xl">
    <x-page-header title="Importar datos" subtitle="Carga inicial de proveedores, productos con su stock, mecánicos y tipos de trabajo desde Excel" />

    <div class="space-y-6">
        <x-card title="1. Descarga la plantilla">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-muted-foreground">
                    Trae una hoja por tipo de dato, listas desplegables y una hoja de instrucciones con ejemplos.
                    Las hojas para llenar vienen vacías.
                </p>
                <x-button variant="secondary" href="{{ route('imports.template') }}" class="shrink-0">
                    <i data-lucide="download" class="size-4"></i>
                    Descargar plantilla
                </x-button>
            </div>
        </x-card>

        <x-card title="2. Sube el archivo lleno">
            <form action="{{ route('imports.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label for="file" class="block text-sm font-medium">Archivo de Excel (.xlsx) <span class="text-destructive-text">*</span></label>
                    <input id="file" name="file" type="file" required accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           class="block w-full cursor-pointer text-sm text-muted-foreground file:mr-3 file:cursor-pointer file:rounded-md file:border file:border-border file:bg-card file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-foreground hover:file:bg-muted">
                    @error('file')
                        <p class="text-xs text-destructive-text">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-col gap-3 rounded-md bg-muted/60 px-3 py-2.5 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>Productos y mecánicos entran en <strong class="font-medium text-foreground">{{ $branch?->name }}</strong>. Proveedores y tipos de trabajo son de todas las sedes.</span>
                </div>
                <div class="flex justify-end">
                    <x-button type="submit">Revisar archivo</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="3. Revisa y confirma">
            <ul class="list-inside list-disc space-y-1 text-sm text-muted-foreground">
                <li>Primero se revisa todo <strong class="font-medium text-foreground">sin guardar nada</strong>: verás qué se va a crear y los errores fila por fila.</li>
                <li>Si hay errores, se corrigen en el Excel y se vuelve a subir. Nada se importa a medias.</li>
                <li>Lo que ya existe con el mismo nombre se omite: no se duplica ni se cambia.</li>
                <li>El stock que se escriba entra como primer lote, con su costo y proveedor (queda en la trazabilidad).</li>
            </ul>
        </x-card>
    </div>
</div>
</x-app-layout>

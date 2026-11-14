<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\DataImport\ImportSchema;
use App\Services\DataImport\WorkbookImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Carga inicial desde la plantilla de Excel, en dos pasos: subir y revisar (no escribe nada), luego confirmar.
 * El archivo revisado queda en storage/app/private/imports hasta que se confirma o se descarta.
 */
class DataImportController extends Controller
{
    private const DIR = 'imports';

    public function index()
    {
        $this->forgetAbandonedFiles();

        return view('imports.index', ['branch' => Branch::find($this->requireCurrentBranchId())]);
    }

    public function template()
    {
        return response()->download(ImportSchema::template(), 'plantilla_carga_inicial_taller.xlsx');
    }

    public function preview(Request $request, WorkbookImporter $importer)
    {
        $request->validate(
            ['file' => 'required|file|mimes:xlsx|max:5120'],
            ['file.required' => 'Elige el archivo de Excel.', 'file.mimes' => 'El archivo debe ser la plantilla en formato .xlsx.', 'file.max' => 'El archivo no puede pesar más de 5 MB.']
        );

        $branchId = $this->requireCurrentBranchId();
        $token = (string) Str::uuid();
        $request->file('file')->storeAs(self::DIR, "{$token}.xlsx", 'local');

        try {
            $report = $importer->analyze($this->path($token), $branchId);
        } catch (RuntimeException $e) {
            Storage::disk('local')->delete(self::DIR . "/{$token}.xlsx");

            return back()->with('error', $e->getMessage());
        }

        return view('imports.preview', [
            'report' => $report,
            'token' => $token,
            'fileName' => $request->file('file')->getClientOriginalName(),
            'branch' => Branch::find($branchId),
        ]);
    }

    public function confirm(Request $request, WorkbookImporter $importer)
    {
        $token = (string) $request->input('token');
        abort_unless(Str::isUuid($token) && Storage::disk('local')->exists(self::DIR . "/{$token}.xlsx"), 404);

        $report = $importer->import($this->path($token), $this->requireCurrentBranchId());
        Storage::disk('local')->delete(self::DIR . "/{$token}.xlsx");

        if ($report->hasErrors()) {
            // Algo cambió entre la revisión y la confirmación (p. ej. alguien creó un registro igual).
            return redirect()->route('imports.index')->with('error', 'No se importó nada: al volver a revisar el archivo aparecieron errores. Súbelo de nuevo.');
        }

        $summary = collect(ImportSchema::SHEETS)->keys()
            ->map(fn ($sheet) => $report->countCreate($sheet) ? $report->countCreate($sheet) . ' ' . mb_strtolower($sheet) : null)
            ->filter()->join(', ');

        return redirect()->route('imports.index')->with('success', $summary ? "Importación lista: {$summary}." : 'No había nada nuevo para importar.');
    }

    public function discard(Request $request)
    {
        $token = (string) $request->input('token');
        if (Str::isUuid($token)) {
            Storage::disk('local')->delete(self::DIR . "/{$token}.xlsx");
        }

        return redirect()->route('imports.index');
    }

    /** Archivos que se revisaron pero nunca se confirmaron ni descartaron (se cerró la pestaña). */
    private function forgetAbandonedFiles(): void
    {
        $disk = Storage::disk('local');
        $limit = now()->subDay()->getTimestamp();

        foreach ($disk->files(self::DIR) as $file) {
            if ($disk->lastModified($file) < $limit) {
                $disk->delete($file);
            }
        }
    }

    private function path(string $token): string
    {
        return Storage::disk('local')->path(self::DIR . "/{$token}.xlsx");
    }
}

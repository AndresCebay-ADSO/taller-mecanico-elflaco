<x-app-layout>
    <x-page-header title="Ventas" subtitle="Registro de ventas directas de productos">
        <x-slot name="actions">
            <x-button variant="primary" onclick="window.location.href='{{ route('sales.create') }}'">
                <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                Nueva Venta
            </x-button>
        </x-slot>
    </x-page-header>

    <!-- Tarjeta de Resumen -->
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-slate-200 border-t-4 border-t-emerald-500 overflow-hidden">
        <div class="p-6 flex justify-between items-center">
            <div>
                <p class="text-sm font-medium text-slate-500 mb-1">Ventas de Hoy</p>
                <h3 class="text-3xl font-bold text-slate-900">${{ number_format($todayTotal) }}</h3>
                <p class="text-sm text-slate-500 mt-1">{{ $todayCount }} venta(s)</p>
            </div>
            <div class="bg-emerald-100 p-4 rounded-xl flex items-center justify-center">
                <i data-lucide="shopping-cart" class="w-8 h-8 text-emerald-600"></i>
            </div>
        </div>
    </div>

    <x-search-filter action="{{ route('sales.index') }}" searchPlaceholder="Cliente, producto, ID...">
        <div>
            <label for="status" class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Estado de Venta</label>
            <select name="status" id="status" class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 transition-all">
                <option value="">Cualquier estado</option>
                <option value="completada" {{ request('status') == 'completada' ? 'selected' : '' }}>Completada</option>
                <option value="anulada" {{ request('status') == 'anulada' ? 'selected' : '' }}>Anulada</option>
            </select>
        </div>
        <div>
            <label for="payment_method" class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Método de Pago</label>
            <select name="payment_method" id="payment_method" class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 transition-all">
                <option value="">Cualquier método</option>
                <option value="Efectivo" {{ request('payment_method') == 'Efectivo' ? 'selected' : '' }}>Efectivo</option>
                <option value="Transferencia" {{ request('payment_method') == 'Transferencia' ? 'selected' : '' }}>Transferencia</option>
                <option value="Tarjeta" {{ request('payment_method') == 'Tarjeta' ? 'selected' : '' }}>Tarjeta</option>
                <option value="Otro" {{ request('payment_method') == 'Otro' ? 'selected' : '' }}>Otro</option>
            </select>
        </div>
        <div>
            <label for="date_start" class="block text-xs font-bold text-slate-400 uppercase mb-2 ml-1">Fecha</label>
            <input type="date" name="date_start" id="date_start" value="{{ request('date_start') }}" class="w-full rounded-2xl border-slate-200 bg-white shadow-sm focus:border-blue-500 focus:ring-blue-500 py-4 px-4 text-sm font-bold text-slate-700 transition-all text-center">
        </div>
    </x-search-filter>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-4">Fecha</th>
                        <th class="px-6 py-4">Cliente / Info</th>
                        <th class="px-6 py-4">Productos</th>
                        <th class="px-6 py-4">Total</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-900">{{ \Carbon\Carbon::parse($sale->created_at)->translatedFormat('d M Y') }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ \Carbon\Carbon::parse($sale->created_at)->format('H:i') }} | #S{{ str_pad($sale->id, 4, '0', STR_PAD_LEFT) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-slate-900">{{ $sale->customer_name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                    <span class="inline-block px-1.5 py-0.5 bg-slate-100 rounded text-slate-600">{{ $sale->payment_method }}</span>
                                    <span>•</span>
                                    <span>Resp: {{ $sale->user?->name ?? 'Sistema' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1.5">
                                    @foreach($sale->saleProducts as $item)
                                        <div class="flex items-center text-sm text-slate-700">
                                            <i data-lucide="package" class="w-3.5 h-3.5 text-blue-500 mr-2 flex-shrink-0"></i>
                                            <span class="truncate max-w-[200px]">{{ $item->product->name }}</span>
                                            <span class="text-slate-500 ml-1.5 font-medium">x{{ $item->quantity }}</span>
                                        </div>
                                    @endforeach
                                    <div class="text-xs text-slate-400 mt-1 font-medium">
                                        Total: {{ $sale->total_items }} artículo(s)
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold {{ $sale->status === 'completada' ? 'text-emerald-600' : 'text-slate-400 line-through' }}">
                                    ${{ number_format($sale->total_amount, 2) }}
                                </span>
                                @if($sale->status !== 'completada')
                                    <div class="text-xs text-rose-500 font-medium mt-0.5"><i data-lucide="x-circle" class="w-3 h-3 inline mr-1"></i>Anulada</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-end gap-2">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('sales.show', $sale) }}" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Ver detalle">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                        <button onclick="window.open('{{ route('sales.show', $sale) }}?print=1', '_blank')" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors" title="Imprimir recibo">
                                            <i data-lucide="printer" class="w-4 h-4"></i>
                                        </button>
                                        @if($sale->status === 'completada')
                                        <form action="{{ route('sales.cancel', $sale) }}" method="POST" onsubmit="return confirm('¿Estás seguro de anular esta venta? El stock será devuelto.')" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Anular venta">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="bg-slate-50 p-3 rounded-full mb-3">
                                        <i data-lucide="shopping-cart" class="w-6 h-6 text-slate-400"></i>
                                    </div>
                                    <p>No hay ventas registradas.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
        <div class="px-6 py-4 border-t border-slate-200">
            {{ $sales->links() }}
        </div>
        @endif
    </div>
</x-app-layout>
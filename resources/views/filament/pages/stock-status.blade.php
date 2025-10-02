<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Statistiques -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">
                        {{ \App\Models\Product::where('track_inventory', true)->count() }}
                    </div>
                    <div class="text-sm text-gray-600">Produits suivis</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-success-600">
                        {{ number_format(\App\Models\Stock::sum('available_quantity'), 0) }}
                    </div>
                    <div class="text-sm text-gray-600">Unités disponibles</div>
                </div>
            </x-filament::section>

            {{--<x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-danger-600">
                        {{ \App\Models\Stock::whereColumn('available_quantity', '<=', 'alert_quantity')->count() }}
                    </div>
                    <div class="text-sm text-gray-600">Stocks bas</div>
                </div>
            </x-filament::section>--}}

            {{--<x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-900">
                        {{ number_format(\App\Models\Stock::sum(\DB::raw('quantity * cost_price')), 0) }} FCFA
                    </div>
                    <div class="text-sm text-gray-600">Valeur totale</div>
                </div>
            </x-filament::section>--}}
        </div>

        <!-- Table -->
        {{ $this->table }}
    </div>
</x-filament-panels::page>

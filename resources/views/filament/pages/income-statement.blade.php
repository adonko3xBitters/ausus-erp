<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filtres -->
        <x-filament::section>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Exercice comptable</label>
                    <select wire:model.live="fiscalYearId" class="block w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="">Personnalisé</option>
                        @foreach(\App\Models\FiscalYear::orderByDesc('start_date')->get() as $fy)
                            <option value="{{ $fy->id }}">{{ $fy->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <input type="date" wire:model.live="startDate" class="block w-full rounded-lg border-gray-300 shadow-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input type="date" wire:model.live="endDate" class="block w-full rounded-lg border-gray-300 shadow-sm" />
                </div>
            </div>
        </x-filament::section>

        @if($reportData)
            <!-- En-tête du rapport -->
            <x-filament::section>
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900">COMPTE DE RÉSULTAT</h2>
                    <p class="text-sm text-gray-600 mt-2">
                        Du {{ \Carbon\Carbon::parse($reportData['period']['start_date'])->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($reportData['period']['end_date'])->format('d/m/Y') }}
                    </p>
                </div>
            </x-filament::section>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- CHARGES (Classe 6) -->
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-bold text-red-600">CHARGES</span>
                            <span class="text-lg font-bold text-red-600">
                                {{ number_format($reportData['expenses']['total'], 0, ',', ' ') }} {{currency()->symbol}}
                            </span>
                        </div>
                    </x-slot>

                    <div class="space-y-2">
                        @foreach($reportData['expenses']['accounts'] as $account)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <div>
                                    <span class="font-medium text-gray-700">{{ $account['code'] }}</span>
                                    <span class="text-gray-600 ml-2">{{ $account['name'] }}</span>
                                </div>
                                <span class="font-medium text-red-600">
                                    {{ number_format(abs($account['balance']), 0, ',', ' ') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                <!-- PRODUITS (Classe 7) -->
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-bold text-green-600">PRODUITS</span>
                            <span class="text-lg font-bold text-green-600">
                                {{ number_format($reportData['revenues']['total'], 0, ',', ' ') }} {{currency()->symbol}}
                            </span>
                        </div>
                    </x-slot>

                    <div class="space-y-2">
                        @foreach($reportData['revenues']['accounts'] as $account)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <div>
                                    <span class="font-medium text-gray-700">{{ $account['code'] }}</span>
                                    <span class="text-gray-600 ml-2">{{ $account['name'] }}</span>
                                </div>
                                <span class="font-medium text-green-600">
                                    {{ number_format(abs($account['balance']), 0, ',', ' ') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            <!-- RÉSULTAT NET -->
            <x-filament::section>
                <div class="py-6">
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-bold text-gray-900">RÉSULTAT NET</span>
                        <span class="text-3xl font-bold {{ $reportData['net_income'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($reportData['net_income'], 0, ',', ' ') }} {{currency()->symbol}}
                        </span>
                    </div>
                    <div class="mt-4 text-center">
                        @if($reportData['net_income'] >= 0)
                            <p class="text-lg text-green-600">✓ Bénéfice</p>
                        @else
                            <p class="text-lg text-red-600">✗ Perte</p>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

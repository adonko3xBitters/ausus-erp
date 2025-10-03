<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filtres -->
        <x-filament::section>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            <!-- En-tête -->
            <x-filament::section>
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900">FLUX DE TRÉSORERIE</h2>
                    <p class="text-sm text-gray-600 mt-2">
                        Du {{ \Carbon\Carbon::parse($reportData['period']['start_date'])->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($reportData['period']['end_date'])->format('d/m/Y') }}
                    </p>
                </div>
            </x-filament::section>

            <!-- Résumé -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament::section>
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Solde initial</p>
                        <p class="text-2xl font-bold text-gray-900 mt-2">
                            {{ number_format($reportData['initial_balance'], 0, ',', ' ') }} {{currency()->symbol}}
                        </p>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Flux de la période</p>
                        <p class="text-2xl font-bold {{ $reportData['cash_flow'] >= 0 ? 'text-green-600' : 'text-red-600' }} mt-2">
                            {{ $reportData['cash_flow'] >= 0 ? '+' : '' }}{{ number_format($reportData['cash_flow'], 0, ',', ' ') }}
                            {{currency()->symbol}}
                        </p>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <p class="text-sm text-gray-600">Solde final</p>
                        <p class="text-2xl font-bold text-blue-600 mt-2">
                            {{ number_format($reportData['final_balance'], 0, ',', ' ') }} {{currency()->symbol}}
                        </p>
                    </div>
                </x-filament::section>
            </div>

            <!-- Détails par compte -->
            <x-filament::section>
                <x-slot name="heading">
                    Détails par compte de trésorerie
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compte</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Solde</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($reportData['accounts'] as $account)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ $account['code'] }}</td>
                                <td class="px-6 py-4 text-sm">{{ $account['name'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $account['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($account['balance'], 0, ',', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

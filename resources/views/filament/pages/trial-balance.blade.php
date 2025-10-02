<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filtres -->
        <x-filament::section>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Exercice comptable</label>
                    <select wire:model.live="fiscalYearId" class="block w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="">Tous</option>
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

        @if($balanceData)
            <!-- En-tête du rapport -->
            <x-filament::section>
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900">BALANCE GÉNÉRALE</h2>
                    <p class="text-sm text-gray-600 mt-2">
                        Du {{ \Carbon\Carbon::parse($balanceData['period']['start_date'])->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($balanceData['period']['end_date'])->format('d/m/Y') }}
                    </p>
                </div>
            </x-filament::section>

            <!-- Tableau de la balance -->
            <x-filament::section>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compte</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Débit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Crédit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Solde</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($balanceData['accounts'] as $account)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $account['account_code'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $account['account_name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">
                                    {{ number_format($account['debit'], 2, ',', ' ') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                                    {{ number_format($account['credit'], 2, ',', ' ') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $account['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($account['balance'], 2, ',', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold">
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-sm text-gray-900 uppercase">Total</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">
                                {{ number_format($balanceData['totals']['debit'], 2, ',', ' ') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                                {{ number_format($balanceData['totals']['credit'], 2, ',', ' ') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $balanceData['totals']['difference'] == 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($balanceData['totals']['difference'], 2, ',', ' ') }}
                            </td>
                        </tr>
                        @if(abs($balanceData['totals']['difference']) > 0.01)
                            <tr>
                                <td colspan="5" class="px-6 py-2 text-center text-sm text-red-600">
                                    ⚠️ Attention : La balance n'est pas équilibrée !
                                </td>
                            </tr>
                        @endif
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

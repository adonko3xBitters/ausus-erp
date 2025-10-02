<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filtres -->
        <x-filament::section>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Compte</label>
                    <select wire:model.live="accountId" class="block w-full rounded-lg border-gray-300 shadow-sm">
                        <option value="">Sélectionner un compte</option>
                        @foreach(\App\Models\Account::where('is_active', true)->orderBy('code')->get() as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <input type="date" wire:model.live="startDate" class="block w-full rounded-lg border-gray-300 shadow-sm"/>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input type="date" wire:model.live="endDate" class="block w-full rounded-lg border-gray-300 shadow-sm" />
                </div>
            </div>
        </x-filament::section>

        @if($ledgerData)
            <!-- En-tête du rapport -->
            <x-filament::section>
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900">GRAND LIVRE</h2>
                    <p class="text-lg text-gray-700 mt-2">
                        Compte {{ $ledgerData['account']['code'] }} - {{ $ledgerData['account']['name'] }}
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        Du {{ \Carbon\Carbon::parse($ledgerData['period']['start_date'])->format('d/m/Y') }}
                        au {{ \Carbon\Carbon::parse($ledgerData['period']['end_date'])->format('d/m/Y') }}
                    </p>
                </div>
            </x-filament::section>

            <!-- Solde initial -->
            <x-filament::section>
                <div class="flex justify-between items-center">
                    <span class="font-medium">Solde initial :</span>
                    <span class="text-lg font-bold {{ $ledgerData['previous_balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($ledgerData['previous_balance'], 2, ',', ' ') }} {{ currency()->symbol  }}
                    </span>
                </div>
            </x-filament::section>

            <!-- Tableau des mouvements -->
            <x-filament::section>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">N° Écriture</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Journal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Libellé</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Débit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Crédit</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Solde</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($ledgerData['transactions'] as $transaction)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $transaction['date'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    {{ $transaction['entry_number'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $transaction['journal'] }}</td>
                                <td class="px-6 py-4 text-sm">{{ $transaction['description'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">
                                    @if($transaction['debit'] > 0)
                                        {{ number_format($transaction['debit'], 2, ',', ' ') }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                                    @if($transaction['credit'] > 0)
                                        {{ number_format($transaction['credit'], 2, ',', ' ') }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium {{ $transaction['balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ number_format($transaction['balance'], 2, ',', ' ') }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <!-- Solde final -->
            <x-filament::section>
                <div class="flex justify-between items-center">
                    <span class="font-medium">Solde final :</span>
                    <span class="text-xl font-bold {{ $ledgerData['final_balance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($ledgerData['final_balance'], 2, ',', ' ') }} {{ currency()->symbol  }}
                    </span>
                </div>
            </x-filament::section>
        @elseif($accountId)
            <x-filament::section>
                <div class="text-center text-gray-500">
                    Aucun mouvement pour ce compte durant la période sélectionnée.
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center text-gray-500">
                    Veuillez sélectionner un compte pour afficher son grand livre.
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

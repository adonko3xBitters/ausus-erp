<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filtre -->
        <x-filament::section>
            <div class="max-w-md">
                <label class="block text-sm font-medium text-gray-700 mb-1">Date du bilan</label>
                <input type="date" wire:model.live="date" class="block w-full rounded-lg border-gray-300 shadow-sm" />
            </div>
        </x-filament::section>

        @if($reportData)
            <!-- En-tête du rapport -->
            <x-filament::section>
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900">BILAN COMPTABLE</h2>
                    <p class="text-sm text-gray-600 mt-2">
                        Au {{ \Carbon\Carbon::parse($reportData['date'])->format('d/m/Y') }}
                    </p>
                </div>
            </x-filament::section>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- ACTIF -->
                <div class="space-y-4">
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-blue-600">ACTIF</span>
                                <span class="text-lg font-bold text-blue-600">
                                    {{ number_format($reportData['assets']['total'], 0, ',', ' ') }} {{currency()->symbol}}
                                </span>
                            </div>
                        </x-slot>

                        <!-- Actif immobilisé -->
                        <div class="mb-6">
                            <h3 class="text-md font-bold text-gray-800 mb-3 pb-2 border-b">Actif immobilisé</h3>
                            <div class="space-y-2">
                                @foreach($reportData['assets']['fixed_assets']['accounts'] as $account)
                                    <div class="flex justify-between items-center py-1.5">
                                        <div>
                                            <span class="font-medium text-gray-700 text-sm">{{ $account['code'] }}</span>
                                            <span class="text-gray-600 ml-2 text-sm">{{ $account['name'] }}</span>
                                        </div>
                                        <span class="font-medium text-blue-600 text-sm">
                                            {{ number_format(abs($account['balance']), 0, ',', ' ') }}
                                        </span>
                                    </div>
                                @endforeach
                                <div class="flex justify-between items-center pt-2 border-t font-bold">
                                    <span>Sous-total</span>
                                    <span class="text-blue-600">
                                        {{ number_format($reportData['assets']['fixed_assets']['total'], 0, ',', ' ') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actif circulant -->
                        <div>
                            <h3 class="text-md font-bold text-gray-800 mb-3 pb-2 border-b">Actif circulant</h3>
                            <div class="space-y-2">
                                @foreach($reportData['assets']['current_assets']['accounts'] as $account)
                                    <div class="flex justify-between items-center py-1.5">
                                        <div>
                                            <span class="font-medium text-gray-700 text-sm">{{ $account['code'] }}</span>
                                            <span class="text-gray-600 ml-2 text-sm">{{ $account['name'] }}</span>
                                        </div>
                                        <span class="font-medium text-blue-600 text-sm">
                                            {{ number_format(abs($account['balance']), 0, ',', ' ') }}
                                        </span>
                                    </div>
                                @endforeach
                                <div class="flex justify-between items-center pt-2 border-t font-bold">
                                    <span>Sous-total</span>
                                    <span class="text-blue-600">
                                        {{ number_format($reportData['assets']['current_assets']['total'], 0, ',', ' ') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </x-filament::section>
                </div>

                <!-- PASSIF -->
                <div class="space-y-4">
                    <x-filament::section>
                        <x-slot name="heading">
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-purple-600">PASSIF</span>
                                <span class="text-lg font-bold text-purple-600">
                                    {{ number_format($reportData['liabilities']['total'], 0, ',', ' ') }} {{currency()->symbol}}
                                </span>
                            </div>
                        </x-slot>

                        <!-- Capitaux propres -->
                        <div class="mb-6">
                            <h3 class="text-md font-bold text-gray-800 mb-3 pb-2 border-b">Capitaux propres</h3>
                            <div class="space-y-2">
                                @foreach($reportData['liabilities']['equity']['accounts'] as $account)
                                    <div class="flex justify-between items-center py-1.5">
                                        <div>
                                            <span class="font-medium text-gray-700 text-sm">{{ $account['code'] }}</span>
                                            <span class="text-gray-600 ml-2 text-sm">{{ $account['name'] }}</span>
                                        </div>
                                        <span class="font-medium text-purple-600 text-sm">
                                            {{ number_format(abs($account['balance']), 0, ',', ' ') }}
                                        </span>
                                    </div>
                                @endforeach
                                <div class="flex justify-between items-center pt-2 border-t font-bold">
                                    <span>Sous-total</span>
                                    <span class="text-purple-600">
                                        {{ number_format($reportData['liabilities']['equity']['total'], 0, ',', ' ') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Dettes -->
                        <div>
                            <h3 class="text-md font-bold text-gray-800 mb-3 pb-2 border-b">Dettes</h3>
                            <div class="space-y-2">
                                @foreach($reportData['liabilities']['liabilities']['accounts'] as $account)
                                    <div class="flex justify-between items-center py-1.5">
                                        <div>
                                            <span class="font-medium text-gray-700 text-sm">{{ $account['code'] }}</span>
                                            <span class="text-gray-600 ml-2 text-sm">{{ $account['name'] }}</span>
                                        </div>
                                        <span class="font-medium text-purple-600 text-sm">
                                            {{ number_format(abs($account['balance']), 0, ',', ' ') }}
                                        </span>
                                    </div>
                                @endforeach
                                <div class="flex justify-between items-center pt-2 border-t font-bold">
                                    <span>Sous-total</span>
                                    <span class="text-purple-600">
                                        {{ number_format($reportData['liabilities']['liabilities']['total'], 0, ',', ' ') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </x-filament::section>
                </div>
            </div>

            <!-- Vérification équilibre -->
            <x-filament::section>
                <div class="py-4">
                    @php
                        $difference = abs($reportData['assets']['total'] - $reportData['liabilities']['total']);
                        $isBalanced = $difference < 0.01;
                    @endphp

                    @if($isBalanced)
                        <div class="text-center">
                            <p class="text-lg text-green-600 font-bold">
                                ✓ Le bilan est équilibré
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                Actif = Passif = {{ number_format($reportData['assets']['total'], 0, ',', ' ') }} {{currency()->symbol}}
                            </p>
                        </div>
                    @else
                        <div class="text-center">
                            <p class="text-lg text-red-600 font-bold">
                                ⚠️ Le bilan n'est pas équilibré
                            </p>
                            <p class="text-sm text-gray-600 mt-1">
                                Différence : {{ number_format($difference, 0, ',', ' ') }} {{currency()->symbol}}
                            </p>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

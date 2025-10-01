<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Filtres de date -->
        <x-filament::section>
            <div class="flex gap-4">
                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="startDate"
                        />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="date"
                            wire:model.live="endDate"
                        />
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <!-- Statistiques -->
        @livewire(\Filament\Widgets\StatsOverviewWidget::class)

        <!-- Dépenses par catégorie -->
        <x-filament::section>
            <x-slot name="heading">
                Dépenses par catégorie
            </x-slot>

            <div class="overflow-hidden">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Catégorie</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Montant</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach($this->getExpensesByCategory() as $item)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $item['category'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-medium">
                                {{ number_format($item['total'], 0) }} FCFA
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

<x-filament-panels::page>
    <form wire:submit="download">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="[
                \Filament\Actions\Action::make('download')
                    ->label('Unduh PDF')
                    ->submit('download'),
            ]"
        />
    </form>
</x-filament-panels::page>

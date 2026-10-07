<x-filament-panels::page>
    <div class="space-y-8">
        <section class="rounded-xl bg-white p-6">
            <h2 class="text-lg font-semibold">Límites activos</h2>
            <table class="mt-4 w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500">
                        <th class="py-2">Sujeto</th>
                        <th>Strikes</th>
                        <th>Hasta</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blocks as $block)
                        <tr class="border-t border-gray-100">
                            <td class="py-2">{{ $block->subject }}</td>
                            <td>{{ $block->strikes }}</td>
                            <td>{{ $block->locked_until?->format('d/m H:i') ?? '—' }}</td>
                            <td class="text-right">
                                <button type="button" wire:click="lift({{ $block->id }})" class="underline">Levantar</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="py-3 text-gray-500" colspan="4">No hay bloqueos de descarga.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="rounded-xl bg-white p-6">
            <h2 class="text-lg font-semibold">IPs bloqueadas</h2>
            <form wire:submit="ban" class="mt-4 flex flex-wrap gap-3">
                <input wire:model="ip" placeholder="IP" class="rounded-lg border border-gray-200 px-3 py-2">
                <input wire:model="reason" placeholder="Motivo" class="rounded-lg border border-gray-200 px-3 py-2">
                <button class="rounded-lg bg-black px-4 py-2 text-white">Banear</button>
            </form>
            @error('ip') <p class="mt-2 text-sm">{{ $message }}</p> @enderror
            <table class="mt-4 w-full text-sm">
                <tbody>
                    @forelse ($bans as $ban)
                        <tr class="border-t border-gray-100">
                            <td class="py-2">{{ $ban->ip }}</td>
                            <td>{{ $ban->reason }}</td>
                            <td class="text-right">
                                <button type="button" wire:click="unban({{ $ban->id }})" class="underline">Quitar</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="py-3 text-gray-500">No hay IPs bloqueadas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-filament-panels::page>

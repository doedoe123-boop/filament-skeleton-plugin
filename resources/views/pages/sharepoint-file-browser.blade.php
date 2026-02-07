<x-filament-panels::page>
    {{-- Breadcrumb navigation --}}
    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
        @foreach ($breadcrumbs as $index => $crumb)
            @if ($index > 0)
                <x-heroicon-m-chevron-right class="w-4 h-4 text-gray-400 dark:text-gray-500" />
            @endif

            @if ($loop->last)
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $crumb['label'] }}</span>
            @else
                <button
                    wire:click="navigateTo('{{ $crumb['path'] }}')"
                    type="button"
                    class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors"
                >
                    {{ $crumb['label'] }}
                </button>
            @endif
        @endforeach
    </div>

    {{-- Back button when not at root --}}
    @if ($currentFolder !== '/')
        <div class="mb-4">
            <x-filament::button
                wire:click="goUp"
                icon="heroicon-o-arrow-left"
                color="gray"
                size="sm"
            >
                {{ __('sharepoint::sharepoint.actions.go_back') }}
            </x-filament::button>
        </div>
    @endif

    {{-- File listing table --}}
    <div class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-ta-content overflow-x-auto">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="divide-y divide-gray-200 dark:divide-white/5">
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-start text-sm font-semibold text-gray-950 dark:text-white" style="width: 40px;"></th>
                        <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                            {{ __('sharepoint::sharepoint.table.name') }}
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                            {{ __('sharepoint::sharepoint.table.size') }}
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                            {{ __('sharepoint::sharepoint.table.last_modified') }}
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:last-of-type:pe-6 text-end text-sm font-semibold text-gray-950 dark:text-white">
                            {{ __('sharepoint::sharepoint.table.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    @forelse ($files as $file)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            {{-- Icon --}}
                            <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6 text-sm text-gray-950 dark:text-white" style="width: 40px;">
                                @if ($file['type'] === 'folder')
                                    <x-heroicon-o-folder class="w-5 h-5 text-warning-500" />
                                @else
                                    <x-heroicon-o-document class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                                @endif
                            </td>

                            {{-- Name --}}
                            <td class="fi-ta-cell px-3 py-4 text-sm text-gray-950 dark:text-white">
                                @if ($file['type'] === 'folder')
                                    <button
                                        wire:click="openFolder('{{ $file['name'] }}')"
                                        type="button"
                                        class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 transition-colors"
                                    >
                                        {{ $file['name'] }}
                                    </button>
                                @else
                                    <span>{{ $file['name'] }}</span>
                                @endif
                            </td>

                            {{-- Size --}}
                            <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if ($file['type'] === 'folder')
                                    &mdash;
                                @else
                                    {{ $this->formatBytes($file['size']) }}
                                @endif
                            </td>

                            {{-- Last Modified --}}
                            <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if ($file['lastModified'])
                                    {{ \Carbon\Carbon::parse($file['lastModified'])->format('M d, Y H:i') }}
                                @else
                                    &mdash;
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="fi-ta-cell px-3 py-4 sm:last-of-type:pe-6 text-end text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($file['type'] === 'file')
                                        <x-filament::icon-button
                                            wire:click="downloadItem('{{ $file['id'] }}', '{{ $file['name'] }}')"
                                            icon="heroicon-o-arrow-down-tray"
                                            color="gray"
                                            size="sm"
                                            :tooltip="__('sharepoint::sharepoint.actions.download')"
                                        />
                                    @endif

                                    <x-filament::icon-button
                                        wire:click="deleteItem('{{ $file['id'] }}', '{{ $file['name'] }}')"
                                        wire:confirm="{{ __('sharepoint::sharepoint.actions.delete_confirm', ['name' => $file['name']]) }}"
                                        icon="heroicon-o-trash"
                                        color="danger"
                                        size="sm"
                                        :tooltip="__('sharepoint::sharepoint.actions.delete')"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <x-heroicon-o-folder-open class="w-8 h-8 text-gray-400 dark:text-gray-500" />
                                    <p>{{ __('sharepoint::sharepoint.table.empty') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>

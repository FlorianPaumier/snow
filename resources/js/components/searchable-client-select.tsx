import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxOption,
    ComboboxOptions,
} from '@headlessui/react';
import { Check, ChevronDown, Search } from 'lucide-react';
import { useId, useState } from 'react';
import { cn } from '@/lib/utils';

type ClientOption = {
    id: string;
    clientNumber: string;
    displayName: string;
};

/**
 * Client picker backed by a Headless UI Combobox: the option list can be
 * narrowed by typing a name or a client number. The value stays the client's
 * id (so Inertia forms serialize it as a plain `clientId` string), while
 * the combobox itself works with the full option objects for display.
 */
export default function SearchableClientSelect({
    clients,
    name,
    id,
    defaultValue = '',
    placeholder = 'Choisir un client',
    searchPlaceholder = 'Aucun client correspondant',
    className,
}: {
    clients: ClientOption[];
    name: string;
    id?: string;
    defaultValue?: string;
    placeholder?: string;
    searchPlaceholder?: string;
    className?: string;
}) {
    const [query, setQuery] = useState('');
    const [selectedId, setSelectedId] = useState(defaultValue);
    const reactId = useId();
    const comboboxId = id ?? reactId;

    const selected = clients.find((client) => client.id === selectedId) ?? null;

    const filtered =
        query === ''
            ? clients
            : clients.filter((client) => {
                  const haystack = `${client.displayName} ${client.clientNumber}`;

                  return haystack.toLowerCase().includes(query.toLowerCase());
              });

    return (
        <div className={cn('relative', className)}>
            {/* Serializes the client id for the surrounding Inertia form... */}
            <input type="hidden" name={name} value={selectedId} />
            <Combobox
                nullable
                value={selected}
                onChange={(client) => setSelectedId(client?.id ?? '')}
            >
                <div className="relative">
                    <Search
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <ComboboxInput
                        id={comboboxId}
                        className="h-9 w-full rounded-md border border-input bg-transparent pr-8 pl-8 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30 dark:hover:bg-input/50"
                        placeholder={placeholder}
                        autoComplete="off"
                        displayValue={(client: ClientOption) =>
                            client?.displayName ?? ''
                        }
                        onChange={(event) => setQuery(event.target.value)}
                    />
                    <ComboboxButton
                        className="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 text-muted-foreground hover:text-foreground"
                        aria-label="Ouvrir la liste des clients"
                        data-test="client-select-open"
                    >
                        <ChevronDown aria-hidden="true" className="size-4" />
                    </ComboboxButton>
                </div>

                <ComboboxOptions
                    className="absolute z-50 mt-1 max-h-60 w-full origin-top overflow-y-auto rounded-md border bg-popover text-popover-foreground shadow-md outline-none"
                    anchor="bottom"
                >
                    {filtered.length === 0 ? (
                        <p className="px-3 py-2 text-sm text-muted-foreground">
                            {searchPlaceholder}
                        </p>
                    ) : (
                        filtered.map((client) => (
                            <ComboboxOption
                                key={client.id}
                                value={client}
                                className="relative flex cursor-default items-center justify-between gap-2 py-1.5 pr-2 pl-2 text-sm outline-hidden select-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                            >
                                {({ focus, selected: isSelected }) => (
                                    <>
                                        <span className="flex flex-col">
                                            <span
                                                className={cn(
                                                    'truncate',
                                                    focus && 'font-medium',
                                                )}
                                            >
                                                {client.displayName}
                                            </span>
                                            <span className="font-mono text-xs text-muted-foreground">
                                                {client.clientNumber}
                                            </span>
                                        </span>
                                        {isSelected && (
                                            <Check
                                                aria-hidden="true"
                                                className="size-4 shrink-0"
                                            />
                                        )}
                                    </>
                                )}
                            </ComboboxOption>
                        ))
                    )}
                </ComboboxOptions>
            </Combobox>
        </div>
    );
}

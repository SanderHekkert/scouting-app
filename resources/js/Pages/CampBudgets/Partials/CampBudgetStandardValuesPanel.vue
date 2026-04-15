<script setup>
const props = defineProps({
    campLocation: { type: String, required: true },
    standardAmountInputValue: { type: Function, required: true },
    onStandardMoneyFocus: { type: Function, required: true },
    onStandardMoneyInput: { type: Function, required: true },
    onStandardMoneyBlur: { type: Function, required: true },
});

const emit = defineEmits(['set-camp-location']);

const clubhuisFields = [
    { key: 'clubhuis_bedrag', label: 'Clubhuis' },
    { key: 'borg_bedrag', label: 'Borg' },
];

const framFields = [
    { key: 'kosten_vaart_pu', label: 'Kosten vaart p/u' },
    { key: 'kosten_aggregaat_pu', label: 'Kosten aggregaat p/u' },
    { key: 'huur_fram_pppd', label: 'Huur Fram pppd' },
];

const sharedFields = [
    { key: 'prijs_per_dag_leiding', label: 'Prijs per dag leiding' },
    { key: 'prijs_per_dag_jeugdlid', label: 'Standaard kamp prijs per Jeugdlid' },
    { key: 'proviand_pppd', label: 'Proviand pppd' },
    { key: 'groepsafdracht_pjpd', label: 'Groepsafdracht pjpd' },
    { key: 'reservering_nawaka_pjpd', label: 'Reservering NaWaKa pjpd' },
];
</script>

<template>
    <div class="rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
        <h3 class="mb-2 text-sm font-semibold text-app-ink dark:text-app-ink-dark">Standaardwaarden</h3>
        <div class="mb-3">
            <div class="inline-flex items-center rounded-full border border-app-border bg-slate-100 p-1 dark:border-app-border-dark dark:bg-slate-800">
                <button
                    type="button"
                    class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                    :class="props.campLocation === 'clubhuis' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink dark:text-app-ink-dark'"
                    @click="emit('set-camp-location', 'clubhuis')"
                >
                    Clubhuis
                </button>
                <button
                    type="button"
                    class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                    :class="props.campLocation === 'fram' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink dark:text-app-ink-dark'"
                    @click="emit('set-camp-location', 'fram')"
                >
                    Fram
                </button>
            </div>
        </div>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <label v-for="field in (props.campLocation === 'clubhuis' ? clubhuisFields : framFields)" :key="field.key" class="text-xs text-app-ink dark:text-app-ink-dark">
                {{ field.label }}
                <div class="relative mt-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span>
                    <input
                        :value="props.standardAmountInputValue(field.key)"
                        type="text"
                        inputmode="decimal"
                        class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        @focus="props.onStandardMoneyFocus(field.key)"
                        @input="props.onStandardMoneyInput(field.key, $event)"
                        @blur="props.onStandardMoneyBlur(field.key)"
                    />
                </div>
            </label>

            <label v-for="field in sharedFields" :key="field.key" class="text-xs text-app-ink dark:text-app-ink-dark">
                {{ field.label }}
                <div class="relative mt-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center rounded-l border-r border-app-border bg-slate-100 px-2 font-semibold text-slate-700 dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark">€</span>
                    <input
                        :value="props.standardAmountInputValue(field.key)"
                        type="text"
                        inputmode="decimal"
                        class="w-full rounded border border-app-border bg-white py-1.5 pl-8 pr-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        @focus="props.onStandardMoneyFocus(field.key)"
                        @input="props.onStandardMoneyInput(field.key, $event)"
                        @blur="props.onStandardMoneyBlur(field.key)"
                    />
                </div>
            </label>
        </div>
    </div>
</template>

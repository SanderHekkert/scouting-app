<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { PencilSquareIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    text: { type: String, default: '' },
    multiline: { type: Boolean, default: true },
    saving: { type: Boolean, default: false },
    /** 'text' | 'date' — date gebruikt yyyy-mm-dd intern, toont d-m-jjjj */
    inputKind: { type: String, default: 'text' },
    /** Uniek per cel, bv. `12:first_name` — koppel aan openRequest* voor knop “Bewerken” */
    cellKey: { type: String, default: '' },
    openRequestKey: { type: String, default: '' },
    openRequestNonce: { type: Number, default: 0 },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['save']);

const editing = ref(false);
const draft = ref('');
const root = ref(null);
const inputRef = ref(null);

function formatIsoToNl(value) {
    if (value == null || value === '') return '–';
    const s = String(value).slice(0, 10);
    const parts = s.split('-');
    if (parts.length !== 3) return s;
    const [y, m, d] = parts;
    return `${d}-${m}-${y}`;
}

const displayLabel = computed(() => {
    if (props.inputKind === 'date') {
        return formatIsoToNl(props.text);
    }
    const t = String(props.text ?? '').trim();
    return t === '' ? '–' : String(props.text ?? '');
});

watch(
    () => props.text,
    (t) => {
        if (!editing.value) {
            draft.value = props.inputKind === 'date' ? (t ? String(t).slice(0, 10) : '') : (t ?? '');
        }
    },
);

watch(
    () => props.openRequestNonce,
    (nonce, prevNonce) => {
        if (!props.cellKey || props.saving) {
            return;
        }
        if (!props.openRequestKey || props.openRequestKey !== props.cellKey) {
            return;
        }
        if (nonce === prevNonce) {
            return;
        }
        if (nonce === 0) {
            return;
        }
        nextTick(() => startEdit());
    },
);

function startEdit() {
    if (props.saving || props.disabled) {
        return;
    }
    if (props.inputKind === 'date') {
        draft.value = props.text ? String(props.text).slice(0, 10) : '';
    } else {
        draft.value = props.text ?? '';
    }
    editing.value = true;
    nextTick(() => inputRef.value?.focus?.());
}

function revertDraft() {
    if (props.inputKind === 'date') {
        draft.value = props.text ? String(props.text).slice(0, 10) : '';
    } else {
        draft.value = props.text ?? '';
    }
    editing.value = false;
}

function commit() {
    if (!editing.value) {
        return;
    }
    editing.value = false;
    const next = draft.value ?? '';
    const prev =
        props.inputKind === 'date'
            ? props.text
                ? String(props.text).slice(0, 10)
                : ''
            : props.text ?? '';
    if (next !== prev) {
        emit('save', next);
    }
}

function onDocPointerDown(event) {
    if (!editing.value || !root.value) {
        return;
    }
    if (root.value.contains(event.target)) {
        return;
    }
    commit();
}

onMounted(() => {
    document.addEventListener('pointerdown', onDocPointerDown, true);
});

onUnmounted(() => {
    document.removeEventListener('pointerdown', onDocPointerDown, true);
});
</script>

<template>
    <div
        ref="root"
        class="relative min-h-[1.5rem]"
        :class="{ 'pointer-events-none opacity-60': saving }"
        :title="saving || disabled ? '' : 'Dubbelklik om te bewerken'"
        @dblclick.prevent="!disabled && startEdit()"
    >
        <span
            v-if="!editing"
            class="block cursor-text whitespace-pre-wrap break-words text-inherit"
        >{{ displayLabel }}</span>
        <button
            v-if="!editing && !disabled"
            type="button"
            class="btn-action-edit mt-1 md:hidden"
            title="Bewerken"
            @click.stop.prevent="startEdit"
        >
            <PencilSquareIcon class="h-4 w-4" />
        </button>
        <input
            v-else-if="inputKind === 'date'"
            ref="inputRef"
            v-model="draft"
            type="date"
            class="w-full rounded border border-brand-blue/55 bg-white px-2 py-1.5 text-sm text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-brand-blue-light/50 dark:bg-app-canvas-dark dark:text-app-ink-dark"
            @keydown.escape.prevent="revertDraft()"
        />
        <textarea
            v-else-if="multiline"
            ref="inputRef"
            v-model="draft"
            rows="4"
            class="w-full rounded border border-brand-blue/55 bg-white px-2 py-1.5 text-sm text-app-ink shadow-sm outline-none ring-0 focus:border-brand-blue dark:border-brand-blue-light/50 dark:bg-app-canvas-dark dark:text-app-ink-dark"
            @keydown.escape.prevent="revertDraft()"
        />
        <input
            v-else
            ref="inputRef"
            v-model="draft"
            type="text"
            class="w-full rounded border border-brand-blue/55 bg-white px-2 py-1.5 text-sm text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-brand-blue-light/50 dark:bg-app-canvas-dark dark:text-app-ink-dark"
            @keydown.escape.prevent="revertDraft()"
        />
    </div>
</template>

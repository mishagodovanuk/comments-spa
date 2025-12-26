<template>
    <div v-if="open" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="close">
        <div class="bg-zinc-900 text-zinc-100 rounded max-w-3xl w-[95%] p-3 border border-white/10 shadow-2xl">
        <div class="flex justify-between items-center mb-2">
                <div class="text-sm opacity-70 truncate">{{ title }}</div>
                <button class="px-2 py-1 border rounded" @click="close">Close</button>
            </div>

            <template v-if="type === 'image'">
                <img :src="src" class="block w-full max-h-[80vh] object-contain" />
            </template>

            <template v-else-if="type === 'text'">
                <div class="border rounded p-3 max-h-[80vh] overflow-auto whitespace-pre-wrap text-sm">
                    <template v-if="loading">Loading...</template>
                    <template v-else-if="error">Failed to load text.</template>
                    <template v-else>{{ text }}</template>
                </div>
            </template>

            <template v-else>
                <div class="text-sm opacity-70">Unsupported preview type.</div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    open: { type: Boolean, required: true },
    src: { type: String, default: '' },
    title: { type: String, default: '' },
    type: { type: String, default: 'image' },
})

const emit = defineEmits(['close'])

/**
 * Close file popup.
 */
function close() { emit('close') }

const loading = ref(false)
const error = ref(false)
const text = ref('')

/**
 * Get text from resource.
 */
async function loadText() {
    loading.value = true
    error.value = false
    text.value = ''

    try {
        const res = await fetch(props.src, { cache: 'no-store' })
        if (!res.ok) throw new Error(String(res.status))
        text.value = await res.text()
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
}

watch(
    () => [props.open, props.src, props.type],
    ([open, src, type]) => {
        if (open && type === 'text' && src) loadText()
    }
)
</script>

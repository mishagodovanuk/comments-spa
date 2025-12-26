<template>
    <div v-if="open" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50" @click.self="close">
        <div class="bg-white rounded max-w-3xl w-[95%] p-3">
            <div class="flex justify-between items-center mb-2">
                <div class="text-sm opacity-70 truncate">{{ title }}</div>
                <button class="px-2 py-1 border rounded" @click="close">Close</button>
            </div>
            <img :src="src" class="max-h-[80vh] w-full object-contain" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    open: { type: Boolean, required: true },
    src: { type: String, default: '' },
    title: { type: String, default: '' },
})

const emit = defineEmits(['close'])
function close() { emit('close') }
</script>

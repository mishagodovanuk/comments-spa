<template>
    <div class="p-6 max-w-5xl mx-auto page-text">
        <h1 class="text-2xl font-bold mb-4">Comments</h1>

        <CommentForm class="glass" :parent-id="null" @created="reload" />

        <div class="mt-6 border rounded">
            <div class="p-3 flex gap-3 items-center flex-wrap">
                <button class="px-2 py-1 border rounded" @click="store.setSort('user_name')">User Name</button>
                <button class="px-2 py-1 border rounded" @click="store.setSort('email')">E-mail</button>
                <button class="px-2 py-1 border rounded" @click="store.setSort('created_at')">Date</button>

                <input
                    v-model="q"
                    type="text"
                    placeholder="Search..."
                    class="px-3 py-1 border rounded bg-transparent ml-auto"
                />
                <button
                    class="px-2 py-1 border rounded disabled:opacity-50"
                    :disabled="!q"
                    @click="q = ''"
                    title="Clear"
                >
                    ✕
                </button>

                <span class="text-sm opacity-70">
                    {{ store.sort }} / {{ store.direction }}
                </span>
            </div>

            <div v-if="store.loading" class="p-6">Loading...</div>

            <TransitionGroup name="fade" tag="div" class="p-3 space-y-3">
                <CommentNode
                    v-for="c in store.tree"
                    :key="c.id"
                    :comment="c"
                    @replied="reload"
                />
            </TransitionGroup>

            <div class="p-3 flex gap-2 items-center border-t">
                <button
                    class="px-3 py-1 border rounded disabled:opacity-50"
                    :disabled="!store.meta || store.meta.current_page <= 1"
                    @click="store.setPage(store.meta.current_page - 1)"
                >
                    Prev
                </button>

                <span v-if="store.meta">
                    Page {{ store.meta.current_page }} / {{ store.meta.last_page }}
                </span>

                <button
                    class="px-3 py-1 border rounded disabled:opacity-50"
                    :disabled="!store.meta || store.meta.current_page >= store.meta.last_page"
                    @click="store.setPage(store.meta.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { useCommentsStore } from '../stores/comments'
import CommentForm from '../widgets/CommentForm.vue'
import CommentNode from '../widgets/CommentNode.vue'

const store = useCommentsStore()

const q = ref(store.q ?? '')
let qTimer = null

watch(q, (val) => {
    if (qTimer) clearTimeout(qTimer)

    qTimer = setTimeout(() => {
        if (typeof store.setQuery === 'function') {
            store.setQuery(val)
        } else {
            store.q = val
        }

        if ((store.meta?.current_page ?? 1) !== 1) {
            store.setPage(1)
        } else {
            store.fetch()
        }
    }, 400)
})

onMounted(() => {
    store.fetch()

    if (window.Echo) {
        window.Echo.channel('comments')
            .listen('.CommentCreated', () => {
                const page = store.meta?.current_page ?? 1

                if (q.value && q.value.trim().length > 0) {
                    return
                }

                if (page === 1 && store.sort === 'created_at' && store.direction === 'desc') {
                    store.fetch()
                }
            })
    }
})

onBeforeUnmount(() => {
    if (qTimer) clearTimeout(qTimer)

    if (window.Echo) {
        window.Echo.leaveChannel('comments')
    }
})

function reload() {
    store.fetch()
}
</script>

<template>
    <div class="border rounded p-3">
        <div class="flex items-start gap-3">
            <div class="flex-1">
                <div class="font-semibold">
                    {{ comment.user_name }}
                    <span class="text-xs opacity-60 ml-2">{{ comment.created_at }}</span>
                </div>

                <div class="text-sm opacity-70">{{ comment.email }}</div>

                <div v-if="comment.home_page" class="text-sm mt-1">
                    <a :href="comment.home_page" target="_blank" rel="noopener" class="underline">
                        {{ comment.home_page }}
                    </a>
                </div>

                <div class="mt-2 prose max-w-none" v-html="comment.text_html"></div>

                <div v-if="comment.attachment" class="mt-2">
                    <template v-if="comment.attachment.type === 'image'">
                        <button type="button" class="underline" @click="lbOpen = true">
                            🖼 {{ comment.attachment.original_name || 'image' }}
                        </button>

                        <ImageLightbox
                            :open="lbOpen"
                            :src="comment.attachment.url"
                            :title="comment.attachment.original_name || 'image'"
                            type="image"
                            @close="lbOpen = false"
                        />
                    </template>

                    <template v-else>
                        <button type="button" class="underline" @click="lbOpen = true">
                            📄 {{ comment.attachment.original_name || 'file' }}
                        </button>

                        <ImageLightbox
                            :open="lbOpen"
                            :src="comment.attachment.url"
                            :title="comment.attachment.original_name || 'file'"
                            type="text"
                            @close="lbOpen = false"
                        />
                    </template>
                </div>

                <button class="mt-3 px-2 py-1 border rounded" @click="showReply = !showReply">
                    Reply
                </button>

                <div v-if="showReply" class="mt-3">
                    <CommentForm :parent-id="comment.id" @created="onReplied" />
                </div>
            </div>
        </div>

        <TransitionGroup
            v-if="comment.children?.length"
            name="fade"
            tag="div"
            class="mt-3 pl-4 border-l space-y-3"
        >
            <CommentNode
                v-for="child in comment.children"
                :key="child.id"
                :comment="child"
                @replied="$emit('replied')"
            />
        </TransitionGroup>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import CommentForm from './CommentForm.vue'
import ImageLightbox from './ImageLightbox.vue'

defineProps({
    comment: { type: Object, required: true },
})

const emit = defineEmits(['replied'])

const showReply = ref(false)
const lbOpen = ref(false)

/**
 * Handle created event from the reply form.
 */
function onReplied() {
    showReply.value = false
    emit('replied')
}
</script>

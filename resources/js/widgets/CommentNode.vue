<template>
    <div class="border rounded p-3">
        <div class="flex items-start gap-3">
            <div class="flex-1">
                <div class="font-semibold">
                    {{ comment.user_name }}
                    <span class="text-xs opacity-60 ml-2">{{ comment.created_at }}</span>
                </div>

                <div class="text-sm opacity-70">{{ comment.email }}</div>

                <div class="mt-2 prose max-w-none" v-html="comment.text_html"></div>

                <div v-if="comment.attachment_url" class="mt-2">
                    <template v-if="comment.attachment_type === 'image'">
                        <button type="button" class="underline" @click="lbOpen = true">
                            🖼 {{ comment.attachment_original_name || 'image' }}
                        </button>

                        <ImageLightbox
                            :open="lbOpen"
                            :src="comment.attachment_url"
                            :title="comment.attachment_original_name || 'image'"
                            @close="lbOpen = false"
                        />
                    </template>

                    <template v-else>
                        <a :href="comment.attachment_url" target="_blank" class="underline">
                            📄 {{ comment.attachment_original_name || 'file' }}
                        </a>
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

function onReplied() {
    showReply.value = false
    emit('replied')
}
</script>

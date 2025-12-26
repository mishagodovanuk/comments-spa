<template>
    <div class="border rounded p-4">
        <h2 class="font-semibold mb-3">
            {{ parentId ? 'Reply' : 'Add comment' }}
        </h2>

        <form class="grid gap-3" @submit.prevent="submit">
            <div class="grid md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm">User Name *</label>
                    <input v-model.trim="form.user_name" class="border rounded w-full p-2" placeholder="latin + digits" />
                    <p v-if="errors.user_name" class="text-red-600 text-sm">{{ errors.user_name }}</p>
                </div>

                <div>
                    <label class="block text-sm">E-mail *</label>
                    <input v-model.trim="form.email" class="border rounded w-full p-2" />
                    <p v-if="errors.email" class="text-red-600 text-sm">{{ errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm">Home Page</label>
                    <input v-model.trim="form.home_page" class="border rounded w-full p-2" placeholder="https://..." />
                    <p v-if="errors.home_page" class="text-red-600 text-sm">{{ errors.home_page }}</p>
                </div>
            </div>

            <div class="flex gap-2 flex-wrap">
                <button type="button" class="px-2 py-1 border rounded btn-neon" @click="wrapTag('i')">i</button>
                <button type="button" class="px-2 py-1 border rounded btn-neon" @click="wrapTag('strong')">strong</button>
                <button type="button" class="px-2 py-1 border rounded btn-neon" @click="wrapTag('code')">code</button>
                <button type="button" class="px-2 py-1 border rounded btn-neon" @click="insertLink()">a</button>
            </div>

            <div>
                <label class="block text-sm">Text *</label>
                <textarea v-model="form.text" class="border rounded w-full p-2" rows="5"></textarea>
                <p v-if="errors.text" class="text-red-600 text-sm">{{ errors.text }}</p>
            </div>

            <div>
                <label class="block text-sm">File (JPG/GIF/PNG <=320x240 OR TXT <=100KB)</label>
                <input type="file" @change="onFile" />
                <p v-if="errors.file" class="text-red-600 text-sm">{{ errors.file }}</p>
            </div>

            <div class="grid md:grid-cols-2 gap-3 items-end">
                <div>
                    <div class="text-sm mb-1">CAPTCHA *</div>

                    <div class="p-3 border rounded text-sm glass captcha-no-copy">
                        <template v-if="captcha.loading">Loading captcha...</template>
                        <template v-else>
                            <div class="font-semibold">Answer:</div>
                            <div class="mt-1">{{ captcha.question }}</div>
                        </template>
                    </div>

                    <input v-model.trim="form.captcha_answer" class="border rounded w-full p-2 mt-2" placeholder="captcha answer" />
                    <p v-if="errors.captcha_answer" class="text-red-600 text-sm">{{ errors.captcha_answer }}</p>

                    <button type="button" class="mt-2 px-3 py-1 border rounded" @click="loadCaptcha">
                        Refresh captcha
                    </button>
                </div>

                <div class="flex gap-2 justify-end">
                    <button type="button" class="px-4 py-2 border rounded" @click="preview" :disabled="previewLoading">
                        {{ previewLoading ? 'Preview...' : 'Preview' }}
                    </button>

                    <button type="submit" class="px-4 py-2 border rounded" :disabled="loading">
                        {{ loading ? 'Saving...' : 'Submit' }}
                    </button>
                </div>
            </div>
        </form>

        <div v-if="previewHtml" class="mt-4 border-t pt-4">
            <h3 class="font-semibold mb-2">Preview</h3>
            <div class="prose max-w-none" v-html="previewHtml"></div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { api } from '../lib/api'

const props = defineProps({
    parentId: { type: [Number, String, null], default: null },
})

const emit = defineEmits(['created'])

const form = reactive({
    user_name: '',
    email: '',
    home_page: '',
    text: '',
    file: null,
    captcha_token: '',
    captcha_answer: '',
})

const errors = reactive({})
const loading = ref(false)
const previewLoading = ref(false)
const previewHtml = ref('')

const captcha = reactive({
    loading: false,
    question: '',
})

function clearErrors() {
    Object.keys(errors).forEach(k => delete errors[k])
}

function validateClient() {
    clearErrors()

    if (!form.user_name) errors.user_name = 'Required'
    else if (form.user_name.length > 70) errors.user_name = 'Max 70 characters'
    else if (!/^[a-zA-Z0-9]+$/.test(form.user_name)) errors.user_name = 'Only latin letters & digits'

    if (!form.email) errors.email = 'Required'
    else if (form.email.length > 255) errors.email = 'Max 255 characters'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errors.email = 'Invalid email'

    if (form.home_page) {
        if (form.home_page.length > 255) errors.home_page = 'Max 255 characters'
        else if (!/^https?:\/\/\S+$/i.test(form.home_page)) errors.home_page = 'Invalid URL'
    }

    if (!form.captcha_token) errors.captcha_answer = 'Captcha not loaded'
    else if (String(form.captcha_token).length > 64) errors.captcha_answer = 'Captcha token too long'

    if (!form.captcha_answer) errors.captcha_answer = 'Required'
    else if (String(form.captcha_answer).length > 20) errors.captcha_answer = 'Max 20 characters'

    if (!form.text) errors.text = 'Required'
    else if (form.text.length > 5000) errors.text = 'Max 5000 characters'

    if (form.file) {
        const ext = (form.file.name.split('.').pop() || '').toLowerCase()
        const ok = ['jpg', 'jpeg', 'png', 'gif', 'txt'].includes(ext)

        if (!ok) errors.file = 'Allowed: jpg, jpeg, png, gif, txt'

        if (!errors.file && ext === 'txt' && form.file.size > 100 * 1024) {
            errors.file = 'TXT max 100KB'
        }
    }

    return Object.keys(errors).length === 0
}

function onFile(e) {
    form.file = e.target.files?.[0] ?? null
}

function wrapTag(tag) {
    form.text = `${form.text}<${tag}></${tag}>`
}

function insertLink() {
    form.text = `${form.text}<a href="https://example.com" title="title">link</a>`
}

async function loadCaptcha() {
    captcha.loading = true
    try {
        const { data } = await api.get('/captcha')

        form.captcha_token = data.token ?? ''
        captcha.question = data.challenge ?? ''
    } finally {
        captcha.loading = false
    }
}

async function preview() {
    if (!validateClient()) return

    previewLoading.value = true
    try {
        const { data } = await api.post('/comments/preview', { text: form.text })
        previewHtml.value = data.html ?? ''
    } catch {
        previewHtml.value = ''
        errors.text = 'Preview error'
    } finally {
        previewLoading.value = false
    }
}

async function submit() {
    if (!validateClient()) return

    loading.value = true
    clearErrors()

    try {
        const fd = new FormData()

        if (props.parentId) fd.append('parent_id', String(props.parentId))
        fd.append('user_name', form.user_name)
        fd.append('email', form.email)
        if (form.home_page) fd.append('home_page', form.home_page)

        fd.append('captcha_token', form.captcha_token)
        fd.append('captcha_answer', form.captcha_answer)

        fd.append('text', form.text)
        if (form.file) fd.append('file', form.file)

        await api.post('/comments', fd, { headers: { 'Content-Type': 'multipart/form-data' } })

        form.text = ''
        form.captcha_answer = ''
        form.file = null
        previewHtml.value = ''

        await loadCaptcha()
        emit('created')
    } catch (e) {
        const ve = e?.response?.data?.errors
        if (ve) {
            Object.keys(ve).forEach(k => { errors[k] = ve[k][0] })
        } else {
            errors.text = 'Server error'
        }
    } finally {
        loading.value = false
    }
}

onMounted(() => loadCaptcha())
</script>

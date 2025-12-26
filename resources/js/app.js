import './bootstrap';
import '../css/app.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

// --- WS test listener (temporary) ---
if (window.Echo) {
    window.Echo.channel('comments')
        .listen('.CommentCreated', (e) => {
            console.log('New comment:', e);
        });
}

// ------------------------------------

createApp(App)
    .use(createPinia())
    .use(router)
    .mount('#app')

import { createRouter, createWebHistory } from 'vue-router'
import CommentsPage from '../pages/CommentsPage.vue'

export default createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'comments', component: CommentsPage },
    ],
})

import { defineStore } from 'pinia'
import { api } from '../lib/api'

function buildTree(roots, flat) {
    const byId = new Map()
    const children = new Map()

    const all = [...roots, ...flat]

    for (const c of all) {
        byId.set(c.id, { ...c, children: [] })
        if (!children.has(c.parent_id ?? null)) children.set(c.parent_id ?? null, [])
        children.get(c.parent_id ?? null).push(c.id)
    }

    for (const [parentId, ids] of children.entries()) {
        if (parentId === null) continue
        const parent = byId.get(parentId)
        if (!parent) continue
        parent.children = ids.map(id => byId.get(id)).filter(Boolean)
    }

    return roots.map(r => byId.get(r.id)).filter(Boolean)
}

export const useCommentsStore = defineStore('comments', {
    state: () => ({
        tree: [],
        roots: [],
        flat: [],
        meta: null,
        loading: false,
        sort: 'created_at',
        direction: 'desc',
        page: 1,
        per_page: 25,
    }),
    actions: {
        async fetch() {
            this.loading = true
            try {
                const { data } = await api.get('/comments', {
                    params: {
                        sort: this.sort,
                        direction: this.direction,
                        page: this.page,
                    },
                })

                this.roots = data.roots ?? []
                this.flat = data.descendants_flat ?? []
                this.meta = data.meta ?? null

                this.tree = buildTree(this.roots, this.flat)
            } finally {
                this.loading = false
            }
        },

        async setSort(sort) {
            if (this.sort === sort) {
                this.direction = this.direction === 'asc' ? 'desc' : 'asc'
            } else {
                this.sort = sort
                this.direction = 'asc'
            }
            this.page = 1
            return this.fetch()
        },

        async setPage(page) {
            this.page = page
            return this.fetch()
        },
    },
})

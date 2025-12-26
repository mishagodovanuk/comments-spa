import { defineStore } from 'pinia'
import { api } from '../lib/api'

function buildTree(roots, flat) {
    const byId = new Map()
    const children = new Map()

    const all = [...roots, ...flat]

    for (const c of all) {
        byId.set(c.id, { ...c, children: [] })
        const pid = c.parent_id ?? null
        if (!children.has(pid)) children.set(pid, [])
        children.get(pid).push(c.id)
    }

    for (const [parentId, ids] of children.entries()) {
        if (parentId === null) continue
        const parent = byId.get(parentId)
        if (!parent) continue
        parent.children = ids.map(id => byId.get(id)).filter(Boolean)
    }

    return roots.map(r => byId.get(r.id)).filter(Boolean)
}

function splitRootsAndFlat(items) {
    const byId = new Map()
    for (const c of items) byId.set(c.id, c)

    const roots = []
    const flat = []

    for (const c of items) {
        const pid = c.parent_id ?? null

        if (pid === null || !byId.has(pid)) {
            roots.push(c)
        } else {
            flat.push(c)
        }
    }

    return { roots, flat }
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
        q: '',
    }),
    actions: {
        async fetch() {
            this.loading = true
            try {
                const hasQuery = (this.q ?? '').trim().length > 0
                const url = hasQuery ? '/comments/search' : '/comments'

                const params = {
                    sort: this.sort,
                    direction: this.direction,
                    page: this.page,
                    per_page: this.per_page,
                }

                if (hasQuery) {
                    params.q = this.q
                }

                const { data } = await api.get(url, { params })

                if (hasQuery) {
                    const items = data.items ?? []
                    const { roots, flat } = splitRootsAndFlat(items)

                    this.roots = roots
                    this.flat = flat
                    this.meta = data.meta ?? null
                    this.tree = buildTree(this.roots, this.flat)
                    return
                }

                this.roots = data.roots ?? []
                this.flat = data.descendants_flat ?? []
                this.meta = data.meta ?? null
                this.tree = buildTree(this.roots, this.flat)
            } finally {
                this.loading = false
            }
        },

        async setQuery(q) {
            this.q = q
            this.page = 1
            return this.fetch()
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

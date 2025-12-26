import { defineStore } from 'pinia'
import { api } from '../lib/api'

/**
 * Build a nested comments tree from API.
 *
 * @param {Array<Object>} roots
 * @param {Array<Object>} flat
 * @returns {Array<Object>} tree
 */
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

/**
 * Split a flat list of comments into:
 * - roots: comments that have no parent id.
 * - flat: comments whose parent exists in the list (descendants)
 *
 * @param {Array<Object>} items
 * @returns {{roots: Array<Object>, flat: Array<Object>}}
 */
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
        /**
         * Fetch comments from API and rebuild tree.
         */
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

        /**
         * Set search query, reset to page 1, then fetch.
         *
         * @param {string} q
         */
        async setQuery(q) {
            this.q = q
            this.page = 1

            return this.fetch()
        },

        /**
         * Update sorting:
         * - If clicking the same sort field => toggle direction asc/desc
         * - If switching to a new sort field => default direction to 'asc'
         *
         * @param {string} sort
         */
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

        /**
         * Set current page.
         *
         * @param {number} page
         */
        async setPage(page) {
            this.page = page

            return this.fetch()
        },
    },
})

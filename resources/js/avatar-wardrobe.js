export default (initial, catalog) => ({
    appearance: { ...initial },
    original: { ...initial },
    catalog,
    tab: ['clothes', 'accessories'].includes(new URLSearchParams(window.location.search).get('tab'))
        ? new URLSearchParams(window.location.search).get('tab') : 'character',
    collection: ['nova', 'lyra', 'all'].includes(new URLSearchParams(window.location.search).get('collection'))
        ? new URLSearchParams(window.location.search).get('collection') : initial.character,
    saving: false,
    ownership: 'all',
    preview: null,
    expanded: false,

    get look() { return { ...this.appearance, ...(this.preview || {}) }; },

    get dirty() {
        return Object.keys(this.original).some(key => this.appearance[key] !== this.original[key]);
    },

    init() {
        this.$watch('appearance', () => { this.preview = null; this.paint(); });
        this.$watch('preview', () => this.paint());
        this.$watch('appearance.character', character => { this.collection = character; });
        this.$nextTick(() => this.paint());
    },

    paint() {
        this.$root.querySelectorAll('[data-avatar-renderer]').forEach(svg => {
            const look = { ...(svg.dataset.choiceKey ? this.appearance : this.look) };
            if (svg.dataset.choiceKey) look[svg.dataset.choiceKey] = svg.dataset.choiceValue;

            for (const key of ['skin', 'hair', 'color']) {
                svg.style.setProperty(`--avatar-${key}`, this.catalog[key].items[look[key]].color);
            }
            for (const key of ['character', 'outfit', 'headwear']) svg.dataset[key] = look[key];
            svg.querySelectorAll('[data-avatar-part]').forEach(part => {
                part.style.display = look[part.dataset.avatarPart] === part.dataset.avatarValue ? 'inline' : 'none';
            });
        });
    },

    randomize() {
        const look = {};
        for (const [key, option] of Object.entries(this.catalog)) {
            const values = Object.keys(option.items).filter(value => {
                const item = option.items[value];
                return !item.locked && (!item.collection || item.collection === look.character);
            });
            look[key] = values[Math.floor(Math.random() * values.length)];
        }
        this.appearance = look;
    },

    reset() {
        this.preview = null;
        this.appearance = { ...this.original };
        this.collection = this.original.character;
    },

    visibleItem(key, value) {
        const item = this.catalog[key].items[value];
        const inCollection = !item.collection || this.collection === 'all' || item.collection === this.collection;
        const owned = !item.locked;
        return inCollection && (this.tab === 'character' || this.ownership === 'all' || (this.ownership === 'owned' ? owned : !owned));
    },

    visibleField(key) {
        return Object.keys(this.catalog[key].items).some(value => this.visibleItem(key, value));
    },

    hasVisibleItems() {
        return Object.keys(this.catalog).some(key => this.catalog[key].tab === this.tab && this.visibleField(key));
    },

    tryOn(key, value) {
        this.preview = { [key]: value };
        this.expanded = true;
        if (window.matchMedia('(max-width: 699px)').matches) {
            this.$nextTick(() => this.$refs.preview.scrollIntoView({ block: 'center', behavior: window.PgaalsPreferences?.reducedMotion ? 'instant' : 'smooth' }));
        }
    },

    selectTab(tab) {
        this.tab = tab;
        this.$nextTick(() => document.getElementById(`wardrobe-tab-${tab}`).focus());
    },

    moveTab(direction) {
        const tabs = ['character', 'clothes', 'accessories'];
        this.selectTab(tabs[(tabs.indexOf(this.tab) + direction + tabs.length) % tabs.length]);
    },
});

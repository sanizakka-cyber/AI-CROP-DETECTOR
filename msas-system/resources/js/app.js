import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global overlay store — single `active` field makes it physically impossible
// for two panels (notification, profile, language) to be open simultaneously.
Alpine.store('ui', {
    active: null,
    is(p)     { return this.active === p; },
    toggle(p) { this.active = this.active === p ? null : p; },
    open(p)   { this.active = p; },
    closeAll(){ this.active = null; }
});

Alpine.start();

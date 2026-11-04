/**
 * SmartSelect - Mobile-First & Desktop Elegant Select Dropdown
 * Replaces annoying TomSelect that forced Android virtual keyboards to open.
 * 
 * - Mobile (< 768px): Silky Bottom Sheet with thumb-friendly tap targets (>= 48px),
 *   NO auto-focus keyboard pop-up, optional search only when tapped.
 * - Desktop (>= 768px): Sleek floating popover dropdown with subtle shadows & keyboard navigation.
 * - Fully syncs with underlying <select> element and triggers 'change' events.
 */

(function (window, document) {
  'use strict';

  class SmartSelect {
    constructor(selectElement, options = {}) {
      if (!selectElement || selectElement._smartSelect) return;
      this.select = selectElement;
      this.select._smartSelect = this;
      this.options = Object.assign({
        title: this.select.getAttribute('data-title') || this.findLabelText() || 'Pilih Opsi',
        placeholder: this.select.getAttribute('data-placeholder') || 'Pilih salah satu...',
        searchable: this.select.options.length > 6,
        searchPlaceholder: 'Ketik untuk memfilter...'
      }, options);

      this.isOpen = false;
      this.isMobile = window.innerWidth < 768;
      this.init();
    }

    findLabelText() {
      if (this.select.id) {
        const lbl = document.querySelector(`label[for="${this.select.id}"]`);
        if (lbl) return lbl.textContent.replace(/[*:]/g, '').trim();
      }
      const parentLabel = this.select.closest('label');
      if (parentLabel) {
        return parentLabel.childNodes[0]?.textContent?.replace(/[*:]/g, '').trim();
      }
      const prevEl = this.select.previousElementSibling;
      if (prevEl && prevEl.tagName === 'LABEL') {
        return prevEl.textContent.replace(/[*:]/g, '').trim();
      }
      return '';
    }

    init() {
      // Hide original select visually but keep in DOM for forms
      this.select.style.display = 'none';

      // Create Custom Trigger Button
      this.trigger = document.createElement('button');
      this.trigger.type = 'button';
      this.trigger.className = 'smart-select-trigger w-full flex items-center justify-between gap-2 px-3.5 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-800 text-xs font-semibold hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-400 transition-all text-left shadow-2xs cursor-pointer select-none min-h-[44px]';
      this.trigger.setAttribute('aria-haspopup', 'listbox');
      this.trigger.setAttribute('aria-expanded', 'false');

      this.triggerText = document.createElement('span');
      this.triggerText.className = 'truncate flex-1';

      this.triggerChevron = document.createElement('span');
      this.triggerChevron.className = 'shrink-0 text-slate-400 text-xs pointer-events-none transition-transform duration-200';
      this.triggerChevron.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>';

      this.trigger.appendChild(this.triggerText);
      this.trigger.appendChild(this.triggerChevron);

      // Wrapper to place trigger in position
      this.wrapper = document.createElement('div');
      this.wrapper.className = 'smart-select-wrapper relative w-full';
      this.select.parentNode.insertBefore(this.wrapper, this.select);
      this.wrapper.appendChild(this.trigger);
      this.wrapper.appendChild(this.select);

      this.updateTriggerDisplay();

      // Click Event
      this.trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.toggle();
      });

      // Listen for external programmatic changes to select
      this.select.addEventListener('change', () => {
        this.updateTriggerDisplay();
      });

      window.addEventListener('resize', () => {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth < 768;
        if (this.isOpen && wasMobile !== this.isMobile) {
          this.close();
        }
      });
    }

    updateTriggerDisplay() {
      const selected = this.select.options[this.select.selectedIndex];
      if (selected && selected.value !== '') {
        this.triggerText.textContent = selected.textContent.trim();
        this.triggerText.classList.remove('text-slate-400');
        this.triggerText.classList.add('text-slate-900', 'font-bold');
      } else {
        this.triggerText.textContent = selected ? selected.textContent.trim() : this.options.placeholder;
        this.triggerText.classList.remove('text-slate-900', 'font-bold');
        this.triggerText.classList.add('text-slate-400');
      }
    }

    toggle() {
      if (this.isOpen) {
        this.close();
      } else {
        this.open();
      }
    }

    open() {
      // Close other open smart selects first
      document.querySelectorAll('.smart-select-open').forEach(el => {
        if (el._smartSelect && el._smartSelect !== this) el._smartSelect.close();
      });

      this.isOpen = true;
      this.select.classList.add('smart-select-open');
      this.trigger.setAttribute('aria-expanded', 'true');
      this.triggerChevron.classList.add('rotate-180');

      if (this.isMobile) {
        this.openMobileSheet();
      } else {
        this.openDesktopPopover();
      }
    }

    close() {
      this.isOpen = false;
      this.select.classList.remove('smart-select-open');
      this.trigger.setAttribute('aria-expanded', 'false');
      this.triggerChevron.classList.remove('rotate-180');

      if (this.backdrop) {
        this.backdrop.remove();
        this.backdrop = null;
      }
      if (this.menu) {
        this.menu.remove();
        this.menu = null;
      }
      document.body.style.overflow = '';
      document.removeEventListener('click', this._onDocClick);
    }

    openMobileSheet() {
      document.body.style.overflow = 'hidden';

      this.backdrop = document.createElement('div');
      this.backdrop.className = 'fixed inset-0 bg-slate-950/70 z-[9999] backdrop-blur-xs flex flex-col justify-end transition-opacity duration-200';
      this.backdrop.addEventListener('click', (e) => {
        if (e.target === this.backdrop) this.close();
      });

      this.menu = document.createElement('div');
      this.menu.className = 'bg-white w-full rounded-t-3xl max-h-[85vh] flex flex-col shadow-2xl border-t border-slate-200 transform transition-transform duration-250 ease-out select-none';
      this.menu.onclick = (e) => e.stopPropagation();

      // Top handle bar
      const handle = document.createElement('div');
      handle.className = 'w-12 h-1.5 bg-slate-300 rounded-full mx-auto my-3 shrink-0';
      this.menu.appendChild(handle);

      // Header with Title & Close
      const header = document.createElement('div');
      header.className = 'px-5 pb-3 border-b border-slate-100 flex items-center justify-between shrink-0';
      header.innerHTML = `
        <div>
          <h3 class="text-sm font-black text-slate-900 leading-tight">${this.options.title}</h3>
          <p class="text-[11px] text-slate-500 mt-0.5">Ketuk opsi yang Anda inginkan</p>
        </div>
        <button type="button" class="w-9 h-9 rounded-full bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center font-bold text-base active:scale-90">&times;</button>
      `;
      header.querySelector('button').onclick = () => this.close();
      this.menu.appendChild(header);

      // Search Box (Only if > 6 options, but NOT focused to prevent virtual keyboard pop-up)
      if (this.options.searchable) {
        const searchBox = document.createElement('div');
        searchBox.className = 'px-4 pt-3 pb-2 shrink-0';
        searchBox.innerHTML = `
          <div class="relative">
            <input type="text" placeholder="${this.options.searchPlaceholder}" class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-amber-400 focus:ring-1 focus:ring-amber-400 transition-all" />
            <button type="button" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-sm font-bold">&times;</button>
          </div>
        `;
        const searchInput = searchBox.querySelector('input');
        const clearBtn = searchBox.querySelector('button');

        searchInput.addEventListener('input', () => {
          const q = searchInput.value.toLowerCase().trim();
          clearBtn.classList.toggle('hidden', !q);
          this.filterOptions(q);
        });

        clearBtn.addEventListener('click', () => {
          searchInput.value = '';
          clearBtn.classList.add('hidden');
          this.filterOptions('');
          searchInput.focus();
        });

        this.menu.appendChild(searchBox);
      }

      // Options List
      const listContainer = document.createElement('div');
      listContainer.className = 'overflow-y-auto flex-1 divide-y divide-slate-100 px-3 py-1';
      this.renderOptions(listContainer);
      this.menu.appendChild(listContainer);

      // Safe bottom padding
      const bottomPad = document.createElement('div');
      bottomPad.className = 'h-6 shrink-0';
      this.menu.appendChild(bottomPad);

      this.backdrop.appendChild(this.menu);
      document.body.appendChild(this.backdrop);
    }

    openDesktopPopover() {
      this.menu = document.createElement('div');
      this.menu.className = 'smart-select-dropdown absolute left-0 right-0 top-full mt-1.5 bg-white rounded-xl shadow-xl border border-slate-200 z-[9999] max-h-72 flex flex-col overflow-hidden select-none animate-in fade-in zoom-in-95 duration-150';

      if (this.options.searchable) {
        const searchBox = document.createElement('div');
        searchBox.className = 'p-2.5 border-b border-slate-100 bg-slate-50 shrink-0';
        searchBox.innerHTML = `
          <input type="text" placeholder="${this.options.searchPlaceholder}" class="w-full bg-white border border-slate-300 rounded-lg px-3 py-1.5 text-xs text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400" />
        `;
        const searchInput = searchBox.querySelector('input');
        searchInput.addEventListener('input', () => {
          this.filterOptions(searchInput.value.toLowerCase().trim());
        });
        this.menu.appendChild(searchBox);
      }

      const listContainer = document.createElement('div');
      listContainer.className = 'overflow-y-auto flex-1 divide-y divide-slate-50 p-1';
      this.renderOptions(listContainer);
      this.menu.appendChild(listContainer);

      this.wrapper.appendChild(this.menu);

      this._onDocClick = (e) => {
        if (!this.wrapper.contains(e.target)) {
          this.close();
        }
      };
      setTimeout(() => {
        document.addEventListener('click', this._onDocClick);
      }, 10);
    }

    renderOptions(container) {
      this.items = [];
      Array.from(this.select.options).forEach((opt, idx) => {
        const isSelected = opt.selected;
        const itemBtn = document.createElement('button');
        itemBtn.type = 'button';
        itemBtn.className = `smart-select-option w-full min-h-[46px] px-3.5 py-2.5 rounded-lg text-left text-xs flex items-center justify-between gap-3 transition-colors active:scale-[0.99] cursor-pointer ${
          isSelected 
            ? 'bg-[#0b1e36] text-amber-400 font-bold' 
            : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium'
        }`;
        itemBtn.setAttribute('data-value', opt.value);
        itemBtn.setAttribute('data-text', opt.textContent.toLowerCase());

        const labelSpan = document.createElement('span');
        labelSpan.className = 'truncate flex-1';
        labelSpan.textContent = opt.textContent;

        const checkSpan = document.createElement('span');
        checkSpan.className = `shrink-0 text-xs ${isSelected ? 'text-amber-400' : 'text-transparent'}`;
        checkSpan.innerHTML = '✓';

        itemBtn.appendChild(labelSpan);
        itemBtn.appendChild(checkSpan);

        itemBtn.onclick = () => {
          this.selectOption(opt.value);
        };

        container.appendChild(itemBtn);
        this.items.push(itemBtn);
      });
    }

    filterOptions(query) {
      if (!this.items) return;
      this.items.forEach(btn => {
        const text = btn.getAttribute('data-text') || '';
        const match = !query || text.includes(query);
        btn.style.display = match ? 'flex' : 'none';
      });
    }

    selectOption(value) {
      this.select.value = value;
      this.updateTriggerDisplay();
      this.close();

      // Trigger event
      if (typeof this.select.onchange === 'function') {
        this.select.onchange();
      } else {
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  }

  // Global Auto-Initializer
  window.SmartSelect = SmartSelect;

  function initAllSmartSelects() {
    const selects = document.querySelectorAll('select.smart-select, select[data-smart-select]');
    selects.forEach(sel => {
      new SmartSelect(sel);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAllSmartSelects);
  } else {
    initAllSmartSelects();
  }
})(window, document);

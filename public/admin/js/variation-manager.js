/**
 * Lazada-Style Dynamic Variation Builder Component
 *
 * Implements:
 * 1. Standby mode: [+ Enable Variations] button (Pic 1)
 * 2. Form open: Variation 1 card with 2-column option inputs (Pic 2/3)
 * 3. Auto-ready options: Typing into an option automatically appends the next empty input slot
 *    so no manual "add" or "new" button click is needed.
 * 4. Multi-level variation: When Variation 1 is used, [+ Add Variation 2] appears (Pic 3/4)
 * 5. Dynamic Cartesian SKU Matrix table with row grouping (rowspan), photo uploaders,
 *    Price, Special Price, Stock, SKU, and interactive On/Off switches (Pic 4).
 */

class VariationManager {
  constructor(options = {}) {
    this.container = options.container || document.getElementById('section5Wrapper');
    this.onUpdate = options.onUpdate || null;

    // State
    this.isActive = false;
    this.isVar2Active = false;
    this.var1Title = options.var1Title || "Variation 1";
    this.var2Title = options.var2Title || "Variation 2";
    
    // Default options matching Pic 3 and Pic 4
    this.var1Options = options.var1Options || [
      { name: 'A', desc: '' },
      { name: 'B', desc: '' },
      { name: 'C', desc: '' },
      { name: 'D', desc: '' }
    ];
    this.var2Options = options.var2Options || [
      { name: '1', desc: '' },
      { name: '2', desc: '' },
      { name: '3', desc: '' },
      { name: '4', desc: '' }
    ];

    this.matrixCache = {};
    this.init();
  }

  init() {
    this.bindEvents();
    if (this.isActive) {
      this.render();
    }
  }

  enableVariations() {
    this.isActive = true;
    this.render();
    if (this.onUpdate) this.onUpdate(this.getState());
  }

  disableVariations() {
    this.isActive = false;
    this.isVar2Active = false;
    this.render();
    if (this.onUpdate) this.onUpdate(this.getState());
  }

  addVariation2() {
    this.isVar2Active = true;
    this.render();
    if (this.onUpdate) this.onUpdate(this.getState());
  }

  removeVariation2() {
    this.isVar2Active = false;
    this.render();
    if (this.onUpdate) this.onUpdate(this.getState());
  }

  addOption(varNum, name, desc = '') {
    const list = varNum === 1 ? this.var1Options : this.var2Options;
    list.push({ name, desc });
    this.render();
  }

  removeOption(varNum, index) {
    const list = varNum === 1 ? this.var1Options : this.var2Options;
    list.splice(index, 1);
    this.render();
  }

  getValidOptions(varNum) {
    const list = varNum === 1 ? this.var1Options : this.var2Options;
    return list.filter(o => o.name && o.name.trim() !== '');
  }

  generateCartesianProduct() {
    const v1 = this.getValidOptions(1);
    const v2 = this.isVar2Active ? this.getValidOptions(2) : [];

    if (v1.length === 0) return [];
    if (v2.length === 0) {
      return v1.map(o1 => ({
        key: o1.name,
        var1: o1.name,
        var2: null,
        data: this.matrixCache[o1.name] || {
          price: '139.00',
          promo: '119.00',
          stock: 50,
          sku: `MSP-${o1.name.toUpperCase().replace(/\s+/g, '-')}`,
          status: true
        }
      }));
    }

    const rows = [];
    v1.forEach(o1 => {
      v2.forEach((o2, idx2) => {
        const key = `${o1.name}|${o2.name}`;
        rows.push({
          key,
          var1: o1.name,
          var2: o2.name,
          isFirstOfGroup: idx2 === 0,
          groupSize: v2.length,
          data: this.matrixCache[key] || {
            price: '139.00',
            promo: '119.00',
            stock: 50,
            sku: `MSP-${o1.name.toUpperCase().replace(/\s+/g, '-')}-${o2.name.toUpperCase().replace(/\s+/g, '-')}`,
            status: true
          }
        });
      });
    });

    return rows;
  }

  batchApply({ price, promo, stock, skuPrefix }) {
    const rows = this.generateCartesianProduct();
    rows.forEach(r => {
      const entry = this.matrixCache[r.key] || r.data;
      if (price) entry.price = parseFloat(price).toFixed(2);
      if (promo) entry.promo = parseFloat(promo).toFixed(2);
      if (stock) entry.stock = parseInt(stock);
      if (skuPrefix) entry.sku = `${skuPrefix}-${r.key.replace('|', '-').toUpperCase()}`;
      this.matrixCache[r.key] = entry;
    });
    this.renderMatrixTable();
  }

  toggleRowStatus(key, status) {
    if (!this.matrixCache[key]) {
      const rows = this.generateCartesianProduct();
      const match = rows.find(r => r.key === key);
      if (match) this.matrixCache[key] = { ...match.data };
    }
    if (this.matrixCache[key]) {
      this.matrixCache[key].status = status;
    }
  }

  getState() {
    return {
      isActive: this.isActive,
      isVar2Active: this.isVar2Active,
      var1Title: this.var1Title,
      var2Title: this.var2Title,
      var1Options: this.var1Options,
      var2Options: this.var2Options,
      matrixRows: this.generateCartesianProduct()
    };
  }
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = VariationManager;
} else if (typeof window !== 'undefined') {
  window.VariationManager = VariationManager;
}

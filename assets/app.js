window.MitraHaidarTransaksi = {
  hargaById: {},
  layananOptionsHtml: "",
  tbody: null,
  totalHidden: null,
  totalLabel: null,

  init: function init(options) {
    this.hargaById = options.hargaById || {};
    this.layananOptionsHtml = options.layananOptionsHtml || "";
    this.tbody = document.getElementById("detailTbody");
    this.totalHidden = document.getElementById("totalHidden");
    this.totalLabel = document.getElementById("totalLabel");

    if (this.tbody && this.tbody.children.length === 0) {
      this.addRow();
    }

    if (this.tbody) {
      this.tbody.addEventListener("change", (e) => {
        if (e.target.classList.contains("layanan-select") || e.target.classList.contains("jumlah-input")) {
          this.recalcRow(e.target.closest("tr"));
        }
      });
      this.tbody.addEventListener("input", (e) => {
        if (e.target.classList.contains("jumlah-input")) {
          this.recalcRow(e.target.closest("tr"));
        }
      });
    }

    const addBtn = document.getElementById("btnAddRow");
    if (addBtn) {
      addBtn.addEventListener("click", () => this.addRow());
    }

    this.recalcAll();
  },

  formatRupiah: function formatRupiah(n) {
    return "Rp " + Number(n || 0).toLocaleString("id-ID");
  },

  addRow: function addRow() {
    if (!this.tbody) return;

    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>
        <select name="id_layanan[]" class="form-select form-select-sm layanan-select" required>
          ${this.layananOptionsHtml}
        </select>
      </td>
      <td>
        <input name="jumlah[]" type="number" min="1" step="1" class="form-control form-control-sm jumlah-input" value="1" required>
      </td>
      <td>
        <input type="text" class="form-control form-control-sm harga-input" value="" readonly>
      </td>
      <td>
        <input type="text" class="form-control form-control-sm subtotal-input" value="" readonly>
        <input name="subtotal_raw[]" type="hidden" class="subtotal-raw-hidden" value="">
      </td>
      <td class="text-end">
        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row">Hapus</button>
      </td>
    `;

    this.tbody.appendChild(tr);
    tr.querySelector(".btn-remove-row").addEventListener("click", () => {
      if (this.tbody.children.length <= 1) return;
      tr.remove();
      this.recalcAll();
    });
    this.recalcRow(tr);
  },

  getSelectedPrice: function getSelectedPrice(row) {
    const select = row.querySelector(".layanan-select");
    const layananId = select ? select.value : "";
    return this.hargaById[layananId] ? Number(this.hargaById[layananId]) : 0;
  },

  recalcRow: function recalcRow(row) {
    if (!row) return;

    const qtyInput = row.querySelector(".jumlah-input");
    const price = this.getSelectedPrice(row);
    const qty = qtyInput ? Number(qtyInput.value) : 0;
    const subtotal = price * qty;

    const hargaInput = row.querySelector(".harga-input");
    const subtotalInput = row.querySelector(".subtotal-input");
    const subtotalRawHidden = row.querySelector(".subtotal-raw-hidden");

    if (hargaInput) hargaInput.value = price ? this.formatRupiah(price) : "";
    if (subtotalInput) subtotalInput.value = subtotal ? this.formatRupiah(subtotal) : "";
    if (subtotalRawHidden) subtotalRawHidden.value = subtotal ? String(subtotal) : "0";

    this.recalcAll();
  },

  recalcAll: function recalcAll() {
    let total = 0;
    if (this.tbody) {
      this.tbody.querySelectorAll("tr").forEach((row) => {
        const hidden = row.querySelector(".subtotal-raw-hidden");
        const val = hidden ? Number(hidden.value) : 0;
        if (!Number.isNaN(val)) total += val;
      });
    }

    if (this.totalHidden) this.totalHidden.value = String(total);
    if (this.totalLabel) this.totalLabel.textContent = this.formatRupiah(total);
  }
};

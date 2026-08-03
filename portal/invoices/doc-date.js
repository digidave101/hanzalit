/**
 * Shared date helpers for Invoice / Packing List / Certificate of Origin.
 * Always date-only — never display a time component.
 */
(function (global) {
  function toDateOnlyISO(v) {
    if (v == null || v === '') return '';
    if (v instanceof Date && !isNaN(v.getTime())) {
      const y = v.getFullYear();
      const m = String(v.getMonth() + 1).padStart(2, '0');
      const d = String(v.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + d;
    }
    let s = String(v).trim();
    // Strip trailing time: "2025-08-01 14:30:00", "2025-08-01T14:30:00.000Z"
    const iso = s.match(/^(\d{4}-\d{2}-\d{2})/);
    if (iso) return iso[1];
    const us = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})/);
    if (us) {
      return us[3] + '-' + us[1].padStart(2, '0') + '-' + us[2].padStart(2, '0');
    }
    // Last resort: parse then take local calendar date (no time in output)
    const parsed = new Date(s);
    if (!isNaN(parsed.getTime())) return toDateOnlyISO(parsed);
    return '';
  }

  function formatDocDate(v) {
    const iso = toDateOnlyISO(v);
    if (!iso) return '';
    const parts = iso.split('-').map(Number);
    const dt = new Date(parts[0], parts[1] - 1, parts[2]);
    return dt.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  }

  function ordinalDocDate(v) {
    const iso = toDateOnlyISO(v) || toDateOnlyISO(new Date());
    const parts = iso.split('-').map(Number);
    const dt = new Date(parts[0], parts[1] - 1, parts[2]);
    const day = dt.getDate();
    const suffix = (day > 3 && day < 21) ? 'th' : ['th', 'st', 'nd', 'rd'][day % 10 > 3 ? 0 : day % 10];
    const month = dt.toLocaleDateString('en-US', { month: 'long' }).toUpperCase();
    return day + suffix + ' DAY OF ' + month + ', ' + dt.getFullYear();
  }

  global.toDateOnlyISO = toDateOnlyISO;
  global.formatDocDate = formatDocDate;
  global.ordinalDocDate = ordinalDocDate;
})(typeof window !== 'undefined' ? window : globalThis);

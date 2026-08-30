// Standardized DataTables config — single source for every table in the app.
// All tables must use this so responsive behavior is uniform (DataTables Responsive extension).
// Responsive strategy: DataTables Responsive (collapses columns into child rows on narrow viewports)
// Alternative approach (stacked card per row) is intentionally NOT used — one approach everywhere.

const STANDARD_DT_OPTIONS = {
  responsive: true,
  autoWidth: false,
  pageLength: 10,
  lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
  language: {
    search: "",
    searchPlaceholder: "Search...",
    lengthMenu: "_MENU_ per page",
    info: "Showing _START_ to _END_ of _TOTAL_",
    infoEmpty: "No records",
    paginate: { previous: "‹", next: "›" },
  },
};

export function initAppTable(selector, extra = {}) {
  const $el = typeof selector === "string" ? $(selector) : selector;
  if (!$el || !$el.length) return null;
  if ($.fn.DataTable && $.fn.DataTable.isDataTable($el)) {
    return $el.DataTable();
  }
  try {
    return $el.DataTable({ ...STANDARD_DT_OPTIONS, ...extra });
  } catch (e) {
    console.error("DataTable init failed (" + selector + ")", e);
    return null;
  }
}

// Expose globally for inline Blade scripts that still call $('#x').DataTable({...})
if (typeof window !== "undefined") {
  window.initAppTable = initAppTable;
  window.STANDARD_DT_OPTIONS = STANDARD_DT_OPTIONS;
}

export default initAppTable;

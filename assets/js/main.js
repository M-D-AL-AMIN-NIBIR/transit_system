/**
 * MetroLink — Shared UI Interactions (main.js)
 * Handles: mobile sidebar toggle, pagination (client-side demo).
 */

(function () {
  'use strict';

  /* -------- Sidebar mobile toggle -------- */
  var sidebar  = document.querySelector('.sidebar');
  var overlay  = document.querySelector('.sidebar-overlay');
  var hamburger= document.querySelector('.hamburger');

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add('open');
    if (overlay) overlay.classList.add('visible');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('visible');
    document.body.style.overflow = '';
  }

  if (hamburger) hamburger.addEventListener('click', openSidebar);
  if (overlay)   overlay.addEventListener('click', closeSidebar);

  /* -------- Pagination (demo, client-side) -------- */
  var tableBody   = document.querySelector('.trip-table tbody');
  var pageDisplay = document.querySelector('.page-current');
  var prevBtn     = document.getElementById('page-prev');
  var nextBtn     = document.getElementById('page-next');

  if (tableBody && prevBtn && nextBtn) {
    var rows      = Array.from(tableBody.querySelectorAll('tr'));
    var perPage   = 3;
    var current   = 1;
    var totalPages= Math.ceil(rows.length / perPage);

    function renderPage(page) {
      rows.forEach(function (row, idx) {
        var inRange = idx >= (page - 1) * perPage && idx < page * perPage;
        row.style.display = inRange ? '' : 'none';
      });
      if (pageDisplay) pageDisplay.textContent = page;
      prevBtn.setAttribute('aria-disabled', page === 1 ? 'true' : 'false');
      nextBtn.setAttribute('aria-disabled', page === totalPages ? 'true' : 'false');
    }

    prevBtn.addEventListener('click', function () {
      if (current > 1) { current--; renderPage(current); }
    });

    nextBtn.addEventListener('click', function () {
      if (current < totalPages) { current++; renderPage(current); }
    });

    renderPage(current);
  }

  /* -------- Search filter (fleet table) -------- */
  var searchInput = document.getElementById('fleet-search');
  var fleetRows   = document.querySelectorAll('.fleet-table tbody tr');

  if (searchInput && fleetRows.length) {
    searchInput.addEventListener('input', function () {
      var q = this.value.toLowerCase().trim();
      fleetRows.forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

})();

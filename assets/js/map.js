/**
 * MetroLink — Leaflet Map Initializer (map.js)
 * Backend integration point: coordinates could be fetched dynamically.
 */

(function () {
  'use strict';

  // Dhaka, Bangladesh (lat: 23.8103, lng: 90.4125, zoom: 13)
  var MAP_CENTER = [23.8103, 90.4125];
  var MAP_ZOOM   = 13;

  function initMap() {
    var mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;

    var map = L.map('map', {
      center: MAP_CENTER,
      zoom: MAP_ZOOM,
      zoomControl: true,
      scrollWheelZoom: false,    // disabled for embedded UX
      attributionControl: true,
    });

    // OpenStreetMap tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // --------------------------------------------------------
    // PHP integration point: add route polyline markers here
    // e.g. fetch('/api/routes.php').then(r => r.json()).then(data => drawRoutes(map, data))
    // --------------------------------------------------------

    // Sample markers — replace with dynamic data from backend
    var sampleStops = [
      { lat: 23.7277, lng: 90.3978, label: 'Motijheel Station' },
      { lat: 23.7805, lng: 90.4066, label: 'Kamalapur Terminal' },
      { lat: 23.8103, lng: 90.4125, label: 'Dhaka Central' },
      { lat: 23.8759, lng: 90.3795, label: 'Uttara Station' },
    ];

    var busIcon = L.divIcon({
      className: '',
      html: '<div style="width:10px;height:10px;background:#2563eb;border:2px solid #fff;border-radius:50%;box-shadow:0 0 0 2px #2563eb;"></div>',
      iconSize: [10, 10],
      iconAnchor: [5, 5],
    });

    sampleStops.forEach(function (stop) {
      L.marker([stop.lat, stop.lng], { icon: busIcon })
        .addTo(map)
        .bindPopup('<strong>' + stop.label + '</strong>');
    });

    // Draw a sample route polyline
    var routeCoords = sampleStops.map(function (s) { return [s.lat, s.lng]; });
    L.polyline(routeCoords, {
      color: '#2563eb',
      weight: 3,
      opacity: 0.75,
      dashArray: null,
    }).addTo(map);
  }

  // Run after DOM + Leaflet are ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMap);
  } else {
    initMap();
  }
})();

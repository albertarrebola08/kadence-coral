// document.addEventListener("DOMContentLoaded", function () {
//   if (typeof L === "undefined") {
//     return;
//   }

//   const maps = document.querySelectorAll(".on-hem-cantat-map");

//   if (!maps.length) {
//     return;
//   }

//   maps.forEach(function (mapElement) {
//     let llocs = [];

//     try {
//       llocs = JSON.parse(mapElement.dataset.llocs || "[]");
//     } catch (error) {
//       llocs = [];
//     }

//     const zona = mapElement.dataset.zona || "tots";

//     let initialCenter = [41.7, 1.8];
//     let initialZoom = 8;

//     if (zona === "resta_espanya") {
//       initialCenter = [40.4, -3.7];
//       initialZoom = 6;
//     }

//     if (zona === "resta_mon") {
//       initialCenter = [30, 0];
//       initialZoom = 2;
//     }

//     const map = L.map(mapElement).setView(initialCenter, initialZoom);

//     L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
//       maxZoom: 19,
//       attribution: "&copy; OpenStreetMap",
//     }).addTo(map);

//     const markers = [];

//     llocs.forEach(function (lloc) {
//       if (!lloc.latitud || !lloc.longitud) {
//         return;
//       }

//       const marker = L.marker([lloc.latitud, lloc.longitud]).addTo(map);

//       let popup = `
//         <div class="on-hem-cantat-popup">
//           <strong>${escapeHtml(lloc.titol)}</strong>
//       `;

//       if (lloc.municipi) {
//         popup += `<p>${escapeHtml(lloc.municipi)}</p>`;
//       }

//       if (lloc.descripcio) {
//         popup += `<div class="on-hem-cantat-popup-desc">${lloc.descripcio}</div>`;
//       }

//       popup += `</div>`;

//       marker.bindPopup(popup);
//       markers.push(marker);
//     });

//     if (markers.length > 0) {
//       const group = L.featureGroup(markers);

//       map.fitBounds(group.getBounds(), {
//         padding: [35, 35],
//         maxZoom: 10,
//       });
//     }
//   });
// });

// function escapeHtml(value) {
//   if (!value) {
//     return "";
//   }

//   return String(value)
//     .replaceAll("&", "&amp;")
//     .replaceAll("<", "&lt;")
//     .replaceAll(">", "&gt;")
//     .replaceAll('"', "&quot;")
//     .replaceAll("'", "&#039;");
// }

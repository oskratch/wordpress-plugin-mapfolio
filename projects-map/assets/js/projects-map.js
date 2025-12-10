(function($) {
    'use strict';

    $(document).ready(function() {
        if ($('#projects-map').length) {
            initMap();
        }
    });

    function initMap() {
        var map = L.map('projects-map', {
            maxZoom: projectsMapData.max_zoom,
            minZoom: projectsMapData.min_zoom
        }).setView([20, 0], projectsMapData.default_zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var markers = L.markerClusterGroup();
        var newTab = $('#projects-map').data('new-tab');

        // Calculate adaptive popup max width based on map container width
        var mapContainer = $('#projects-map');
        var mapWidth = mapContainer.width();
        var popupMaxWidth = Math.min(500, mapWidth - 40);

        projectsMapData.projects.forEach(function(project) {
            var icon = L.divIcon({
                className: 'custom-marker',
                html: '<div style="background-color: ' + project.color + '; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            });

            var marker = L.marker([project.lat, project.lng], {icon: icon});
            var popupContent = '<div class="project-popup">';
            if (project.image) {
                popupContent += '<img src="' + project.image + '" alt="' + project.title + '">';
            }
            popupContent += '<h3>' + project.title + '</h3>';
            popupContent += '<p>' + project.description + '</p>';
            if (project.url) {
                popupContent += '<a href="' + project.url + '"' + (newTab ? ' target="_blank"' : '') + '>' + projectsMapData.strings.see_more + '</a>';
            }
            popupContent += '</div>';
            marker.bindPopup(popupContent, {
                maxWidth: popupMaxWidth,
                autoPan: true,
                keepInView: true,
                autoPanPadding: [100, 50],
                autoPanPaddingTopLeft: [20, 20],
                autoPanPaddingBottomRight: [100, 50]
            });
            markers.addLayer(marker);
        });

        map.addLayer(markers);
    }

})(jQuery);
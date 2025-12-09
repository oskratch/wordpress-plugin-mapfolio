(function($) {
    'use strict';

    $(document).ready(function() {
        if ($('#projects-map').length) {
            initMap();
        }
    });

    function initMap() {
        var map = L.map('projects-map').setView([20, 0], 2);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var markers = L.markerClusterGroup();

        projectsMapData.projects.forEach(function(project) {
            var marker = L.marker([project.lat, project.lng]);
            var popupContent = '<div class="project-popup">';
            if (project.image) {
                popupContent += '<img src="' + project.image + '" alt="' + project.title + '" style="max-width:100px;"><br>';
            }
            popupContent += '<strong>' + project.title + '</strong><br>';
            popupContent += project.description + '<br>';
            if (project.url) {
                popupContent += '<a href="' + project.url + '" target="_blank">' + projectsMapData.strings.see_more + '</a>';
            }
            popupContent += '</div>';
            marker.bindPopup(popupContent);
            markers.addLayer(marker);
        });

        map.addLayer(markers);
    }

})(jQuery);
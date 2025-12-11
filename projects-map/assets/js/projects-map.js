(function($) {
    'use strict';

    $(document).ready(function() {
        if ($('#projects-map').length) {
            initMap();
        }
    });

    function initMap() {
        var debug = $('#projects-map').data('debug');
        
        if (debug) {
            console.log('=== PROJECTS MAP PLUGIN LOADED ===');
            console.log('jQuery version:', $.fn.jquery);
            console.log('Leaflet loaded:', typeof L !== 'undefined');
            console.log('Projects Map: Document ready, map container found');
            console.log('Projects Map: Initializing map...');
            console.log('Projects Map: Raw debug data attribute:', $('#projects-map').attr('data-debug'));
            console.log('Projects Map: Parsed debug value:', debug, 'Type:', typeof debug);
        }
        
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

        if (debug) {
            console.log('Projects Map Debug:');
            console.log('- jQuery loaded:', typeof $ !== 'undefined');
            console.log('- Map container exists:', mapContainer.length > 0);
            console.log('- Map width:', mapWidth);
            console.log('- Projects count:', projectsMapData.projects.length);
            console.log('- Projects data:', projectsMapData.projects);
            console.log('- Max zoom:', projectsMapData.max_zoom);
            console.log('- Min zoom:', projectsMapData.min_zoom);
            console.log('- Default zoom:', projectsMapData.default_zoom);
        }

        projectsMapData.projects.forEach(function(project, index) {
            if (debug) {
                console.log('Processing project ' + index + ':', project);
            }

            try {
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

                if (debug) {
                    console.log('Marker added for project:', project.title);
                }
            } catch (error) {
                console.error('Error creating marker for project:', project, error);
            }
        });

        map.addLayer(markers);

        if (debug) {
            console.log('Map initialization complete');
        }
    }

})(jQuery);
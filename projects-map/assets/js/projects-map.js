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

        // Calculate adaptive popup max width based on map container width and screen size
        var mapContainer = $('#projects-map');
        var mapWidth = mapContainer.width();
        var screenWidth = window.innerWidth;
        var isMobile = screenWidth < 768;
        var popupMaxWidth = isMobile ? Math.min(250, Math.min(mapWidth, screenWidth) - 20) : Math.min(500, Math.min(mapWidth, screenWidth) - 40);

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
                    html: '<div style="background-color: ' + (project.category_color || project.color || '#0080c0') + '; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });

                var marker = L.marker([project.lat, project.lng], {icon: icon});
                var popupContent = '<div class="project-popup">';
                if (project.image) {
                    // Determine if image is SVG (ignore query string and hash)
                    var imgUrl = project.image || '';
                    var imgUrlNoQuery = imgUrl.split('?')[0].split('#')[0];
                    var isSvg = imgUrlNoQuery.toLowerCase().endsWith('.svg');
                    var imgClass = isSvg ? ' class="is-svg"' : '';
                    var imgStyle = isSvg ? 'style="display:block; margin-bottom:8px; height:40px !important; width:auto !important; max-width:none !important;"' : 'style="max-width:100px; display:block; margin-bottom:8px;"';
                    popupContent += '<img src="' + project.image + '" alt="' + project.title + '"' + imgClass + ' ' + imgStyle + '>';
                }
                popupContent += '<h3>' + project.title + '</h3>';
                popupContent += '<p>' + project.description + '</p>';
                if (project.url) {
                    var linkText = (project.link_text && project.link_text.trim()) || projectsMapData.strings.read_more;
                    popupContent += '<a href="' + project.url + '"' + (newTab ? ' target="_blank"' : '') + '>' + linkText + '</a>';
                }
                popupContent += '</div>';
                marker.bindPopup(popupContent, {
                    maxWidth: popupMaxWidth,
                    autoPan: true,
                    keepInView: true,
                    autoPanPadding: isMobile ? [50, 25] : [100, 50],
                    autoPanPaddingTopLeft: isMobile ? [10, 10] : [20, 20],
                    autoPanPaddingBottomRight: isMobile ? [50, 25] : [100, 50]
                });
                markers.addLayer(marker);

                // When popup opens, ensure images have loaded before Leaflet calculates popup size.
                // If an image loads after opening, update the popup and adjust map so popup stays visible.
                marker.on('popupopen', function(e) {
                    var popup = e.popup;
                    var container = popup.getElement ? popup.getElement() : (popup._container || null);
                    if (!container) return;
                    var imgs = container.querySelectorAll('img');
                    if (!imgs || imgs.length === 0) return;

                    imgs.forEach(function(img) {
                        if (img.complete) {
                            try { popup.update(); } catch (err) {}
                        } else {
                            img.addEventListener('load', function() {
                                try { popup.update(); } catch (err) {}
                                // small timeout to let layout settle, then try to keep popup in view
                                setTimeout(function() {
                                    try {
                                        // center map on popup latlng as fallback to ensure visibility
                                        if (popup.getLatLng) {
                                            map.panTo(popup.getLatLng(), { animate: false });
                                        }
                                    } catch (e) {}
                                }, 50);
                            });
                        }
                    });
                });

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
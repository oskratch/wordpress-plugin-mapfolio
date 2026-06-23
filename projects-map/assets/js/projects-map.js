(function($) {
    'use strict';

    $(document).ready(function() {
        $('.projects-map-container').each(function() {
            initMap($(this));
        });
    });

    function initMap($container) {
        var mapId = $container.data('map-id');
        var debug = $container.data('debug');
        var containerId = $container.attr('id');
        
        if (debug) {
            console.log('=== PROJECTS MAP PLUGIN LOADED ===');
            console.log('jQuery version:', $.fn.jquery);
            console.log('Leaflet loaded:', typeof L !== 'undefined');
            console.log('Map ID:', mapId);
            console.log('Container ID:', containerId);
        }
        
        // Per-map settings (set by shortcode via data attributes, fall back to global)
        var maxZoom     = parseInt($container.data('max-zoom'))     || projectsMapGlobal.max_zoom;
        var minZoom     = parseInt($container.data('min-zoom'))     || projectsMapGlobal.min_zoom;
        var defaultZoom = parseInt($container.data('default-zoom')) || projectsMapGlobal.default_zoom;
        var mapStyle    = $container.data('map-style')              || projectsMapGlobal.map_style || 'openstreetmap';

        var map = L.map(containerId, {
            maxZoom: maxZoom,
            minZoom: minZoom
        });

        // Map tile layers based on selected style
        var tileLayer, attribution;
        
        switch(mapStyle) {
            case 'cartodb-positron':
                tileLayer = 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png';
                attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';
                break;
            case 'cartodb-voyager':
                tileLayer = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
                attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';
                break;
            case 'esri-gray':
                tileLayer = 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}';
                attribution = 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ';
                break;
            case 'openstreetmap':
            default:
                tileLayer = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
                attribution = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
                break;
        }

        L.tileLayer(tileLayer, {
            attribution: attribution
        }).addTo(map);

        var markers = L.markerClusterGroup();
        var newTab = $container.data('new-tab');

        // Calculate adaptive popup max width based on map container width and screen size
        var mapWidth = $container.width();
        var screenWidth = window.innerWidth;
        var isMobile = screenWidth < 768;
        var popupMaxWidth = isMobile ? Math.min(250, Math.min(mapWidth, screenWidth) - 20) : Math.min(500, Math.min(mapWidth, screenWidth) - 40);

        // Load locations via AJAX
        $.ajax({
            url: projectsMapGlobal.ajax_url,
            type: 'POST',
            data: {
                action: 'get_map_locations',
                map_id: mapId,
                nonce: projectsMapGlobal.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    var locations = response.data;
                    
                    if (debug) {
                        console.log('Locations loaded:', locations.length);
                        console.log('Locations data:', locations);
                    }

                    locations.forEach(function(location, index) {
                        if (debug) {
                            console.log('Processing location ' + index + ':', location);
                        }

                        try {
                            var icon = L.divIcon({
                                className: 'custom-marker',
                                html: '<div style="background-color: ' + (location.category_color || location.color || '#0080c0') + '; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.5);"></div>',
                                iconSize: [20, 20],
                                iconAnchor: [10, 10]
                            });

                            var marker = L.marker([location.lat, location.lng], {icon: icon});
                            var popupContent = '<div class="project-popup">';
                            if (location.image) {
                                // Determine if image is SVG (ignore query string and hash)
                                var imgUrl = location.image || '';
                                var imgUrlNoQuery = imgUrl.split('?')[0].split('#')[0];
                                var isSvg = imgUrlNoQuery.toLowerCase().endsWith('.svg');
                                var imgClass = isSvg ? ' class="is-svg"' : '';
                                var imgStyle = isSvg ? 'style="display:block; margin-bottom:8px; height:40px !important; width:auto !important; max-width:none !important;"' : 'style="max-width:100px; display:block; margin-bottom:8px;"';
                                popupContent += '<img src="' + location.image + '" alt="' + location.title + '"' + imgClass + ' ' + imgStyle + '>';
                            }
                            popupContent += '<h3>' + location.title + '</h3>';
                            popupContent += '<p>' + location.description + '</p>';
                            if (location.url) {
                                var linkText = (location.link_text && location.link_text.trim()) || projectsMapGlobal.strings.read_more;
                                popupContent += '<a href="' + location.url + '"' + (newTab ? ' target="_blank"' : '') + '>' + linkText + '</a>';
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
                                console.log('Marker added for location:', location.title);
                            }
                        } catch (error) {
                            console.error('Error creating marker for location:', location, error);
                        }
                    });

                    map.addLayer(markers);

                    // Auto-fit map to show all locations with appropriate padding
                    if (locations.length > 0) {
                        try {
                            var bounds = markers.getBounds();
                            if (bounds.isValid()) {
                                map.fitBounds(bounds, {
                                    padding: [50, 50], // Add 50px padding on all sides
                                    maxZoom: defaultZoom // Don't zoom in too much for single locations
                                });
                            } else {
                                // Fallback if bounds are invalid
                                map.setView([20, 0], defaultZoom);
                            }
                        } catch (e) {
                            console.error('Error fitting bounds:', e);
                            // Fallback to default view
                            map.setView([20, 0], defaultZoom);
                        }
                    } else {
                        // No locations, use default view
                        map.setView([20, 0], defaultZoom);
                    }

                    if (debug) {
                        console.log('Map initialization complete');
                    }
                } else {
                    console.error('Failed to load locations:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading locations:', error);
            }
        });
    }

})(jQuery);
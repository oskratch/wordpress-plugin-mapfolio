(function($) {
    'use strict';

    var TILE_LAYERS = {
        'openstreetmap': {
            url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        },
        'cartodb-positron': {
            url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            carto: true
        },
        'cartodb-voyager': {
            url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            carto: true
        },
        'esri-gray': {
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
            attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ'
        }
    };

    $(function() {
        $('.mapfolio-container').each(function() {
            initMap($(this));
        });
    });

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Only allow http(s) and relative URLs in links and images
    function safeUrl(value) {
        var url = String(value || '').trim();
        return /^(https?:)?\/\//i.test(url) || url.charAt(0) === '/' ? url : '';
    }

    function initMap($container) {
        var debug = !!$container.data('debug');
        var log = function() {
            if (debug && window.console) {
                console.log.apply(console, ['[Mapfolio]'].concat(Array.prototype.slice.call(arguments)));
            }
        };

        var mapId       = $container.data('map-id');
        var newTab      = !!$container.data('new-tab');
        var minZoom     = parseInt($container.data('min-zoom'), 10) || 1;
        var maxZoom     = parseInt($container.data('max-zoom'), 10) || 18;
        var defaultZoom = parseInt($container.data('default-zoom'), 10) || 2;
        var tiles       = TILE_LAYERS[$container.data('map-style')] || TILE_LAYERS.openstreetmap;

        log('Init map', mapId, 'in #' + $container.attr('id'), '| Leaflet', typeof L !== 'undefined' ? L.version : 'not loaded');

        var map = L.map($container.attr('id'), { minZoom: minZoom, maxZoom: maxZoom });

        // CARTO basemaps require an API key since August 2026
        var tileUrl = tiles.url;
        if (tiles.carto && mapfolioGlobal.carto_api_key) {
            tileUrl += '?key=' + encodeURIComponent(mapfolioGlobal.carto_api_key);
        }
        L.tileLayer(tileUrl, { attribution: tiles.attribution }).addTo(map);

        var clusterColorSmall = mapfolioGlobal.cluster_color_small || '#29B246';
        var clusterColorLarge = mapfolioGlobal.cluster_color_large || '#E67E22';
        var markers = L.markerClusterGroup({
            iconCreateFunction: function(cluster) {
                var count = cluster.getChildCount();
                var color = count < 10 ? clusterColorSmall : clusterColorLarge;
                var size  = count < 10 ? 40 : (count < 100 ? 48 : 56);
                return L.divIcon({
                    className: 'mapfolio-cluster',
                    html: '<div class="mapfolio-cluster-bubble" style="background-color: ' + escapeHtml(color) + '; width: ' + size + 'px; height: ' + size + 'px; line-height: ' + (size - 6) + 'px;">' + count + '</div>',
                    iconSize: L.point(size, size)
                });
            }
        });

        // Fixed popup width (clamped to the map/screen size) so every popup looks the same
        // regardless of title length; the image adapts to this width.
        var available = Math.min($container.width(), window.innerWidth);
        var isMobile  = window.innerWidth < 768;
        var popupWidth = isMobile ? Math.min(250, available - 20) : Math.min(320, available - 40);

        $.post(mapfolioGlobal.ajax_url, { action: 'mapfolio_get_locations', map_id: mapId })
            .done(function(response) {
                if (!response || !response.success) {
                    console.error('[Mapfolio] Failed to load locations:', response);
                    map.setView([20, 0], defaultZoom);
                    return;
                }

                var locations = response.data || [];
                var byCategory = {};
                log('Locations loaded:', locations.length, locations);

                locations.forEach(function(location) {
                    try {
                        var marker = L.marker([location.lat, location.lng], {
                            icon: L.divIcon({
                                className: 'mapfolio-marker',
                                html: '<div class="mapfolio-marker-dot" style="background-color: ' + escapeHtml(location.color) + ';"></div>',
                                iconSize: [20, 20],
                                iconAnchor: [10, 10]
                            })
                        });
                        marker.bindPopup(buildPopup(location, newTab), {
                            minWidth: popupWidth,
                            maxWidth: popupWidth,
                            autoPan: true,
                            keepInView: true,
                            autoPanPaddingTopLeft: isMobile ? [10, 10] : [20, 20],
                            autoPanPaddingBottomRight: isMobile ? [50, 25] : [100, 50]
                        });
                        marker.on('popupopen', function(e) { refreshPopupOnImageLoad(map, e.popup); });
                        markers.addLayer(marker);
                        (byCategory[location.category_id] = byCategory[location.category_id] || []).push(marker);
                    } catch (error) {
                        console.error('[Mapfolio] Error creating marker for location:', location, error);
                    }
                });

                map.addLayer(markers);
                initLegendFilter($container, markers, byCategory);

                var bounds = markers.getBounds();
                if (locations.length && bounds.isValid()) {
                    // maxZoom keeps a single location from being zoomed in too much
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: defaultZoom });
                } else {
                    map.setView([20, 0], defaultZoom);
                }
                log('Map ready');
            })
            .fail(function(xhr, status, error) {
                console.error('[Mapfolio] AJAX error loading locations:', error);
                map.setView([20, 0], defaultZoom);
            });
    }

    // Clicking a category in the legend shows or hides its markers
    function initLegendFilter($container, markers, byCategory) {
        var $legend = $('.mapfolio-legend-filter[data-map-container="' + $container.attr('id') + '"]');
        $legend.on('click', 'button.mapfolio-legend-item', function() {
            var $item = $(this);
            var layers = byCategory[$item.data('category')] || [];
            var visible = $item.attr('aria-pressed') !== 'false';
            if (visible) {
                markers.removeLayers(layers);
            } else {
                markers.addLayers(layers);
            }
            $item.attr('aria-pressed', visible ? 'false' : 'true');
        });
    }

    function buildPopup(location, newTab) {
        var html  = '<div class="mapfolio-popup">';
        var image = safeUrl(location.image);
        if (image) {
            // SVGs (logos, icons) get a fixed height instead of full width; ignore query string and hash
            var isSvg = /\.svg$/i.test(image.split('?')[0].split('#')[0]);
            html += '<img src="' + escapeHtml(image) + '" alt=""' + (isSvg ? ' class="is-svg"' : '') + '>';
        }
        html += '<h3>' + escapeHtml(location.title) + '</h3>';
        if (location.description) {
            html += '<p>' + escapeHtml(location.description).replace(/\r?\n/g, '<br>') + '</p>';
        }
        var url = safeUrl(location.url);
        if (url) {
            var linkText = $.trim(location.link_text || '') || mapfolioGlobal.strings.read_more;
            html += '<a href="' + escapeHtml(url) + '"' + (newTab ? ' target="_blank" rel="noopener"' : '') + '>' + escapeHtml(linkText) + '</a>';
        }
        return html + '</div>';
    }

    // Leaflet sizes the popup when it opens; if an image loads afterwards, resize it and keep it in view
    function refreshPopupOnImageLoad(map, popup) {
        var element = popup.getElement();
        if (!element) {
            return;
        }
        $(element).find('img').each(function() {
            if (this.complete) {
                popup.update();
                return;
            }
            $(this).one('load', function() {
                popup.update();
                setTimeout(function() {
                    map.panTo(popup.getLatLng(), { animate: false });
                }, 50);
            });
        });
    }

})(jQuery);

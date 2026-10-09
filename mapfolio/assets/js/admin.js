jQuery(function($) {
    'use strict';

    var strings = mapfolioAdmin.strings;

    // Confirm before following delete links
    $(document).on('click', '.mapfolio-confirm', function(e) {
        if (!window.confirm($(this).data('confirm'))) {
            e.preventDefault();
        }
    });

    // Copy shortcode to the clipboard
    $(document).on('click', '.mapfolio-copy', function() {
        var $button = $(this);
        var input = $button.siblings('input')[0];
        var done = function() {
            var label = $button.text();
            $button.text(strings.copied);
            setTimeout(function() { $button.text(label); }, 1500);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(input.value).then(done);
        } else {
            input.select();
            document.execCommand('copy');
            done();
        }
    });

    // Keep the native color picker and its text field in sync
    $('[data-mapfolio-color-target]').each(function() {
        var $picker = $(this);
        var $text = $('#' + $picker.data('mapfolio-color-target'));
        $picker.on('input', function() { $text.val(this.value); });
        $text.on('input', function() {
            if (/^#[0-9a-f]{6}$/i.test(this.value)) {
                $picker.val(this.value);
            }
        });
    });

    // Generate the map slug from the title while the slug has not been edited by hand
    $('[data-mapfolio-slug-source]').each(function() {
        var $slug = $(this);
        var source = $slug.data('mapfolio-slug-source');
        if (!source) {
            return;
        }
        var touched = $slug.val() !== '';
        $slug.on('input', function() { touched = this.value !== ''; });
        $(source).on('input', function() {
            if (touched) {
                return;
            }
            $slug.val(this.value
                .normalize('NFD').replace(/[̀-ͯ]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/[\s-]+/g, '-'));
        });
    });

    // Media library picker for the location image
    var mediaFrame;
    $('[data-mapfolio-media-target]').on('click', function(e) {
        e.preventDefault();
        var $input = $($(this).data('mapfolio-media-target'));
        if (!mediaFrame) {
            mediaFrame = wp.media({
                title: strings.choose_image,
                button: { text: strings.use_image },
                library: { type: 'image' },
                multiple: false
            });
            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                $input.val(attachment.url).trigger('input');
            });
        }
        mediaFrame.open();
    });

    $('#image').on('input change', function() {
        var url = $.trim(this.value);
        $('.mapfolio-image-preview').attr('src', url).prop('hidden', !url);
    });

    initLocationPicker();

    // Map to set the position of a location: click, drag the marker, or search for a place
    function initLocationPicker() {
        var element = document.getElementById('mapfolio-location-picker');
        if (!element || typeof L === 'undefined') {
            return;
        }

        var $lat = $('#lat');
        var $lng = $('#lng');
        var marker = null;

        var map = L.map(element);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        function round(value) {
            return Math.round(value * 1e6) / 1e6;
        }

        function placeMarker(latlng) {
            if (marker) {
                marker.setLatLng(latlng);
                return;
            }
            marker = L.marker(latlng, { draggable: true }).addTo(map);
            marker.on('dragend', function() { updateFields(marker.getLatLng()); });
        }

        function updateFields(latlng) {
            $lat.val(round(latlng.lat));
            $lng.val(round(L.Util.wrapNum(latlng.lng, [-180, 180], true)));
        }

        function readFields() {
            var lat = parseFloat($lat.val());
            var lng = parseFloat($lng.val());
            if (isNaN(lat) || isNaN(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
                return null;
            }
            return L.latLng(lat, lng);
        }

        var initial = readFields();
        if (initial) {
            placeMarker(initial);
            map.setView(initial, 12);
        } else {
            map.setView([20, 0], 2);
        }

        map.on('click', function(e) {
            placeMarker(e.latlng);
            updateFields(e.latlng);
        });

        $lat.add($lng).on('change', function() {
            var latlng = readFields();
            if (latlng) {
                placeMarker(latlng);
                map.panTo(latlng);
            }
        });

        // Place search with OpenStreetMap Nominatim (only runs when the user clicks "Search")
        var $query = $('#mapfolio-geocode-query');
        var $status = $('.mapfolio-geocode-status');

        function search() {
            var query = $.trim($query.val());
            if (!query) {
                return;
            }
            $status.text(strings.searching);
            $.getJSON('https://nominatim.openstreetmap.org/search', {
                q: query,
                format: 'jsonv2',
                limit: 1,
                addressdetails: 1,
                'accept-language': mapfolioAdmin.language
            }).done(function(results) {
                if (!results || !results.length) {
                    $status.text(strings.search_empty);
                    return;
                }
                $status.text('');
                var result = results[0];
                var latlng = L.latLng(parseFloat(result.lat), parseFloat(result.lon));
                placeMarker(latlng);
                updateFields(latlng);
                map.setView(latlng, 14);
                if (result.address && result.address.country && !$.trim($('#country').val())) {
                    $('#country').val(result.address.country);
                }
            }).fail(function() {
                $status.text(strings.search_error);
            });
        }

        $('#mapfolio-geocode-button').on('click', search);
        $query.on('keydown', function(e) {
            // Enter searches instead of submitting the form
            if (e.key === 'Enter') {
                e.preventDefault();
                search();
            }
        });
    }
});

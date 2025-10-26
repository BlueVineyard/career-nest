/* global google */
(function () {
  function $(id) {
    return document.getElementById(id);
  }

  function bindAutocomplete(config) {
    var input = $(config.inputId);
    if (!input) return;
    if (typeof google === "undefined" || !google.maps || !google.maps.places)
      return;
    try {
      var options = {
        fields: ["formatted_address", "geometry", "name", "place_id"],
      };

      // Use address types for more specific location searches (includes street addresses)
      // Don't restrict types for job locations to allow full address input
      if (config.inputId !== "careernest_job_location") {
        options.types = ["geocode"];
      }

      // Add country restrictions if available
      if (
        typeof careerNestMaps !== "undefined" &&
        careerNestMaps.countries &&
        careerNestMaps.countries.length > 0
      ) {
        options.componentRestrictions = { country: careerNestMaps.countries };
      }

      var ac = new google.maps.places.Autocomplete(input, options);
      ac.addListener("place_changed", function () {
        var place = ac.getPlace();
        var pid = $(config.placeIdId);
        var lat = $(config.latId);
        var lng = $(config.lngId);
        if (place) {
          if (place.formatted_address) {
            input.value = place.formatted_address;
          }
          if (pid) {
            pid.value = place.place_id || "";
          }
          if (place.geometry && place.geometry.location) {
            if (lat) {
              lat.value = String(place.geometry.location.lat());
            }
            if (lng) {
              lng.value = String(place.geometry.location.lng());
            }
          }
        }
      });
      // If user edits manually, clear stored place metadata to avoid stale coords
      input.addEventListener("input", function () {
        var pid = $(config.placeIdId);
        var lat = $(config.latId);
        var lng = $(config.lngId);
        if (pid) pid.value = "";
        if (lat) lat.value = "";
        if (lng) lng.value = "";
      });
    } catch (e) {
      // Fail silently; input remains a plain text field
    }
  }

  function bindPickOnMap(cfg) {
    var btn = $(cfg.buttonId);
    var modal = $(cfg.modalId);
    var mapEl = $(cfg.mapId);
    var useBtn = $(cfg.useId);
    var cancelEls = (cfg.cancelIds || []).map(function (id) {
      return $(id);
    });
    if (!btn || !modal || !mapEl || !useBtn) return;
    if (typeof google === "undefined" || !google.maps) return;

    var map, marker, geocoder;
    function openModal() {
      modal.style.display = "flex";
      setTimeout(function () {
        // Initialize or refresh map
        if (!map) {
          var lat =
            parseFloat(($(cfg.latId) && $(cfg.latId).value) || "") || -25.2744; // AU default
          var lng =
            parseFloat(($(cfg.lngId) && $(cfg.lngId).value) || "") || 133.7751;
          var center = { lat: lat, lng: lng };

          // Determine zoom and bounds based on selected countries
          var zoom = 5;
          var bounds = null;

          if (
            typeof careerNestMaps !== "undefined" &&
            careerNestMaps.countries &&
            careerNestMaps.countries.length > 0
          ) {
            // Country bounds (approximate bounding boxes)
            var countryBounds = {
              au: { north: -9.0, south: -45.0, east: 155.0, west: 112.0 },
              ca: { north: 83.0, south: 41.7, east: -52.6, west: -141.0 },
              nz: { north: -34.0, south: -47.3, east: 179.0, west: 166.0 },
              us: { north: 49.4, south: 24.5, east: -66.9, west: -125.0 },
              gb: { north: 60.9, south: 49.9, east: 1.8, west: -8.6 },
              de: { north: 55.1, south: 47.3, east: 15.0, west: 5.9 },
              fr: { north: 51.1, south: 41.3, east: 9.6, west: -5.1 },
            };

            // Create bounds that encompass all selected countries
            bounds = new google.maps.LatLngBounds();
            var hasValidCountry = false;

            for (var i = 0; i < careerNestMaps.countries.length; i++) {
              var countryCode = careerNestMaps.countries[i].toLowerCase();
              if (countryBounds[countryCode]) {
                hasValidCountry = true;
                var cb = countryBounds[countryCode];
                bounds.extend(new google.maps.LatLng(cb.north, cb.west));
                bounds.extend(new google.maps.LatLng(cb.south, cb.east));
              }
            }

            // If we have valid countries, use first country's center
            if (hasValidCountry && careerNestMaps.countries.length > 0) {
              var firstCountry = careerNestMaps.countries[0].toLowerCase();
              if (countryBounds[firstCountry]) {
                var cb = countryBounds[firstCountry];
                center = {
                  lat: (cb.north + cb.south) / 2,
                  lng: (cb.east + cb.west) / 2,
                };
              }
            }
          }

          map = new google.maps.Map(mapEl, {
            center: center,
            zoom: zoom,
            mapTypeControl: false,
          });

          // Fit bounds if we have them
          if (bounds && !bounds.isEmpty()) {
            map.fitBounds(bounds);
          }

          geocoder = new google.maps.Geocoder();
          marker = new google.maps.Marker({
            position: center,
            map: map,
            draggable: true,
          });
          map.addListener("click", function (e) {
            marker.setPosition(e.latLng);
          });
        } else {
          google.maps.event.trigger(map, "resize");
        }
      }, 10);
    }
    function closeModal() {
      modal.style.display = "none";
    }

    function useLocation() {
      var latLng = marker.getPosition();
      if (!latLng) {
        closeModal();
        return;
      }
      var lat = latLng.lat();
      var lng = latLng.lng();
      var latInput = $(cfg.latId);
      var lngInput = $(cfg.lngId);
      var pidInput = $(cfg.placeIdId);
      var addrInput = $(cfg.inputId);
      if (latInput) latInput.value = String(lat);
      if (lngInput) lngInput.value = String(lng);
      // Reverse geocode to fill address and possibly place_id
      if (geocoder && addrInput) {
        geocoder.geocode(
          { location: { lat: lat, lng: lng } },
          function (res, status) {
            if (status === "OK" && res && res[0]) {
              addrInput.value = res[0].formatted_address || addrInput.value;
              if (pidInput) pidInput.value = res[0].place_id || "";
            } else {
              if (pidInput) pidInput.value = "";
            }
            closeModal();
          }
        );
      } else {
        if (pidInput) pidInput.value = "";
        closeModal();
      }
    }

    btn.addEventListener("click", openModal);
    useBtn.addEventListener("click", useLocation);
    cancelEls.forEach(function (el) {
      if (el) el.addEventListener("click", closeModal);
    });
  }

  function init() {
    // Admin autocomplete bindings
    bindAutocomplete({
      inputId: "careernest_applicant_location",
      placeIdId: "careernest_applicant_place_id",
      latId: "careernest_applicant_lat",
      lngId: "careernest_applicant_lng",
    });
    bindAutocomplete({
      inputId: "careernest_location",
      placeIdId: "careernest_employer_place_id",
      latId: "careernest_employer_lat",
      lngId: "careernest_employer_lng",
    });
    bindAutocomplete({
      inputId: "careernest_job_location",
      placeIdId: "careernest_job_place_id",
      latId: "careernest_job_lat",
      lngId: "careernest_job_lng",
    });

    // Frontend location filter autocomplete (job listing page)
    // Only binds to text value, no hidden fields needed for filtering
    var frontendLocationInput = $("job_location");
    if (
      frontendLocationInput &&
      typeof google !== "undefined" &&
      google.maps &&
      google.maps.places
    ) {
      try {
        var options = {
          fields: ["formatted_address", "geometry", "name"],
          types: ["geocode"],
        };

        // Add country restrictions if available
        if (
          typeof careerNestMaps !== "undefined" &&
          careerNestMaps.countries &&
          careerNestMaps.countries.length > 0
        ) {
          options.componentRestrictions = { country: careerNestMaps.countries };
        }

        var autocomplete = new google.maps.places.Autocomplete(
          frontendLocationInput,
          options
        );

        // When user selects from autocomplete, trigger geocoding for radius search
        autocomplete.addListener("place_changed", function () {
          var place = autocomplete.getPlace();
          if (place && place.formatted_address) {
            frontendLocationInput.value = place.formatted_address;
            // Trigger input event to geocode and search
            var event = new Event("input", { bubbles: true });
            frontendLocationInput.dispatchEvent(event);
          }
        });
      } catch (e) {
        // Fail silently; remains a plain text field
      }
    }

    // Bind Pick-on-Map modals
    bindPickOnMap({
      buttonId: "careernest_applicant_pick_map",
      modalId: "careernest_applicant_map_modal",
      mapId: "careernest_applicant_map_canvas",
      useId: "careernest_applicant_map_use",
      cancelIds: [
        "careernest_applicant_map_cancel",
        "careernest_applicant_map_cancel_2",
      ],
      inputId: "careernest_applicant_location",
      placeIdId: "careernest_applicant_place_id",
      latId: "careernest_applicant_lat",
      lngId: "careernest_applicant_lng",
    });
    bindPickOnMap({
      buttonId: "careernest_employer_pick_map",
      modalId: "careernest_employer_map_modal",
      mapId: "careernest_employer_map_canvas",
      useId: "careernest_employer_map_use",
      cancelIds: [
        "careernest_employer_map_cancel",
        "careernest_employer_map_cancel_2",
      ],
      inputId: "careernest_location",
      placeIdId: "careernest_employer_place_id",
      latId: "careernest_employer_lat",
      lngId: "careernest_employer_lng",
    });
    bindPickOnMap({
      buttonId: "careernest_job_pick_map",
      modalId: "careernest_job_map_modal",
      mapId: "careernest_job_map_canvas",
      useId: "careernest_job_map_use",
      cancelIds: ["careernest_job_map_cancel", "careernest_job_map_cancel_2"],
      inputId: "careernest_job_location",
      placeIdId: "careernest_job_place_id",
      latId: "careernest_job_lat",
      lngId: "careernest_job_lng",
    });
  }

  // Expose init as global callback for Google Maps async loading
  window.initCareerNestMaps = function () {
    init();
  };

  // Also initialize immediately if Google Maps is already loaded
  if (
    document.readyState === "complete" ||
    document.readyState === "interactive"
  ) {
    if (typeof google !== "undefined" && google.maps) {
      init();
    }
  } else {
    document.addEventListener("DOMContentLoaded", function () {
      if (typeof google !== "undefined" && google.maps) {
        init();
      }
    });
  }
})();

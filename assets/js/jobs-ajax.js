/**
 * CareerNest Job Listings AJAX Handler
 */
(function ($) {
  "use strict";

  const JobsAjax = {
    init: function () {
      this.filterForm = $("#cn-jobs-filter-form");
      this.jobsContainer = $(".cn-jobs-main");
      this.isLoading = false;
      this.userLat = null;
      this.userLng = null;

      // Map view properties
      this.map = null;
      this.markers = [];
      this.infoWindows = [];
      this.userMarker = null;
      this.radiusCircle = null;
      this.markerClusterer = null;
      this.currentView = "list"; // 'list' or 'map'
      this.mapData = null; // Store latest map data

      this.bindEvents();
      this.initializeRadius();
      this.initializeViewToggle();
    },

    bindEvents: function () {
      const self = this;

      // Form submission
      this.filterForm.on("submit", function (e) {
        e.preventDefault();
        self.loadJobs(1);
      });

      // Real-time search (debounced)
      let searchTimeout;
      $("#job_search").on("input", function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
          self.loadJobs(1);
        }, 500);
      });

      // Filter changes (instant)
      $("#job_category, #job_type, #sort, #employer_id, #date_posted").on(
        "change",
        function () {
          // Skip if we're clearing filters
          if (self.isClearing) return;
          self.loadJobs(1);
        }
      );

      // Location filter (debounced)
      let locationTimeout;
      $("#job_location").on("input", function () {
        clearTimeout(locationTimeout);
        const location = $(this).val();
        locationTimeout = setTimeout(function () {
          // Geocode the location to get coordinates for radius search
          if (location) {
            self.geocodeLocation(location, function () {
              // Geocoding complete, now load jobs
              self.loadJobs(1);
            });
          } else {
            self.userLat = null;
            self.userLng = null;
            self.updateLocationStatus(false);
            self.loadJobs(1);
          }
        }, 500);
      });

      // Salary range sliders
      $("#min_salary, #max_salary").on("input", function () {
        self.updateSalaryDisplay();
      });

      $("#min_salary, #max_salary").on("change", function () {
        self.loadJobs(1);
      });

      // Radius slider
      $("#search_radius").on("input", function () {
        self.updateRadiusDisplay();
        self.updateRadiusBadge();
      });

      $("#search_radius").on("change", function () {
        self.loadJobs(1);
      });

      // Get location button
      $(document).on("click", ".cn-get-location-btn", function (e) {
        e.preventDefault();
        self.getUserLocation();
      });

      // Distance chip click to expand radius filter
      $(document).on("click", ".cn-distance-chip-text", function (e) {
        e.stopPropagation();
        $(".cn-radius-filter").slideDown(300);
      });

      // Distance chip close button (X) to clear radius
      $(document).on("click", ".cn-distance-chip-close", function (e) {
        e.stopPropagation();
        $("#search_radius").val(0);
        self.updateRadiusDisplay();
        self.updateRadiusBadge();
        self.loadJobs(1);
      });

      // Hide radius filter when clicking outside
      $(document).on("click", function (e) {
        if (
          !$(e.target).closest(".cn-radius-filter").length &&
          !$(e.target).closest(".cn-distance-chip-text").length
        ) {
          const radius = parseInt($("#search_radius").val()) || 0;
          if (radius > 0 && $(".cn-radius-filter").is(":visible")) {
            $(".cn-radius-filter").slideUp(300);
          }
        }
      });

      // Pagination clicks (delegated)
      $(document).on("click", ".cn-pagination a", function (e) {
        e.preventDefault();
        const url = $(this).attr("href");
        const page = self.getPageFromUrl(url);
        self.loadJobs(page);
      });

      // Clear filters button
      $(document).on("click", ".cn-clear-filters-btn", function (e) {
        e.preventDefault();
        self.clearFilters();
      });

      // Clear filters link in empty state
      $(document).on("click", ".cn-btn-secondary", function (e) {
        if ($(this).text().includes("Clear")) {
          e.preventDefault();
          self.clearFilters();
        }
      });

      // Bookmark button
      $(document).on("click", ".cn-job-bookmark-btn", function (e) {
        e.preventDefault();
        e.stopPropagation();
        self.toggleBookmark($(this));
      });
    },

    toggleBookmark: function (btn) {
      const jobCard = btn.closest(".cn-job-card");
      const jobId = jobCard.data("job-id");

      if (!jobId) {
        console.error("Job ID not found");
        return;
      }

      // Disable button during request
      btn.prop("disabled", true);

      $.ajax({
        url: careerNestAjax.ajaxurl,
        type: "POST",
        data: {
          action: "careernest_toggle_bookmark",
          nonce: careerNestAjax.nonce,
          job_id: jobId,
        },
        success: (response) => {
          if (response.success) {
            // Toggle bookmarked class
            if (response.data.is_bookmarked) {
              btn.addClass("bookmarked");
              btn.attr("title", "Remove bookmark");
            } else {
              btn.removeClass("bookmarked");
              btn.attr("title", "Bookmark this job");
            }
          } else {
            // Handle non-applicant users - check if login required
            if (response.data.require_login) {
              // Get login page URL from CareerNest pages option
              const pages = careerNestAjax.pages || {};
              const loginUrl = pages.login || "/login/";
              window.location.href = loginUrl;
            } else {
              this.showError(response.data.message);
            }
          }
        },
        error: () => {
          this.showError("Failed to update bookmark. Please try again.");
        },
        complete: () => {
          btn.prop("disabled", false);
        },
      });
    },

    loadJobs: function (page) {
      if (this.isLoading) return;

      this.isLoading = true;
      this.showLoading();

      const formData = {
        action: "careernest_filter_jobs",
        nonce: careerNestAjax.nonce,
        search: $("#job_search").val(),
        category: $("#job_category").val(),
        type: $("#job_type").val(),
        location: $("#job_location").val(),
        employer: $("#employer_id").val(),
        min_salary: $("#min_salary").val(),
        max_salary: $("#max_salary").val(),
        date_posted: $("#date_posted").val(),
        sort: $("#sort").val(),
        radius: $("#search_radius").val() || 0,
        user_lat: this.userLat || 0,
        user_lng: this.userLng || 0,
        paged: page || 1,
      };

      $.ajax({
        url: careerNestAjax.ajaxurl,
        type: "POST",
        data: formData,
        success: (response) => {
          if (response.success) {
            this.updateResults(response.data);
            this.updateURL(formData);
            this.scrollToTop();
          } else {
            this.showError(response.data.message || "An error occurred");
          }
        },
        error: () => {
          this.showError("Failed to load jobs. Please try again.");
        },
        complete: () => {
          this.isLoading = false;
          this.hideLoading();
        },
      });
    },

    showLoading: function () {
      this.jobsContainer.css("pointer-events", "none");

      if (!$(".cn-loading-spinner").length) {
        this.jobsContainer.prepend(
          '<div class="cn-loading-spinner"><div class="spinner"></div><p>Loading jobs...</p></div>'
        );
      }
    },

    hideLoading: function () {
      this.jobsContainer.css("pointer-events", "auto");
      $(".cn-loading-spinner").remove();
    },

    updateResults: function (data) {
      // Store map data for later use
      if (data.markers) {
        this.mapData = {
          markers: data.markers,
          userLocation: data.user_location,
          radius: data.radius,
        };
      }

      // Update header
      if (data.header_html) {
        $(".cn-jobs-header").html(data.header_html);
      }

      // Update the appropriate view
      if (this.currentView === "map") {
        // Update list data silently (without showing)
        $(".cn-jobs-list-container").html(data.jobs_html);

        // Update map view
        if (this.mapData) {
          this.updateMapView();
        }
      } else {
        // Update and show list view
        $(".cn-jobs-list-container").fadeOut(200, () => {
          $(".cn-jobs-list-container").html(data.jobs_html);
          $(".cn-jobs-list-container").fadeIn(200);
        });
      }
    },

    reinitializeDropdowns: function () {
      // Reinitialize custom dropdowns if the component is available
      if (
        typeof window.CareerNestDropdown !== "undefined" &&
        window.CareerNestDropdown.init
      ) {
        window.CareerNestDropdown.init();
      }
    },

    showError: function (message) {
      const errorHtml = `
                <div class="cn-ajax-error">
                    <p><strong>Error:</strong> ${message}</p>
                </div>
            `;
      this.jobsContainer.prepend(errorHtml);

      setTimeout(() => {
        $(".cn-ajax-error").fadeOut(300, function () {
          $(this).remove();
        });
      }, 3000);
    },

    clearFilters: function () {
      // Set flag to prevent change handlers from firing loadJobs
      this.isClearing = true;

      // Clear all values
      $("#job_search").val("");
      $("#job_category").val("").trigger("change");
      $("#job_type").val("").trigger("change");
      $("#job_location").val("");
      $("#employer_id").val("").trigger("change");
      $("#min_salary").val(0);
      $("#max_salary").val(200000);
      $("#date_posted").val("").trigger("change");
      $("#sort").val("date_desc").trigger("change");
      $("#search_radius").val(0);

      this.userLat = null;
      this.userLng = null;
      this.updateSalaryDisplay();
      this.updateRadiusDisplay();
      this.updateRadiusBadge();
      this.updateLocationStatus(false);

      // Clear the flag
      this.isClearing = false;

      // Now load jobs once
      this.loadJobs(1);
    },

    updateSalaryDisplay: function () {
      const minVal = parseInt($("#min_salary").val()) || 0;
      const maxVal = parseInt($("#max_salary").val()) || 200000;
      const display =
        "$" +
        minVal.toLocaleString() +
        " - $" +
        (maxVal >= 200000 ? "200k+" : maxVal.toLocaleString());
      $("#salary-range-display").text(display);
    },

    updateURL: function (data) {
      const params = new URLSearchParams();

      if (data.search) params.set("job_search", data.search);
      if (data.category) params.set("job_category", data.category);
      if (data.type) params.set("job_type", data.type);
      if (data.location) params.set("job_location", data.location);
      if (data.employer) params.set("employer_id", data.employer);
      if (data.min_salary && data.min_salary > 0)
        params.set("min_salary", data.min_salary);
      if (data.max_salary && data.max_salary < 200000)
        params.set("max_salary", data.max_salary);
      if (data.date_posted) params.set("date_posted", data.date_posted);
      if (data.sort && data.sort !== "date_desc") params.set("sort", data.sort);
      if (data.paged && data.paged > 1) params.set("paged", data.paged);

      // Use current page path, removing any existing query string
      const currentPath = window.location.pathname;
      const newURL = params.toString()
        ? currentPath + "?" + params.toString()
        : currentPath;

      window.history.pushState({}, "", newURL);
    },

    getPageFromUrl: function (url) {
      // Try ?paged= or &paged= format first
      let match = url.match(/[?&]paged=(\d+)/);
      if (match) {
        return parseInt(match[1]);
      }

      // Try /page/X/ format
      match = url.match(/\/page\/(\d+)\/?/);
      if (match) {
        return parseInt(match[1]);
      }

      return 1;
    },

    scrollToTop: function () {
      $("html, body").animate(
        {
          scrollTop: $(".cn-jobs-header").offset().top - 100,
        },
        300
      );
    },

    initializeRadius: function () {
      const self = this;

      // Check if radius filter exists
      if ($("#search_radius").length) {
        this.updateRadiusDisplay();
        this.updateRadiusBadge(); // Initialize chip state

        // If location is already in input, try to geocode it
        const locationInput = $("#job_location").val();
        if (locationInput) {
          this.geocodeLocation(locationInput);
        }
      }
    },

    updateRadiusDisplay: function () {
      const radius = parseInt($("#search_radius").val()) || 0;
      if (radius === 0) {
        $("#radius-display").text("Any distance");
      } else {
        $("#radius-display").text(radius + " km");
      }
    },

    updateRadiusBadge: function () {
      const radius = parseInt($("#search_radius").val()) || 0;
      const chip = $(".cn-distance-chip");
      const hasLocation = this.userLat && this.userLng;

      // Show chip when location is set
      if (hasLocation) {
        if (radius > 0) {
          chip.find(".cn-distance-chip-text").text(radius + " km");
        } else {
          chip.find(".cn-distance-chip-text").text("Any distance");
        }
        chip.show();
      } else {
        chip.hide();
        // Hide radius filter when no location
        $(".cn-radius-filter").slideUp(300);
      }
    },

    getUserLocation: function () {
      const self = this;

      if (!navigator.geolocation) {
        self.showError("Geolocation is not supported by your browser");
        return;
      }

      const btn = $(".cn-get-location-btn");
      btn.prop("disabled", true).addClass("cn-loading");

      navigator.geolocation.getCurrentPosition(
        function (position) {
          self.userLat = position.coords.latitude;
          self.userLng = position.coords.longitude;

          // Reverse geocode to get location name
          self.reverseGeocode(self.userLat, self.userLng);

          self.updateLocationStatus(true);
          btn.prop("disabled", false).removeClass("cn-loading");

          // Trigger search if radius is set
          if (parseInt($("#search_radius").val()) > 0) {
            self.loadJobs(1);
          }
        },
        function (error) {
          console.error("Geolocation error:", error);

          let errorMsg = "Unable to get your location. ";
          if (error.code === 1) {
            // Permission denied or not available on HTTP
            if (window.location.protocol === "http:") {
              errorMsg =
                "Geolocation requires HTTPS. Please enter location manually or use a secure connection.";
            } else {
              errorMsg += "Please enable location permissions and try again.";
            }
          } else if (error.code === 2) {
            errorMsg += "Location information unavailable.";
          } else if (error.code === 3) {
            errorMsg += "Location request timed out.";
          }

          self.showError(errorMsg);
          btn.prop("disabled", false).removeClass("cn-loading");
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 300000, // 5 minutes
        }
      );
    },

    reverseGeocode: function (lat, lng) {
      // Only if Google Maps API is available
      if (typeof google !== "undefined" && google.maps) {
        const geocoder = new google.maps.Geocoder();
        const latlng = { lat: lat, lng: lng };

        geocoder.geocode({ location: latlng }, (results, status) => {
          if (status === "OK" && results[0]) {
            // Get city/locality from results
            let locationName = "";
            for (let component of results[0].address_components) {
              if (component.types.includes("locality")) {
                locationName = component.long_name;
                break;
              }
            }
            if (!locationName && results[0].formatted_address) {
              locationName = results[0].formatted_address.split(",")[0];
            }
            if (locationName) {
              $("#job_location").val(locationName);
            }
          }
        });
      }
    },

    geocodeLocation: function (location, callback) {
      const self = this;

      // Only if Google Maps API is available
      if (typeof google !== "undefined" && google.maps) {
        const geocoder = new google.maps.Geocoder();

        geocoder.geocode({ address: location }, (results, status) => {
          if (status === "OK" && results[0]) {
            self.userLat = results[0].geometry.location.lat();
            self.userLng = results[0].geometry.location.lng();
            self.updateLocationStatus(true);
          } else {
            self.userLat = null;
            self.userLng = null;
            self.updateLocationStatus(false);
          }

          // Call callback when geocoding is complete
          if (callback) {
            callback();
          }
        });
      } else {
        // Google Maps not available, still call callback
        if (callback) {
          callback();
        }
      }
    },

    updateLocationStatus: function (hasLocation) {
      const btn = $(".cn-get-location-btn");

      if (hasLocation) {
        btn
          .addClass("cn-location-active")
          .attr("title", "Location set - click to refresh");
        // Show distance chip when location is set
        this.updateRadiusBadge();
      } else {
        btn
          .removeClass("cn-location-active")
          .attr("title", "Use my current location");
        // Hide distance chip when location is cleared
        this.updateRadiusBadge();
      }
    },

    // ===== MAP VIEW METHODS =====

    initializeViewToggle: function () {
      const self = this;

      // Bind view toggle buttons
      $(document).on("click", ".cn-view-toggle-btn", function (e) {
        e.preventDefault();
        const view = $(this).data("view");
        self.switchView(view);
      });
    },

    switchView: function (view) {
      if (view === this.currentView) return;

      this.currentView = view;

      // Update button states
      $(".cn-view-toggle-btn").removeClass("active");
      $(`.cn-view-toggle-btn[data-view="${view}"]`).addClass("active");

      if (view === "map") {
        // Switch to map view
        $(".cn-jobs-list-container").hide();
        $(".cn-jobs-map-container").show();

        // Initialize map if not already done
        if (!this.map) {
          this.initializeMap();
        }

        // Load jobs if no map data exists yet
        if (!this.mapData) {
          this.loadJobs(1);
        } else {
          // Update map with current data
          this.updateMapView();
        }
      } else {
        // Switch to list view
        $(".cn-jobs-map-container").hide();
        $(".cn-jobs-list-container").show();
      }
    },

    initializeMap: function () {
      if (typeof google === "undefined" || !google.maps) {
        this.showError("Google Maps is not available");
        return;
      }

      // Default center and zoom (will be updated based on markers or country restrictions)
      let defaultCenter = { lat: 0, lng: 0 };
      let defaultZoom = 10;

      // Debug: Log country restrictions
      console.log(
        "CareerNest Map - Country restrictions:",
        typeof careerNestMaps !== "undefined"
          ? careerNestMaps.countries
          : "No restrictions"
      );

      // Apply country restrictions to initial map view
      if (
        typeof careerNestMaps !== "undefined" &&
        careerNestMaps.countries &&
        careerNestMaps.countries.length > 0
      ) {
        // Country bounds (approximate bounding boxes)
        const countryBounds = {
          au: { north: -9.0, south: -45.0, east: 155.0, west: 112.0 },
          ca: { north: 83.0, south: 41.7, east: -52.6, west: -141.0 },
          nz: { north: -34.0, south: -47.3, east: 179.0, west: 166.0 },
          us: { north: 49.4, south: 24.5, east: -66.9, west: -125.0 },
          gb: { north: 60.9, south: 49.9, east: 1.8, west: -8.6 },
          de: { north: 55.1, south: 47.3, east: 15.0, west: 5.9 },
          fr: { north: 51.1, south: 41.3, east: 9.6, west: -5.1 },
        };

        // Use first country's center as default
        const firstCountry = careerNestMaps.countries[0].toLowerCase();
        if (countryBounds[firstCountry]) {
          const cb = countryBounds[firstCountry];
          defaultCenter = {
            lat: (cb.north + cb.south) / 2,
            lng: (cb.east + cb.west) / 2,
          };
          defaultZoom = 5;
        }
      }

      this.map = new google.maps.Map(document.getElementById("cn-jobs-map"), {
        center: defaultCenter,
        zoom: defaultZoom,
        mapTypeControl: true,
        streetViewControl: false,
        fullscreenControl: true,
      });

      // Fit map to selected countries if restrictions are set
      if (
        typeof careerNestMaps !== "undefined" &&
        careerNestMaps.countries &&
        careerNestMaps.countries.length > 0
      ) {
        const countryBounds = {
          au: { north: -9.0, south: -45.0, east: 155.0, west: 112.0 },
          ca: { north: 83.0, south: 41.7, east: -52.6, west: -141.0 },
          nz: { north: -34.0, south: -47.3, east: 179.0, west: 166.0 },
          us: { north: 49.4, south: 24.5, east: -66.9, west: -125.0 },
          gb: { north: 60.9, south: 49.9, east: 1.8, west: -8.6 },
          de: { north: 55.1, south: 47.3, east: 15.0, west: 5.9 },
          fr: { north: 51.1, south: 41.3, east: 9.6, west: -5.1 },
        };

        const bounds = new google.maps.LatLngBounds();
        let hasValidCountry = false;

        for (let i = 0; i < careerNestMaps.countries.length; i++) {
          const countryCode = careerNestMaps.countries[i].toLowerCase();
          if (countryBounds[countryCode]) {
            hasValidCountry = true;
            const cb = countryBounds[countryCode];
            bounds.extend(new google.maps.LatLng(cb.north, cb.west));
            bounds.extend(new google.maps.LatLng(cb.south, cb.east));
          }
        }

        // Fit map to country bounds if we have valid countries
        if (hasValidCountry && !bounds.isEmpty()) {
          this.map.fitBounds(bounds);
        }
      }
    },

    updateMapView: function () {
      if (!this.map || !this.mapData) return;

      // Clear existing markers
      this.clearMarkers();

      const { markers, userLocation, radius } = this.mapData;

      // Debug: Log marker count
      console.log(
        "CareerNest Map - Creating markers:",
        markers ? markers.length : 0
      );

      // Create markers for each job
      if (markers && markers.length > 0) {
        console.log(
          "CareerNest Map - Marker details:",
          markers.map((m) => ({
            title: m.title,
            lat: m.lat,
            lng: m.lng,
            location: m.location,
          }))
        );

        markers.forEach((job) => {
          this.createMarker(job);
        });

        // Fit map to show all markers
        this.fitMapToMarkers();
      }

      // Update user location marker
      if (userLocation && userLocation.lat && userLocation.lng) {
        this.updateUserMarker(userLocation);

        // Update radius circle
        if (radius > 0) {
          this.updateRadiusCircle(userLocation, radius);
        }
      }
    },

    clearMarkers: function () {
      // Remove all job markers
      this.markers.forEach((marker) => marker.setMap(null));
      this.markers = [];

      // Close all info windows
      this.infoWindows.forEach((infoWindow) => infoWindow.close());
      this.infoWindows = [];

      // Remove user marker
      if (this.userMarker) {
        this.userMarker.setMap(null);
        this.userMarker = null;
      }

      // Remove radius circle
      if (this.radiusCircle) {
        this.radiusCircle.setMap(null);
        this.radiusCircle = null;
      }

      // Clear marker clusterer
      if (this.markerClusterer) {
        this.markerClusterer.clearMarkers();
        this.markerClusterer = null;
      }
    },

    createMarker: function (job) {
      const marker = new google.maps.Marker({
        position: { lat: job.lat, lng: job.lng },
        map: this.map,
        title: job.title,
        icon: {
          url: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
        },
      });

      // Create info window
      const infoWindow = new google.maps.InfoWindow({
        content: this.buildInfoWindowContent(job),
      });

      // Add click listener
      marker.addListener("click", () => {
        // Close all other info windows
        this.infoWindows.forEach((iw) => iw.close());
        // Open this info window
        infoWindow.open(this.map, marker);
      });

      this.markers.push(marker);
      this.infoWindows.push(infoWindow);
    },

    buildInfoWindowContent: function (job) {
      const logoHtml = job.logo
        ? `<img src="${job.logo}" alt="${job.company}" class="cn-map-info-logo">`
        : `<div class="cn-map-info-logo-placeholder"><span>${
            job.company ? job.company.charAt(0) : job.title.charAt(0)
          }</span></div>`;

      const distanceHtml = job.distance
        ? `<div class="cn-map-info-meta-item">
             <svg width="14" height="14" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
               <path d="M3.33337 8.95258C3.33337 5.20473 6.31814 2.1665 10 2.1665C13.6819 2.1665 16.6667 5.20473 16.6667 8.95258C16.6667 12.6711 14.5389 17.0102 11.2192 18.5619C10.4453 18.9236 9.55483 18.9236 8.78093 18.5619C5.46114 17.0102 3.33337 12.6711 3.33337 8.95258Z" stroke="currentColor" stroke-width="1.5"/>
               <ellipse cx="10" cy="8.8335" rx="2.5" ry="2.5" stroke="currentColor" stroke-width="1.5"/>
             </svg>
             ${job.distance} km away
           </div>`
        : "";

      return `
        <div class="cn-map-info-window">
          <div class="cn-map-info-header">
            ${logoHtml}
            <div class="cn-map-info-content">
              <h3 class="cn-map-info-title">${job.title}</h3>
              ${
                job.company
                  ? `<p class="cn-map-info-company">${job.company}</p>`
                  : ""
              }
            </div>
          </div>
          <div class="cn-map-info-meta">
            ${
              job.location
                ? `<div class="cn-map-info-meta-item"><svg width="14" height="14" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.33337 8.95258C3.33337 5.20473 6.31814 2.1665 10 2.1665C13.6819 2.1665 16.6667 5.20473 16.6667 8.95258C16.6667 12.6711 14.5389 17.0102 11.2192 18.5619C10.4453 18.9236 9.55483 18.9236 8.78093 18.5619C5.46114 17.0102 3.33337 12.6711 3.33337 8.95258Z" stroke="currentColor" stroke-width="1.5"/><ellipse cx="10" cy="8.8335" rx="2.5" ry="2.5" stroke="currentColor" stroke-width="1.5"/></svg>${job.location}</div>`
                : ""
            }
            ${distanceHtml}
            ${
              job.job_type
                ? `<div class="cn-map-info-meta-item"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.97883 9.68508C2.99294 8.89073 2 8.49355 2 8C2 7.50645 2.99294 7.10927 4.97883 6.31492L7.7873 5.19153C9.77318 4.39718 10.7661 4 12 4C13.2339 4 14.2268 4.39718 16.2127 5.19153L19.0212 6.31492C21.0071 7.10927 22 7.50645 22 8C22 8.49355 21.0071 8.89073 19.0212 9.68508L16.2127 10.8085C14.2268 11.6028 13.2339 12 12 12C10.7661 12 9.77318 11.6028 7.7873 10.8085L4.97883 9.68508Z" stroke="currentColor" stroke-width="1.5"/></svg>${job.job_type}</div>`
                : ""
            }
          </div>
          <a href="${job.permalink}" class="cn-map-info-link">
            View Details
            <svg width="12" height="12" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </a>
        </div>
      `;
    },

    fitMapToMarkers: function () {
      if (!this.map || this.markers.length === 0) return;

      // Fit map bounds to show all markers
      const bounds = new google.maps.LatLngBounds();
      this.markers.forEach((marker) => {
        bounds.extend(marker.getPosition());
      });

      // Include user marker in bounds if exists
      if (this.userMarker) {
        bounds.extend(this.userMarker.getPosition());
      }

      this.map.fitBounds(bounds);

      // Zoom out a bit if only one marker
      if (this.markers.length === 1) {
        const listener = google.maps.event.addListener(this.map, "idle", () => {
          if (this.map.getZoom() > 15) {
            this.map.setZoom(15);
          }
          google.maps.event.removeListener(listener);
        });
      }
    },

    updateUserMarker: function (userLocation) {
      if (this.userMarker) {
        this.userMarker.setMap(null);
      }

      this.userMarker = new google.maps.Marker({
        position: { lat: userLocation.lat, lng: userLocation.lng },
        map: this.map,
        title: "Your Location",
        icon: {
          url: "https://maps.google.com/mapfiles/ms/icons/green-dot.png",
        },
      });
    },

    updateRadiusCircle: function (userLocation, radius) {
      if (this.radiusCircle) {
        this.radiusCircle.setMap(null);
      }

      this.radiusCircle = new google.maps.Circle({
        center: { lat: userLocation.lat, lng: userLocation.lng },
        radius: radius * 1000, // Convert km to meters
        map: this.map,
        strokeColor: "#0073aa",
        strokeOpacity: 0.8,
        strokeWeight: 2,
        fillColor: "#0073aa",
        fillOpacity: 0.15,
      });
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    if ($("#cn-jobs-filter-form").length) {
      JobsAjax.init();
    }
  });
})(jQuery);

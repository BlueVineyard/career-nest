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

      this.bindEvents();
      this.initializeRadius();
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
      $("#job_category, #job_type, #sort, #employer, #date_posted").on(
        "change",
        function () {
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

      // Radius badge click to expand filter
      $(document).on("click", ".cn-radius-badge-indicator", function (e) {
        e.stopPropagation();
        $(".cn-radius-filter").slideDown(300);
        $(this).fadeOut(200);
      });

      // Hide radius filter on blur, show badge if distance is set
      $(document).on(
        "blur",
        "#job_location, .cn-get-location-btn",
        function (e) {
          setTimeout(function () {
            // Check if focus moved to radius slider
            if (
              !$(".cn-radius-filter").is(":focus-within") &&
              !$(":focus").closest(".cn-radius-filter").length
            ) {
              const radius = parseInt($("#search_radius").val()) || 0;
              if (radius > 0) {
                $(".cn-radius-filter").slideUp(300, function () {
                  $(".cn-radius-badge-indicator").fadeIn(200);
                });
              }
            }
          }, 200);
        }
      );

      // Keep radius filter open when interacting with it
      $(document).on("focus", ".cn-radius-filter input", function () {
        $(".cn-radius-badge-indicator").fadeOut(200);
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
        employer: $("#employer").val(),
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
      // Fade out
      this.jobsContainer.fadeOut(200, () => {
        // Update header
        if (data.header_html) {
          $(".cn-jobs-header").html(data.header_html);
        }

        // Update content
        this.jobsContainer.html(data.jobs_html);

        // Fade in
        this.jobsContainer.fadeIn(200);
      });
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
      $("#job_search").val("");
      $("#job_category").val("");
      $("#job_type").val("");
      $("#job_location").val("");
      $("#employer").val("");
      $("#min_salary").val(0);
      $("#max_salary").val(200000);
      $("#date_posted").val("");
      $("#sort").val("date_desc");
      $("#search_radius").val(0);
      this.userLat = null;
      this.userLng = null;
      this.updateSalaryDisplay();
      this.updateRadiusDisplay();
      this.updateRadiusBadge();
      this.updateLocationStatus(false);
      this.reinitializeDropdowns();
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
      if (data.employer) params.set("employer", data.employer);
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
      const match = url.match(/[?&]paged=(\d+)/);
      return match ? parseInt(match[1]) : 1;
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
      let badge = $(".cn-radius-badge-indicator");

      if (radius > 0) {
        if (!badge.length) {
          // Create badge if it doesn't exist
          $("#job_location").after(
            '<span class="cn-radius-badge-indicator" title="Click to adjust distance">' +
              radius +
              " km</span>"
          );
        } else {
          // Update existing badge
          badge.text(radius + " km");
        }
      } else {
        // Remove badge if radius is 0
        badge.remove();
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
      const radiusFilter = $(".cn-radius-filter");

      if (hasLocation) {
        btn
          .addClass("cn-location-active")
          .attr("title", "Location set - click to refresh");

        // Show radius filter with slide down animation
        if (radiusFilter.length && radiusFilter.is(":hidden")) {
          $(".cn-radius-badge-indicator").fadeOut(200);
          radiusFilter.slideDown(300);
        }
      } else {
        btn
          .removeClass("cn-location-active")
          .attr("title", "Use my current location");

        // Hide radius filter and reset value
        if (radiusFilter.length) {
          $("#search_radius").val(0);
          this.updateRadiusDisplay();
          this.updateRadiusBadge();
          radiusFilter.slideUp(300);
          $(".cn-radius-badge-indicator").remove();
        }
      }
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    if ($("#cn-jobs-filter-form").length) {
      JobsAjax.init();
    }
  });
})(jQuery);

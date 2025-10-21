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

      this.bindEvents();
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
        locationTimeout = setTimeout(function () {
          self.loadJobs(1);
        }, 500);
      });

      // Salary range sliders
      $("#min_salary, #max_salary").on("input", function () {
        self.updateSalaryDisplay();
      });

      $("#min_salary, #max_salary").on("change", function () {
        self.loadJobs(1);
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
      this.jobsContainer.css("opacity", "0.5");
      this.jobsContainer.css("pointer-events", "none");

      if (!$(".cn-loading-spinner").length) {
        this.jobsContainer.prepend(
          '<div class="cn-loading-spinner"><div class="spinner"></div><p>Loading jobs...</p></div>'
        );
      }
    },

    hideLoading: function () {
      this.jobsContainer.css("opacity", "1");
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
      this.updateSalaryDisplay();
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

      const newURL = params.toString()
        ? window.location.pathname + "?" + params.toString()
        : window.location.pathname;

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
  };

  // Initialize on document ready
  $(document).ready(function () {
    console.log("CareerNest Jobs AJAX: Script loaded");
    console.log("Form found:", $("#cn-jobs-filter-form").length > 0);
    console.log(
      "careerNestAjax object:",
      typeof careerNestAjax !== "undefined" ? careerNestAjax : "NOT DEFINED"
    );

    if ($("#cn-jobs-filter-form").length) {
      console.log("CareerNest Jobs AJAX: Initializing...");
      JobsAjax.init();
      console.log("CareerNest Jobs AJAX: Initialized successfully");
    } else {
      console.log("CareerNest Jobs AJAX: Form not found on page");
    }
  });
})(jQuery);

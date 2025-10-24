/**
 * CareerNest Employer Dashboard - Job Management
 * Handles job posting, editing, and deletion via AJAX
 */

(function ($) {
  "use strict";

  // Job management object
  const JobManager = {
    currentJobId: null,
    modalOpen: false,

    init: function () {
      this.bindEvents();
      this.initializeModal();
    },

    bindEvents: function () {
      // Post new job buttons
      $(document).on(
        "click",
        "#cn-show-job-form, .cn-post-job-trigger",
        function (e) {
          e.preventDefault();
          JobManager.openJobModal("create");
        }
      );

      // Edit job buttons
      $(document).on("click", ".cn-edit-job", function (e) {
        e.preventDefault();
        const jobId = $(this).data("job-id");
        JobManager.openJobModal("edit", jobId);
      });

      // Delete job buttons
      $(document).on("click", ".cn-delete-job", function (e) {
        e.preventDefault();
        const jobId = $(this).data("job-id");
        JobManager.deleteJob(jobId);
      });

      // Modal close buttons
      $(document).on(
        "click",
        ".cn-job-modal-close, .cn-job-modal-cancel",
        function (e) {
          e.preventDefault();
          JobManager.closeJobModal();
        }
      );

      // Close modal on outside click
      $(document).on("click", "#cn-job-modal", function (e) {
        if (e.target.id === "cn-job-modal") {
          JobManager.closeJobModal();
        }
      });

      // Form submission
      $(document).on("submit", "#cn-job-form", function (e) {
        e.preventDefault();
        JobManager.submitJob();
      });

      // Apply externally toggle
      $(document).on("change", "#cn-job-apply-externally", function () {
        if ($(this).is(":checked")) {
          $("#cn-external-apply-container").slideDown();
        } else {
          $("#cn-external-apply-container").slideUp();
        }
      });

      // Escape key to close modal
      $(document).on("keydown", function (e) {
        if (e.key === "Escape" && JobManager.modalOpen) {
          JobManager.closeJobModal();
        }
      });
    },

    initializeModal: function () {
      // Modal will be created dynamically when needed
    },

    openJobModal: function (mode, jobId) {
      const isEdit = mode === "edit";
      this.currentJobId = isEdit ? jobId : null;

      // Reset form
      $("#cn-job-form")[0].reset();
      $("#cn-job-form-errors").hide().empty();
      $("#cn-external-apply-container").hide();

      // Update modal title
      const title = isEdit ? "Edit Job Listing" : "Post New Job";
      $("#cn-job-modal-title").text(title);

      // Update submit button
      const submitText = isEdit ? "Update Job" : "Post Job";
      $("#cn-job-submit").text(submitText);

      if (isEdit && jobId) {
        // Load job data for editing
        this.loadJobData(jobId);
      }

      // Show modal
      $("#cn-job-modal").fadeIn(300);
      this.modalOpen = true;

      // Focus on first field
      setTimeout(() => {
        $("#cn-job-title").focus();
      }, 350);
    },

    closeJobModal: function () {
      $("#cn-job-modal").fadeOut(300);
      this.modalOpen = false;
      this.currentJobId = null;
    },

    loadJobData: function (jobId) {
      // Show loading state
      const $modal = $("#cn-job-modal");
      $modal.addClass("cn-loading");

      $.ajax({
        url: careerNestJob.ajaxurl,
        type: "POST",
        data: {
          action: "cn_get_job_data",
          nonce: careerNestJob.nonce,
          job_id: jobId,
        },
        success: function (response) {
          $modal.removeClass("cn-loading");

          if (response.success && response.data) {
            const data = response.data;

            // Populate form fields
            $("#cn-job-title").val(data.job_title || "");
            $("#cn-job-location").val(data.job_location || "");
            $("#cn-job-remote").prop("checked", data.remote_position || false);
            $("#cn-job-opening-date").val(data.opening_date || "");
            $("#cn-job-closing-date").val(data.closing_date || "");
            $("#cn-job-salary-range").val(data.salary_range || "");
            $("#cn-job-apply-externally").prop(
              "checked",
              data.apply_externally || false
            );
            $("#cn-job-external-apply").val(data.external_apply || "");

            // Show/hide external apply field
            if (data.apply_externally) {
              $("#cn-external-apply-container").show();
            }

            // Populate textarea fields
            $("#cn-job-overview").val(data.overview || "");
            $("#cn-job-who-we-are").val(data.who_we_are || "");
            $("#cn-job-what-we-offer").val(data.what_we_offer || "");
            $("#cn-job-responsibilities").val(data.responsibilities || "");
            $("#cn-job-how-to-apply").val(data.how_to_apply || "");
          } else {
            JobManager.showError(
              response.data?.message || "Failed to load job data"
            );
          }
        },
        error: function () {
          $modal.removeClass("cn-loading");
          JobManager.showError("Failed to load job data. Please try again.");
        },
      });
    },

    submitJob: function () {
      const isEdit = this.currentJobId !== null;
      const action = isEdit ? "cn_update_job" : "cn_create_job";

      // Get form data
      const formData = {
        action: action,
        nonce: careerNestJob.nonce,
        job_title: $("#cn-job-title").val(),
        job_location: $("#cn-job-location").val(),
        remote_position: $("#cn-job-remote").is(":checked") ? 1 : 0,
        opening_date: $("#cn-job-opening-date").val(),
        closing_date: $("#cn-job-closing-date").val(),
        salary_range: $("#cn-job-salary-range").val(),
        apply_externally: $("#cn-job-apply-externally").is(":checked") ? 1 : 0,
        external_apply: $("#cn-job-external-apply").val(),
        overview: $("#cn-job-overview").val(),
        who_we_are: $("#cn-job-who-we-are").val(),
        what_we_offer: $("#cn-job-what-we-offer").val(),
        responsibilities: $("#cn-job-responsibilities").val(),
        how_to_apply: $("#cn-job-how-to-apply").val(),
      };

      if (isEdit) {
        formData.job_id = this.currentJobId;
      }

      // Show loading state
      const $submitBtn = $("#cn-job-submit");
      const originalText = $submitBtn.text();
      $submitBtn.prop("disabled", true).text("Saving...");
      $("#cn-job-form-errors").hide();

      $.ajax({
        url: careerNestJob.ajaxurl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            // Show success message
            JobManager.showSuccess(response.data.message);

            // Close modal after short delay
            setTimeout(() => {
              JobManager.closeJobModal();
              // Reload page to show updated job list
              window.location.reload();
            }, 1500);
          } else {
            $submitBtn.prop("disabled", false).text(originalText);
            JobManager.showError(
              response.data?.message || "Failed to save job"
            );
          }
        },
        error: function () {
          $submitBtn.prop("disabled", false).text(originalText);
          JobManager.showError("An error occurred. Please try again.");
        },
      });
    },

    deleteJob: function (jobId) {
      if (
        !confirm(
          "Are you sure you want to delete this job? This will move it to trash."
        )
      ) {
        return;
      }

      $.ajax({
        url: careerNestJob.ajaxurl,
        type: "POST",
        data: {
          action: "cn_delete_job",
          nonce: careerNestJob.nonce,
          job_id: jobId,
        },
        success: function (response) {
          if (response.success) {
            // Show success and reload
            alert(response.data.message);
            window.location.reload();
          } else {
            alert(response.data?.message || "Failed to delete job");
          }
        },
        error: function () {
          alert("An error occurred. Please try again.");
        },
      });
    },

    showError: function (message) {
      const $errorDiv = $("#cn-job-form-errors");
      $errorDiv.html("<p>" + message + "</p>").slideDown();

      // Scroll to error
      const modal = document.getElementById("cn-job-modal-content");
      if (modal) {
        modal.scrollTop = 0;
      }
    },

    showSuccess: function (message) {
      const $errorDiv = $("#cn-job-form-errors");
      $errorDiv
        .removeClass("cn-error")
        .addClass("cn-success")
        .html("<p>" + message + "</p>")
        .slideDown();

      // Reset error styling after a delay
      setTimeout(() => {
        $errorDiv.removeClass("cn-success").addClass("cn-error");
      }, 2000);
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    JobManager.init();
  });
})(jQuery);

/**
 * CareerNest Employer Applications Management JavaScript
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // View details toggle
    $(".cn-view-details").on("click", function (e) {
      e.preventDefault();
      const appId = $(this).data("app-id");
      $("#details-" + appId).slideToggle(300);

      // Update button text
      const $btn = $(this);
      if ($("#details-" + appId).is(":visible")) {
        $btn.text("Hide Details");
      } else {
        $btn.text("View Details");
      }
    });

    // Change status button
    $(".cn-change-status").on("click", function (e) {
      e.preventDefault();
      const appId = $(this).data("app-id");
      const currentStatus = $(this).data("current-status");

      // Set app ID in hidden field
      $("#status-app-id").val(appId);

      // Set current status in dropdown
      $("#new-status").val(currentStatus);

      // Show modal
      $("#cn-status-modal").fadeIn(300);
    });

    // Close modal
    $(".cn-modal-close").on("click", function (e) {
      e.preventDefault();
      $(this).closest(".cn-modal").fadeOut(300);
    });

    // Close on outside click
    $(".cn-modal").on("click", function (e) {
      if (e.target === this) {
        $(this).fadeOut(300);
      }
    });

    // Save status button
    $(".cn-save-status").on("click", function (e) {
      e.preventDefault();

      const appId = $("#status-app-id").val();
      const newStatus = $("#new-status").val();

      if (!appId || !newStatus) {
        alert("Please select a status.");
        return;
      }

      // Disable button
      const $btn = $(this);
      const originalText = $btn.text();
      $btn.prop("disabled", true).text("Saving...");

      $.ajax({
        url: careerNestApp.ajaxurl,
        type: "POST",
        data: {
          action: "cn_update_app_status",
          nonce: careerNestApp.nonce,
          app_id: appId,
          new_status: newStatus,
        },
        success: function (response) {
          if (response.success) {
            // Reload page to show updated status
            window.location.reload();
          } else {
            alert(response.data?.message || "Failed to update status");
            $btn.prop("disabled", false).text(originalText);
          }
        },
        error: function () {
          alert("An error occurred. Please try again.");
          $btn.prop("disabled", false).text(originalText);
        },
      });
    });

    // Escape key to close modal
    $(document).on("keydown", function (e) {
      if (e.key === "Escape") {
        $(".cn-modal:visible").fadeOut(300);
      }
    });
  });
})(jQuery);

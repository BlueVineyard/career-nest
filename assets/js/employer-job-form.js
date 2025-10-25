/**
 * CareerNest Employer Job Form JavaScript
 * Handles interactions on the job add/edit form page
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // Initialize Google Maps autocomplete for location
    if (typeof google !== "undefined" && google.maps && google.maps.places) {
      const locationInput = document.getElementById("job_location");
      if (locationInput) {
        const autocomplete = new google.maps.places.Autocomplete(
          locationInput,
          {
            fields: ["formatted_address", "name", "address_components"],
          }
        );

        autocomplete.addListener("place_changed", function () {
          const place = autocomplete.getPlace();
          if (place) {
            locationInput.value = place.formatted_address || place.name || "";
          }
        });
      }
    }

    // Apply externally toggle
    $("#apply_externally").on("change", function () {
      if ($(this).is(":checked")) {
        $("#external-apply-container").slideDown(300);
      } else {
        $("#external-apply-container").slideUp(300);
      }
    });

    // Fixed navigation smooth scrolling
    $(".cn-job-nav-link").on("click", function (e) {
      e.preventDefault();

      const target = $(this).attr("href");
      const $target = $(target);

      if ($target.length) {
        // Remove active class from all links
        $(".cn-job-nav-link").removeClass("active");

        // Add active class to clicked link
        $(this).addClass("active");

        // Smooth scroll to section
        $("html, body").animate(
          {
            scrollTop: $target.offset().top - 80,
          },
          500
        );
      }
    });

    // Highlight active section on scroll
    if ($(".cn-job-nav").length) {
      $(window).on("scroll", function () {
        const scrollPos = $(window).scrollTop() + 100;

        $(".cn-form-card").each(function () {
          const $section = $(this);
          const sectionTop = $section.offset().top;
          const sectionBottom = sectionTop + $section.outerHeight();
          const sectionId = $section.attr("id");

          if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
            $(".cn-job-nav-link").removeClass("active");
            $('.cn-job-nav-link[href="#' + sectionId + '"]').addClass("active");
          }
        });
      });
    }

    // Handle save draft button click
    $(".cn-save-draft").on("click", function (e) {
      $("#submit_action").val("draft");
    });

    // Handle publish button click
    $(".cn-publish").on("click", function (e) {
      $("#submit_action").val("publish");
    });

    // Form validation before submit
    $("#cn-job-submit-form").on("submit", function (e) {
      const $form = $(this);
      const $submitButtons = $form.find('button[type="submit"]');

      const jobTitle = $("#job_title").val().trim();

      if (!jobTitle) {
        e.preventDefault();
        alert("Job title is required.");
        $("#job_title").focus();
        // Re-enable buttons
        $submitButtons.prop("disabled", false).css("opacity", "1");
        return false;
      }

      // If apply externally is checked, validate external_apply field
      if ($("#apply_externally").is(":checked")) {
        const externalApply = $("#external_apply").val().trim();

        if (!externalApply) {
          e.preventDefault();
          alert("Please provide an external application URL or email address.");
          $("#external_apply").focus();
          // Re-enable buttons
          $submitButtons.prop("disabled", false).css("opacity", "1");
          return false;
        }
      }

      // Disable submit buttons to prevent double submission
      $submitButtons.prop("disabled", true).css("opacity", "0.6");

      // Re-enable buttons after 3 seconds in case of validation error
      setTimeout(function () {
        $submitButtons.prop("disabled", false).css("opacity", "1");
      }, 3000);
    });

    // Auto-save to localStorage (optional enhancement)
    const autoSaveEnabled = false; // Set to true to enable auto-save

    if (autoSaveEnabled) {
      // Save form data every 30 seconds
      setInterval(function () {
        const formData = $("#cn-job-submit-form").serializeArray();
        localStorage.setItem("cn_job_draft", JSON.stringify(formData));
      }, 30000);

      // Restore from localStorage on page load
      const savedData = localStorage.getItem("cn_job_draft");
      if (savedData && confirm("Restore previously saved draft?")) {
        const formData = JSON.parse(savedData);
        formData.forEach(function (item) {
          const $field = $('[name="' + item.name + '"]');
          if ($field.attr("type") === "checkbox") {
            $field.prop("checked", item.value === "1");
          } else {
            $field.val(item.value);
          }
        });
      }
    }
  });
})(jQuery);

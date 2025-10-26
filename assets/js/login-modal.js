/**
 * CareerNest Login Modal
 * Handles the login modal functionality including AJAX form submission
 */

(function ($) {
  "use strict";

  // DOM elements
  const $loginModal = $("#cn-login-modal");
  const $loginTrigger = $("#cn-login-trigger");
  const $modalClose = $("#cn-modal-close");
  const $loginForm = $("#cn-login-form");
  const $loginMessages = $(".cn-login-messages");

  // Open modal when login button is clicked
  $loginTrigger.on("click", function (e) {
    e.preventDefault();
    openModal();
  });

  // Close modal when close button is clicked
  $modalClose.on("click", function (e) {
    e.preventDefault();
    closeModal();
  });

  // Close modal when clicking on overlay
  $loginModal.on("click", function (e) {
    if ($(e.target).is(".cn-modal-overlay")) {
      closeModal();
    }
  });

  // Close modal with ESC key
  $(document).on("keydown", function (e) {
    if (e.key === "Escape" && $loginModal.is(":visible")) {
      closeModal();
    }
  });

  // Handle form submission via AJAX
  $loginForm.on("submit", function (e) {
    e.preventDefault();

    // Clear previous messages
    clearMessages();

    // Get form data
    const formData = {
      action: "careernest_login",
      nonce: $("#careernest_login_nonce").val(),
      username: $("#cn-username").val(),
      password: $("#cn-password").val(),
      remember: $('input[name="remember"]').is(":checked") ? "1" : "0",
      redirect_to: $('input[name="redirect_to"]').val(),
    };

    // Disable submit button and show loading state
    const $submitBtn = $loginForm.find('button[type="submit"]');
    const originalText = $submitBtn.text();
    $submitBtn.prop("disabled", true).text("Logging in...");

    // Submit via AJAX
    $.ajax({
      url: careerNestLogin.ajaxurl,
      type: "POST",
      data: formData,
      dataType: "json",
      success: function (response) {
        if (response.success) {
          // Show success message
          showMessage("success", response.data.message || "Login successful!");

          // Redirect after short delay
          setTimeout(function () {
            window.location.href = response.data.redirect;
          }, 500);
        } else {
          // Show error message
          showMessage(
            "error",
            response.data.message || "Login failed. Please try again."
          );

          // Re-enable submit button
          $submitBtn.prop("disabled", false).text(originalText);
        }
      },
      error: function (xhr, status, error) {
        // Show error message
        showMessage("error", "An error occurred. Please try again.");

        // Re-enable submit button
        $submitBtn.prop("disabled", false).text(originalText);

        console.error("Login error:", error);
      },
    });
  });

  /**
   * Open the login modal
   */
  function openModal() {
    $loginModal.fadeIn(200);

    // Focus on username field
    setTimeout(function () {
      $("#cn-username").focus();
    }, 250);

    // Prevent body scroll
    $("body").css("overflow", "hidden");
  }

  /**
   * Close the login modal
   */
  function closeModal() {
    $loginModal.fadeOut(200);

    // Clear form and messages
    clearMessages();
    $loginForm[0].reset();

    // Restore body scroll
    $("body").css("overflow", "");
  }

  /**
   * Show a message in the modal
   *
   * @param {string} type - Message type: 'success' or 'error'
   * @param {string} message - Message text
   */
  function showMessage(type, message) {
    const alertClass =
      type === "success" ? "cn-alert-success" : "cn-alert-error";
    const messageHtml =
      '<div class="cn-alert ' +
      alertClass +
      '">' +
      "<p>" +
      message +
      "</p>" +
      "</div>";

    $loginMessages.html(messageHtml);
  }

  /**
   * Clear all messages
   */
  function clearMessages() {
    $loginMessages.empty();
  }

  /**
   * Password toggle functionality
   */
  $loginModal.on("click", ".cn-password-toggle", function () {
    const $wrapper = $(this).closest(".cn-password-wrapper");
    const $input = $wrapper.find("input");
    const $eyeOpen = $(this).find(".cn-eye-open");
    const $eyeClosed = $(this).find(".cn-eye-closed");

    if ($input.attr("type") === "password") {
      $input.attr("type", "text");
      $eyeOpen.hide();
      $eyeClosed.show();
    } else {
      $input.attr("type", "password");
      $eyeOpen.show();
      $eyeClosed.hide();
    }
  });
})(jQuery);

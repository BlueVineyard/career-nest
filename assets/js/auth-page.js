/**
 * CareerNest Auth Page JavaScript
 * Login Form Handler
 */
(function ($) {
  "use strict";

  const AuthPage = {
    init: function () {
      this.loginForm = $("#cn-login-form");

      if (this.loginForm.length) {
        this.bindEvents();
        this.initPasswordToggles();
      }
    },

    bindEvents: function () {
      const self = this;

      // Login form submission
      this.loginForm.on("submit", function (e) {
        e.preventDefault();
        self.handleLogin($(this));
      });

      // Real-time field validation
      $(".cn-input").on("blur", function () {
        self.validateField($(this));
      });

      // Clear errors on input
      $(".cn-input").on("input", function () {
        self.clearFieldError($(this));
      });
    },

    /**
     * Initialize password visibility toggles
     */
    initPasswordToggles: function () {
      $(".cn-password-toggle").on("click", function () {
        const wrapper = $(this).closest(".cn-password-wrapper");
        const input = wrapper.find("input");
        const eyeOpen = $(this).find(".cn-eye-open");
        const eyeClosed = $(this).find(".cn-eye-closed");

        if (input.attr("type") === "password") {
          input.attr("type", "text");
          eyeOpen.hide();
          eyeClosed.show();
        } else {
          input.attr("type", "password");
          eyeOpen.show();
          eyeClosed.hide();
        }
      });
    },

    /**
     * Validate individual field
     */
    validateField: function (field) {
      const value = field.val().trim();
      const type = field.attr("type");
      let error = "";

      // Required check
      if (field.prop("required") && !value) {
        error = "This field is required";
      }

      // Email validation
      if (type === "email" && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
          error = "Please enter a valid email address";
        }
      }

      // Password validation
      if (type === "password" && value) {
        if (value.length < 6) {
          error = "Password must be at least 6 characters";
        }
      }

      if (error) {
        this.showFieldError(field, error);
        return false;
      } else {
        this.clearFieldError(field);
        return true;
      }
    },

    /**
     * Validate entire form
     */
    validateForm: function (form) {
      const self = this;
      let isValid = true;

      form.find(".cn-input[required]").each(function () {
        if (!self.validateField($(this))) {
          isValid = false;
        }
      });

      return isValid;
    },

    /**
     * Show field error
     */
    showFieldError: function (field, message) {
      field.addClass("error");
      field.closest(".cn-form-group").find(".cn-field-error").text(message);
    },

    /**
     * Clear field error
     */
    clearFieldError: function (field) {
      field.removeClass("error");
      field.closest(".cn-form-group").find(".cn-field-error").text("");
    },

    /**
     * Show form message
     */
    showMessage: function (form, type, message) {
      const messagesContainer = form.find(".cn-form-messages");

      const icon =
        type === "error"
          ? '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" stroke="currentColor" stroke-width="2"/><path d="M10 6V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
          : '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" stroke="currentColor" stroke-width="2"/><path d="M6 10L9 13L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

      const messageHtml = `
        <div class="cn-form-message cn-form-message-${type}">
          <span class="cn-form-message-icon">${icon}</span>
          <span>${message}</span>
        </div>
      `;

      messagesContainer.html(messageHtml);

      // Auto-dismiss success messages
      if (type === "success") {
        setTimeout(() => {
          messagesContainer.fadeOut(300, function () {
            $(this).html("").show();
          });
        }, 3000);
      }
    },

    /**
     * Clear all messages
     */
    clearAllMessages: function () {
      $(".cn-form-messages").html("");
      $(".cn-field-error").text("");
      $(".cn-input").removeClass("error");
    },

    /**
     * Set form loading state
     */
    setFormLoading: function (form, loading) {
      const submitBtn = form.find(".cn-btn-submit");

      if (loading) {
        submitBtn.addClass("loading").prop("disabled", true);
        form.find(".cn-input").prop("disabled", true);
      } else {
        submitBtn.removeClass("loading").prop("disabled", false);
        form.find(".cn-input").prop("disabled", false);
      }
    },

    /**
     * Handle login form submission
     */
    handleLogin: function (form) {
      const self = this;

      // Clear previous messages
      self.clearAllMessages();

      // Validate form
      if (!self.validateForm(form)) {
        self.showMessage(form, "error", "Please fix the errors above");
        return;
      }

      // Get form data
      const formData = {
        action: "careernest_page_login",
        email: form.find("#login_email").val(),
        password: form.find("#login_password").val(),
        remember: form.find("#remember_me").is(":checked") ? 1 : 0,
        nonce: form.find('[name="login_nonce"]').val(),
      };

      // Set loading state
      self.setFormLoading(form, true);

      // Submit via AJAX
      $.ajax({
        url: careerNestAuth.ajaxurl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            self.showMessage(form, "success", response.data.message);

            // Redirect after short delay
            setTimeout(function () {
              window.location.href = response.data.redirect;
            }, 1000);
          } else {
            self.setFormLoading(form, false);
            self.showMessage(
              form,
              "error",
              response.data.message || "Login failed. Please try again."
            );
          }
        },
        error: function () {
          self.setFormLoading(form, false);
          self.showMessage(
            form,
            "error",
            "An error occurred. Please try again."
          );
        },
      });
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    if ($(".cn-auth-page").length) {
      AuthPage.init();
    }
  });
})(jQuery);

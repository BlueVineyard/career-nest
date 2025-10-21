/**
 * CareerNest Custom Dropdown Component
 * A reusable dropdown component with icon support and accessibility features
 */
(function ($) {
  "use strict";

  const CustomDropdown = {
    // Icon library
    icons: {
      folder:
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7C3 5.89543 3.89543 5 5 5H9.58579C9.851 5 10.1054 5.10536 10.2929 5.29289L12 7H19C20.1046 7 21 7.89543 21 9V17C21 18.1046 20.1046 19 19 19H5C3.89543 19 3 18.1046 3 17V7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
      layers:
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.97883 9.68508C2.99294 8.89073 2 8.49355 2 8C2 7.50645 2.99294 7.10927 4.97883 6.31492L7.7873 5.19153C9.77318 4.39718 10.7661 4 12 4C13.2339 4 14.2268 4.39718 16.2127 5.19153L19.0212 6.31492C21.0071 7.10927 22 7.50645 22 8C22 8.49355 21.0071 8.89073 19.0212 9.68508L16.2127 10.8085C14.2268 11.6028 13.2339 12 12 12C10.7661 12 9.77318 11.6028 7.7873 10.8085L4.97883 9.68508Z" stroke="currentColor" stroke-width="1.5"/><path d="M22 12C22 12 21.0071 12.8907 19.0212 13.6851L16.2127 14.8085C14.2268 15.6028 13.2339 16 12 16C10.7661 16 9.77318 15.6028 7.7873 14.8085L4.97883 13.6851C2.99294 12.8907 2 12 2 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M22 16C22 16 21.0071 16.8907 19.0212 17.6851L16.2127 18.8085C14.2268 19.6028 13.2339 20 12 20C10.7661 20 9.77318 19.6028 7.7873 18.8085L4.97883 17.6851C2.99294 16.8907 2 16 2 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
      building:
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 21H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M5 21V7L13 3V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 21V11L13 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 9V9.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12V12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 15V15.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 18V18.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
      calendar:
        '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="6" width="18" height="15" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10H21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 3V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M17 3V6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="7.5" cy="14.5" r="0.5" fill="currentColor"/><circle cx="12" cy="14.5" r="0.5" fill="currentColor"/><circle cx="16.5" cy="14.5" r="0.5" fill="currentColor"/><circle cx="7.5" cy="18" r="0.5" fill="currentColor"/><circle cx="12" cy="18" r="0.5" fill="currentColor"/></svg>',
      sort: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 6L7 10M11 6L11 20M11 6L15 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M17 18L21 14M17 18L17 4M17 18L13 14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    },

    init: function () {
      this.createDropdowns();
      this.bindEvents();
    },

    getIconSvg: function (iconName) {
      return this.icons[iconName] || "";
    },

    createDropdowns: function () {
      $(".cn-custom-select").each(function () {
        const $select = $(this);
        const $wrapper = $select.closest(".cn-custom-select-wrapper");

        // Skip if already initialized
        if ($wrapper.hasClass("cn-dropdown-initialized")) {
          return;
        }

        const iconName = $wrapper.data("icon") || "";
        const iconSvg = CustomDropdown.getIconSvg(iconName);
        const selectedOption = $select.find("option:selected");
        const selectedText = selectedOption.text();

        // Create custom dropdown structure
        const $dropdown = $(`
                    <div class="cn-dropdown" tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox">
                        <div class="cn-dropdown-header">
                            ${
                              iconSvg
                                ? `<span class="cn-dropdown-icon">${iconSvg}</span>`
                                : ""
                            }
                            <span class="cn-dropdown-selected">${selectedText}</span>
                            <svg class="cn-dropdown-arrow" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="cn-dropdown-list" role="listbox">
                            ${CustomDropdown.generateOptions($select)}
                        </div>
                    </div>
                `);

        // Hide native select and append custom dropdown
        $select.hide();
        $wrapper.append($dropdown);
        $wrapper.addClass("cn-dropdown-initialized");
      });
    },

    generateOptions: function ($select) {
      let optionsHtml = "";

      $select.find("option").each(function () {
        const $option = $(this);
        const value = $option.val();
        const text = $option.text();
        const isSelected = $option.is(":selected");

        optionsHtml += `
                    <div class="cn-dropdown-option ${
                      isSelected ? "cn-selected" : ""
                    }" 
                         data-value="${value}" 
                         role="option" 
                         aria-selected="${isSelected}">
                        ${text}
                    </div>
                `;
      });

      return optionsHtml;
    },

    bindEvents: function () {
      const self = this;

      // Toggle dropdown on header click
      $(document).on("click", ".cn-dropdown-header", function (e) {
        e.stopPropagation();
        const $dropdown = $(this).closest(".cn-dropdown");
        const isOpen = $dropdown.hasClass("cn-open");

        // Close all other dropdowns
        $(".cn-dropdown").removeClass("cn-open").attr("aria-expanded", "false");

        if (!isOpen) {
          $dropdown.addClass("cn-open").attr("aria-expanded", "true");
          self.positionDropdown($dropdown);

          // Focus first option
          $dropdown.find(".cn-dropdown-option").first().focus();
        }
      });

      // Select option
      $(document).on("click", ".cn-dropdown-option", function (e) {
        e.stopPropagation();
        const $option = $(this);
        const $dropdown = $option.closest(".cn-dropdown");
        const $wrapper = $dropdown.closest(".cn-custom-select-wrapper");
        const $select = $wrapper.find(".cn-custom-select");
        const value = $option.data("value");
        const text = $option.text();

        // Update UI
        $option
          .siblings()
          .removeClass("cn-selected")
          .attr("aria-selected", "false");
        $option.addClass("cn-selected").attr("aria-selected", "true");
        $dropdown.find(".cn-dropdown-selected").text(text);

        // Update native select
        $select.val(value).trigger("change");

        // Close dropdown
        $dropdown.removeClass("cn-open").attr("aria-expanded", "false");
      });

      // Close dropdown when clicking outside
      $(document).on("click", function (e) {
        if (!$(e.target).closest(".cn-dropdown").length) {
          $(".cn-dropdown")
            .removeClass("cn-open")
            .attr("aria-expanded", "false");
        }
      });

      // Keyboard navigation
      $(document).on("keydown", ".cn-dropdown", function (e) {
        const $dropdown = $(this);
        const $options = $dropdown.find(".cn-dropdown-option");
        const $focused = $options.filter(":focus");
        let index = $options.index($focused);

        switch (e.key) {
          case "Enter":
          case " ":
            e.preventDefault();
            if (!$dropdown.hasClass("cn-open")) {
              $dropdown.addClass("cn-open").attr("aria-expanded", "true");
              $options.first().focus();
            } else if ($focused.length) {
              $focused.trigger("click");
            }
            break;

          case "Escape":
            e.preventDefault();
            $dropdown.removeClass("cn-open").attr("aria-expanded", "false");
            $dropdown.focus();
            break;

          case "ArrowDown":
            e.preventDefault();
            if (!$dropdown.hasClass("cn-open")) {
              $dropdown.addClass("cn-open").attr("aria-expanded", "true");
              $options.first().focus();
            } else {
              const nextIndex = index < $options.length - 1 ? index + 1 : 0;
              $options.eq(nextIndex).focus();
            }
            break;

          case "ArrowUp":
            e.preventDefault();
            if ($dropdown.hasClass("cn-open")) {
              const prevIndex = index > 0 ? index - 1 : $options.length - 1;
              $options.eq(prevIndex).focus();
            }
            break;

          case "Home":
            e.preventDefault();
            if ($dropdown.hasClass("cn-open")) {
              $options.first().focus();
            }
            break;

          case "End":
            e.preventDefault();
            if ($dropdown.hasClass("cn-open")) {
              $options.last().focus();
            }
            break;
        }
      });

      // Handle native select changes (for programmatic updates)
      $(document).on("change", ".cn-custom-select", function () {
        const $select = $(this);
        const $wrapper = $select.closest(".cn-custom-select-wrapper");
        const $dropdown = $wrapper.find(".cn-dropdown");

        if ($dropdown.length) {
          const selectedOption = $select.find("option:selected");
          const selectedText = selectedOption.text();
          const selectedValue = selectedOption.val();

          // Update custom dropdown display
          $dropdown.find(".cn-dropdown-selected").text(selectedText);

          // Update selected option in list
          $dropdown
            .find(".cn-dropdown-option")
            .removeClass("cn-selected")
            .attr("aria-selected", "false");
          $dropdown
            .find(`.cn-dropdown-option[data-value="${selectedValue}"]`)
            .addClass("cn-selected")
            .attr("aria-selected", "true");
        }
      });
    },

    positionDropdown: function ($dropdown) {
      const $list = $dropdown.find(".cn-dropdown-list");
      const dropdownOffset = $dropdown.offset();
      const dropdownHeight = $dropdown.outerHeight();
      const listHeight = $list.outerHeight();
      const windowHeight = $(window).height();
      const spaceBelow = windowHeight - (dropdownOffset.top + dropdownHeight);
      const spaceAbove = dropdownOffset.top;

      // Position dropdown list above if not enough space below
      if (spaceBelow < listHeight && spaceAbove > spaceBelow) {
        $dropdown.addClass("cn-dropdown-up");
      } else {
        $dropdown.removeClass("cn-dropdown-up");
      }
    },

    refreshDropdown: function ($select) {
      const $wrapper = $select.closest(".cn-custom-select-wrapper");
      const $dropdown = $wrapper.find(".cn-dropdown");

      if ($dropdown.length) {
        // Regenerate options
        const $list = $dropdown.find(".cn-dropdown-list");
        $list.html(this.generateOptions($select));

        // Update selected text
        const selectedOption = $select.find("option:selected");
        $dropdown.find(".cn-dropdown-selected").text(selectedOption.text());
      }
    },
  };

  // Initialize on document ready
  $(document).ready(function () {
    console.log("CareerNest Custom Dropdown: Initializing...");
    CustomDropdown.init();
    console.log("CareerNest Custom Dropdown: Initialized successfully");
  });

  // Expose for external use
  window.CareerNestDropdown = CustomDropdown;
})(jQuery);

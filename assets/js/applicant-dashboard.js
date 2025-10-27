/**
 * CareerNest Applicant Dashboard JavaScript
 */

document.addEventListener("DOMContentLoaded", function () {
  // Profile edit toggle functionality
  const toggleBtn = document.getElementById("cn-toggle-edit");
  const editForm = document.getElementById("cn-profile-edit-form");
  const editText = document.querySelector(".cn-edit-text");
  const cancelText = document.querySelector(".cn-cancel-text");
  const cancelBtn = document.getElementById("cn-cancel-edit");

  // Get all profile display sections (exclude the edit form and its children)
  const profileSections = document.querySelectorAll(
    ".cn-dashboard-main > .cn-dashboard-section:not(.cn-profile-edit-form)"
  );

  if (toggleBtn && editForm) {
    toggleBtn.addEventListener("click", function () {
      const isEditing = editForm.style.display !== "none";

      if (isEditing) {
        // Switch to view mode
        exitEditMode();
      } else {
        // Switch to edit mode
        enterEditMode();
      }
    });
  }

  // Cancel button functionality
  if (cancelBtn) {
    cancelBtn.addEventListener("click", function () {
      exitEditMode();
    });
  }

  function enterEditMode() {
    // Hide all profile display sections
    profileSections.forEach(function (section) {
      section.style.display = "none";
    });

    // Show edit form
    editForm.style.display = "block";

    // Show all form sections within the edit form
    const formSections = editForm.querySelectorAll(".cn-dashboard-section");
    formSections.forEach(function (section) {
      section.style.display = "block";
    });

    // Update button text
    editText.style.display = "none";
    cancelText.style.display = "inline";

    // Scroll to the profile picture section (first edit section)
    setTimeout(function () {
      const firstSection = editForm.querySelector(".cn-dashboard-section");
      if (firstSection) {
        firstSection.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    }, 100);
  }

  function exitEditMode() {
    // Show all profile display sections
    profileSections.forEach(function (section) {
      section.style.display = "block";
    });

    // Hide edit form
    editForm.style.display = "none";

    // Update button text
    editText.style.display = "inline";
    cancelText.style.display = "none";
  }

  // Auto-hide success message after 5 seconds
  const successMessage = document.querySelector(".cn-profile-success");
  if (successMessage) {
    setTimeout(function () {
      successMessage.style.opacity = "0";
      setTimeout(function () {
        successMessage.style.display = "none";
      }, 300);
    }, 5000);
  }

  // Form validation enhancement
  const profileForm = document.querySelector(".cn-profile-form");
  if (profileForm) {
    profileForm.addEventListener("submit", function (e) {
      const fullName = document.getElementById("full_name");
      if (fullName && fullName.value.trim() === "") {
        e.preventDefault();
        alert("Full name is required.");
        fullName.focus();
        return false;
      }
    });
  }

  // Skills input enhancement
  const skillsInput = document.getElementById("skills_input");
  if (skillsInput) {
    skillsInput.addEventListener("blur", function () {
      // Clean up skills input (remove extra commas, spaces)
      let skills = this.value
        .split(",")
        .map((skill) => skill.trim())
        .filter((skill) => skill !== "");
      this.value = skills.join(", ");
    });
  }

  // Repeater field functionality
  initializeRepeaterFields();

  // Current job checkbox functionality
  initializeCurrentJobCheckboxes();

  // Application withdrawal functionality
  initializeWithdrawalButtons();

  // Tab switching functionality
  initializeTabSwitching();

  // Bookmark removal functionality
  initializeBookmarkRemoval();
});

/**
 * Initialize tab switching functionality
 */
function initializeTabSwitching() {
  const tabButtons = document.querySelectorAll(".cn-tab-btn");
  const tabContents = document.querySelectorAll(".cn-tab-content");

  tabButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const targetTab = this.getAttribute("data-tab");

      // Remove active class from all buttons and contents
      tabButtons.forEach((btn) => btn.classList.remove("cn-tab-active"));
      tabContents.forEach((content) => {
        content.classList.remove("cn-tab-content-active");
        content.style.display = "none";
      });

      // Add active class to clicked button
      this.classList.add("cn-tab-active");

      // Show target content
      const targetContent = document.querySelector(
        `[data-tab-content="${targetTab}"]`
      );
      if (targetContent) {
        targetContent.classList.add("cn-tab-content-active");
        targetContent.style.display = "block";
      }
    });
  });
}

/**
 * Initialize bookmark removal functionality
 */
function initializeBookmarkRemoval() {
  const removeButtons = document.querySelectorAll(".cn-remove-bookmark-btn");

  removeButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const jobId = this.getAttribute("data-job-id");
      const jobTitle = this.getAttribute("data-job-title");

      if (!jobId) {
        return;
      }

      // Confirm removal
      if (
        !confirm(
          `Are you sure you want to remove "${jobTitle}" from your bookmarks?`
        )
      ) {
        return;
      }

      // Disable button during request
      button.disabled = true;
      button.textContent = "Removing...";

      // Send AJAX request using the same nonce as jobs page
      fetch(careerNestJobs.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          action: "careernest_toggle_bookmark",
          job_id: jobId,
          nonce: careerNestJobs.nonce,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Remove the bookmark card from the DOM
            const bookmarkCard = button.closest(".cn-bookmark-card");
            if (bookmarkCard) {
              bookmarkCard.style.opacity = "0";
              bookmarkCard.style.transition = "opacity 0.3s ease";
              setTimeout(function () {
                bookmarkCard.remove();

                // Check if there are any bookmarks left
                const bookmarksContainer =
                  document.querySelector(".cn-bookmarks-list");
                const remainingBookmarks =
                  bookmarksContainer?.querySelectorAll(".cn-bookmark-card");

                if (!remainingBookmarks || remainingBookmarks.length === 0) {
                  // Show empty state
                  const bookmarksSection = document.querySelector(
                    '[data-tab-content="bookmarks"] .cn-dashboard-section'
                  );
                  if (bookmarksSection && bookmarksContainer) {
                    bookmarksContainer.remove();

                    // Create and insert empty state
                    const emptyState = document.createElement("div");
                    emptyState.className = "cn-empty-state";
                    emptyState.innerHTML = `
                      <div class="cn-empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" stroke="#ccc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                      </div>
                      <h3>No Bookmarked Jobs</h3>
                      <p>Jobs you bookmark will appear here for easy access.</p>
                    `;

                    // Find jobs page link if it exists in header
                    const browseJobsLink = document.querySelector(
                      '[data-tab-content="bookmarks"] .cn-section-header .cn-btn-primary'
                    );
                    if (browseJobsLink) {
                      const browseBtn = browseJobsLink.cloneNode(true);
                      emptyState.appendChild(browseBtn);
                    }

                    bookmarksSection.appendChild(emptyState);
                  }
                }

                // Update bookmark count in tab
                const bookmarksTab = document.querySelector(
                  '[data-tab="bookmarks"] .cn-tab-count'
                );
                if (bookmarksTab) {
                  const currentCount = parseInt(bookmarksTab.textContent) || 0;
                  bookmarksTab.textContent = Math.max(0, currentCount - 1);
                }
              }, 300);
            }
          } else {
            // Show error message
            alert(
              data.data?.message ||
                "Failed to remove bookmark. Please try again."
            );
            // Re-enable button
            button.disabled = false;
            button.textContent = "Remove Bookmark";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
          // Re-enable button
          button.disabled = false;
          button.textContent = "Remove Bookmark";
        });
    });
  });
}

/**
 * Initialize withdrawal button functionality
 */
function initializeWithdrawalButtons() {
  const withdrawButtons = document.querySelectorAll(".cn-withdraw-btn");

  withdrawButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const applicationId = this.getAttribute("data-application-id");
      const jobTitle = this.getAttribute("data-job-title");

      if (!applicationId) {
        return;
      }

      // Confirm withdrawal
      if (
        !confirm(
          `Are you sure you want to withdraw your application for "${jobTitle}"?\n\nThis action cannot be undone.`
        )
      ) {
        return;
      }

      // Disable button during request
      button.disabled = true;
      button.textContent = "Withdrawing...";

      // Send AJAX request
      fetch(careerNestWithdraw.ajaxurl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          action: "cn_withdraw_application",
          application_id: applicationId,
          nonce: careerNestWithdraw.nonce,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Show success message
            alert(data.data.message || "Application withdrawn successfully.");
            // Reload page to update application list
            window.location.reload();
          } else {
            // Show error message
            alert(
              data.data.message ||
                "Failed to withdraw application. Please try again."
            );
            // Re-enable button
            button.disabled = false;
            button.textContent = "Withdraw";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
          // Re-enable button
          button.disabled = false;
          button.textContent = "Withdraw";
        });
    });
  });
}

/**
 * Initialize repeater field functionality (add/remove items)
 */
function initializeRepeaterFields() {
  // Education repeater
  const addEducationBtn = document.getElementById("cn-add-education");
  const educationContainer = document.getElementById("cn-education-fields");

  if (addEducationBtn && educationContainer) {
    addEducationBtn.addEventListener("click", function () {
      addRepeaterItem(educationContainer, "education", getEducationTemplate);
    });

    // Initialize remove buttons for existing items
    initializeRemoveButtons(educationContainer);
  }

  // Experience repeater
  const addExperienceBtn = document.getElementById("cn-add-experience");
  const experienceContainer = document.getElementById("cn-experience-fields");

  if (addExperienceBtn && experienceContainer) {
    addExperienceBtn.addEventListener("click", function () {
      addRepeaterItem(experienceContainer, "experience", getExperienceTemplate);
    });

    // Initialize remove buttons for existing items
    initializeRemoveButtons(experienceContainer);
  }

  // Licenses repeater
  const addLicenseBtn = document.getElementById("cn-add-license");
  const licensesContainer = document.getElementById("cn-licenses-fields");

  if (addLicenseBtn && licensesContainer) {
    addLicenseBtn.addEventListener("click", function () {
      addRepeaterItem(licensesContainer, "licenses", getLicenseTemplate);
    });

    // Initialize remove buttons for existing items
    initializeRemoveButtons(licensesContainer);
  }

  // Links repeater
  const addLinkBtn = document.getElementById("cn-add-link");
  const linksContainer = document.getElementById("cn-links-fields");

  if (addLinkBtn && linksContainer) {
    addLinkBtn.addEventListener("click", function () {
      addRepeaterItem(linksContainer, "links", getLinkTemplate);
    });

    // Initialize remove buttons for existing items
    initializeRemoveButtons(linksContainer);
  }
}

/**
 * Add a new repeater item
 */
function addRepeaterItem(container, fieldName, templateFunction) {
  const items = container.querySelectorAll(".cn-repeater-item");
  const newIndex = items.length;
  const template = templateFunction(fieldName, newIndex);

  const div = document.createElement("div");
  div.innerHTML = template;
  const newItem = div.firstElementChild;

  container.appendChild(newItem);

  // Initialize remove button for the new item
  initializeRemoveButtons(container);

  // Initialize current job checkbox if this is experience
  if (fieldName === "experience") {
    initializeCurrentJobCheckboxes();
  }
}

/**
 * Initialize remove buttons for repeater items
 */
function initializeRemoveButtons(container) {
  const removeButtons = container.querySelectorAll(".cn-remove-item");

  removeButtons.forEach(function (button) {
    // Remove existing event listeners to prevent duplicates
    button.replaceWith(button.cloneNode(true));
  });

  // Re-select buttons after cloning
  const newRemoveButtons = container.querySelectorAll(".cn-remove-item");

  newRemoveButtons.forEach(function (button) {
    button.addEventListener("click", function () {
      const item = this.closest(".cn-repeater-item");
      if (item) {
        item.remove();
        // Reindex remaining items
        reindexRepeaterItems(container);
      }
    });
  });
}

/**
 * Reindex repeater items after removal
 */
function reindexRepeaterItems(container) {
  const items = container.querySelectorAll(".cn-repeater-item");

  items.forEach(function (item, index) {
    item.setAttribute("data-index", index);

    // Update all input names within this item
    const inputs = item.querySelectorAll("input, textarea, select");
    inputs.forEach(function (input) {
      const name = input.getAttribute("name");
      if (name) {
        // Replace the index in the name attribute
        const newName = name.replace(/\[\d+\]/, `[${index}]`);
        input.setAttribute("name", newName);
      }
    });
  });
}

/**
 * Initialize current job checkbox functionality
 */
function initializeCurrentJobCheckboxes() {
  const currentJobCheckboxes = document.querySelectorAll(".cn-current-job");

  currentJobCheckboxes.forEach(function (checkbox) {
    checkbox.addEventListener("change", function () {
      const repeaterItem = this.closest(".cn-repeater-item");
      if (repeaterItem) {
        const endDateInput = repeaterItem.querySelector(".cn-end-date");
        if (endDateInput) {
          if (this.checked) {
            endDateInput.value = "";
            endDateInput.disabled = true;
            endDateInput.style.opacity = "0.5";
          } else {
            endDateInput.disabled = false;
            endDateInput.style.opacity = "1";
          }
        }
      }
    });

    // Initialize state on page load
    if (checkbox.checked) {
      const repeaterItem = checkbox.closest(".cn-repeater-item");
      if (repeaterItem) {
        const endDateInput = repeaterItem.querySelector(".cn-end-date");
        if (endDateInput) {
          endDateInput.disabled = true;
          endDateInput.style.opacity = "0.5";
        }
      }
    }
  });
}

/**
 * Get education item template
 */
function getEducationTemplate(fieldName, index) {
  return `
    <div class="cn-repeater-item" data-index="${index}">
      <div class="cn-form-field">
        <label>Institution</label>
        <div class="cn-input-with-icon">
          <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <input type="text" name="${fieldName}[${index}][institution]" class="cn-input cn-input-small cn-input-with-icon-field">
        </div>
      </div>
      <div class="cn-form-field">
        <label>Degree/Certification</label>
        <div class="cn-input-with-icon">
          <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z M6 12v5c0 1 2 3 6 3s6-2 6-3v-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <input type="text" name="${fieldName}[${index}][certification]" class="cn-input cn-input-small cn-input-with-icon-field">
        </div>
      </div>
      <div class="cn-form-field">
        <label>Completion Date</label>
        <input type="month" name="${fieldName}[${index}][end_date]" class="cn-input cn-input-small">
      </div>
      <div class="cn-form-field">
        <label class="cn-checkbox-label-small">
          <input type="checkbox" name="${fieldName}[${index}][complete]" value="1" class="cn-checkbox">
          <span>Completed</span>
        </label>
      </div>
      <button type="button" class="cn-btn cn-btn-small cn-btn-outline cn-remove-item">Remove</button>
    </div>
  `;
}

/**
 * Get experience item template
 */
function getExperienceTemplate(fieldName, index) {
  return `
    <div class="cn-repeater-item" data-index="${index}">
      <div class="cn-form-field">
        <label>Company</label>
        <div class="cn-input-with-icon">
          <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <input type="text" name="${fieldName}[${index}][company]" class="cn-input cn-input-small cn-input-with-icon-field">
        </div>
      </div>
      <div class="cn-form-field">
        <label>Job Title</label>
        <div class="cn-input-with-icon">
          <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="7" width="20" height="14" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <input type="text" name="${fieldName}[${index}][title]" class="cn-input cn-input-small cn-input-with-icon-field">
        </div>
      </div>
      <div class="cn-form-field">
        <label>Start Date</label>
        <input type="month" name="${fieldName}[${index}][start_date]" class="cn-input cn-input-small">
      </div>
      <div class="cn-form-field">
        <label>End Date</label>
        <input type="month" name="${fieldName}[${index}][end_date]" class="cn-input cn-input-small cn-end-date">
      </div>
      <div class="cn-form-field">
        <label class="cn-checkbox-label-small">
          <input type="checkbox" name="${fieldName}[${index}][current]" value="1" class="cn-checkbox cn-current-job">
          <span>Current Position</span>
        </label>
      </div>
      <div class="cn-form-field">
        <label>Description</label>
        <textarea name="${fieldName}[${index}][description]" rows="3" class="cn-input cn-input-small"></textarea>
      </div>
      <button type="button" class="cn-btn cn-btn-small cn-btn-outline cn-remove-item">Remove</button>
    </div>
  `;
}

/**
 * Get license item template
 */
function getLicenseTemplate(fieldName, index) {
  return `
    <div class="cn-repeater-item" data-index="${index}">
      <div class="cn-form-field">
        <label>Name</label>
        <div class="cn-input-with-icon">
          <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="14 2 14 8 20 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M12 18v-6M9 15l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <input type="text" name="${fieldName}[${index}][name]" class="cn-input cn-input-small cn-input-with-icon-field">
        </div>
      </div>
      <div class="cn-form-field">
        <label>Issuing Organization</label>
        <div class="cn-input-with-icon">
          <svg class="cn-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <input type="text" name="${fieldName}[${index}][issuer]" class="cn-input cn-input-small cn-input-with-icon-field">
        </div>
      </div>
      <div class="cn-form-field">
        <label>Issue Date</label>
        <input type="month" name="${fieldName}[${index}][issue_date]" class="cn-input cn-input-small">
      </div>
      <div class="cn-form-field">
        <label>Expiry Date</label>
        <input type="month" name="${fieldName}[${index}][expiry_date]" class="cn-input cn-input-small">
      </div>
      <div class="cn-form-field">
        <label>Credential ID</label>
        <input type="text" name="${fieldName}[${index}][credential_id]" class="cn-input cn-input-small">
      </div>
      <button type="button" class="cn-btn cn-btn-small cn-btn-outline cn-remove-item">Remove</button>
    </div>
  `;
}

/**
 * Get link item template
 */
function getLinkTemplate(fieldName, index) {
  return `
    <div class="cn-repeater-item" data-index="${index}">
      <div class="cn-form-field">
        <label>Label</label>
        <input type="text" name="${fieldName}[${index}][label]" class="cn-input cn-input-small" placeholder="e.g., Portfolio, GitHub, Twitter">
      </div>
      <div class="cn-form-field">
        <label>URL</label>
        <input type="url" name="${fieldName}[${index}][url]" class="cn-input cn-input-small" placeholder="https://example.com">
      </div>
      <button type="button" class="cn-btn cn-btn-small cn-btn-outline cn-remove-item">Remove</button>
    </div>
  `;
}

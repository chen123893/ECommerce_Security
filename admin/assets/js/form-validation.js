(function () {
  "use strict";

  const maxFileSize = 5 * 1024 * 1024;
  const imageExtensions = ["jpg", "jpeg", "png", "gif", "webp"];

  function messageFor(field) {
    if (field.validity.valueMissing) {
      return "This field is required.";
    }
    if (field.validity.typeMismatch && field.type === "email") {
      return "Enter a valid email address.";
    }
    if (field.validity.typeMismatch && field.type === "url") {
      return "Enter a valid URL, including https://.";
    }
    if (field.validity.rangeUnderflow) {
      return `Value must be at least ${field.min}.`;
    }
    if (field.validity.stepMismatch) {
      return "Enter a valid value.";
    }
    if (field.validity.tooShort) {
      return `Enter at least ${field.minLength} characters.`;
    }
    return "Check this value and try again.";
  }

  function errorElement(field) {
    let error = field.parentElement.querySelector(".field-error");
    if (!error) {
      error = document.createElement("small");
      error.className = "field-error";
      field.insertAdjacentElement("afterend", error);
    }
    return error;
  }

  function validateField(field) {
    if (field.matches(":disabled") || field.type === "submit" || field.type === "button") {
      return true;
    }

    let message = "";
    const value = field.value.trim();
    field.setCustomValidity("");

    if (field.required && value === "") {
      message = "This field is required.";
    } else if (
      ["name", "position", "city"].includes(field.name) &&
      value !== "" &&
      value.length < 2
    ) {
      message = "Enter at least 2 characters.";
    } else if (
      field.name === "phone" &&
      value !== "" &&
      !/^\+?[0-9 ()-]{7,20}$/.test(value)
    ) {
      message = "Enter a valid phone number.";
    } else if (
      field.type === "email" &&
      value !== "" &&
      !field.validity.valid
    ) {
      message = "Enter a valid email address.";
    } else if (field.type === "url" && value !== "" && !field.validity.valid) {
      message = "Enter a valid URL, including https://.";
    } else if (
      field.type === "number" &&
      value !== "" &&
      !field.validity.valid
    ) {
      message = messageFor(field);
    } else if (
      field.minLength > 0 &&
      value !== "" &&
      value.length < field.minLength
    ) {
      message = `Enter at least ${field.minLength} characters.`;
    } else if (field.name === "password" && value !== "" && value.length < 8) {
      message = "Password must be at least 8 characters.";
    } else if (field.type === "file" && field.files.length > 0) {
      const extension = field.files[0].name.split(".").pop().toLowerCase();
      if (field.files[0].size > maxFileSize) {
        message = "File must be smaller than 5 MB.";
      } else if (!imageExtensions.includes(extension)) {
        message = "Use a JPG, PNG, GIF, or WEBP image.";
      }
    }

    field.setCustomValidity(message);
    field.classList.toggle("is-invalid", message !== "");
    field.classList.toggle("is-valid", message === "" && value !== "");

    const error = errorElement(field);
    error.textContent = message;
    error.hidden = message === "";
    return message === "";
  }

  function initializeForm(form) {
    form.noValidate = true;

    const fields = Array.from(form.elements).filter(
      (field) =>
        field.tagName === "INPUT" ||
        field.tagName === "SELECT" ||
        field.tagName === "TEXTAREA",
    );

    fields.forEach((field) => {
      field.addEventListener("blur", () => validateField(field));
      field.addEventListener("input", () => {
        if (field.classList.contains("is-invalid")) {
          validateField(field);
        }
      });
      field.addEventListener("change", () => validateField(field));
    });

    form.addEventListener("submit", (event) => {
      const valid = fields.every(validateField);
      if (!valid) {
        event.preventDefault();
        const firstInvalid = fields.find((field) =>
          field.classList.contains("is-invalid"),
        );
        firstInvalid?.focus();
      }
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("form").forEach(initializeForm);
  });
})();

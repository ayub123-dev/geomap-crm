(function (window, $) {
    "use strict";

    var body = document.body;
    var baseUrl = body.getAttribute("data-base-url") || "";
    var csrfToken = body.getAttribute("data-csrf-token") || "";
    if (!csrfToken) {
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        csrfToken = csrfMeta ? csrfMeta.getAttribute("content") || "" : "";
    }

    window.GeoMapCRM = window.GeoMapCRM || {};
    window.GeoMapCRM.baseUrl = baseUrl;
    window.GeoMapCRM.apiBase = baseUrl + "/api";
    window.GeoMapCRM.csrfToken = csrfToken;

    window.GeoMapCRM.notify = function (type, message) {
        var alertType = type || "info";
        var container = document.getElementById("globalAlerts");
        if (!container) {
            return;
        }

        var wrapper = document.createElement("div");
        wrapper.className = "alert alert-" + alertType + " alert-dismissible fade show mt-2";
        wrapper.role = "alert";
        wrapper.innerHTML =
            "<span>" + escapeHtml(message) + "</span>" +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';

        container.appendChild(wrapper);
        setTimeout(function () {
            if (wrapper.parentNode) {
                wrapper.parentNode.removeChild(wrapper);
            }
        }, 5000);
    };

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    window.GeoMapCRM.ensureCsrfFormField = function (formEl) {
        if (!formEl || !csrfToken) {
            return;
        }

        var tokenInput = formEl.querySelector('input[name="_csrf_token"]');
        if (!tokenInput) {
            tokenInput = document.createElement("input");
            tokenInput.type = "hidden";
            tokenInput.name = "_csrf_token";
            formEl.appendChild(tokenInput);
        }
        tokenInput.value = csrfToken;
    };

    window.GeoMapCRM.attachCsrfToAllForms = function () {
        if (!csrfToken) {
            return;
        }

        var forms = document.querySelectorAll("form");
        Array.prototype.forEach.call(forms, function (formEl) {
            window.GeoMapCRM.ensureCsrfFormField(formEl);
        });
    };

    window.GeoMapCRM.appendCsrfToFormData = function (formData) {
        if (!formData || !csrfToken || typeof formData.append !== "function") {
            return formData;
        }

        if (!formData.has("_csrf_token")) {
            formData.append("_csrf_token", csrfToken);
        }
        return formData;
    };

    $.ajaxSetup({
        beforeSend: function (xhr, settings) {
            var method = String((settings && (settings.type || settings.method)) || "GET").toUpperCase();
            if (method === "GET" || method === "HEAD" || method === "OPTIONS") {
                return;
            }
            if (csrfToken) {
                xhr.setRequestHeader("X-CSRF-Token", csrfToken);
            }
        }
    });

    window.GeoMapCRM.attachCsrfToAllForms();
    window.GeoMapCRM.escapeHtml = escapeHtml;
})(window, jQuery);

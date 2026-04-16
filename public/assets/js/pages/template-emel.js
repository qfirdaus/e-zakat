(function () {
    function normalizeJsonInput(value) {
        return String(value || '')
            .replace(/^\uFEFF/, '')
            .replace(/\u00A0/g, ' ')
            .replace(/[\u2018\u2019]/g, '\'')
            .replace(/[\u201C\u201D]/g, '"')
            .replace(/,\s*([}\]])/g, '$1')
            .trim();
    }

    function safeJsonParse(value) {
        try {
            return JSON.parse(normalizeJsonInput(value));
        } catch (error) {
            return null;
        }
    }

    function setFieldValue(form, field, value) {
        var input = form.querySelector('[data-field="' + field + '"]');
        if (!input) {
            return;
        }

        if (input.type === 'checkbox') {
            input.checked = !!Number(value) || value === true;
            return;
        }

        input.value = value == null ? '' : String(value);
    }

    function resetForm(form) {
        form.reset();
        setFieldValue(form, 'template_id', 0);
        setFieldValue(form, 'status', 'DRAFT');
        setFieldValue(form, 'is_default', 0);
    }

    function fillForm(form, payload) {
        Object.keys(payload).forEach(function (key) {
            setFieldValue(form, key, payload[key]);
        });
    }

    function insertAtCursor(field, text) {
        if (!field) {
            return;
        }

        var start = field.selectionStart || 0;
        var end = field.selectionEnd || 0;
        var currentValue = field.value || '';
        field.value = currentValue.slice(0, start) + text + currentValue.slice(end);
        field.focus();
        var nextPosition = start + text.length;
        field.setSelectionRange(nextPosition, nextPosition);
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function setButtonLoading(button, loading, fallbackLabel) {
        if (!button) {
            return;
        }

        if (loading) {
            button.disabled = true;
            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (fallbackLabel || 'Processing...');
            return;
        }

        button.disabled = false;
        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
    }

    function parseSampleVariables(text, invalidMessage) {
        var value = normalizeJsonInput(text);
        if (!value) {
            return {};
        }

        var decoded = safeJsonParse(value);
        if (!decoded || typeof decoded !== 'object' || Array.isArray(decoded)) {
            throw new Error(invalidMessage || 'Invalid JSON');
        }

        return decoded;
    }

    function syncSampleVariablesField(field, fallbackJson) {
        if (!field) {
            return {};
        }

        var normalized = normalizeJsonInput(field.value);
        if (!normalized) {
            normalized = normalizeJsonInput(fallbackJson || '{}') || '{}';
        }

        var decoded = safeJsonParse(normalized);
        if (!decoded || typeof decoded !== 'object' || Array.isArray(decoded)) {
            throw new Error((window.EmailTemplatePageData || {}).invalidJsonText || 'Sample variables mesti dalam format JSON yang sah.');
        }

        field.value = JSON.stringify(decoded, null, 2);
        return decoded;
    }

    function buildBadgeHtml(items, className, emptyLabel) {
        if (!Array.isArray(items) || !items.length) {
            return '<span class="et-preview-badge is-empty">' + String(emptyLabel || '-') + '</span>';
        }

        return items.map(function (item) {
            var text = String(item == null ? '' : item)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
            return '<span class="et-preview-badge ' + (className || '') + '">' + text + '</span>';
        }).join('');
    }

    function showAlert(icon, title, text) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'OK',
                customClass: {
                    container: 'et-swal-container'
                }
            });
            return;
        }

        window.alert((title ? title + '\n' : '') + (text || ''));
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (window.jQuery && jQuery.fn && jQuery.fn.DataTable && !jQuery.fn.dataTable.isDataTable('#emailTemplateDT')) {
            var templateTable = jQuery('#emailTemplateDT').DataTable({
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                ordering: true,
                responsive: false,
                order: [[6, 'desc']],
                columnDefs: [{ targets: [0, 7], orderable: false }],
                language: (window.DataTableStandard && typeof window.DataTableStandard.language === 'function')
                    ? window.DataTableStandard.language()
                    : {},
                initComplete: function () {
                    jQuery('#emailTemplateDT thead th.col-bil, #emailTemplateDT thead th.col-actions')
                        .removeClass('sorting sorting_asc sorting_desc')
                        .addClass('sorting_disabled')
                        .attr('aria-sort', 'none');
                }
            });

            templateTable.on('order.dt search.dt draw.dt', function () {
                var info = templateTable.page.info();
                templateTable.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, index) {
                    cell.textContent = info.start + index + 1;
                });
            }).draw();

            if (window.DataTableStandard && typeof window.DataTableStandard.decorate === 'function') {
                window.DataTableStandard.decorate('#emailTemplateDT', { controlsClass: 'mb-3' });
            }
        }

        var pageData = window.EmailTemplatePageData || {};
        var modalEl = document.getElementById('emailTemplateModal');
        var form = document.getElementById('emailTemplateForm');
        var modal = modalEl && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        var activeField = null;
        var titleNode = modalEl ? modalEl.querySelector('[data-modal-title]') : null;
        var submitNode = modalEl ? modalEl.querySelector('[data-submit-label]') : null;
        var previewButton = document.getElementById('btnEmailTemplatePreview');
        var testSendButton = document.getElementById('btnEmailTemplateTestSend');
        var sampleVariablesField = document.getElementById('emailTemplateSampleVariables');
        var testEmailField = document.getElementById('emailTemplateTestEmail');
        var previewSubject = document.getElementById('emailTemplatePreviewSubject');
        var previewUsed = document.getElementById('emailTemplatePreviewUsed');
        var previewMissing = document.getElementById('emailTemplatePreviewMissing');
        var previewInvalid = document.getElementById('emailTemplatePreviewInvalid');
        var previewText = document.getElementById('emailTemplatePreviewText');
        var previewFrame = document.getElementById('emailTemplatePreviewFrame');
        var previewToggleButtons = Array.prototype.slice.call(document.querySelectorAll('[data-preview-toggle]'));
        var filterToggleButton = document.querySelector('[data-filter-toggle]');
        var filterPanel = document.querySelector('[data-filter-panel]');
        var modalTabButtons = Array.prototype.slice.call(document.querySelectorAll('[data-template-tab]'));
        var modalTabPanes = Array.prototype.slice.call(document.querySelectorAll('[data-tab-pane]'));

        function setFilterPanelState(isOpen) {
            if (!filterPanel || !filterToggleButton) {
                return;
            }

            filterPanel.classList.toggle('d-none', !isOpen);
            filterToggleButton.classList.toggle('is-active', isOpen);
            filterToggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            filterToggleButton.setAttribute('data-filter-toggle', isOpen ? 'open' : 'closed');
        }

        function activateModalTab(tabName) {
            modalTabButtons.forEach(function (button) {
                var isActive = button.getAttribute('data-template-tab') === tabName;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            modalTabPanes.forEach(function (pane) {
                var isActive = pane.getAttribute('data-tab-pane') === tabName;
                pane.classList.toggle('is-active', isActive);
            });
        }

        function bindPreviewCollapse(button) {
            var targetId = button.getAttribute('data-preview-toggle') || '';
            var target = targetId ? document.getElementById(targetId) : null;
            if (!target || !window.bootstrap || !window.bootstrap.Collapse) {
                return;
            }

            var collapse = window.bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });

            target.addEventListener('shown.bs.collapse', function () {
                button.classList.remove('collapsed');
                button.setAttribute('aria-expanded', 'true');
            });

            target.addEventListener('hidden.bs.collapse', function () {
                button.classList.add('collapsed');
                button.setAttribute('aria-expanded', 'false');
            });

            button.addEventListener('click', function () {
                collapse.toggle();
            });
        }

        function getFormPayload() {
            var normalizedVariables = syncSampleVariablesField(sampleVariablesField, pageData.defaultSampleVariablesJson);
            var formData = new FormData();
            formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '');
            formData.append('subject_template', form ? (form.querySelector('[data-field="subject_template"]') || {}).value || '' : '');
            formData.append('body_html', form ? (form.querySelector('[data-field="body_html"]') || {}).value || '' : '');
            formData.append('body_text', form ? (form.querySelector('[data-field="body_text"]') || {}).value || '' : '');
            formData.append('sample_variables', JSON.stringify(normalizedVariables || {}, null, 2));
            return formData;
        }

        function renderPreview(preview) {
            if (previewSubject) {
                previewSubject.textContent = preview && preview.subject ? preview.subject : 'Belum dijana';
            }
            if (previewUsed) {
                previewUsed.innerHTML = buildBadgeHtml(preview ? preview.used_placeholders : [], '', 'Tiada');
            }
            if (previewMissing) {
                previewMissing.innerHTML = buildBadgeHtml(preview ? preview.missing_placeholders : [], 'is-missing', 'Lengkap');
            }
            if (previewInvalid) {
                previewInvalid.innerHTML = buildBadgeHtml(preview ? preview.invalid_placeholders : [], 'is-invalid', 'Tiada');
            }
            if (previewText) {
                previewText.textContent = preview && preview.text ? preview.text : 'Klik Preview Render untuk melihat output text template.';
            }
            if (previewFrame) {
                previewFrame.srcdoc = preview && preview.html ? preview.html : '';
            }
        }

        function applyCreateMode() {
            if (!form) {
                return;
            }
            resetForm(form);
            syncSampleVariablesField(sampleVariablesField, pageData.defaultSampleVariablesJson);
            setFieldValue(form, 'form_action', 'save');
            activateModalTab('editor');
            if (titleNode) {
                titleNode.textContent = pageData.modalCreateTitle || 'Tambah Template Emel';
            }
            if (submitNode) {
                submitNode.textContent = pageData.submitCreateLabel || 'Simpan Template';
            }
        }

        function applyEditMode(payload) {
            if (!form) {
                return;
            }
            resetForm(form);
            syncSampleVariablesField(sampleVariablesField, pageData.defaultSampleVariablesJson);
            setFieldValue(form, 'form_action', 'save');
            fillForm(form, payload);
            activateModalTab('editor');
            if (titleNode) {
                titleNode.textContent = pageData.modalEditTitle || 'Kemaskini Template Emel';
            }
            if (submitNode) {
                submitNode.textContent = pageData.submitEditLabel || 'Kemaskini Template';
            }
        }

        document.querySelectorAll('[data-create-template]').forEach(function (button) {
            button.addEventListener('click', function () {
                applyCreateMode();
                if (modal) {
                    modal.show();
                }
            });
        });

        document.querySelectorAll('[data-edit-template]').forEach(function (button) {
            button.addEventListener('click', function () {
                var payload = safeJsonParse(button.getAttribute('data-edit-template') || '{}');
                if (!payload) {
                    return;
                }
                applyEditMode(payload);
                if (modal) {
                    modal.show();
                }
            });
        });

        if (form) {
            form.querySelectorAll('input, textarea').forEach(function (field) {
                field.addEventListener('focus', function () {
                    activeField = field;
                });
            });

            var codeField = form.querySelector('[data-field="template_code"]');
            if (codeField) {
                codeField.addEventListener('input', function () {
                    codeField.value = String(codeField.value || '')
                        .toUpperCase()
                        .replace(/\s+/g, '_')
                        .replace(/[^A-Z0-9_-]/g, '');
                });
            }

            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    form.reportValidity();
                }
            });
        }

        document.querySelectorAll('[data-insert-placeholder]').forEach(function (button) {
            button.addEventListener('click', function () {
                var placeholder = button.getAttribute('data-insert-placeholder') || '';
                if (!placeholder) {
                    return;
                }

                if (!activeField || !activeField.matches('[data-placeholder-target], input[type="text"]')) {
                    activeField = form ? (form.querySelector('[data-field="body_html"]') || form.querySelector('[data-field="subject_template"]')) : null;
                }

                insertAtCursor(activeField, placeholder);
            });
        });

        modalTabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                activateModalTab(button.getAttribute('data-template-tab') || 'editor');
            });
        });

        if (filterToggleButton && filterPanel) {
            filterToggleButton.addEventListener('click', function () {
                setFilterPanelState(filterPanel.classList.contains('d-none'));
            });
        }

        previewToggleButtons.forEach(bindPreviewCollapse);

        if (previewButton) {
            previewButton.addEventListener('click', function () {
                try {
                    syncSampleVariablesField(sampleVariablesField, pageData.defaultSampleVariablesJson);
                } catch (error) {
                    showAlert('error', pageData.previewFailedTitle || 'Preview Gagal', error.message || pageData.invalidJsonText);
                    return;
                }

                setButtonLoading(previewButton, true, 'Preview...');
                fetch(pageData.previewUrl || '', {
                    method: 'POST',
                    body: getFormPayload(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-No-Loader': '1',
                        'Accept': 'application/json'
                    }
                })
                    .then(function (response) {
                        return response.json().catch(function () {
                            throw new Error(pageData.networkErrorText || 'Ralat rangkaian semasa memproses permintaan.');
                        });
                    })
                    .then(function (payload) {
                        if (!payload || payload.success !== true) {
                            throw new Error((payload && payload.message) || (pageData.previewFailedTitle || 'Preview Gagal'));
                        }
                        renderPreview(payload.preview || {});
                        activateModalTab('preview');
                    })
                    .catch(function (error) {
                        showAlert('error', pageData.previewFailedTitle || 'Preview Gagal', error.message || pageData.networkErrorText);
                    })
                    .finally(function () {
                        setButtonLoading(previewButton, false);
                    });
            });
        }

        if (testSendButton) {
            testSendButton.addEventListener('click', function () {
                try {
                    syncSampleVariablesField(sampleVariablesField, pageData.defaultSampleVariablesJson);
                } catch (error) {
                    showAlert('error', pageData.testSendFailedTitle || 'Emel Ujian Gagal', error.message || pageData.invalidJsonText);
                    return;
                }

                var emailValue = testEmailField ? String(testEmailField.value || '').trim() : '';
                if (!emailValue) {
                    showAlert('error', pageData.testSendFailedTitle || 'Emel Ujian Gagal', 'Alamat emel ujian diperlukan.');
                    return;
                }

                var formData = getFormPayload();
                formData.append('test_email', emailValue);

                setButtonLoading(testSendButton, true, 'Sending...');
                fetch(pageData.testSendUrl || '', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-No-Loader': '1',
                        'Accept': 'application/json'
                    }
                })
                    .then(function (response) {
                        return response.json().catch(function () {
                            throw new Error(pageData.networkErrorText || 'Ralat rangkaian semasa memproses permintaan.');
                        });
                    })
                    .then(function (payload) {
                        if (!payload || payload.success !== true) {
                            throw new Error((payload && payload.message) || (pageData.testSendFailedTitle || 'Emel Ujian Gagal'));
                        }
                        showAlert('success', pageData.testSendSuccessTitle || 'Emel Ujian Berjaya', payload.message || 'Emel ujian berjaya dihantar.');
                    })
                    .catch(function (error) {
                        showAlert('error', pageData.testSendFailedTitle || 'Emel Ujian Gagal', error.message || pageData.networkErrorText);
                    })
                    .finally(function () {
                        setButtonLoading(testSendButton, false);
                    });
            });
        }

        if (pageData.shouldOpenModal && modal) {
            try {
                syncSampleVariablesField(sampleVariablesField, pageData.defaultSampleVariablesJson);
            } catch (error) {
                if (sampleVariablesField) {
                    sampleVariablesField.value = normalizeJsonInput(pageData.defaultSampleVariablesJson || '{}') || '{}';
                }
            }
            activateModalTab('editor');
            modal.show();
        }
    });
})();

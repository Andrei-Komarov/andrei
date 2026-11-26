const themeStorageKey = 'laptop-finder-theme';

const applyTheme = (theme) => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.dataset.theme = theme;
};

let savedTheme = 'dark';

try {
    savedTheme = localStorage.getItem(themeStorageKey) === 'light' ? 'light' : 'dark';
} catch (error) {
    console.error('Unable to read the saved theme preference.', error);
}

applyTheme(savedTheme);

document.addEventListener('DOMContentLoaded', () => {
    const profitabilityForm = document.querySelector('[data-profitability-form]');

    if (profitabilityForm) {
        const listingSelect = profitabilityForm.querySelector('[name="listing_id"]');
        const purchasePriceInput = profitabilityForm.querySelector('[name="purchase_price"]');
        const shippingInput = profitabilityForm.querySelector('[name="additional_costs"]');
        const purchasePriceNotice = profitabilityForm.querySelector('[data-purchase-price-notice]');
        const currencyCode = profitabilityForm.dataset.currency;
        const kgsPerUsd = Number(profitabilityForm.dataset.kgsPerUsd);
        const defaultPurchasePriceNotice = 'При выборе товара цена и доставка подставятся автоматически. USD и KGS пересчитываются по курсу из настроек.';

        const ebayLinkContainer = profitabilityForm.querySelector('[data-ebay-link-container]');
        const ebayItemLink = profitabilityForm.querySelector('[data-ebay-item-link]');

        const convertToFormCurrency = (amount, listingCurrency) => {
            if (listingCurrency === 'USD' && currencyCode === 'KGS') {
                return amount * kgsPerUsd;
            }

            if (listingCurrency === 'KGS' && currencyCode === 'USD') {
                return amount / kgsPerUsd;
            }

            return amount;
        };

        listingSelect.addEventListener('change', () => {
            const selectedOption = listingSelect.selectedOptions[0];

            if (!selectedOption.value) {
                purchasePriceInput.value = '';
                shippingInput.value = '0.00';
                purchasePriceNotice.textContent = defaultPurchasePriceNotice;
                if (ebayLinkContainer) ebayLinkContainer.classList.add('hidden');
                return;
            }

            const listingCurrency = selectedOption.dataset.currency.toUpperCase();
            const currenciesCanBeConverted = listingCurrency === currencyCode
                || (listingCurrency === 'USD' && currencyCode === 'KGS')
                || (listingCurrency === 'KGS' && currencyCode === 'USD');

            if (!currenciesCanBeConverted) {
                purchasePriceInput.value = '';
                shippingInput.value = '0.00';
                purchasePriceNotice.textContent = `Автопересчёт ${listingCurrency} в ${currencyCode} не настроен. Укажите цену покупки и доставку вручную.`;
            }
            else {
                purchasePriceInput.value = convertToFormCurrency(Number(selectedOption.dataset.price), listingCurrency).toFixed(2);
                shippingInput.value = convertToFormCurrency(Number(selectedOption.dataset.shippingPrice || 0), listingCurrency).toFixed(2);
                purchasePriceNotice.textContent = defaultPurchasePriceNotice;
            }

            if (ebayItemLink && selectedOption.dataset.itemUrl) {
                ebayItemLink.href = selectedOption.dataset.itemUrl;
                ebayItemLink.textContent = 'Открыть товар на eBay';
                ebayLinkContainer.classList.remove('hidden');
            }
        });
    }

    const partSelector = document.querySelector('[data-part-selector]');

    if (partSelector) {
        const partRows = partSelector.querySelector('[data-part-rows]');
        const rowTemplate = partSelector.querySelector('[data-part-row-template]');
        const addPartButton = partSelector.querySelector('[data-add-part]');
        const maxParts = Number(partSelector.dataset.maxParts);

        const updatePartRows = () => {
            const rows = [...partRows.querySelectorAll('[data-part-row]')];

            rows.forEach((row) => {
                row.querySelector('[data-remove-part]').disabled = rows.length === 1;
            });

            addPartButton.disabled = rows.length >= maxParts;
        };

        addPartButton.addEventListener('click', () => {
            const row = rowTemplate.content.firstElementChild.cloneNode(true);
            partRows.append(row);
            updatePartRows();
            row.querySelector('select').focus();
        });

        partSelector.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-remove-part]');

            if (removeButton && partRows.querySelectorAll('[data-part-row]').length > 1) {
                removeButton.closest('[data-part-row]').remove();
                updatePartRows();
            }
        });

        updatePartRows();
    }

    const currencySelect = document.querySelector('[data-currency-select]');

    if (currencySelect) {
        const exchangeRateField = document.querySelector('[data-exchange-rate-field]');
        const exchangeRateInput = document.querySelector('[data-exchange-rate-input]');
        const updateExchangeRateVisibility = () => {
            const isUsd = currencySelect.value === 'USD';
            exchangeRateField.classList.toggle('hidden', !isUsd);
            exchangeRateInput.disabled = !isUsd;
            exchangeRateInput.required = isUsd;
        };

        currencySelect.addEventListener('change', updateExchangeRateVisibility);
        updateExchangeRateVisibility();
    }

    const notificationMenus = document.querySelectorAll('[data-notification-menu]');

    document.addEventListener('pointerdown', (event) => {
        notificationMenus.forEach((notificationMenu) => {
            if (!notificationMenu.contains(event.target)) {
                notificationMenu.open = false;
            }
        });
    });

    const confirmDialog = document.querySelector('[data-confirm-dialog]');
    let resolveConfirmation = null;
    let confirmationTrigger = null;
    const confirmedForms = new WeakSet();

    const requestConfirmation = (message, confirmButtonLabel = 'Удалить') => {
        if (!confirmDialog) {
            console.error('The confirmation dialog is missing from this page.');
            return Promise.resolve(false);
        }

        if (confirmDialog.open || resolveConfirmation) {
            console.error('A confirmation dialog is already open.');
            return Promise.resolve(false);
        }

        confirmDialog.querySelector('[data-confirm-dialog-message]').textContent = message;
        confirmDialog.querySelector('[data-confirm-dialog-accept]').textContent = confirmButtonLabel;
        confirmationTrigger = document.activeElement instanceof HTMLElement ? document.activeElement : null;

        return new Promise((resolve) => {
            resolveConfirmation = resolve;
            confirmDialog.showModal();
        });
    };

    if (confirmDialog) {
        document.addEventListener('submit', (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement) || !form.matches('[data-confirm-dialog-form]')) {
                return;
            }

            if (confirmedForms.has(form)) {
                confirmedForms.delete(form);
                return;
            }

            const selectedCount = document.querySelectorAll('[data-deleted-listing-select]:checked').length;

            if (form.dataset.confirmMessage === 'bulk-delete' && selectedCount === 0) {
                return;
            }

            event.preventDefault();
            const message = form.dataset.confirmMessage === 'bulk-delete'
                ? `Удалить выбранные товары (${selectedCount}) навсегда? Это действие нельзя отменить.`
                : form.dataset.confirmMessage;
            const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;

            requestConfirmation(message, form.dataset.confirmButton || 'Удалить').then((confirmed) => {
                if (confirmed && form.isConnected) {
                    confirmedForms.add(form);
                    form.requestSubmit(submitter ?? undefined);
                }
            });
        });

        confirmDialog.querySelector('[data-confirm-dialog-cancel]').addEventListener('click', () => confirmDialog.close());
        confirmDialog.querySelector('[data-confirm-dialog-accept]').addEventListener('click', () => {
            const resolve = resolveConfirmation;
            resolveConfirmation = null;
            confirmDialog.close();
            resolve?.(true);
        });
        confirmDialog.addEventListener('click', (event) => {
            if (event.target === confirmDialog) {
                confirmDialog.close();
            }
        });
        confirmDialog.addEventListener('close', () => {
            const resolve = resolveConfirmation;
            resolveConfirmation = null;
            resolve?.(false);
            confirmationTrigger?.focus();
            confirmationTrigger = null;
        });
    }

    const imageGalleryDialog = document.querySelector('[data-listing-image-gallery-dialog]');

    if (imageGalleryDialog) {
        const imageGalleryTrack = imageGalleryDialog.querySelector('[data-gallery-track]');
        const imageGalleryViewport = imageGalleryDialog.querySelector('[data-gallery-viewport]');
        const imageGalleryCounter = imageGalleryDialog.querySelector('[data-gallery-counter]');
        const imageGalleryItemTitle = imageGalleryDialog.querySelector('[data-gallery-item-title]');
        const imageGalleryPrevious = imageGalleryDialog.querySelector('[data-gallery-previous]');
        const imageGalleryNext = imageGalleryDialog.querySelector('[data-gallery-next]');
        const imageGalleryZoomLevel = imageGalleryDialog.querySelector('[data-gallery-zoom-level]');
        let activeGalleryButton = null;
        let zoomLevel = 1;
        let panX = 0;
        let panY = 0;
        let dragStart = null;

        const updateImageTransform = (image) => {
            image.style.transform = `translate3d(${panX}px, ${panY}px, 0) scale(${zoomLevel})`;
        };

        const limitPan = (image) => {
            const maxPanX = Math.max(0, (image.clientWidth * zoomLevel - imageGalleryViewport.clientWidth) / 2);
            const maxPanY = Math.max(0, (image.clientHeight * zoomLevel - imageGalleryViewport.clientHeight) / 2);

            panX = Math.min(maxPanX, Math.max(-maxPanX, panX));
            panY = Math.min(maxPanY, Math.max(-maxPanY, panY));
            updateImageTransform(image);
        };

        const updateGalleryControls = () => {
            const slideWidth = imageGalleryViewport.clientWidth;
            const currentIndex = slideWidth === 0 ? 0 : Math.round(imageGalleryViewport.scrollLeft / slideWidth);
            const slideCount = imageGalleryTrack.children.length;

            imageGalleryCounter.textContent = slideCount > 0 ? `${currentIndex + 1} / ${slideCount}` : '';
            imageGalleryPrevious.disabled = currentIndex === 0;
            imageGalleryNext.disabled = currentIndex >= slideCount - 1;
        };

        const setZoomLevel = (nextZoomLevel) => {
            zoomLevel = Math.min(3, Math.max(1, nextZoomLevel));
            panX = 0;
            panY = 0;
            imageGalleryZoomLevel.textContent = `${Math.round(zoomLevel * 100)}%`;
            imageGalleryTrack.querySelectorAll('img').forEach((image) => {
                image.classList.toggle('cursor-grab', zoomLevel > 1);
                image.classList.toggle('cursor-grabbing', false);
                updateImageTransform(image);
                limitPan(image);
            });
        };

        const scrollGallery = (direction) => {
            imageGalleryViewport.scrollBy({
                left: direction * imageGalleryViewport.clientWidth,
                behavior: 'smooth',
            });
        };

        document.querySelectorAll('[data-listing-image-gallery]').forEach((button) => {
            button.addEventListener('click', () => {
                let images;

                try {
                    images = JSON.parse(button.dataset.imageGallery);
                } catch (error) {
                    console.error('Unable to read the listing image gallery.', error);
                    return;
                }

                if (!Array.isArray(images) || images.length === 0) {
                    console.error('The listing image gallery does not contain any images.', button);
                    return;
                }

                activeGalleryButton = button;
                imageGalleryItemTitle.textContent = button.dataset.imageTitle;
                imageGalleryTrack.replaceChildren(...images.map((imageUrl, index) => {
                    const slide = document.createElement('div');
                    slide.className = 'flex w-full shrink-0 snap-center items-center justify-center overflow-hidden p-4';

                    const image = document.createElement('img');
                    image.src = imageUrl;
                    image.alt = `${button.dataset.imageTitle} — фото ${index + 1}`;
                    image.className = 'max-h-[60vh] max-w-full origin-center object-contain transition-transform duration-200';
                    image.loading = index === 0 ? 'eager' : 'lazy';
                    image.draggable = false;
                    image.addEventListener('pointerdown', (event) => {
                        if (zoomLevel <= 1 || event.button !== 0) {
                            return;
                        }

                        event.preventDefault();
                        image.setPointerCapture(event.pointerId);
                        image.classList.remove('transition-transform', 'cursor-grab');
                        image.classList.add('cursor-grabbing');
                        dragStart = {
                            pointerId: event.pointerId,
                            startX: event.clientX,
                            startY: event.clientY,
                            panX,
                            panY,
                            image,
                        };
                    });
                    image.addEventListener('pointermove', (event) => {
                        if (dragStart?.pointerId !== event.pointerId || dragStart.image !== image) {
                            return;
                        }

                        panX = dragStart.panX + event.clientX - dragStart.startX;
                        panY = dragStart.panY + event.clientY - dragStart.startY;
                        limitPan(image);
                    });
                    const stopDragging = (event) => {
                        if (dragStart?.pointerId !== event.pointerId || dragStart.image !== image) {
                            return;
                        }

                        dragStart = null;
                        image.classList.remove('cursor-grabbing');
                        image.classList.add('transition-transform', 'cursor-grab');
                    };
                    image.addEventListener('pointerup', stopDragging);
                    image.addEventListener('pointercancel', stopDragging);
                    image.addEventListener('lostpointercapture', stopDragging);
                    slide.append(image);

                    return slide;
                }));
                imageGalleryViewport.scrollLeft = 0;
                setZoomLevel(1);
                updateGalleryControls();
                imageGalleryDialog.showModal();
                imageGalleryViewport.focus();
            });
        });

        imageGalleryDialog.querySelector('[data-gallery-close]').addEventListener('click', () => imageGalleryDialog.close());
        imageGalleryDialog.addEventListener('click', (event) => {
            if (event.target === imageGalleryDialog) {
                imageGalleryDialog.close();
            }
        });
        imageGalleryDialog.addEventListener('close', () => activeGalleryButton?.focus());
        imageGalleryViewport.addEventListener('scroll', updateGalleryControls);
        imageGalleryPrevious.addEventListener('click', () => scrollGallery(-1));
        imageGalleryNext.addEventListener('click', () => scrollGallery(1));
        imageGalleryDialog.querySelector('[data-gallery-zoom-out]').addEventListener('click', () => setZoomLevel(zoomLevel - 0.5));
        imageGalleryDialog.querySelector('[data-gallery-zoom-in]').addEventListener('click', () => setZoomLevel(zoomLevel + 0.5));
        imageGalleryDialog.querySelector('[data-gallery-zoom-reset]').addEventListener('click', () => setZoomLevel(1));
        imageGalleryDialog.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                scrollGallery(-1);
            } else if (event.key === 'ArrowRight') {
                scrollGallery(1);
            }
        });
    }

    const listingActionStatus = document.querySelector('[data-listing-action-status]');
    const bulkListingsForm = document.querySelector('[data-bulk-listings]');
    const exportListingsForm = document.querySelector('[data-export-listings]');
    const listingCheckboxes = [...document.querySelectorAll('[data-listing-select]')];
    const selectAllListings = document.querySelector('[data-select-all-listings]');
    const deletedListingCheckboxes = [...document.querySelectorAll('[data-deleted-listing-select]')];
    const selectAllDeletedListings = document.querySelector('[data-deleted-select-all]');
    const deletedSelectionCount = document.querySelector('[data-deleted-selection-count]');
    const listingNoteForms = [...document.querySelectorAll('[data-listing-note-form]')];
    const showListingActionStatus = (message, isError = false) => {
        if (!listingActionStatus) {
            console.error(message);
            return;
        }

        listingActionStatus.textContent = message;
        listingActionStatus.classList.remove('hidden', 'bg-red-100', 'text-red-800', 'bg-emerald-100', 'text-emerald-800');
        listingActionStatus.classList.add(...(isError
            ? ['bg-red-100', 'text-red-800']
            : ['bg-emerald-100', 'text-emerald-800']));
    };

    if (deletedSelectionCount && selectAllDeletedListings) {
        const updateDeletedSelection = () => {
            const selectedCount = deletedListingCheckboxes.filter((checkbox) => checkbox.checked).length;

            deletedSelectionCount.textContent = selectedCount > 0
                ? `Выбрано товаров: ${selectedCount}`
                : '';
            selectAllDeletedListings.checked = selectedCount > 0 && selectedCount === deletedListingCheckboxes.length;
            selectAllDeletedListings.indeterminate = selectedCount > 0 && selectedCount < deletedListingCheckboxes.length;
        };

        deletedListingCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateDeletedSelection));
        selectAllDeletedListings.addEventListener('change', () => {
            deletedListingCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectAllDeletedListings.checked;
            });
            updateDeletedSelection();
        });
    }

    const updateStatusControls = (profileListing, newStatus) => {
        profileListing.dataset.status = newStatus;
        profileListing.querySelectorAll('button[name="status"]').forEach((button) => {
            const isCurrent = button.value === newStatus;
            button.disabled = isCurrent;
            button.classList.toggle('border-blue-200', isCurrent);
            button.classList.toggle('bg-blue-50', isCurrent);
            button.classList.toggle('font-semibold', isCurrent);
            button.classList.toggle('text-blue-800', isCurrent);
            button.classList.toggle('border-slate-300', !isCurrent);
            button.classList.toggle('text-slate-600', !isCurrent);
            button.classList.toggle('hover:bg-slate-100', !isCurrent);
        });

    };

    const appendListingsEmptyState = () => {
        const tbody = document.querySelector('[data-listings-tbody]');

        if (!tbody || tbody.querySelector('[data-listing-row]')) {
            return;
        }

        const listingsTable = document.querySelector('[data-table-key="listings"]')?.closest('section')?.querySelector('table');

        if (!listingsTable) {
            console.error('Unable to find the listings table for its empty state.');
            return;
        }

        const emptyRow = document.createElement('tr');
        const emptyCell = document.createElement('td');
        emptyCell.className = 'p-8 text-center text-slate-500';
        emptyCell.textContent = 'На этой странице больше нет товаров.';
        emptyCell.dataset.columnEmptyState = '';
        emptyCell.colSpan = [...listingsTable.querySelectorAll('thead th')].filter((header) => !header.hidden).length;
        emptyRow.append(emptyCell);
        tbody.append(emptyRow);
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.matches('[data-listing-status-form]')) {
            return;
        }

        event.preventDefault();
        const profileListing = form.closest('[data-profile-listing-id]');
        const submitButton = event.submitter;

        if (!profileListing || !(submitButton instanceof HTMLButtonElement)) {
            console.error('Unable to determine the selected listing status.');
            showListingActionStatus('Не удалось определить выбранный статус.', true);
            return;
        }

        const newStatus = submitButton.value;
        const formButtons = [...form.querySelectorAll('button[name="status"]')];
        formButtons.forEach((button) => {
            button.disabled = true;
        });

        try {
            const formData = new FormData(form);
            formData.set('status', newStatus);
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                },
                body: formData,
                credentials: 'same-origin',
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.errors?.status?.[0] || result.message || 'Не удалось изменить статус товара.');
            }

            updateStatusControls(profileListing, result.status);
            const listingRow = profileListing.closest('[data-listing-row]');
            const currentFilter = listingRow?.closest('[data-listings-tbody]')?.dataset.currentStatus;

            // Remove the listing row if the status changed to a different value
            // On the main listings page (empty filter), remove when status is set to anything other than null/new
            // On filtered pages, remove when the new status doesn't match the filter
            if (result.status && result.status !== 'new') {
                profileListing.remove();

                if (!listingRow.querySelector('[data-profile-listing-id]')) {
                    listingRow.remove();
                    appendListingsEmptyState();
                }
            } else if (currentFilter && currentFilter !== result.status) {
                profileListing.remove();

                if (!listingRow.querySelector('[data-profile-listing-id]')) {
                    listingRow.remove();
                    appendListingsEmptyState();
                }
            }

            showListingActionStatus(result.message);
        } catch (error) {
            console.error('Unable to update the listing status.', error);
            showListingActionStatus(error.message || 'Не удалось изменить статус товара.', true);
        } finally {
            formButtons.forEach((button) => {
                button.disabled = button.value === profileListing.dataset.status;
            });
        }
    });

    listingNoteForms.forEach((form) => {
        const textarea = form.querySelector('[data-listing-note]');
        const saveStatus = form.querySelector('[data-note-save-status]');
        const csrfToken = form.querySelector('input[name="_token"]').value;
        let saveTimer;
        let isSaving = false;
        let saveAgain = false;
        let savedValue = textarea.value;

        const saveNote = async () => {
            clearTimeout(saveTimer);

            if (textarea.value === savedValue) {
                return;
            }

            saveAgain = true;

            if (isSaving) {
                return;
            }

            isSaving = true;

            while (saveAgain) {
                saveAgain = false;
                const note = textarea.value;
                saveStatus.textContent = 'Сохраняю...';
                saveStatus.classList.remove('text-red-700', 'text-emerald-700');
                saveStatus.classList.add('text-slate-500');

                try {
                    const response = await fetch(form.action, {
                        method: 'PATCH',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ note }),
                        credentials: 'same-origin',
                    });
                    const result = await response.json();

                    if (!response.ok) {
                        throw new Error(result.errors?.note?.[0] || result.message || 'Не удалось сохранить заметку.');
                    }

                    savedValue = note;
                    saveStatus.textContent = 'Сохранено';
                    saveStatus.classList.remove('text-slate-500', 'text-red-700');
                    saveStatus.classList.add('text-emerald-700');

                    if (textarea.value !== note) {
                        saveAgain = true;
                    }
                } catch (error) {
                    if (textarea.value !== note) {
                        saveAgain = true;
                        continue;
                    }

                    saveStatus.textContent = error.message || 'Не удалось сохранить заметку.';
                    saveStatus.classList.remove('text-slate-500', 'text-emerald-700');
                    saveStatus.classList.add('text-red-700');
                }
            }

            isSaving = false;
        };

        textarea.addEventListener('input', () => {
            clearTimeout(saveTimer);
            saveStatus.textContent = textarea.value === savedValue ? 'Сохранено' : 'Ожидаю ввода...';
            saveStatus.classList.remove('text-red-700', 'text-emerald-700');
            saveStatus.classList.add('text-slate-500');

            if (textarea.value !== savedValue) {
                saveTimer = setTimeout(saveNote, 600);
            }
        });

        textarea.addEventListener('blur', saveNote);
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            saveNote();
        });
    });

    // Listing checked toggle
    const listingCheckedCheckboxes = [...document.querySelectorAll('[data-listing-checked]')];
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfTokenValue = csrfMeta?.content ?? document.querySelector('input[name="_token"]')?.value ?? '';

    listingCheckedCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', async () => {
            const listingId = checkbox.dataset.listingId;
            const isChecked = checkbox.checked;
            const visualSpan = checkbox.nextElementSibling;

            // Optimistic UI update
            checkbox.disabled = true;
            if (isChecked) {
                visualSpan.classList.add('border-emerald-500', 'bg-emerald-50', 'text-emerald-600');
                visualSpan.textContent = '✓';
            } else {
                visualSpan.classList.remove('border-emerald-500', 'bg-emerald-50', 'text-emerald-600');
                visualSpan.textContent = '';
            }

            try {
                const response = await fetch(`/listings/${listingId}/checked`, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfTokenValue,
                    },
                    body: JSON.stringify({ checked: isChecked }),
                    credentials: 'same-origin',
                });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Не удалось обновить отметку.');
                }

                // Update title attribute
                const label = checkbox.closest('label');
                if (label) {
                    label.title = isChecked ? 'Снять отметку' : 'Отметить как проверенный';
                }
                checkbox.setAttribute('aria-label', isChecked ? 'Товар проверен' : 'Товар не проверен');
            } catch (error) {
                // Revert on error
                checkbox.checked = !isChecked;
                if (isChecked) {
                    visualSpan.classList.remove('border-emerald-500', 'bg-emerald-50', 'text-emerald-600');
                    visualSpan.textContent = '';
                } else {
                    visualSpan.classList.add('border-emerald-500', 'bg-emerald-50', 'text-emerald-600');
                    visualSpan.textContent = '✓';
                }
                console.error('Unable to update checked status.', error);
            } finally {
                checkbox.disabled = false;
            }
        });
    });

    if (bulkListingsForm) {
        const bulkSubmit = bulkListingsForm.querySelector('[data-bulk-submit]');
        const selectionCount = bulkListingsForm.querySelector('[data-bulk-selection-count]');
        const exportSubmit = exportListingsForm?.querySelector('[data-export-submit]');
        const updateSelection = () => {
            const selectedCount = listingCheckboxes.filter((checkbox) => checkbox.isConnected && checkbox.checked).length;
            const visibleCheckboxCount = listingCheckboxes.filter((checkbox) => checkbox.isConnected).length;
            bulkSubmit.disabled = selectedCount === 0;
            if (exportSubmit) {
                exportSubmit.disabled = selectedCount === 0;
            }
            selectionCount.textContent = selectedCount > 0
                ? `Выбрано товаров: ${selectedCount}`
                : '';

            if (selectAllListings) {
                selectAllListings.checked = selectedCount > 0 && selectedCount === visibleCheckboxCount;
                selectAllListings.indeterminate = selectedCount > 0 && selectedCount < visibleCheckboxCount;
            }
        };

        listingCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
        selectAllListings?.addEventListener('change', () => {
            listingCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectAllListings.checked;
            });
            updateSelection();
        });

        const selectedListingIds = () => [...new Set(listingCheckboxes
            .filter((checkbox) => checkbox.isConnected && checkbox.checked)
            .flatMap((checkbox) => JSON.parse(checkbox.dataset.listingIds)))];

        exportListingsForm?.addEventListener('submit', (event) => {
            const selectedIds = selectedListingIds();

            if (selectedIds.length === 0) {
                event.preventDefault();
                return;
            }

            exportListingsForm.querySelectorAll('input[name="listing_ids[]"]').forEach((input) => input.remove());
            selectedIds.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'listing_ids[]';
                input.value = id;
                exportListingsForm.append(input);
            });
        });

        bulkListingsForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const selectedCount = listingCheckboxes.filter((checkbox) => checkbox.isConnected && checkbox.checked).length;
            const action = bulkListingsForm.querySelector('[name="bulk_action"]').value;

            if (selectedCount === 0 || !action) {
                return;
            }

            if (action === 'delete') {
                const confirmed = await requestConfirmation(
                    `Переместить выбранные товары (${selectedCount}) в удалённые?`,
                    'Переместить',
                );

                if (!confirmed) {
                    return;
                }
            }

            bulkSubmit.disabled = true;

            try {
                const formData = new FormData(bulkListingsForm);
                formData.delete('listing_ids[]');
                selectedListingIds().forEach((id) => formData.append('listing_ids[]', id));
                const response = await fetch(bulkListingsForm.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': bulkListingsForm.querySelector('input[name="_token"]').value,
                    },
                    body: formData,
                    credentials: 'same-origin',
                });
                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Не удалось выполнить действие для выбранных товаров.');
                }

                const selectedCheckboxes = listingCheckboxes.filter((checkbox) => checkbox.isConnected && checkbox.checked);

                if (result.action === 'delete' || result.action === 'unexclude' || result.action === 'unbuy') {
                    window.location.reload();
                    return;
                } else {
                    selectedCheckboxes.forEach((checkbox) => {
                        const row = checkbox.closest('[data-listing-row]');
                        if (!row) {
                            return;
                        }

                        const newStatus = result.action;
                        row.dataset.listingStatus = newStatus;

                        const tbody = row.closest('[data-listings-tbody]');
                        if (tbody && tbody.dataset.currentStatus && tbody.dataset.currentStatus !== newStatus) {
                            row.remove();
                            return;
                        }

                        row.querySelectorAll('[data-profile-listing-id]').forEach((profileListing) => {
                            updateStatusControls(profileListing, newStatus);
                        });
                    });
                }

                selectedCheckboxes.forEach((checkbox) => {
                    checkbox.checked = false;
                });

                appendListingsEmptyState();

                updateSelection();
                showListingActionStatus(result.message);
            } catch (error) {
                showListingActionStatus(error.message, true);
                updateSelection();
            }
        });
    }

    document.querySelectorAll('[data-column-settings]').forEach((settings) => {
        const table = settings.closest('section')?.querySelector('table');
        const toggle = settings.querySelector('[data-column-settings-toggle]');
        const panel = settings.querySelector('[data-column-settings-panel]');
        const options = settings.querySelector('[data-column-settings-options]');
        const errorMessage = settings.querySelector('[data-column-settings-error]');

        if (!table || !toggle || !panel || !options || !errorMessage) {
            console.error('Table column settings are missing a required element.', settings);
            return;
        }

        const headers = [...table.querySelectorAll('thead th')];
        const columnKeys = headers.map((header) => header.dataset.columnKey);

        if (columnKeys.length === 0 || columnKeys.some((key) => !key) || new Set(columnKeys).size !== columnKeys.length) {
            console.error('Table column settings require unique data-column-key values for every header.', table);
            return;
        }

        const storageKey = `laptop-finder-table-columns:${settings.dataset.tableKey}`;
        let hiddenColumns = new Set();

        try {
            const savedColumns = JSON.parse(localStorage.getItem(storageKey) || '[]');

            if (!Array.isArray(savedColumns) || savedColumns.some((key) => typeof key !== 'string')) {
                throw new TypeError('Saved table columns must be an array of column keys.');
            }

            hiddenColumns = new Set(savedColumns.filter((key) => columnKeys.includes(key)));
        } catch (error) {
            console.error('Unable to read the saved table column preferences.', error);
        }

        const applyColumnVisibility = () => {
            const visibleColumnCount = columnKeys.filter((key) => !hiddenColumns.has(key)).length;

            [...table.rows].forEach((row) => {
                const emptyState = row.querySelector('[data-column-empty-state]');

                if (emptyState) {
                    emptyState.colSpan = visibleColumnCount;
                    return;
                }

                [...row.cells].forEach((cell, index) => {
                    if (columnKeys[index]) {
                        cell.hidden = hiddenColumns.has(columnKeys[index]);
                    }
                });
            });
        };

        headers.forEach((header, index) => {
            const columnKey = columnKeys[index];
            const label = header.dataset.columnLabel
                || header.textContent.replace(/[↑↓]/g, '').trim()
                || header.querySelector('input')?.getAttribute('aria-label')
                || `Столбец ${index + 1}`;
            const optionId = `column-${settings.dataset.tableKey}-${columnKey}`;
            const optionLabel = document.createElement('label');
            const checkbox = document.createElement('input');
            const labelText = document.createElement('span');

            optionLabel.className = 'flex cursor-pointer items-start gap-2 text-sm';
            checkbox.type = 'checkbox';
            checkbox.id = optionId;
            checkbox.checked = !hiddenColumns.has(columnKey);
            checkbox.className = 'mt-0.5 rounded border-slate-300';
            labelText.textContent = label;
            optionLabel.append(checkbox, labelText);
            options.append(optionLabel);

            checkbox.addEventListener('change', () => {
                errorMessage.textContent = '';
                errorMessage.classList.add('hidden');

                if (!options.querySelector('input:checked')) {
                    checkbox.checked = true;
                    errorMessage.textContent = 'Оставьте видимым хотя бы один столбец.';
                    errorMessage.classList.remove('hidden');
                    return;
                }

                if (checkbox.checked) {
                    hiddenColumns.delete(columnKey);
                } else {
                    hiddenColumns.add(columnKey);
                }

                applyColumnVisibility();

                try {
                    localStorage.setItem(storageKey, JSON.stringify([...hiddenColumns]));
                } catch (error) {
                    console.error('Unable to save the table column preferences.', error);
                    errorMessage.textContent = 'Не удалось сохранить настройки на этом устройстве.';
                    errorMessage.classList.remove('hidden');
                }
            });
        });

        applyColumnVisibility();

        const closePanel = () => {
            panel.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                closePanel();
                return;
            }

            document.querySelectorAll('[data-column-settings-panel]').forEach((otherPanel) => otherPanel.classList.add('hidden'));
            document.querySelectorAll('[data-column-settings-toggle]').forEach((otherToggle) => otherToggle.setAttribute('aria-expanded', 'false'));
            panel.classList.remove('hidden');
            toggle.setAttribute('aria-expanded', 'true');
        });

        document.addEventListener('click', (event) => {
            if (!settings.contains(event.target)) {
                closePanel();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                closePanel();
                toggle.focus();
            }
        });
    });

    // Scroll-to-top button
    {
        const scrollButton = document.createElement('button');
        scrollButton.type = 'button';
        scrollButton.setAttribute('aria-label', 'Наверх');
        scrollButton.className = 'fixed bottom-6 right-6 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition-opacity duration-300 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2';
        scrollButton.style.opacity = '0';
        scrollButton.style.pointerEvents = 'none';
        scrollButton.innerHTML = '<svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>';

        scrollButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        document.body.appendChild(scrollButton);

        const toggleScrollButton = () => {
            const scrolled = window.scrollY > 300;
            scrollButton.style.opacity = scrolled ? '1' : '0';
            scrollButton.style.pointerEvents = scrolled ? 'auto' : 'none';
        };

        window.addEventListener('scroll', toggleScrollButton, { passive: true });
        toggleScrollButton();
    }

    const themeSelector = document.querySelector('[data-theme-selector]');

    if (!themeSelector) {
        return;
    }

    themeSelector.value = savedTheme;
    themeSelector.addEventListener('change', () => {
        savedTheme = themeSelector.value;
        applyTheme(savedTheme);

        try {
            localStorage.setItem(themeStorageKey, savedTheme);
        } catch (error) {
            console.error('Unable to save the theme preference.', error);
        }
    });
});

document.addEventListener("DOMContentLoaded", function () {
    
    // ==========================================================================
    // 1. FLATPICKR INITIALIZATION (Restores Date Fields)
    // ==========================================================================
    flatpickr("#start_date", {
        dateFormat: "m/d/Y",
        allowInput: true,
        monthSelectorType: "static",
        position: "below"
    });

    flatpickr("#end_date", {
        dateFormat: "m/d/Y",
        allowInput: true,
        monthSelectorType: "static",
        position: "below"
    });

    // ==========================================================================
    // 2. CUSTOM TIME DROPDOWN INTERFACE (Restores Time Option Pickers)
    // ==========================================================================
    const startInput = document.getElementById('start_time');
    const startDropdown = document.getElementById('start_time_dropdown');
    const endInput = document.getElementById('end_time');
    const endDropdown = document.getElementById('end_time_dropdown');

    function generateTimeOptions() {
        const times = [];
        const periods = ['AM', 'PM'];
        for (let p = 0; p < 2; p++) {
            for (let h = 1; h <= 12; h++) {
                const hourStr = h < 10 ? '0' + h : h;
                times.push(`${hourStr}:00 ${periods[p]}`);
                times.push(`${hourStr}:30 ${periods[p]}`);
            }
        }
        return times;
    }

    const timeOptionsArr = generateTimeOptions();

    function populateDropdown(dropdown, inputEl) {
        if (!dropdown) return;
        dropdown.innerHTML = ''; 
        timeOptionsArr.forEach(timeStr => {
            const rowItem = document.createElement('div');
            rowItem.className = 'time-option';
            rowItem.textContent = timeStr;
            
            rowItem.addEventListener('click', function(e) {
                e.stopPropagation();
                inputEl.value = timeStr;
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(rowItem);
        });
    }

    if (startDropdown && startInput) populateDropdown(startDropdown, startInput);
    if (endDropdown && endInput) populateDropdown(endDropdown, endInput);

    if (startInput) {
        startInput.addEventListener('click', function(e) {
            e.stopPropagation();
            closeAllDropdowns();
            if (startDropdown) startDropdown.style.display = 'block';
        });
    }

    if (endInput) {
        endInput.addEventListener('click', function(e) {
            e.stopPropagation();
            closeAllDropdowns();
            if (endDropdown) endDropdown.style.display = 'block';
        });
    }

    // ==========================================================================
    // 3. RECONFIGURED VISIBILITY BAR SYSTEM CONTROLLER
    // ==========================================================================
    const visibilityTrigger = document.getElementById('visibilityTrigger');
    const visibilityPanel = document.getElementById('visibilityPanel');
    const filterDeptOption = document.getElementById('filterDeptOption');
    const deptChecklist = document.getElementById('deptChecklist');
    const visibilityValue = document.getElementById('visibilityValue');
    
    const hiddenType = document.getElementById('hiddenVisibilityType');
    const hiddenDepts = document.getElementById('hiddenSelectedDepartments');

    if (visibilityTrigger && visibilityPanel) {
        visibilityTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = visibilityPanel.style.display === 'block';
            closeAllDropdowns();
            if (!isOpen) {
                visibilityPanel.style.display = 'block';
                visibilityTrigger.classList.add('active');
            }
        });
    }

    // only wire visibility handlers when expected UI exists (prevents errors on pages using inline variants)
    if (visibilityTrigger && visibilityPanel && visibilityValue && hiddenType && hiddenDepts) {
        document.querySelectorAll('.visibility-option').forEach(option => {
            option.addEventListener('click', function (e) {
                e.stopPropagation();
                const val = this.getAttribute('data-value');

                if (val === 'Public') {
                    visibilityValue.textContent = '🌐 Public';
                    hiddenType.value = 'public';
                } else if (val === 'Department Only') {
                    visibilityValue.textContent = '🏢 Department Only';
                    hiddenType.value = 'department_only';
                }

                document.querySelectorAll('.dept-cb').forEach(cb => cb.checked = false);
                if (hiddenDepts) hiddenDepts.value = '';

                if(deptChecklist) deptChecklist.style.display = 'none';
                if(filterDeptOption) filterDeptOption.classList.remove('open');
                visibilityPanel.style.display = 'none';
                visibilityTrigger.classList.remove('active');
            });
        });

        if (filterDeptOption) {
            filterDeptOption.addEventListener('click', function (e) {
                e.stopPropagation();
                this.classList.toggle('open');
                if (deptChecklist) {
                    const isVisible = deptChecklist.style.display === 'block';
                    deptChecklist.style.display = isVisible ? 'none' : 'block';
                }
            });
        }

        document.querySelectorAll('.dept-cb').forEach(cb => {
            cb.addEventListener('click', function(e) {
                e.stopPropagation(); 
            });
            
            cb.addEventListener('change', function () {
                const checkedBoxes = document.querySelectorAll('.dept-cb:checked');
                if (checkedBoxes.length === 0) {
                    visibilityValue.textContent = '🌐 Public';
                    hiddenType.value = 'public';
                    hiddenDepts.value = '';
                } else {
                    const selectedIds = Array.from(checkedBoxes).map(item => item.value);
                    const selectedCodes = Array.from(checkedBoxes).map(item => item.getAttribute('data-code'));
                    
                    visibilityValue.textContent = `🎯 Restricted: ${selectedCodes.join(', ')}`;
                    hiddenType.value = 'restricted';
                    hiddenDepts.value = selectedIds.join(',');
                }
            });
        });
    }

    // ==========================================================================
    // 4. LANDMARK SUGGESTIONS AUTOCOMPLETE (Restores Location Search UI)
    // ==========================================================================
    const locationInput = document.getElementById('location');
    const locationDropdown = document.getElementById('locationDropdown');
    const suggestionsContainer = document.getElementById('locationSuggestionsContainer');

    const phRealLocations = [
        "CvSU Main Campus - Gymnasium, Indang, Cavite",
        "CvSU Main Campus - Quadrangle, Indang, Cavite",
        "CvSU Main Campus - CEIT Building, Indang, Cavite",
        "CvSU Main Campus - International House, Indang, Cavite",
        "CvSU Main Campus - University Library, Indang, Cavite",
        "Central Student Government Office, Indang, Cavite"
    ];

    function renderLocationSuggestions(filterText = '') {
        if (!suggestionsContainer) return;
        suggestionsContainer.innerHTML = '';
        const cleanQuery = filterText.trim().toLowerCase();
        const filtered = phRealLocations.filter(loc => loc.toLowerCase().includes(cleanQuery));

        if (filtered.length === 0) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'suggestion-item empty-item';
            emptyDiv.textContent = 'No matching locations found.';
            suggestionsContainer.appendChild(emptyDiv);
            return;
        }

        filtered.forEach(locationName => {
            const itemRow = document.createElement('div');
            itemRow.className = 'suggestion-item';
            itemRow.innerHTML = `<i class="fa-solid fa-location-crosshairs"></i> <span>${locationName}</span>`;
            itemRow.addEventListener('click', function (e) {
                e.stopPropagation();
                locationInput.value = locationName;
                locationDropdown.style.display = 'none';
            });
            suggestionsContainer.appendChild(itemRow);
        });
    }

    if (locationInput && locationDropdown) {
        locationInput.addEventListener('click', function (e) {
            e.stopPropagation();
            closeAllDropdowns();
            renderLocationSuggestions(locationInput.value);
            locationDropdown.style.display = 'block';
        });

        locationInput.addEventListener('input', function () {
            renderLocationSuggestions(this.value);
            locationDropdown.style.display = 'block';
        });
    }

    document.addEventListener('click', function () {
        closeAllDropdowns();
    });

    function closeAllDropdowns() {
        if (startDropdown) startDropdown.style.display = 'none';
        if (endDropdown) endDropdown.style.display = 'none';
        if (locationDropdown) locationDropdown.style.display = 'none';
        if (visibilityPanel) {
            visibilityPanel.style.display = 'none';
            if(visibilityTrigger) visibilityTrigger.classList.remove('active');
        }
    }
});

// ==========================================================================
// 5. GLOBAL CAPACITY MODAL CONTROLLER (Restores Modal Trigger and Inputs)
// ==========================================================================
function handleCapacityClick() {
    const modal = document.getElementById('capacityModal');
    if (modal) modal.style.display = 'flex';
}

function handleCloseCapacityModal() {
    const modal = document.getElementById('capacityModal');
    if (modal) modal.style.display = 'none';
}

function toggleCapacityInput() {
    const toggleState = document.getElementById('limitCapacityToggle');
    const capacityInputRow = document.getElementById('capacityInputRow');
    if (capacityInputRow && toggleState) {
        capacityInputRow.style.display = toggleState.checked ? 'flex' : 'none';
    }
}

function saveCapacitySettings() {
    const limitToggle = document.getElementById('limitCapacityToggle');
    const maxInput = document.getElementById('maxCapacityInput');
    const statusTextDisplay = document.getElementById('capacityStatusText');
    
    const hiddenLimit = document.getElementById('hiddenLimitCapacity');
    const hiddenMax = document.getElementById('hiddenMaxCapacity');

    if (limitToggle && limitToggle.checked) {
        const finalValue = (maxInput && maxInput.value) ? maxInput.value : '1';
        if(statusTextDisplay) statusTextDisplay.innerHTML = `${finalValue} ✏️`;
        if(hiddenLimit) hiddenLimit.value = "1";
        if(hiddenMax) hiddenMax.value = finalValue;
    } else {
        if(statusTextDisplay) statusTextDisplay.innerHTML = `Unlimited ✏️`;
        if(hiddenLimit) hiddenLimit.value = "0";
        if(hiddenMax) hiddenMax.value = "null";
    }
    handleCloseCapacityModal();
}
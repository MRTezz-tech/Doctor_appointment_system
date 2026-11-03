/**
 * DocCare Clinic - Frontend Interactive Scripts
 * Dynamic Slot Loader, Client-Side Validations, Demo Fillers
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Datepicker Minimums (Prevent Past Dates)
    const datePickers = document.querySelectorAll('input[type="date"].future-date-only');
    const todayStr = new Date().toISOString().split('T')[0];
    datePickers.forEach(picker => {
        picker.setAttribute('min', todayStr);
    });

    // 2. Dynamic Time Slot Loader for Booking Forms
    const doctorSelect = document.getElementById('doctor_id');
    const dateInput = document.getElementById('appointment_date');
    const slotsContainer = document.getElementById('slotsContainer');
    const slotsMessage = document.getElementById('slotsMessage');
    const submitBtn = document.getElementById('submitAppointmentBtn');

    if (doctorSelect && dateInput && slotsContainer) {
        function fetchAvailableSlots() {
            const doctorId = doctorSelect.value;
            const dateVal = dateInput.value;

            if (!doctorId || !dateVal) {
                slotsContainer.innerHTML = '';
                if (slotsMessage) {
                    slotsMessage.innerHTML = '<div class="alert alert-info py-2 small mb-0"><i class="bi bi-info-circle me-1"></i> Please select a doctor and date to view available time slots.</div>';
                }
                if (submitBtn) submitBtn.disabled = true;
                return;
            }

            // Show loading state
            if (slotsMessage) {
                slotsMessage.innerHTML = '<div class="text-primary py-2 small"><span class="spinner-border spinner-border-sm me-2" role="status"></span> Checking doctor schedule and available slots...</div>';
            }
            slotsContainer.innerHTML = '';
            if (submitBtn) submitBtn.disabled = true;

            const apiUrl = `${BASE_URL}/api/get-slots.php?doctor_id=${encodeURIComponent(doctorId)}&date=${encodeURIComponent(dateVal)}`;

            fetch(apiUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network error loading slots');
                    }
                    return response.json();
                })
                .then(data => {
                    if (!data.success) {
                        if (slotsMessage) {
                            slotsMessage.innerHTML = `<div class="alert alert-warning py-2 small mb-0"><i class="bi bi-exclamation-triangle me-1"></i> ${data.message}</div>`;
                        }
                        slotsContainer.innerHTML = '';
                        if (submitBtn) submitBtn.disabled = true;
                        return;
                    }

                    // Doctor schedule summary
                    if (slotsMessage) {
                        slotsMessage.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted"><i class="bi bi-calendar-check text-success me-1"></i> Slots for <strong>${data.day}</strong> (${data.date})</span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Fee: ₹${parseFloat(data.consultation_fee).toFixed(2)}</span>
                            </div>
                        `;
                    }

                    if (!data.slots || data.slots.length === 0) {
                        slotsContainer.innerHTML = '<div class="alert alert-secondary py-2 small w-100">No consultation slots configured for this day.</div>';
                        if (submitBtn) submitBtn.disabled = true;
                        return;
                    }

                    // Render Slot Grid
                    let gridHtml = '<div class="slot-grid">';
                    let availableCount = 0;

                    data.slots.forEach((slot, index) => {
                        const slotId = `slot_${index}`;
                        if (slot.available) {
                            availableCount++;
                            gridHtml += `
                                <div>
                                    <input type="radio" class="slot-radio" name="appointment_time" id="${slotId}" value="${slot.time}" required>
                                    <label class="slot-label" for="${slotId}">
                                        <i class="bi bi-clock me-1"></i>${slot.label}
                                    </label>
                                </div>
                            `;
                        } else {
                            const reasonTitle = slot.is_booked ? 'Already Booked' : 'Slot Passed';
                            gridHtml += `
                                <div title="${reasonTitle}">
                                    <input type="radio" class="slot-radio" disabled>
                                    <label class="slot-label disabled">
                                        ${slot.label}
                                    </label>
                                </div>
                            `;
                        }
                    });

                    gridHtml += '</div>';

                    if (availableCount === 0) {
                        gridHtml = '<div class="alert alert-warning py-2 small w-100"><i class="bi bi-calendar-x me-1"></i> All slots for this date are fully booked or have passed. Please select another date.</div>';
                        if (submitBtn) submitBtn.disabled = true;
                    }

                    slotsContainer.innerHTML = gridHtml;

                    // Enable submit button once a slot is chosen
                    const radioButtons = slotsContainer.querySelectorAll('input[type="radio"]:not(:disabled)');
                    radioButtons.forEach(rb => {
                        rb.addEventListener('change', function () {
                            if (submitBtn) submitBtn.disabled = false;
                        });
                    });
                })
                .catch(err => {
                    console.error('Slot loading error:', err);
                    if (slotsMessage) {
                        slotsMessage.innerHTML = '<div class="alert alert-danger py-2 small mb-0"><i class="bi bi-wifi-off me-1"></i> Failed to fetch time slots. Please try again.</div>';
                    }
                });
        }

        doctorSelect.addEventListener('change', fetchAvailableSlots);
        dateInput.addEventListener('change', fetchAvailableSlots);

        // If page loaded with pre-selected doctor and date, trigger immediately
        if (doctorSelect.value && dateInput.value) {
            fetchAvailableSlots();
        }
    }

    // 3. Quick Demo Login Credentials Auto-Fill
    const demoButtons = document.querySelectorAll('.btn-demo-fill');
    demoButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const email = this.getAttribute('data-email');
            const pass = this.getAttribute('data-pass');
            const emailField = document.getElementById('login_email');
            const passField = document.getElementById('login_password');

            if (emailField && passField) {
                emailField.value = email;
                passField.value = pass;

                // Visual flash indicator
                emailField.classList.add('is-valid');
                passField.classList.add('is-valid');
                setTimeout(() => {
                    emailField.classList.remove('is-valid');
                    passField.classList.remove('is-valid');
                }, 1000);
            }
        });
    });

    // 4. Password confirmation check on registration
    const regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            const pass = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            if (pass !== confirm) {
                e.preventDefault();
                alert('Passwords do not match! Please check and try again.');
                document.getElementById('confirm_password').focus();
            }
        });
    }

    // 5. Confirm cancellation or actions
    const confirmActions = document.querySelectorAll('.confirm-action');
    confirmActions.forEach(btn => {
        btn.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
});

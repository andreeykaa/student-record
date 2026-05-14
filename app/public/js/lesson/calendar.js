$(function () {
    const $calendar = $('#calendar');

    if (!$calendar.length) {
        return;
    }

    const eventsUrl = $calendar.data('events-url');
    const createUrl = $calendar.data('create-url');
    const updateUrlTemplate = $calendar.data('update-url-template');
    const updateTimeUrlTemplate = $calendar.data('update-time-url-template');
    const updateTimeToken = $calendar.data('update-time-token');
    const openLessonIdRaw = $calendar.data('open-lesson-id');
    let openLessonId = openLessonIdRaw ? String(openLessonIdRaw) : null;
    const initialDate = $calendar.data('initial-date');

    const calendarEl = $calendar[0];
    const lessonModalEl = $('#lessonCreateModal');
    const lessonModal = new bootstrap.Modal(lessonModalEl[0]);

    const $lessonForm = $('#lessonCreateForm');
    const $lessonError = $('#lessonCreateError');
    const $saveButton = $('#saveLessonButton');

    let currentSubmitUrl = createUrl;

    $('.js-lesson-datetime').each(function () {
        flatpickr(this, {
            locale: 'uk',
            enableTime: true,
            time_24hr: true,
            minuteIncrement: 5,
            hourIncrement: 1,
            dateFormat: 'Y-m-d\\TH:i',
            altInput: true,
            altFormat: 'd.m.Y H:i',
            allowInput: true,
            disableMobile: true
        });
    });

    function formatForDatetimeLocal(date) {
        const year = date.getUTCFullYear();
        const month = String(date.getUTCMonth() + 1).padStart(2, '0');
        const day = String(date.getUTCDate()).padStart(2, '0');
        const hours = String(date.getUTCHours()).padStart(2, '0');
        const minutes = String(date.getUTCMinutes()).padStart(2, '0');

        return `${year}-${month}-${day}T${hours}:${minutes}`;
    }

    function setFlatpickrValue(selector, value) {
        const input = $(selector)[0];

        if (input && input._flatpickr) {
            input._flatpickr.setDate(value, true);
        } else {
            $(selector).val(value);
        }
    }

    function resetLessonForm() {
        $lessonError.addClass('d-none').html('');
        $lessonForm[0].reset();

        $('#lesson_id').val('');
        currentSubmitUrl = createUrl;
        $saveButton.text('Зберегти заняття');
        $('#lessonCreateModalLabel').text('Нове заняття');
    }

    function openCreateModal(startDate, endDate) {
        resetLessonForm();

        setFlatpickrValue('#lesson_start_at', formatForDatetimeLocal(startDate));
        setFlatpickrValue('#lesson_end_at', formatForDatetimeLocal(endDate));
        $('#lesson_status').val('scheduled');

        lessonModal.show();
    }

    function openEditModal(event) {
        resetLessonForm();

        $('#lesson_id').val(event.id);
        $('#lesson_title').val(event.title || '');
        $('#lesson_student').val(event.extendedProps.studentId || '');
        $('#lesson_status').val(event.extendedProps.status || 'scheduled');
        $('#lesson_notes').val(event.extendedProps.notes || '');

        setFlatpickrValue('#lesson_start_at', formatForDatetimeLocal(event.start));
        setFlatpickrValue('#lesson_end_at', formatForDatetimeLocal(event.end));

        currentSubmitUrl = updateUrlTemplate.replace('__ID__', event.id);
        $saveButton.text('Зберегти зміни');
        $('#lessonCreateModalLabel').text('Редагування заняття');

        lessonModal.show();
    }

    function saveDraggedOrResizedLesson(info) {
        const updateUrl = updateTimeUrlTemplate.replace('__ID__', info.event.id);
        const payload = {
            _token: updateTimeToken,
            start_at: formatForDatetimeLocal(info.event.start),
            end_at: formatForDatetimeLocal(info.event.end)
        };

        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: payload,
            error: function (xhr) {
                let message = 'Не вдалося оновити час заняття.';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }

                info.revert();
                alert(message);
            }
        });
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'uk',
        timeZone: 'Europe/Kyiv',
        initialView: 'timeGridWeek',
        initialDate: initialDate || undefined,
        firstDay: 1,
        nowIndicator: true,
        allDaySlot: false,
        selectable: true,
        selectMirror: true,
        editable: true,
        eventStartEditable: true,
        eventDurationEditable: true,
        eventResizableFromStart: true,
        slotDuration: '00:30:00',
        snapDuration: '00:15:00',
        slotLabelInterval: '01:00:00',
        height: 'auto',
        slotMinTime: '08:00:00',
        slotMaxTime: '21:00:00',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'Сьогодні',
            week: 'Тиждень',
            day: 'День'
        },
        events: eventsUrl,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        select: function (info) {
            openCreateModal(info.start, info.end);
        },
        dateClick: function (info) {
            const startDate = info.date;
            const endDate = new Date(startDate.getTime() + 60 * 60 * 1000);

            openCreateModal(startDate, endDate);
        },
        eventClick: function (info) {
            openEditModal(info.event);
        },
        eventDrop: function (info) {
            saveDraggedOrResizedLesson(info);
        },
        eventResize: function (info) {
            saveDraggedOrResizedLesson(info);
        },
        eventsSet: function () {
            if (!openLessonId) {
                return;
            }

            const event = calendar.getEvents().find(function (item) {
                return String(item.id) === openLessonId;
            });

            if (!event) {
                return;
            }

            openEditModal(event);
            openLessonId = null;

            const url = new URL(window.location.href);
            url.searchParams.delete('open_lesson');
            url.searchParams.delete('date');
            window.history.replaceState({}, '', url.toString());
        },
        eventContent: function (arg) {
            const timeText = arg.timeText || '';
            const studentName = arg.event.extendedProps.studentName || '';

            return {
                html: `
                    <div class="fc-custom-event">
                        <div class="fc-custom-event-time">${timeText}</div>
                        <div class="fc-custom-event-title">${studentName}</div>
                    </div>
        `
            };
        },
    });

    calendar.render();

    $saveButton.on('click', function () {
        $.ajax({
            url: currentSubmitUrl,
            type: 'POST',
            data: $lessonForm.serialize(),
            success: function () {
                lessonModal.hide();
                calendar.refetchEvents();
            },
            error: function (xhr) {
                let html = 'Не вдалося зберегти заняття.';

                if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.length) {
                    html = xhr.responseJSON.errors.join('<br>');
                }

                $lessonError.removeClass('d-none').html(html);
            }
        });
    });
});

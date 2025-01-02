<?php

/**
 * Calendar overview page
 * Contains a calendar view of events, activities and conferences
 * 
 * This file is part of the OSIRIS package.
 * Copyright (c) 2024 Julia Koblitz, OSIRIS Solutions GmbH
 *
 * @package     OSIRIS
 * @since       1.4.0
 * 
 * @copyright	Copyright (c) 2024 Julia Koblitz, OSIRIS Solutions GmbH
 * @author		Julia Koblitz <julia.koblitz@osiris-solutions.de>
 * @license     MIT
 */
?>

<div class="container">
    <!-- <h1><?= lang('Calendar', 'Kalender') ?></h1> -->
    <div id="calendar"></div>
    <div id="legend">
        <div class="legend-item">
            <span class="legend-color conference"></span>
            Konferenz
        </div>
        <!-- <div class="legend-item">
            <span class="legend-color research-trip"></span>
            Forschungsreise
        </div> -->
        <div class="legend-item">
            <span class="legend-color activity"></span>
            Aktivität
        </div>
        <div class="legend-item">
            <span class="legend-color project"></span>
            Projekt
        </div>
        <!-- guests -->
        <div class="legend-item">
            <span class="legend-color guest"></span>
            Gast
        </div>
    </div>

    <style>
        #legend {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            font-family: Arial, sans-serif;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            display: inline-block;
            border-radius: 4px;
        }

        .legend-color.conference {
            background-color: #007bff;
        }

        .legend-color.research-trip {
            background-color: #28a745;
        }

        .legend-color.activity {
            background-color: #ffc107;
        }

        .legend-color.project {
            background-color: #dc3545;
        }

        .legend-color.guest {
            background-color: #6c757d;
        }
    </style>
</div>
<style>
    .fc-scrollgrid {
        background-color: white;
    }

    .toastui-calendar-layout {
        height: 50rem !important;
    }

    .calendar-tooltip {
        max-width: 300px;
    }
    .calendar-tooltip h3 {
        margin-bottom: 0.5rem;
        margin-top: 0;
        font-size: 1.8rem;
    }

    .badge.project {
        background-color: #dc3545;
        color: white;
    }
    .badge.activity {
        background-color: #ffc107;
        color: black;
    }
    .badge.research_trip {
        background-color: #28a745;
        color: white;
    }
    .badge.event {
        background-color: #007bff;
        color: white;
    }
    
</style>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script src="<?= ROOTPATH ?>/js/popover.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: lang('en', 'de'),
            events: function(fetchInfo, successCallback, failureCallback) {
                // start and end as ISO dates
                var start = fetchInfo.startStr.split('T')[0];
                var end = fetchInfo.endStr.split('T')[0];

                // Ajax-Request, um die Events vom Backend zu holen
                fetch(ROOTPATH + '/api/calendar?start=' + start + '&end=' + end, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(fetchInfo);
                        if (data.status === 200) {
                            successCallback(data.data); // Nur die Events weitergeben
                        } else {
                            failureCallback('Fehler beim Laden der Events');
                        }
                    })
                    .catch(error => {
                        failureCallback(error);
                    });
            },
            eventDataTransform: function(eventData) {
                // Farben basierend auf dem Typ setzen
                if (eventData.type === 'event') {
                    eventData.backgroundColor = '#007bff'; // Blau
                    eventData.borderColor = '#0056b3'; // Dunkler Blau
                } else if (eventData.type === 'research_trip') {
                    eventData.backgroundColor = '#28a745'; // Grün
                    eventData.borderColor = '#1e7e34'; // Dunkler Grün
                } else if (eventData.type === 'activity') {
                    eventData.backgroundColor = '#ffc107'; // Gelb
                    eventData.borderColor = '#e0a800'; // Dunkler Gelb
                } else if (eventData.type === 'project') {
                    eventData.backgroundColor = '#dc3545'; // Rot
                    eventData.borderColor = '#bd2130'; // Dunkler Rot
                } else if (eventData.type === 'guest') {
                    eventData.backgroundColor = '#6c757d'; // Grau
                    eventData.borderColor = '#495057'; // Dunkler Grau
                }

                if (eventData.end) {
                    // add one day to end date, because fullcalendar is exclusive
                    let endDate = new Date(eventData.end);
                    endDate.setDate(endDate.getDate() + 1);
                    eventData.end = endDate.toISOString().split('T')[0];
                }
                return eventData;
            },
            eventClick: function(info) {
                // Popover auf das Event-Element anwenden
                const $el = $(info.el); // Das HTML-Element des Events

                // Vorherige Popovers entfernen, um Mehrfach-Instanzen zu verhindern
                $('.popover').remove();
                console.log(info.event);
                let date = info.event.start.toLocaleDateString();
                // add end date if available
                if (info.event.end) {
                    // use end date - 1 day
                    const end = info.event.end
                    end.setDate(end.getDate() - 1);
                    if (end.toLocaleDateString() !== date)
                        date += ' - ' + end.toLocaleDateString();
                }

                let type = info.event.extendedProps.type || 'na';
                let label = lang('Unknown', 'Nicht angegeben');
                let link = '#';
                
                switch (type) {
                    case 'event':
                        label = lang('Event', 'Veranstaltung');
                        link = ROOTPATH + '/conferences/' + info.event.id;
                        break;
                    case 'research_trip':
                        label = lang('Field research', 'Forschungsreise');
                        link = ROOTPATH + '/research-trips/' + info.event.id;
                        break;
                    case 'activity':
                        label = lang('Activity', 'Aktivität');
                        link = ROOTPATH + '/activities/view/' + info.event.id;
                        break;
                    case 'project':
                        label = lang('Project', 'Projekt');
                        link = ROOTPATH + '/projects/view/' + info.event.id;
                        break;
                    case 'guest':
                        label = lang('Guest', 'Gast');
                        link = ROOTPATH + '/guests/view/' + info.event.id;
                        break;
                }


                // Popover mit Bootstrap initialisieren
                $el.popover({
                    placement: 'auto', 
                    container: '#calendar', 
                    trigger: 'focus',
                    html: true, 
                    content: `
                        <div class="calendar-tooltip">
                            <h3>${info.event.title}</h3>
                            <p><strong>${lang('Type', 'Typ')}:</strong> <span class="badge ${type}">${label}</span></p>
                            <p><strong>${lang('Date', 'Datum')}:</strong> ${date}</p>
                            <a class="btn small" href="${link}">${lang('More details', 'Mehr Details')}</a>
                            <button class="btn small" onclick="$('.popover').remove()">
                                Schließen
                            </button>
                        </div>`
                });

                // Popover anzeigen
                $el.popover('show');
            }
        });
        calendar.render();
    });

</script>
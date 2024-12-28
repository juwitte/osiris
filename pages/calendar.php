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
    <h1><?= lang('Calendar', 'Kalender') ?></h1>
    <div id="calendar"></div>
</div>
<style>
    .fc-scrollgrid {
        background-color: white;
    }
</style>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: lang('en', 'de'),
            events: function(fetchInfo, successCallback, failureCallback) {
                // Ajax-Request, um die Events vom Backend zu holen
                fetch(ROOTPATH + '/api/events', {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
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
            eventClick: function(info) {
            // Popup anzeigen
            var popupContent = `
                <div>
                    <h3>${info.event.title}</h3>
                    <p>Start: ${info.event.start}</p>
                    <p>Ende: ${info.event.end || 'Nicht angegeben'}</p>
                    <button onclick="window.location.href='${ROOTPATH}/conferences/${info.event.id}'">
                        Mehr Details
                    </button>
                </div>
            `;
            showPopup(popupContent); // Eigene Popup-Funktion aufrufen
        }
        });
        calendar.render();
    });

    // Beispiel für eine Popup-Funktion (kann angepasst werden)
function showPopup(content) {
    var popup = document.createElement('div');
    popup.style.position = 'fixed';
    popup.style.top = '20%';
    popup.style.left = '50%';
    popup.style.transform = 'translate(-50%, -50%)';
    popup.style.background = '#fff';
    popup.style.padding = '20px';
    popup.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
    popup.style.zIndex = '1000';
    popup.innerHTML = content;

    var closeBtn = document.createElement('button');
    closeBtn.textContent = 'Schließen';
    closeBtn.onclick = function() {
        document.body.removeChild(popup);
    };
    popup.appendChild(closeBtn);

    document.body.appendChild(popup);
}
</script>
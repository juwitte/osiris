function navigate(key) {
    $('section').hide()
    $('section#' + key).show()

    $('.pills .btn').removeClass('active')
    $('.pills .btn#btn-' + key).addClass('active')

    // hash
    window.location.hash = 'section-' + key;
}

function addName(evt, el) {
    var group = $('<div class="input-group d-inline-flex w-auto m-5"> ')
    group.append('<input type="text" name="values[names][]" value="" required class="form-control">')
    // var input = $()
    var btn = $('<a class="btn text-danger">')
    btn.on('click', function () {
        $(this).closest('.input-group').remove();
    })
    btn.html('&times;')

    group.append($('<div class="input-group-append">').append(btn))
    // $(el).prepend(group);
    $(group).insertBefore(el);
}

function addResearchInterest(evt) {
    if ($('.research-interest').length >= 5) {
        toastError(lang('Max. 5 research interests.', 'Maximal 5 Forschungsinteressen können angegeben werden.'));
        return;
    }

    var tr = `
            <tr class="research-interest">
                <td>
                    <input type="text" name="values[research][]" list="research-list" required class="form-control">
                </td>
                <td>
                    <input type="text" name="values[research_de][]" list="research-list-de" class="form-control">
                </td>
                <td><a class="btn text-danger" onclick="$(this).closest('.research-interest').remove();"><i class="ph ph-trash"></i></a></td>
            </tr>
            `;
    $('#research-interests').append(tr);
}

$(document).ready(function () {

    // read hash to navigate
    var hash = window.location.hash;
    if (hash) {
        hash = hash.replace('#section-', '')
        // check if hash is a valid section
        if ($(`section#${hash}`).length > 0) {
            navigate(hash);
        }
    }

    $.ajax({
        url: ROOTPATH + "/api/groups/tree",
        method: "GET",
        dataType: "json",
        success: function (response) {
            if (response.status === 200) {
                generateCheckboxTree(response.data, $('#organization-tree'));

                // Markiere die vorausgewählten Organisationseinheiten
                preselectCheckboxes(selectedOrgIds);

                // Aktualisiere die Eltern-Checkboxen und öffne die Listen, falls notwendig
                selectedOrgIds.forEach(function (id) {
                    var $checkbox = $('#' + id);
                    updateParentCheckboxes($checkbox);
                    // Öffne die untergeordneten Listen
                    $checkbox.closest('li').parents('ul').show();
                    // Ändere das Icon der Eltern auf "expanded"
                    $checkbox.closest('li').parents('li').find('> span > .toggle-icon').addClass('expanded');
                });
            }
        }
    });
    // Toggle Funktion
    $(document).on('click', '.toggle-icon', function () {
        var $this = $(this);
        $this.parent().siblings('ul').slideToggle(); // Klappt die untergeordneten Einheiten ein/aus
        $this.toggleClass('expanded'); // Wechselt das Icon zwischen plus/minus
    });
    // Event für die Checkbox-Änderung
    $(document).on('change', 'input[type="checkbox"]', function () {
        var $this = $(this);

        // Markiere alle Kinder je nach dem Zustand der aktuellen Checkbox
        // $this.closest('li').find('ul input[type="checkbox"]').prop('checked', $this.is(':checked'));

        if (!$this.is(':checked')) {
            // check if children are checked and intermediate this box
            var $childCheckboxes = $this.closest('li').find('ul input[type="checkbox"]:checked');
            if ($childCheckboxes.length > 0) {
                $this.prop('indeterminate', true);
            }
        }

        // Aktualisiere die Eltern-Checkboxen
        updateParentCheckboxes($this);
    });

});

function generateCheckboxTree(node, $container, depth = 0) {
    var $li = $('<li></li>');
    console.log(node);

    var $span = $('<span></span>');

    var $checkbox = $('<input type="checkbox">').attr('id', node.id).attr('name', 'values[depts][]').val(node.id);
    var $label = $('<label></label>').attr('for', node.id).text(node.name);

    var inactive = node.inactive ?? false;
    if (inactive){
        $li.addClass('inactive')
    }

    // Nur wenn es Kinder gibt, wird das Plus-Icon hinzugefügt
    if (node.children && node.children.length > 0) {
        var $icon = $('<i class="ph ph-caret-right toggle-icon"></i>'); // Phosphoricon Plus-Symbol
        $span.append($icon);
    }

    $span.append($checkbox).append($label);
    $li.append($span);

    if (node.children && node.children.length > 0) {
        var $childrenContainer = $('<ul></ul>');

        // Ebene 2 und tiefer werden standardmäßig zugeklappt
        if (depth >= 1) {
            $childrenContainer.hide();
        }

        $.each(node.children, function (index, child) {
            generateCheckboxTree(child, $childrenContainer, depth + 1);
        });
        $li.append($childrenContainer);
    }

    if (depth === 0) {
        var $ul = $('<ul></ul>');
        $ul.append($li);
        $container.append($ul);
    } else {
        $container.append($li);
    }
}


// Funktion zum Aktualisieren der Eltern-Checkboxen
function updateParentCheckboxes($checkbox) {
    var $parentLi = $checkbox.parents('li').first().parent().closest('li');

    // if parent list is checked already: keep it
    if ($parentLi.find('> span > input[type="checkbox"]').is(':checked')) {
        return;
    }

    if ($parentLi.length > 0) {
        var $childCheckboxes = $parentLi.find('ul input[type="checkbox"]');
        var allChecked = $childCheckboxes.length === $childCheckboxes.filter(':checked').length;
        var noneChecked = $childCheckboxes.filter(':checked').length === 0;

        if (noneChecked) {
            $parentLi.find('> span > input[type="checkbox"]').prop({
                checked: false,
                indeterminate: false
            });
        } else {
            $parentLi.find('> span > input[type="checkbox"]').prop({
                checked: false,
                indeterminate: true
            });
        }

        // Rekursiv die Eltern aktualisieren
        updateParentCheckboxes($parentLi.find('> span > input[type="checkbox"]'));
    }
}

// Funktion zum Vorab-Auswählen der Checkboxen
function preselectCheckboxes(selectedIds) {
    selectedIds.forEach(function (id) {
        $('#' + id).prop('checked', true);
    });
}

function updateScienceUnit(user, unit){
    
    $.ajax({
        url: ROOTPATH + "/crud/users/update-science-unit",
        method: "POST",
        data: {
            user: user,
            unit: unit
        },
        dataType: "html",
        success: function (response) {
            toastSuccess(response);
            location.reload();

        }
    });
}

function validateGoogleScholar(element){
    var id = $(element).val();
    // regex for google scholar id
    var regex = /^[a-zA-Z0-9_-]{12}$/;
    if (id === '') {
        $(element).removeClass('is-invalid');
        $(element).removeClass('is-valid');
        $('#google-scholar-wrong').hide();
        return [true, ''];
    } else if (!regex.test(id)) {
        $(element).addClass('is-invalid');
        $(element).removeClass('is-valid');
        $('#google-scholar-wrong').show();
        return [false, lang('Google Scholar ID must be 12 characters long and can only contain letters and numbers.', 'Die Google Scholar ID muss 12 Zeichen lang sein und darf nur Buchstaben und Zahlen enthalten.')];
    } else {
        $(element).removeClass('is-invalid');
        $(element).addClass('is-valid');
        $('#google-scholar-wrong').hide();
        return [true, ''];
    }
}

function validateORCID(input) {
    var orcid = $(input).val();
    // regex for orcid
    var regex = /^\d{4}-\d{4}-\d{4}-\d{3}[0-9X]{1}$/;
    if (orcid === '') {
        $(input).removeClass('is-invalid');
        $(input).removeClass('is-valid');
        $('#orcid-wrong').hide();
        return [true, ''];
    } else if (!regex.test(orcid)) {
        $(input).addClass('is-invalid');
        $(input).removeClass('is-valid');
        $('#orcid-wrong').show();
        return [false, lang('ORCID must be in the format 0000-0000-0000-0000', 'Die ORCID muss im Format 0000-0000-0000-0000 angegeben werden')];
    } else {
        $(input).removeClass('is-invalid');
        $(input).addClass('is-valid');
        $('#orcid-wrong').hide();
        return [true, ''];
    }
}

// allowed social media domains
const socialHostRules = {
    github: ['github.com', 'www.github.com'],
    linkedin: ['linkedin.com', 'www.linkedin.com', 'de.linkedin.com'],
    researchgate: ['researchgate.net', 'www.researchgate.net'],
    youtube: ['youtube.com', 'www.youtube.com', 'youtu.be'],
    mastodon: [], // hard to pin to one domain; allow any valid URL
    bluesky: ['bsky.app', 'www.bsky.app'],
    instagram: ['instagram.com', 'www.instagram.com'],
    facebook: ['facebook.com', 'www.facebook.com', 'fb.com', 'www.fb.com'],
    x: ['x.com', 'www.x.com', 'twitter.com', 'www.twitter.com'],
    matrix: ['matrix.to', 'www.matrix.to'],
    website: [] // personal website: allow any valid URL
};

function validateSocial(element){
    let msg = '';
    const name = $(element).attr('name') || '';
    const match = name.match(/\[socials\]\[([^\]]+)\]/i);
    if (!match) return [true, msg];

    const type = match[1].toLowerCase();
    const url = String($(element).val() || '').trim();

    if (url === '') {
        $(element).toggleClass('is-invalid', false);
        return [true, msg];
    }

    
    let parsedUrl;
    try {
        parsedUrl = new URL(url);
    } catch (e) {
        msg = lang(`Socials: ${type} - please enter a valid URL.`, `Soziale Medien: ${type} - bitte geben Sie eine gültige URL ein.`);
        $(element).toggleClass('is-invalid', true);
        return [false, msg];
    }

    if (!['http:', 'https:'].includes(parsedUrl.protocol)) {
        msg = lang(`Socials: ${type} - URL must start with http:// or https://`, `Soziale Medien: ${type} - URL muss mit http:// oder https:// beginnen`);
        $(element).toggleClass('is-invalid', true);
        return [false, msg];
    }

    if (socialHostRules[type] && socialHostRules[type].length > 0) {
        const hostname = parsedUrl.hostname;
        if (!socialHostRules[type].includes(hostname)) {
            msg = lang(`Socials: ${type} - URL must point to ${socialHostRules[type].join(', ')}`, `Soziale Medien: ${type} - URL muss auf ${socialHostRules[type].join(', ')} zeigen`);
            $(element).toggleClass('is-invalid', true);
            return [false, msg];
        };
    }

    $(element).toggleClass('is-invalid', false);
    return [true, msg];
}

const validators = {
    social: validateSocial,
    googleScholar: validateGoogleScholar,
    orcid: validateORCID
}


function validateUserForm(event, form) {
    let valid = true;
    let firstInvalidElement = null;
    
    $('.need-validation').each(function() {
        const validatorName = $(this).data('validator');
        const validator = validators[validatorName];
        if (validator) {
            const [isValid, msg] = validator(this);
            if (!isValid){
                valid = false;
                toastError(msg);
                $(this).toggleClass('is-invalid', true);
                if (!firstInvalidElement) {
                    firstInvalidElement = this;
                }
            }
            else {
                $(this).toggleClass('is-invalid', false);
            }
        }
    });
   
    if (!valid) {
        event.preventDefault();
        toastError(lang('Please correct the errors in the form.', 'Bitte korrigieren Sie die Fehler im Formular.'));
        if (firstInvalidElement) {
            const tabName = $(firstInvalidElement).closest('section').attr('id');
            if (tabName) {
                navigate(tabName);
            }
            firstInvalidElement.focus();
        }
    }
}
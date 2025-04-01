<?php

/**
 * Edit details of a organization
 * Created in cooperation with DSMZ
 * 
 * This file is part of the OSIRIS package.
 * Copyright (c) 2024 Julia Koblitz, OSIRIS Solutions GmbH
 *
 * @package     OSIRIS
 * @since       1.3.8
 * 
 * @copyright	Copyright (c) 2024 Julia Koblitz, OSIRIS Solutions GmbH
 * @author		Julia Koblitz <julia.koblitz@osiris-solutions.de>
 * @license     MIT
 */


function val($index, $default = '')
{
    $val = $GLOBALS['form'][$index] ?? $default;
    if (is_string($val)) {
        return htmlspecialchars($val);
    }
    if ($val instanceof MongoDB\Model\BSONArray) {
        return implode(',', DB::doc2Arr($val));
    }
    return $val;
}

function sel($index, $value)
{
    return val($index) == $value ? 'selected' : '';
}

$form = $GLOBALS['form'] ?? [];

if (empty($form) || !isset($form['_id'])) {
    $formaction = ROOTPATH . "/crud/organizations/create";
    $url = ROOTPATH . "/organizations/view/*";
} else {
    $formaction = ROOTPATH . "/crud/organizations/update/" . $form['_id'];
    $url = ROOTPATH . "/organizations/view/" . $form['_id'];
}

?>

<script src="<?= ROOTPATH ?>/js/quill.min.js?v=<?= CSS_JS_VERSION ?>"></script>


<h3 class="title">
    <?php
    if (empty($form) || !isset($form['_id'])) {
        echo lang('New Organization', 'Neue Infrastruktur');
    } else {
        echo lang('Edit Organization', 'Infrastruktur bearbeiten');
    }
    ?>
</h3>

<form action="<?= $formaction ?>" method="post" class="form">
    <input type="hidden" name="redirect" value="<?= $url ?>">

    <div class="form-group floating-form">
        <?php if (empty($form)) { ?>
            <input type="text" id="id" class="form-control" name="values[id]" required value="<?= uniqid() ?>" placeholder="ID is a required field">
            <label for="id" class="required">ID</label>
            <small class="text-muted">
                <?= lang('It it recommended to choose something short you can recognize.', 'Es wird empfohlen, etwas Kurzes, Wiedererkennbares zu nehmen.') ?>
            </small>
        <?php } else { ?>
            <small class="font-weight-bold">ID:</small><br>
            <?= $form['id'] ?>
        <?php } ?>
    </div>

    <div class="row row-eq-spacing">
        <div class="col-md-6">
            <label for="start_date" class="required">
                <?= lang('Start', 'Anfang') ?> <span class="badge kdsf">KDSF-B-13-3</span>
            </label>
            <input type="date" class="form-control" name="values[start_date]" id="start_date" required value="<?= $form['start_date'] ?? '' ?>">
        </div>
        <div class="col-md-6">
            <label for="end_date">
                <?= lang('End', 'Ende') ?> <span class="badge kdsf">KDSF-B-13-4</span>
            </label>
            <input type="date" class="form-control" name="values[end_date]" id="end_date" value="<?= $form['end_date'] ?? '' ?>">
        </div>
    </div>

    <div class="row row-eq-spacing mb-0">
        <div class="col-md-6">
            <fieldset>
                <legend class="d-flex"><?= lang('English', 'Englisch') ?> <img src="<?= ROOTPATH ?>/img/gb.svg" alt="EN" class="flag"></legend>
                <div class="form-group">
                    <label for="name" class="required">
                        <?= lang('Title', 'Titel') ?> (EN)
                        <span class="badge kdsf">KDSF-B-13-2</span>
                    </label>
                    <input type="text" class="form-control large" name="values[name]" id="name" required value="<?= $form['name'] ?? '' ?>">
                </div>

                <div class="form-group">
                    <label for="subtitle">
                        <?= lang('Subtitle', 'Untertitel') ?> (EN)
                    </label>
                    <input type="text" class="form-control" name="values[subtitle]" id="subtitle" value="<?= $form['subtitle'] ?? ''  ?>">
                </div>
            </fieldset>
        </div>
        <div class="col-md-6">
            <fieldset>
                <legend class="d-flex"><?= lang('German', 'Deutsch') ?> <img src="<?= ROOTPATH ?>/img/de.svg" alt="DE" class="flag"></legend>
                <div class="form-group">
                    <label for="name_de">
                        <?= lang('Title', 'Titel') ?> (DE)
                    </label>
                    <input type="text" class="form-control large" name="values[name_de]" id="name_de" value="<?= $form['name_de'] ?? '' ?>">
                </div>

                <div class="form-group">
                    <label for="name_de">
                        <?= lang('Subtitle', 'Untertitel') ?> (DE)
                    </label>
                    <input type="text" class="form-control" name="values[subtitle_de]" id="subtitle_de" value="<?= $form['subtitle_de'] ?? '' ?>">
                </div>
            </fieldset>
        </div>
    </div>


    <label for="description">
        <?= lang('Description', 'Beschreibung') ?>
        <span class="badge kdsf">KDSF-B-13-11</span>
    </label>
    <div class="form-group">
        <div id="description-quill"><?= $form['description'] ?? '' ?></div>
        <textarea name="values[description]" id="description" class="d-none" readonly><?= $form['description'] ?? '' ?></textarea>
        <script>
            quillEditor('description');
        </script>
    </div>


    <div class="row row-eq-spacing">
        <div class="col-md-6">
            <label for="type">
                <?= lang('Type', 'Typ') ?>
                <span class="badge kdsf">KDSF-B-13-5</span>
            </label>
            <select name="values[type]" id="type" class="form-control">
                <option value="Großgeräte und Instrumente" <?= sel('type', 'Großgeräte und Instrumente') ?>><?= lang('Equipment and Instruments', 'Großgeräte und Instrumente') ?></option>
                <option value="Wissensressourcen" <?= sel('type', 'Wissensressourcen') ?>><?= lang('Knowledge Resources', 'Wissensressourcen') ?></option>
                <option value="Informations- und Kommunikationsinfrastrukturen" <?= sel('type', 'Informations- und Kommunikationsinfrastrukturen') ?>><?= lang('Information and Communication Organizations', 'Informations- und Kommunikationsinfrastrukturen') ?></option>
                <option value="Sonstiges" <?= sel('type', 'Sonstiges') ?>><?= lang('Other', 'Sonstiges') ?></option>
            </select>
        </div>

        <div class="col-md-6">
            <label for="organization_type">
                <?= lang('Type of organization', 'Art der Infrastruktur') ?>
                <span class="badge kdsf">KDSF-B-13-6</span>
            </label>
            <select name="values[organization_type]" id="organization_type" class="form-control">
                <option value="Lokal" <?= sel('organization_type', 'Lokal') ?>><?= lang('Local', 'Lokal') ?></option>
                <option value="Verteilt" <?= sel('organization_type', 'Verteilt') ?>><?= lang('Distributed', 'Verteilt') ?></option>
                <option value="Virtuell" <?= sel('organization_type', 'Virtuell') ?>><?= lang('Virtual', 'Virtuell') ?></option>
            </select>
        </div>
    </div>


    <div class="row row-eq-spacing">
        <div class="col-md-6">
            <label for="access">
                <?= lang('User Access', 'Art des Zugangs') ?>
                <span class="badge kdsf">KDSF-B-13-7</span>
            </label>
            <select name="values[access]" id="access" class="form-control">
                <option value="User Access" <?= sel('access', 'User Access') ?>><?= lang('User Access', 'User Access') ?></option>
                <option value="Shared Access" <?= sel('access', 'Shared Access') ?>><?= lang('Shared Access', 'Shared Access') ?></option>
                <option value="Open Access" <?= sel('access', 'Open Access') ?>><?= lang('Open Access', 'Open Access') ?></option>
                <option value="Sonstiges" <?= sel('access', 'Sonstiges') ?>><?= lang('Other', 'Sonstiges') ?></option>
            </select>
        </div>
    </div>

    <h6>
        <?= lang('Collaborative research organization', 'Verbundforschungsinfrastruktur') ?>
    </h6>

    <?php
    $collaborative = $form['collaborative'] ?? 'no';
    ?>


    <div class="form-group">
        <label for="collaborative">
            <?= lang('Is this a collaborative research organization?', 'Ist dies eine Verbundforschungsinfrastruktur?') ?>
            <span class="badge kdsf">KDSF-B-13-12</span>
        </label>
        <div>
            <input type="radio" name="values[collaborative]" id="collaborative-yes" value="yes" <?= ($collaborative == 'yes') ? 'checked' : '' ?>>
            <label for="collaborative-yes">Yes</label>
            <input type="radio" name="values[collaborative]" id="collaborative-no" value="no" <?= ($collaborative == 'no') ? 'checked' : '' ?>>
            <label for="collaborative-no">No</label>
        </div>

        <div id="form-collaborative" style="display: <?= ($collaborative == 'yes') ? 'block' : 'none' ?>;" class="box padded">

            <div class="form-group">
                <label for="coordinator">
                    <?= lang('Does your institution coordinate the collaborative organization?', 'Koordiniert deine Einrichtung die Verbundinfrastruktur?') ?>
                    <span class="badge kdsf">KDSF-B-13-13</span>
                </label>
                <select name="values[coordinator]" id="coordinator" class="form-control">
                    <option value="yes" <?= sel('coordinator', 'yes') ?>><?= lang('Yes', 'Ja') ?></option>
                    <option value="no" <?= sel('coordinator', 'no') ?>><?= lang('No', 'Nein') ?></option>
                </select>

            </div>
            <div class="form-group">
                <label for="external_coordinator">
                    <?= lang('If not, who is the coordinator?', 'Falls nein, wer ist die Koordinatoreinrichtung?') ?>
                    <span class="badge kdsf">KDSF-B-13-14</span>
                </label>
                <input type="text" class="form-control" name="values[external_coordinator]" id="external_coordinator" value="<?= $form['external_coordinator'] ?? '' ?>">

            </div>

            <div class="form-group mb-10">
                <label for="cooperation_partners">
                    <?= lang('Cooperation Partners', 'Ko-Betreiber:innen') ?>
                    <span class="badge kdsf">KDSF-B-13-15</span>
                </label>
                <input type="text" class="form-control" name="values[cooperation_partners]" id="cooperation_partners" value="<?= $form['cooperation_partners'] ?? '' ?>">
            </div>
        </div>

        <script>
            document.getElementById('collaborative-yes').addEventListener('change', function() {
                document.getElementById('form-collaborative').style.display = 'block';
            });
            document.getElementById('collaborative-no').addEventListener('change', function() {
                document.getElementById('form-collaborative').style.display = 'none';
            });
        </script>

    </div>

    <button type="submit" class="btn secondary"><?= lang('Save', 'Speichern') ?></button>
</form>
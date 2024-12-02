<?php

/**
 * Page to edit user information
 * 
 * This file is part of the OSIRIS package.
 * Copyright (c) 2024 Julia Koblitz, OSIRIS Solutions GmbH
 * 
 * @link        /user/edit/<username>
 *
 * @package     OSIRIS
 * @since       1.0.0
 * 
 * @copyright	Copyright (c) 2024 Julia Koblitz, OSIRIS Solutions GmbH
 * @author		Julia Koblitz <julia.koblitz@osiris-solutions.de>
 * @license     MIT
 */

$depts = DB::doc2Arr($data['depts'] ?? []);
?>


<script>
    const selectedOrgIds = JSON.parse('<?= json_encode($depts) ?>');
</script>
<script src="<?= ROOTPATH ?>/js/user-editor.js"></script>

<h1 class="mt-0">
    <i class="ph ph-student"></i>
    <?= $data['name'] ?>
</h1>

<?php if ($data['is_active'] ?? true) { ?>
    <div class="text-success">
        <?= lang('This user account is active.', 'Dieser Benutzeraccount ist aktiv.') ?>
    </div>
<?php } else { ?>
    <div class="text-danger">
        <?= lang('This user account is inactive.', 'Dieser Benutzeraccount ist inaktiv.') ?>
    </div>
<?php } ?>


<nav class="pills mt-20 mb-0">
    <a onclick="navigate('personal')" id="btn-personal" class="btn active">
        <i class="ph ph-user" aria-hidden="true"></i>
        <?= lang('Personal', 'Persönlich') ?>
    </a>
    <a onclick="navigate('organization')" id="btn-organization" class="btn">
        <i class="ph ph-building" aria-hidden="true"></i>
        <?= lang('Organization', 'Organisation') ?>
    </a>

    <a onclick="navigate('research')" id="btn-research" class="btn">
        <i class="ph ph-flask" aria-hidden="true"></i>
        <?= lang('Research', 'Forschung') ?>
    </a>

    <a onclick="navigate('biography')" id="btn-biography" class="btn">
        <i class="ph ph-book-open-text" aria-hidden="true"></i>
        <?= lang('Biography', 'Biografie') ?>
    </a>

    <?php if ($Settings->featureEnabled('portal')) { ?>
        <a onclick="navigate('portfolio')" id="btn-portfolio" class="btn">
            <i class="ph ph-eye" aria-hidden="true"></i>
            <?= lang('Portfolio', 'Portfolio') ?>
        </a>
    <?php } ?>
    <a onclick="navigate('contact')" id="btn-contact" class="btn">
        <i class="ph ph-envelope" aria-hidden="true"></i>
        <?= lang('Contact', 'Kontakt') ?>
    </a>
    <a onclick="navigate('account')" id="btn-account" class="btn">
        <i class="ph ph-key" aria-hidden="true"></i>
        <?= lang('Account', 'Account') ?>
    </a>
    <?php if ($data['username'] == $_SESSION['username'] || $Settings->hasPermission('user.settings')) { ?>
        <a onclick="navigate('preferences')" id="btn-preferences" class="btn">
            <i class="ph ph-gear" aria-hidden="true"></i>
            <?= lang('Preferences', 'Einstellungen') ?>
        </a>
    <?php } ?>
</nav>

<form action="<?= ROOTPATH ?>/crud/users/update/<?= $data['username'] ?>" method="post">
    <input type="hidden" class="hidden" name="redirect" value="<?= $url ?? $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?>">

    <section id="personal">
        <h2 class="title"><?= lang('Name and personal information', 'Name und persönliche Informationen') ?></h2>

        <div class="form-row row-eq-spacing">
            <div class="col-sm-2">
                <label for="academic_title">Title</label>
                <select name="values[academic_title]" id="academic_title" class="form-control">
                    <option value="" <?= $data['academic_title'] == '' ? 'selected' : '' ?>></option>
                    <option value="Dr." <?= $data['academic_title'] == 'Dr.' ? 'selected' : '' ?>>Dr.</option>
                    <option value="Prof. Dr." <?= $data['academic_title'] == 'Prof. Dr.' ? 'selected' : '' ?>>Prof. Dr.</option>
                    <option value="PD Dr." <?= $data['academic_title'] == 'PD Dr.' ? 'selected' : '' ?>>PD Dr.</option>
                    <option value="Prof." <?= $data['academic_title'] == 'Prof.' ? 'selected' : '' ?>>Prof.</option>
                    <option value="PD" <?= $data['academic_title'] == 'PD' ? 'selected' : '' ?>>PD</option>
                    <!-- <option value="Prof. Dr." <?= $data['academic_title'] == 'Prof. Dr.' ? 'selected' : '' ?>>Prof. Dr.</option> -->
                </select>
            </div>
            <div class="col-sm">
                <label for="first"><?= lang('First name', 'Vorname') ?></label>
                <input type="text" name="values[first]" id="first" class="form-control" value="<?= $data['first'] ?? '' ?>">
            </div>
            <div class="col-sm">
                <label for="last"><?= lang('Last name', 'Nachname') ?></label>
                <input type="text" name="values[last]" id="last" class="form-control" value="<?= $data['last'] ?? '' ?>">
            </div>
        </div>


        <?php
        if (!isset($data['names'])) {
            $names = [
                $data['formalname'],
                Document::abbreviateAuthor($data['last'], $data['first'], true, ' ')
            ];
        } else {
            $names = $data['names'];
        }
        ?>


        <div class="form-group">
            <label for="names" class=""><?= lang('Names for author matching', 'Namen für das Autoren-Matching') ?></label>

            <div class="box m-0 p-5">
                <?php foreach ($names as $n) { ?>
                    <div class="input-group d-inline-flex w-auto m-5">
                        <input type="text" name="values[names][]" value="<?= $n ?>" required class="form-control">
                        <div class="input-group-append">
                            <a class="btn text-danger" onclick="$(this).closest('.input-group').remove();">×</a>
                        </div>
                    </div>
                <?php } ?>

                <button class="btn secondary m-5" type="button" onclick="addName(event, this);">
                    <i class="ph ph-plus"></i> <?= lang('Add name', 'Füge Namen hinzu') ?>
                </button>
            </div>
        </div>


        <div class="form-group">
            <span><?= lang('Gender', 'Geschlecht') ?>:</span><br>
            <?php
            $gender = $data['gender'] ?? 'n';
            ?>

            <div class="custom-radio d-inline-block mr-10">
                <input type="radio" name="values[gender]" id="gender-m" value="m" <?= $gender == 'm' ? 'checked' : '' ?>>
                <label for="gender-m"><?= lang('Male', 'Männlich') ?></label>
            </div>
            <div class="custom-radio d-inline-block mr-10">
                <input type="radio" name="values[gender]" id="gender-f" value="f" <?= $gender == 'f' ? 'checked' : '' ?>>
                <label for="gender-f"><?= lang('Female', 'Weiblich') ?></label>
            </div>
            <div class="custom-radio d-inline-block mr-10">
                <input type="radio" name="values[gender]" id="gender-d" value="d" <?= $gender == 'd' ? 'checked' : '' ?>>
                <label for="gender-d"><?= lang('Non-binary', 'Divers') ?></label>
            </div>
            <div class="custom-radio d-inline-block mr-10">
                <input type="radio" name="values[gender]" id="gender-n" value="n" <?= $gender == 'n' ? 'checked' : '' ?>>
                <label for="gender-n"><?= lang('Not specified', 'Nicht angegeben') ?></label>
            </div>

        </div>

    </section>



    <section id="organization" style="display:none;">

        <h2 class="title mb-0">
            <?= lang('Organizational information', 'Organisatorische Informationen') ?>
        </h2>

        <style>
            #depts .table tr.selected td::before {
                content: '\E182';
                font-family: 'Phosphor';
                font-size: 1em;
                color: var(--primary-color);
            }
        </style>

        <div class="depts mb-20">



            <div class="form-group">
                <label for="position">
                    <h5><?= lang('Current Position', 'Aktuelle Position') ?></h5>
                </label>

                <div class="row row-eq-spacing my-0">
                    <div class="col-md-6">
                        <label for="position_de" class="d-flex">Deutsch <img src="<?= ROOTPATH ?>/img/de.svg" alt="DE" class="flag"></label>
                        <input name="values[position_de]" id="position_de" type="text" class="form-control" value="<?= htmlspecialchars($data['position_de'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="position" class="d-flex">English <img src="<?= ROOTPATH ?>/img/gb.svg" alt="EN" class="flag"></label>
                        <input name="values[position]" id="position" type="text" class="form-control" value="<?= htmlspecialchars($data['position'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- if topics are registered, you can choose them here -->
            <?php $Settings->topicChooser($data['topics'] ?? []) ?>

            <h5>
                <?= lang('Currently selected organisational units', 'Zurzeit ausgewählte Organisationseinheiten') ?>
            </h5>

            <p>
            <i class="ph ph-flask text-secondary"></i>
            <?= lang('This is the main unit counting for your scientific output. This unit and all parent units are assigned to your output automatically.', 'Dies ist die Einheit, die für deine wissenschaftliche Ausgabe gezählt wird. Diese Einheit und alle übergeordneten Einheiten werden Ihrer Ausgabe automatisch zugewiesen.') ?>
            </p>

            <?php
            $depts = DB::doc2Arr($data['depts'] ?? []);
            $science_unit = $data['science_unit'] ?? $depts[0] ?? null;
            ?>
            <table class="table small w-auto mb-10">
                <tbody>
                    <?php
                    if (!empty($depts)) {
                        $hierarchy = $Groups->getPersonHierarchyTree($depts);
                        $tree = $Groups->readableHierarchy($hierarchy);

                        foreach ($tree as $row) {
                            $selected = in_array($row['id'], $depts);
                            if ($selected) { ?>
                                <tr class="selected primary">
                                    <td style="padding-left: <?= ($row['indent'] * 2 + 2) . 'rem' ?>;">
                                        <?= lang($row['name_en'], $row['name_de'] ?? null) ?>
                                        <?php if ($science_unit == $row['id']) { ?>
                                           <i class="ph ph-flask text-secondary"></i>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <tr>
                                    <td class="muted">
                                        <?= lang($row['name_en'], $row['name_de'] ?? null) ?>
                                    </td>
                                </tr>
                        <?php }
                        }
                    } else { ?>
                        <tr>
                            <td>
                                <?= lang('No organisational unit selected', 'Keine Organisationseinheit ausgewählt') ?>
                            </td>
                        </tr>
                    <?php }
                    ?>
                </tbody>
            </table>

            <a onclick="$('#organisation-editor').slideToggle()"><i class="ph ph-edit"></i> <?= lang('Edit', 'Bearbeiten') ?></a>
        </div>

        <div id="organisation-editor" class="alert mb-20" style="display:none">

            <style>
                #organization-tree {
                    padding-left: 2rem;
                }

                #organization-tree ul {
                    margin-top: 0;
                    list-style-type: none;
                }

                #organization-tree ul li {
                    margin: 5px 0;
                    position: relative;
                    /* display: flex; */
                }

                #organization-tree ul li span label {
                    margin: 0;

                    /* display: flex; */
                }

                #organization-tree ul li span {
                    display: flex;
                    align-items: center;
                    flex-direction: row;
                    flex-wrap: nowrap;
                }

                #organization-tree ul li [type=checkbox] {
                    margin-right: .5rem;
                }

                .toggle-icon {
                    cursor: pointer;
                    margin-right: 5px;
                    position: absolute;
                    left: -2rem;
                    font-size: 1em !important;
                }

                .toggle-icon.expanded::before {
                    content: '\E136';
                    /* Minus symbol for expanded state */
                }
            </style>
            <input type="hidden" name="values[depts]" value="">
            <!-- checkbox tree -->
            <div id="organization-tree"></div>

            <button class="btn primary small">
                <?= lang('Save', 'Speichern') ?>
            </button>
        </div>

    </section>

    <?php if ($Settings->featureEnabled('portal')) { ?>

        <section id="portfolio" style="display:none;">
            <h2 class="title"><?= lang('Public visibility', 'Öffentliche Darstellung') ?> (Portfolio)</h2>

            <div class="alert danger">
                <div class="custom-checkbox">
                    <input type="checkbox" id="hide" value="1" name="values[hide]" <?= ($data['hide'] ?? false) ? 'checked' : '' ?>>
                    <label for="hide"><?= lang('Hide profile in Portfolio', 'Profil nicht im Portfolio zeigen') ?></label>
                </div>
                <small class="text-danger">
                    <?= lang(
                        'By hiding your profile, you prevent OSIRIS Portfolio from displaying your profile to the public. You can revoke this at any time by unticking the checkbox again.',
                        'Indem du dein Profil versteckst, verhinderst du, dass OSIRIS Portfolio dein Profil öffentlich zeigt. Du kannst dies jederzeit wieder rückgängig machen, indem du das Häkchen wieder entfernst.'
                    ) ?>
                </small>
            </div>

            <!-- show profile picture -->
            <p class="text-danger">
                <?= lang(
                    'By setting the image, mail or phone number to publicly visible, you allow OSIRIS Portfolio to display this personal data of yours to the open public. You can retract this at any time by unticking the check boxes again.',
                    'Indem du das Bild, die Mail oder die Telefonnummer auf öffentlich sichtbar setzt, erlaubst du OSIRIS Portfolio, diese persönlichen Daten öffentlich zu zeigen. Du kannst dies jederzeit wieder rückgängig machen, indem du die Häkchen wieder entfernst.'
                ) ?>
            </p>
            <div class="custom-checkbox mb-20">
                <input type="checkbox" id="public_image" value="1" name="values[public_image]" <?= ($data['public_image'] ?? false) ? 'checked' : '' ?>>
                <label for="public_image"><?= lang('Show profile picture', 'Zeige Profilbild') ?></label>
            </div>

            <div class="custom-checkbox mb-20">
                <input type="checkbox" id="public_email" value="1" name="values[public_email]" <?= ($data['public_email'] ?? true) ? 'checked' : '' ?>>
                <label for="public_email"><?= lang('Show email address', 'Zeige E-Mail-Adresse') ?></label>
            </div>

            <div class="custom-checkbox mb-20">
                <input type="checkbox" id="public_phone" value="1" name="values[public_phone]" <?= ($data['public_phone'] ?? false) ? 'checked' : '' ?>>
                <label for="public_phone"><?= lang('Show telephone number', 'Zeige Telefonnummer') ?></label>
            </div>

            <!-- alternative mail -->
            <div class="form-group">
                <label for="mail_alternative"><?= lang('Alternative Mail', 'Alternative Mail-Adresse') ?></label>
                <input type="text" name="values[mail_alternative]" id="mail_alternative" class="form-control" value="<?= $data['mail_alternative'] ?? '' ?>">
            </div>
            <!-- comment for mail -->
            <div class="form-group">
                <label for="mail_alternative_comment"><?= lang('Explanation for alternative mail', 'Erklärung für die alternative Mail') ?></label>
                <input type="text" name="values[mail_alternative_comment]" id="mail_alternative_comment" class="form-control" value="<?= $data['mail_alternative_comment'] ?? '' ?>">
            </div>

        </section>
    <?php } ?>


    <section id="contact" style="display:none;">
        <h2 class="title"><?= lang('Contact', 'Kontakt') ?></h2>
        <div class="form-row row-eq-spacing">

            <div class="col-sm-6">
                <label for="telephone"><?= lang('Telephone', 'Telefon') ?></label>
                <input type="text" name="values[telephone]" id="telephone" class="form-control" value="<?= $data['telephone'] ?? '' ?>">
            </div>

            <div class="col-sm-6">
                <label for="mail">Mail</label>
                <input type="text" name="values[mail]" id="mail" class="form-control" value="<?= $data['mail'] ?? '' ?>">
            </div>

        </div>


        <?php if ($Settings->featureEnabled('portal')) { ?>
            <p class="text-danger">
                <?= lang('
            Please note that the following information is optional. If you do not wish to make your contact information publicly visible, you can leave the corresponding fields blank. If you fill them in, you authorise OSIRIS Portfolio to show this data publicly. You can revoke this at any time by leaving the fields blank.
            ', '
            Bitte beachte, dass die folgenden Informationen freiwillige Angaben sind. Wenn du deine Kontaktinformationen nicht öffentlich sichtbar machen möchtest, kannst du die entsprechenden Felder leer lassen. Solltest du sie ausfüllen, erlaubst du OSIRIS Portfolio, diese Daten öffentlich zu zeigen. Du kannst dies jederzeit wieder rückgängig machen, indem du die Felder leer lässt.
            ') ?>
            </p>
        <?php } ?>

        <div class="row row-eq-spacing mb-10 mt-0">
            <div class="col-md-6 col-sm-4 mb-20">
                <label for="orcid">ORCID</label>
                <input type="text" name="values[orcid]" id="orcid" class="form-control" value="<?= $data['orcid'] ?? '' ?>">
            </div>

            <div class="col-md-6 col-sm-4 mb-20">
                <label for="twitter">Twitter</label>
                <input type="text" name="values[twitter]" id="twitter" class="form-control" value="<?= $data['twitter'] ?? '' ?>">
            </div>

            <div class="col-md-6 col-sm-4 mb-20">
                <label for="linkedin">LinkedIn</label>
                <input type="text" name="values[linkedin]" id="linkedin" class="form-control" value="<?= $data['linkedin'] ?? '' ?>">
            </div>

            <div class="col-md-6 col-sm-4 mb-20">
                <label for="researchgate">ResearchGate Handle</label>
                <input type="text" name="values[researchgate]" id="researchgate" class="form-control" value="<?= $data['researchgate'] ?? '' ?>">
            </div>

            <div class="col-md-6 col-sm-4 mb-20">
                <label for="google_scholar">Google Scholar ID</label>
                <input type="text" name="values[google_scholar]" id="google_scholar" class="form-control" value="<?= $data['google_scholar'] ?? '' ?>">
                <small class="text-muted">
                    <?= lang('Not the URL! Only the bold part: https://scholar.google.com/citations?user=<b>2G1YzvwAAAAJ</b>&hl=de ', 'Nicht die URL! Nur der fettgedruckte Teil: https://scholar.google.com/citations?user=<b>2G1YzvwAAAAJ</b>&hl=de') ?>
                </small>
                <div class="text-danger" id="google-scholar-wrong" style="display: none;">
                    <?= lang('Please enter a valid Google Scholar ID.', 'Bitte gib eine gültige Google Scholar ID ein.') ?>
                </div>
            </div>
        </div>

        <script>
            // validate google scholar id on change
            $('#google_scholar').on('change', function() {
                var id = $(this).val();
                // regex for google scholar id
                var regex = /^[a-zA-Z0-9_-]{12}$/;
                if (id === '') {
                    $('#google_scholar').removeClass('is-invalid');
                    $('#google-scholar-wrong').hide();
                } else if (!regex.test(id)) {
                    $('#google_scholar').addClass('is-invalid');
                    $('#google-scholar-wrong').show();
                } else {
                    $('#google_scholar').addClass('is-valid');
                    $('#google-scholar-wrong').hide();
                }
            });
        </script>

    </section>



    <section id="account" style="display:none;">
        <h2 class="title">
            <?= lang('Account settings', 'Account-Einstellungen') ?>
        </h2>

        <?php if (!($data['is_active'] ?? true)) { ?>
            <h5>
                <?= lang('Reactivate inactive user account', 'Inaktiven Account reaktivieren') ?>
            </h5>
            <div class="custom-checkbox mb-10">
                <input type="checkbox" id="is_active" value="1" name="values[is_active]">
                <label for="is_active"><?= lang('Reactivate', 'Reaktivieren') ?></label>
            </div>
        <?php } ?>

        <h5>
            <?= lang('Change password', 'Passwort ändern') ?>
        </h5>
        <?php if (
            USER_MANAGEMENT == 'AUTH' &&
            $data['username'] == ($_SESSION['realuser'] ?? $_SESSION['username'])
        ) { ?>

            <div class="form-group">
                <label for="old_password"><?= lang('Old password', 'Vorheriges Password') ?></label>
                <input type="password" name="old_password" id="old_password" class="form-control">
            </div>

            <div class="form-row row-eq-spacing">
                <div class="col-sm-6">
                    <label for="password"><?= lang('New password', 'Neues Passwort') ?></label>
                    <input type="password" name="password" id="password" class="form-control">
                </div>
                <div class="col-sm-6">
                    <label for="password2"><?= lang('Repeat password', 'Passwort wiederholen') ?></label>
                    <input type="password" name="password2" id="password2" class="form-control">
                </div>
            </div>
        <?php } ?>


        <h5><?= lang('Roles', 'Rollen') ?></h5>
        <?php
        // dump($data['roles']);
        foreach ($Settings->get('roles') as $role) {
            // everyone is user: no setting needed
            if ($role == 'user') continue;

            // check if user has role
            $has_role = in_array($role, DB::doc2Arr($data['roles'] ?? array()));

            $disable = false;
            if (!$Settings->hasPermission('user.roles')) $disable = true;
            // only admin can make others admins
            if ($role == 'admin' && !$Settings->hasPermission('admin.give-right')) $disable = true;
        ?>
            <div class="form-group custom-checkbox d-inline-block ml-10 mb-10 <?= $disable ? 'text-muted' : '' ?>">
                <input type="checkbox" id="role-<?= $role ?>" value="<?= $role ?>" name="values[roles][]" <?= ($has_role) ? 'checked' : '' ?> <?= $disable ? 'onclick="return false;"' : '' ?>>
                <label for="role-<?= $role ?>"><?= strtoupper($role) ?></label>
            </div>
        <?php } ?>

        <h5>
            <?= lang('Transfer the maintenance of your profile', 'Übertrage die Pflege deines Profils') ?>
        </h5>

        <div class="form-group mb-0">
            <label for="maintenance"><?= lang('User', 'Nutzende Person') ?>:</label>

            <!-- <input type="text" list="user-list" name="values[maintenance]" id="maintenance" class="form-control" value="<?= $data['maintenance'] ?? '' ?>"> -->
            <select name="values[maintenance]" id="maintenance" class="form-control">
                <option value="">
                    <?= lang('Profile is not shared with someone', 'Du hast dein Profil an niemanden übertragen') ?>
                </option>

                <?php
                $selected = $data['maintenance'] ?? '';
                $all_users = $osiris->persons->find(['is_active' => ['$ne' => false]], ['sort' => ['last' => 1, 'first' => 1]]);
                foreach ($all_users as $s) { ?>
                    <option value="<?= $s['username'] ?>" <?= $selected == $s['username'] ? 'selected' : '' ?>><?= "$s[last], $s[first] ($s[username])" ?></option>
                <?php } ?>
            </select>
        </div>

        <p class=" text-danger">
            <i class="ph ph-warning"></i>
            <?= lang(
                'Warning: this person gets full access to your OSIRIS profile and can edit in your name.',
                'Warnung: diese Person erhält vollen Zugriff auf dein OSIRIS-Profil und kann in deinem Namen editieren.'
            ) ?>
        </p>

    </section>

    <?php if ($data['username'] == $_SESSION['username'] || $Settings->hasPermission('user.settings')) { ?>

        <section id="preferences" style="display:none;">
            <h2 class="title"><?= lang('Profile preferences', 'Profil-Einstellungen') ?></h2>


            <h5><?= lang('Activity display', 'Aktivitäten-Anzeige') ?>:</h5>
            <?php
            $display_activities = $data['display_activities'] ?? 'web';
            ?>

            <div class="custom-radio d-inline-block mr-10">
                <input type="radio" name="values[display_activities]" id="display_activities-web" value="web" <?= $display_activities == 'web' ? 'checked' : '' ?>>
                <label for="display_activities-web"><?= lang('Web') ?></label>
            </div>
            <div class="custom-radio d-inline-block mr-10">
                <input type="radio" name="values[display_activities]" id="display_activities-print" value="print" <?= $display_activities != 'web' ? 'checked' : '' ?>>
                <label for="display_activities-print"><?= lang('Print', 'Druck') ?></label>
            </div>


            <?php
            if ($Settings->featureEnabled('coins')) {
            ?>

                <div class="mt-10">
                    <h5><?= lang('Coin visibility', 'Sichtbarkeit der Coins') ?>:</h5>
                    <?php
                    $show_coins = $data['show_coins'] ?? 'none';
                    ?>

                    <div class="custom-radio d-inline-block mr-10">
                        <input type="radio" name="values[show_coins]" id="show_coins-true" value="none" <?= $show_coins == 'none' ? 'checked' : '' ?>>
                        <label for="show_coins-true"><?= lang('For nobody', 'Für niemanden') ?></label>
                    </div>
                    <div class="custom-radio d-inline-block mr-10">
                        <input type="radio" name="values[show_coins]" id="show_coins-myself" value="myself" <?= $show_coins == 'myself' ? 'checked' : '' ?>>
                        <label for="show_coins-myself"><?= lang('For myself', 'Für mich') ?></label>
                    </div>
                    <div class="custom-radio d-inline-block mr-10">
                        <input type="radio" name="values[show_coins]" id="show_coins-all" value="all" <?= $show_coins == 'all' ? 'checked' : '' ?>>
                        <label for="show_coins-all"><?= lang('For all', 'Für jeden') ?></label>
                    </div>
                </div>
            <?php
            }
            ?>


            <?php
            if ($Settings->featureEnabled('achievements')) {
            ?>
                <div class="mb-20">
                    <h5><?= lang('Show achievements', 'Zeige Errungenschaften') ?>:</h5>
                    <?php
                    $hide_achievements = $data['hide_achievements'] ?? false;
                    ?>

                    <div class="custom-radio d-inline-block mr-10">
                        <input type="radio" name="values[hide_achievements]" id="hide_achievements-false" value="false" <?= $hide_achievements ? '' : 'checked' ?>>
                        <label for="hide_achievements-false"><?= lang('Yes', 'Ja') ?></label>
                    </div>
                    <div class="custom-radio d-inline-block mr-10">
                        <input type="radio" name="values[hide_achievements]" id="hide_achievements-true" value="true" <?= $hide_achievements ? 'checked' : '' ?>>
                        <label for="hide_achievements-true"><?= lang('No', 'Nein') ?></label>
                    </div>
                </div>
            <?php
            }
            ?>
        </section>
    <?php } ?>


    <section id="research" style="display:none">

        <h2 class="title">
            <?= lang('Research interest', 'Forschungsinteressen') ?>
        </h2>

        <small class="text-muted">Max. 5</small><br>
        <table class="table simple">
            <thead>
                <tr>
                    <th><label for="position" class="d-flex">English <img src="<?= ROOTPATH ?>/img/gb.svg" alt="EN" class="flag"></label></th>
                    <th><label for="position_de" class="d-flex">Deutsch <img src="<?= ROOTPATH ?>/img/de.svg" alt="DE" class="flag"></label></th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="research-interests">
                <?php
                $data['research_de'] = $data['research_de'] ?? array();
                foreach (($data['research'] ?? array()) as $i => $n) {
                    $n_de = $data['research_de'][$i] ?? '';
                ?>
                    <tr class="research-interest">
                        <td>
                            <input type="text" name="values[research][]" value="<?= $n ?>" list="research-list" required class="form-control">
                        </td>
                        <td>
                            <input type="text" name="values[research_de][]" value="<?= $n_de ?>" list="research-list-de" class="form-control">
                        </td>
                        <td><a class="btn text-danger" onclick="$(this).closest('.research-interest').remove();"><i class="ph ph-trash"></i></a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <button class="btn" type="button" onclick="addResearchInterest(event);">
            <i class="ph ph-plus"></i>
        </button>

        <datalist id="research-list">
            <?php
            foreach ($osiris->persons->distinct('research') as $d) { ?>
                <option><?= $d ?></option>
            <?php } ?>
        </datalist>
        <datalist id="research-list-de">
            <?php
            foreach ($osiris->persons->distinct('research-de') as $d) { ?>
                <option><?= $d ?></option>
            <?php } ?>
        </datalist>

    </section>


    <section id="biography" style="display:none">
        <h2 class="title"><?= lang('Curriculum Vitae') ?></h2>

        <button class="btn" type="button" onclick="addCVrow(event, '#cv-list')"><i class="ph ph-plus text-success"></i> <?= lang('Add entry', 'Eintrag hinzufügen') ?></button>
        <br>
        <small class="text-muted float-right"><?= lang('Sorting will be done automatically', 'Wir sortieren das automatisch für dich') ?></small>
        <br>
        <div id="cv-list">
            <?php
            if (isset($data['cv']) && !empty($data['cv'])) {

                foreach ($data['cv'] as $i => $con) { ?>

                    <div class="alert mb-10">
                        <div class="input-group my-10">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><?= lang('From', 'Von') ?></span>
                            </div>
                            <input type="number" name="values[cv][<?= $i ?>][from][month]" value="<?= $con['from']['month'] ?? '' ?>" class="form-control" placeholder="month *" min="1" max="12" step="1" id="from-month" required>
                            <input type="number" name="values[cv][<?= $i ?>][from][year]" value="<?= $con['from']['year'] ?? '' ?>" class="form-control" placeholder="year *" min="1900" max="<?= CURRENTYEAR ?>" step="1" id="from-year" required>
                            <div class="input-group-prepend">
                                <span class="input-group-text"><?= lang('to', 'bis') ?></span>
                            </div>
                            <input type="number" name="values[cv][<?= $i ?>][to][month]" value="<?= $con['to']['month'] ?? '' ?>" class="form-control" placeholder="month" min="1" max="12" step="1" id="to-month">
                            <input type="number" name="values[cv][<?= $i ?>][to][year]" value="<?= $con['to']['year'] ?? '' ?>" class="form-control" placeholder="year" min="1900" step="1" id="to-year">
                        </div>

                        <div class="form-group mb-10">
                            <input name="values[cv][<?= $i ?>][position]" type="text" class="form-control" value="<?= $con['position'] ?? '' ?>" placeholder="Position *" required>
                        </div>
                        <div class="form-group mb-0">
                            <input name="values[cv][<?= $i ?>][affiliation]" type="text" class="form-control" value="<?= $con['affiliation'] ?? '' ?>" placeholder="Affiliation *" list="affiliation-list" required>
                        </div>

                        <small class="text-muted">* <?= lang('required', 'benötigt') ?></small><br>

                        <!-- checkbox to hide from portfolio -->

                        <?php if ($Settings->featureEnabled('portal')) { ?>
                            <div class="custom-checkbox ml-10">
                                <input type="checkbox" id="hide-<?= $i ?>" <?= ($con['hide'] ?? false) ? 'checked' : '' ?> name="values[cv][<?= $i ?>][hide]">
                                <label for="hide-<?= $i ?>">
                                    <?= lang('Hide in portfolio', 'Im Portfolio verstecken') ?>
                                </label>
                            </div>
                        <?php } ?>


                        <button class="btn danger my-10" type="button" onclick="$(this).closest('.alert').remove()"><i class="ph ph-trash"></i></button>
                    </div>
            <?php }
            } ?>
        </div>

        <script>
            var i = <?= $i ?? 0 ?>

            var CURRENTYEAR = <?= CURRENTYEAR ?>;

            function addCVrow(evt, parent) {
                i++;
                var el = `
            <div class="alert mb-10">
                    <div class="input-group my-10">
                        <div class="input-group-prepend">
                            <span class="input-group-text">${lang('From', 'Von')}</span>
                        </div>
                        <input type="number" name="values[cv][${i}][from][month]" class="form-control" placeholder="month *" min="1" max="12" step="1" id="from-month" required>
                        <input type="number" name="values[cv][${i}][from][year]" class="form-control" placeholder="year *" min="1900" max="${CURRENTYEAR}" step="1" id="from-year" required>
                        <div class="input-group-prepend">
                            <span class="input-group-text">${lang('to', 'bis')}</span>
                        </div>
                        <input type="number" name="values[cv][${i}][to][month]" class="form-control" placeholder="month" min="1" max="12" step="1" id="to-month">
                        <input type="number" name="values[cv][${i}][to][year]" class="form-control" placeholder="year" min="1900" step="1" id="to-year">
                    </div>

                    <div class="form-group mb-10">
                        <input name="values[cv][${i}][position]" type="text" class="form-control" placeholder="Position *" required>
                    </div>

                    <div class="form-group mb-0">
                        <input name="values[cv][${i}][affiliation]" type="text" class="form-control" placeholder="Affiliation *" list="affiliation-list" required>
                    </div>

                    <small class="text-muted">* required</small><br>

                    <button class="btn danger my-10" type="button" onclick="$(this).closest('.alert').remove()"><i class="ph ph-trash"></i></button>
                </div>
                `;
                $(parent).prepend(el);
            }
        </script>


        <datalist id="affiliation-list">
            <?php
            foreach ($osiris->persons->distinct('cv.affiliation') as $d) { ?>
                <option><?= $d ?></option>
            <?php } ?>
        </datalist>

    </section>



    <button type="submit" class="btn secondary">
        Update
    </button>
</form>
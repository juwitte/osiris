<?php
$user = $_SESSION['username'] ?? null;
// say good morning or good afternoon or good evening depending on the time of day
$hour = date('H');
if ($hour < 10) {
    $greeting = lang('Good morning,', 'Guten Morgen,');
    $icon = 'sun-horizon';
} elseif ($hour < 18) {
    $greeting = lang('Good afternoon,', 'Guten Tag,');
    $icon = 'sun';
} else {
    $greeting = lang('Good evening,', 'Guten Abend,');
    $icon = 'moon';
}
// random welcome message
$welcome_messages = [
    lang('It\'s great to have you here.', 'Schön, dass du hier bist.'),
    lang('Hope you have a productive day!', 'Ich hoffe, du hast einen produktiven Tag!'),
    lang('Let\'s make today a great day!', 'Lass uns heute zu einem großartigen Tag machen!'),
    lang('Welcome back! Let\'s get to work!', 'Willkommen zurück! Lass es uns anpacken!'),
    lang('Ready to achieve great things today?', 'Bereit, heute Großartiges zu erreichen?'),
    lang('Let\'s make today amazing!', 'Lass uns heute großartig machen!'),
    lang('Nice to see you again! Let\'s have a productive day!', 'Schön, dich wiederzusehen! Lass uns einen produktiven Tag haben!'),
    lang('Here is what\'s happening in OSIRIS.', 'Schau dir an, was in OSIRIS los ist.'),
];
$welcome = $welcome_messages[array_rand($welcome_messages)];


$Q = CURRENTQUARTER - 1;
$Y = CURRENTYEAR;
if ($Q < 1) {
    $Q = 4;
    $Y -= 1;
}
$lastquarter = $Y . "Q" . $Q;

$currentuser = $user == $_SESSION['username'];
?>
<!-- <script src="<?= ROOTPATH ?>/js/chart.min.js"></script> -->
<!-- <script src="<?= ROOTPATH ?>/js/chartjs-plugin-datalabels.min.js"></script> -->
<script src="<?= ROOTPATH ?>/js/d3.v4.min.js"></script>
<script src="<?= ROOTPATH ?>/js/popover.js"></script>
<!-- <script src="<?= ROOTPATH ?>/js/d3-chords.js?v=<?= OSIRIS_BUILD ?>"></script> -->
<!-- <script src="<?= ROOTPATH ?>/js/d3.layout.cloud.js"></script> -->


<style>
    #home {
        /* --border-width: 0; */
    }

    .home-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.5rem;
        align-items: start;
    }

    .home-widget {
        min-width: 0;
        height: 100%;
    }

    .home-widget.span-2 {
        grid-column: span 2;
    }

    .home-widget.span-3 {
        grid-column: 1 / -1;
    }

    /* box height adjusted */
    .home-widget .box {
        height: 100%;
    }

    .home-widget.stack {
        display: grid;
        grid-auto-rows: minmax(0, auto);
        gap: 1.5rem;
    }

    @media (max-width: 1200px) {
        .home-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .home-widget.span-3 {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 768px) {
        .home-grid {
            grid-template-columns: 1fr;
        }

        .home-widget,
        .home-widget.span-2,
        .home-widget.span-3 {
            grid-column: 1 / -1;
        }
    }

    h1 {
        font-size: 2.4rem;
        margin-bottom: 0;
    }

    h1 i.ph-moon {
        color: #7f8c8d;
    }

    h1 i.ph-sun-horizon {
        color: #f39c12;
    }

    h1 i.ph-sun {
        color: #f1c40f;
    }

    p.welcome-text {
        color: var(--muted-color);
        margin-top: 0;
    }


    .widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        margin-top: 1rem;
    }

    .widget-header h2 {
        font-size: 1.8rem !important;
        margin: 0;
    }

    .colleague {
        display: flex;
        align-items: center;
    }

    .colleague-img {
        /* clip image */
        width: 50px;
        height: 50px;
        /* border-radius: 50%; */
        border-radius: var(--border-radius);
        object-fit: cover;
        margin-right: 1rem;
    }

    #announcement {
        /* display: flex;
        align-items: center;
        justify-content: space-between;*/
        /* // box mb-0 border-primary primary */
        background-image: url('<?= ROOTPATH ?>/img/sophie/sophie-banner-notification.png');
        background-size: contain;
        background-repeat: no-repeat;
        background-position: right;
        position: relative;
        padding: 1rem 20rem 1rem 1.5rem;
        margin-bottom: 0;
        --border-color: #3ca39d;
        --primary-color: #3ca39d;
        --primary-color-dark: #31716b;
        --primary-color-20: rgba(60, 163, 157, 0.2);
        --primary-color-30: rgba(60, 163, 157, 0.3);
    }

    #announcement .dismiss-btn {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        color: var(--primary-color);
    }

    #announcement img#sophie-announcement {
        max-width: 120px;
        margin: -1rem 2rem -1rem 0;
    }

    #announcement h1,
    #announcement h2,
    #announcement h3,
    #announcement h4,
    #announcement h5,
    #announcement h6 {
        color: var(--primary-color);
        margin-top: 0;
    }

    #announcement h1 {
        font-size: 2.2rem;
    }

    #announcement h2 {
        font-size: 1.8rem;
    }

    #announcement h3 {
        font-size: 1.6rem;
    }

    #announcement p {
        background-color: #ffffffbf;
    }

    /* make sure that links in tables do not exceed the table width */
    .table a {
        word-break: break-word;
    }

    #home {
        margin-bottom: 2rem;
    }
</style>



<main id="home">

    <!-- Welcome Section -->
    <div class="welcome">
        <h1>
            <?= $greeting ?> <?= $USER['first'] ?? $USER['displayname'] ?? 'User' ?>!
            <i class="ph-duotone ph-<?= $icon ?>"></i>
        </h1>

        <p class="welcome-text">
            <?= $welcome ?>
        </p>
    </div>
    <!-- End Welcome Section -->


    <div class="home-grid">
        <!-- Announcement Section -->
        <?php
        $announcement_active = $Settings->isAnnouncementActive();
        if ($announcement_active) {
            $announcement = $Settings->get('announcement'); ?>
            <section class="home-widget span-3">
                <div class="box position-relative" id="announcement" data-announcement-version="<?= strtotime($announcement['updated_at'] ?? 'now') ?>">
                    <?php
                    $en = trim($announcement['en'] ?? null);
                    $de = trim($announcement['de'] ?? null);
                    if (!empty(strip_tags($en)) && !empty(strip_tags($de))) {
                        echo  lang($en, $de);
                    } elseif (!empty(strip_tags($en))) {
                        echo $en;
                    } elseif (!empty(strip_tags($de))) {
                        echo $de;
                    }
                    ?>
                    <button class="btn primary small" onclick="dismissAnnouncement()">
                        <i class="ph ph-x-circle"></i>
                        <?= lang('Don’t show again', 'Nicht mehr anzeigen') ?>
                    </button>
                </div>
                <script>
                    function dismissAnnouncement() {
                        fetch('<?= ROOTPATH ?>/crud/users/dismiss-announcement', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            }
                        }).then(() => {
                            document.getElementById('announcement').remove();
                        });
                    }

                    function dismissAnnouncementSession() {
                        const announcementVersion = $('#announcement').data('announcement-version');
                        localStorage.setItem('osiris_hide_announcement_hidden_' + announcementVersion, Date.now() + 24 * 60 * 60 * 1000);
                        document.getElementById('announcement').remove();
                    }
                    $(document).ready(function() {
                        const announcementVersion = $('#announcement').data('announcement-version');
                        const hideUntil = localStorage.getItem('osiris_hide_announcement_hidden_' + announcementVersion);
                        if (hideUntil && Date.now() < hideUntil) {
                            $('#announcement').remove();
                        }
                    });
                </script>
            </section>
        <?php

        }

        ?>

        <?php if ($Settings->featureEnabled('quarterly-reporting', true) && isset($notifications['approval'])) {
        ?>
            <section class="home-widget">
                <div class="box padded">
                    <div class="d-flex align-items-center">
                        <div>
                            <b>
                                <?= lang("You can now approve the past quarter", "Du kannst jetzt das vergangene Quartal freigeben") ?>
                            </b>
                            <p class="text-muted my-5 font-size-12">
                                <?= lang(
                                    'To complete the quarterly review, please confirm that all activities from the previous quarter have been entered and are up to date.',
                                    'Für den Quartalsabschluss brauchen wir seine Bestätigung, dass alle Aktivitäten aus dem vergangenen Quartal erfasst und aktuell sind.'
                                ) ?>
                            </p>
                            <a class="btn success filled" href="<?= ROOTPATH ?>/my-year/<?= $_SESSION['username'] ?>?quarter=<?= $quarter ?>">
                                <?= lang('Review & Approve', 'Überprüfen & Freigeben') ?>
                            </a>
                        </div>

                        <img src="<?= ROOTPATH ?>/img/sophie/sophie-checklist.png" class="w-100">
                    </div>
                </div>
            </section>
        <?php } ?>


        <?php if ($Settings->featureEnabled('new-publications', true)) { ?>
            <section class="home-widget">
                <div class="box padded">
                    <div class="widget-header">
                        <h2>
                            <i class="ph-duotone ph-newspaper"></i>
                            <?= lang('Newest publications', 'Neueste Veröffentlichungen') ?>
                        </h2>
                        <a href="<?= ROOTPATH ?>/activities#type=publication" class="btn small">
                            <?= lang('Show all', 'Zeige alle') ?>
                        </a>
                    </div>
                    <?php
                    $pubs = $osiris->activities->find(
                        ['authors.aoi' => true, 'type' => 'publication'],
                        [
                            'sort' => ['start_date' => -1],
                            'limit' => 5,
                            'projection' => ['html' => '$rendered.title', 'date' => '$start_date']
                        ]
                    )->toArray();
                    ?>
                    <table class="font-size-12">
                        <?php foreach ($pubs as $doc) { ?>
                            <tr>
                                <td class="py-5">
                                    <small class="badge primary font-weight-bold"><?= format_date($doc['date']) ?></small><br>
                                    <a href="<?= ROOTPATH ?>/activities/view/<?= $doc['_id'] ?>" class="colorless link"><?= $doc['html'] ?></a>
                                </td>
                            </tr>
                        <?php } ?>

                    </table>
                </div>
            </section>
        <?php } ?>


        <?php if ($Settings->featureEnabled('events', true)) { ?>

            <section class="home-widget stack">
                <?php if ($Settings->featureEnabled('deadlines', false)) {
                    include_once BASEPATH . '/php/Vocabulary.php';
                    $Vocabulary = new Vocabulary();
                ?>

                    <div class="box padded">

                        <div class="widget-header">
                            <h2>
                                <i class="ph-duotone ph-flag"></i>
                                <?= lang('Deadlines') ?>
                            </h2>
                            <a href="<?= ROOTPATH ?>/deadlines" class="btn small">
                                <?= lang('Show all', 'Zeige alle') ?>
                            </a>
                        </div>
                        <?php
                        $deadlines = $osiris->deadlines->find(
                            [
                                '$and' => [
                                    ['date' => ['$gte' => date('Y-m-d', strtotime('-3 days'))]],
                                    ['date' => ['$lte' => date('Y-m-d', strtotime('+12 month'))]]
                                ],
                                // 'dismissed' => ['$ne' => $user],
                                'roles' => ['$in' => $Settings->roles]
                            ],
                            ['sort' => ['date' => 1], 'projection' => [
                                '_id' => 0,
                                'id' => ['$toString' => '$_id'],
                                'date' => 1,
                                'type' => 1,
                                'title' => 1
                            ]]
                        )->toArray();
                        $deadlineTypes = $Vocabulary->getValues('deadline-type');
                        $deadlineTypes = array_column($deadlineTypes, null, 'id');
                        ?>
                        <div id="deadline-timeline" class="mb-10"></div>
                        <style>
                            #deadline-timeline {
                                position: relative;
                            }

                            #deadline-timeline svg {
                                width: 100%;
                                height: auto;
                                display: block;
                            }

                            #deadline-timeline .deadline-dot {
                                transition: transform .15s ease;
                            }
                        </style>
                        <script>
                            $(document).ready(function() {

                                deadlineTimeline({
                                    divSelector: '#deadline-timeline',
                                    monthsAhead: 12,
                                    startLabel: lang('Today', 'Heute'),
                                    endLabel: lang('+12 months', '+12 Monate'),
                                    typeInfo: <?= json_encode($deadlineTypes) ?>,
                                    deadlines: <?= json_encode($deadlines) ?>
                                });
                                // deadlineTimeline(options);
                            });
                        </script>
                    </div>
                <?php } ?>

                <div class="box padded">
                    <div class="widget-header">
                        <h2>
                            <i class="ph-duotone ph-calendar-dots"></i>
                            <?= lang('Events') ?>
                        </h2>
                        <a href="<?= ROOTPATH ?>/conferences" class="btn small">
                            <?= lang('Show all', 'Zeige alle') ?>
                        </a>
                    </div>

                    <?php
                    // conferences max past 3 month
                    $conferences = $osiris->conferences->find(
                        [
                            '$or' => [
                                ['end' => ['$gte' => date('Y-m-d', strtotime('-3 days'))], 'start' => ['$lte' => date('Y-m-d', strtotime('+6 month'))]],
                                [
                                    'start' => ['$gte' => date('Y-m-d', strtotime('-6 month'))],
                                    '$or' => [
                                        ['participants' => $user],
                                        ['interests' => $user]
                                    ]
                                ]
                            ],
                            'dismissed' => ['$ne' => $user]
                        ],
                        ['sort' => ['start' => 1]]
                    )->toArray();
                    ?>
                    <table class="table simple font-size-12">
                        <?php foreach ($conferences as $n => $c) {
                            $past = strtotime($c['end']) > time();
                            if ($past) {
                                $days = ceil((strtotime($c['start']) - time()) / 86400);
                                $days = $days > 0 ? $days : 0;
                                $days = $days == 0 ? lang('today', 'heute') : 'in ' . $days . ' ' . lang('days', 'Tagen');
                            }
                            // user is interested in conference
                            $interest = in_array($user, DB::doc2Arr($c['interests'] ?? []));
                            $participate = in_array($user, DB::doc2Arr($c['participants'] ?? []));
                            $interestTooltip = $interest ? lang('Click to remove interest', 'Klicken um Interesse zu entfernen') : lang('Click to show interest', 'Klicken um Interesse zu zeigen');
                            $participateTooltip = $participate ? lang('Click to remove participation', 'Klicken um Teilnahme zu entfernen') : lang('Click to show participation', 'Klicken um Teilnahme zu zeigen');

                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex justify-content-between">
                                        <b class="m-0 font-size-14">
                                            <a href="<?= ROOTPATH ?>/conferences/view/<?= $c['_id'] ?>">
                                                <?= $c['title'] ?>
                                            </a>
                                            <?php if (!empty($c['url'] ?? null)) { ?>
                                                <a href="<?= $c['url'] ?>" target="_blank" rel="noopener noreferrer">
                                                    <i class="ph ph-link"></i>
                                                </a>
                                            <?php } ?>
                                        </b>
                                        <!-- dismiss btn -->
                                        <a class="text-danger" onclick="conferenceToggle(this, '<?= $c['_id'] ?>', 'dismissed')" data-toggle="tooltip" data-title="<?= lang('Dismiss', 'Verwerfen') ?>">
                                            <i class="ph ph-x"></i>
                                        </a>
                                    </div>
                                    <p class="my-5 text-muted">
                                        <?= $c['title_full'] ?? '' ?>
                                    </p>
                                    <p class="my-5 text-muted">
                                        <small class="text- mr-10">
                                            <?= fromToDate($c['start'], $c['end']) ?>
                                        </small>
                                        <small>
                                            <?= $c['location'] ?>
                                        </small>
                                    </p>

                                    <div class="btn-toolbar font-size-12">
                                        <?php
                                        // check if conference is in the future
                                        if ($past) {
                                        ?>
                                            <div class="btn-group">
                                                <small class="btn small cursor-default">
                                                    <?= $days ?>
                                                </small>
                                                <a class="btn small" href="<?= ROOTPATH ?>/conference/ics/<?= $c['_id'] ?>" data-toggle="tooltip" data-title="<?= lang('Add to calendar', 'Zum Kalender hinzufügen') ?>">
                                                    <i class="ph ph-calendar-plus"></i>
                                                </a>
                                            </div>
                                            <div class="btn-group">
                                                <a class="btn small <?= $interest ? 'active primary' : '' ?>" onclick="conferenceToggle(this, '<?= $c['_id'] ?>', 'interests')" data-toggle="tooltip" data-title="<?= $interestTooltip ?>">
                                                    <b><?= count($c['interests'] ?? []) ?></b>
                                                    <?= lang('Interested', 'Interessiert') ?>
                                                </a>
                                                <a class="btn small <?= $participate ? 'active primary' : '' ?>" onclick="conferenceToggle(this, '<?= $c['_id'] ?>', 'participants')" data-toggle="tooltip" data-title="<?= $participateTooltip ?>">
                                                    <b><?= count($c['participants'] ?? []) ?></b>
                                                    <?= lang('Participants', 'Teilnehmer') ?>
                                                </a>
                                            </div>
                                        <?php } else { ?>
                                            <a class="btn small primary" href="<?= ROOTPATH ?>/add-activity?type=poster&conference=<?= $c['_id'] ?>">
                                                <i class="ph ph-plus-circle"></i>
                                                <?= lang('Add contribution', 'Beitrag hinzufügen') ?>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>

                    </table>

                </div>
            </section>
        <?php } ?>


        <?php if ($Settings->featureEnabled('new-colleagues') || ($Settings->featureEnabled('quarterly-reporting', true) && $Settings->hasPermission('report.dashboard'))) { ?>

            <section class="home-widget stack">
                <?php if ($Settings->featureEnabled('new-colleagues')) { ?>
                    <!-- Show new users -->
                    <div class="box padded">
                        <div class="widget-header">
                            <h2>
                                <i class="ph-duotone ph-user"></i>
                                <?= lang('New Colleagues', 'Neue Kolleg:innen') ?>
                            </h2>
                            <a href="<?= ROOTPATH ?>/user/browse" class="btn small">
                                <?= lang('Show all', 'Zeige alle') ?>
                            </a>
                        </div>
                        <?php
                        $new_colleagues = $osiris->persons->find(
                            ['created' => ['$exists' => true], 'is_active' => ['$ne' => false]],
                            [
                                'sort' => ['created' => -1],
                                'limit' => 3,
                            ]
                        )->toArray();
                        ?>
                        <table class="table simple">
                            <?php foreach ($new_colleagues as $colleague) { ?>
                                <tr>
                                    <td>
                                        <div class="colleague">
                                            <?= $Settings->printProfilePicture($colleague['username'], 'colleague-img') ?>
                                            <div>
                                                <h5 class="my-0">
                                                    <a href="<?= ROOTPATH ?>/profile/<?= $colleague['username'] ?>" class="">
                                                        <?= $colleague['displayname'] ?? $colleague['username'] ?>
                                                    </a>
                                                </h5>
                                                <small class="text-muted">
                                                    <?= lang($colleague['position'] ?? '', $colleague['position_de'] ?? null) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                <?php } ?>
                <?php if (($Settings->featureEnabled('quarterly-reporting', true) && $Settings->hasPermission('report.dashboard'))) {
                    $n_scientists = $osiris->persons->count(["roles" => 'scientist', "is_active" => true]);
                    $n_approved = $osiris->persons->count(["roles" => 'scientist', "is_active" => true, "approved" => $lastquarter]);
                    $progress = ($n_scientists > 0) ? ($n_approved / $n_scientists) * 100 : 0;
                ?>
                    <div class="box padded">
                        <div class="widget-header">
                            <h2>
                                <i class="ph-duotone ph-chart-pie"></i>
                                <?= lang('Last quarter', 'Vergangenes Quartal') ?>
                            </h2>
                            <button class="btn small" onclick="loadModal('components/controlling-approved', {q: '<?= $Q ?>', y: '<?= $Y ?>'})">
                                <i class="ph ph-magnifying-glass-plus"></i> <?= lang('Details') ?>
                            </button>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-10 font-size-12">
                            <span class="text-muted">
                                <?= lang(
                                    "$n_approved of $n_scientists scientists have approved their activities.",
                                    "$n_approved von $n_scientists Wissenschaftler:innen haben ihre Aktivitäten freigegeben."
                                ) ?>
                            </span>
                            <b class="badge success"><?= $lastquarter ?></b>
                        </div>
                    </div>
                <?php } ?>
            </section>
        <?php } ?>

    </div>
</main>
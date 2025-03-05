<?php

/**
 * The statistics of all infrastructures
 * Created in cooperation with DSMZ
 * 
 * This file is part of the OSIRIS package.
 * Copyright (c) 2024 Julia Koblitz, OSIRIS Solutions GmbH
 *
 * @package     OSIRIS
 * @since       1.4.1
 * 
 * @copyright	Copyright (c) 2024 Julia Koblitz, OSIRIS Solutions GmbH
 * @author		Julia Koblitz <julia.koblitz@osiris-solutions.de>
 * @license     MIT
 */

// today is the default reportdate
if (!isset($_GET['reportdate']) || empty($_GET['reportdate'])) {
    $reportdate = date('Y-m-d');
} else {
    $reportdate = $_GET['reportdate'];
}

$filter = [
    'start_date' => ['$lte' => $reportdate],
    '$or' => [
        ['end_date' => ['$gte' => $reportdate]],
        ['end_date' => null]
    ]
];

$infrastructures  = $osiris->infrastructures->find($filter)->toArray();

$all = $osiris->infrastructures->count();
?>

<h1>
    <i class="ph ph-chart-bar" aria-hidden="true"></i>
    <?= lang('Statistics', 'Statistiken') ?>
</h1>

<div class="btn-toolbar">
    <a href="<?= ROOTPATH ?>/infrastructures">
        <i class="ph ph-arrow-left"></i>
        <?= lang('Back to Infrastructures', 'Zurück zu Infrastrukturen') ?>
    </a>
</div>

<h6><?= lang('Change Reporting Date', 'Stichtag ändern') ?>:</h6>
<form action="<?= ROOTPATH ?>/infrastructures/statistics" method="get">
    <input type="date" name="reportdate" value="<?= $reportdate ?>" class="form-control w-auto d-inline-block" />
    <button class="btn primary" type="submit">Ändern</button>
</form>


<div id="statistics">
    <p class="lead">
        <?= lang('Number of infrastructures on the reporting date', 'Anzahl der Infrastrukturen zum Stichtag') ?>:
        <b class="badge primary"><?= count($infrastructures) ?></b>
        <span class="text-muted">(<?= $all ?> <?= lang('total', 'gesamt') ?>)</span>
    </p>

    <h3>
        <?= lang('List of research infrastructures existing on the reporting date', 'Liste zum Stichtag bestehender Forschungsinfrastrukturen') ?>
    </h3>
    <table class="table" id="infrastructures">
        <thead>
            <tr>
                <th><?= lang('Name', 'Name') ?></th>
                <th><?= lang('Type', 'Typ') ?></th>
                <th><?= lang('Access Type', 'Art des Zugangs') ?></th>
                <th><?= lang('Infrastructure Type', 'Art der Infrastruktur') ?></th>
                <th><?= lang('Description', 'Beschreibung') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($infrastructures as $infrastructure) { ?>
                <tr>
                    <td>
                        <a href="<?= ROOTPATH ?>/infrastructures/view/<?= $infrastructure['_id'] ?>">
                            <?= lang($infrastructure['name'], $infrastructure['name_de'] ?? null) ?>
                        </a>
                    </td>
                    <td>
                        <?= $infrastructure['type'] ?? '-' ?>
                    </td>
                    <td>
                        <?= $infrastructure['access'] ?? '-' ?>
                    </td>
                    <td>
                        <?= $infrastructure['infrastructure_type'] ?? '-' ?>
                    </td>
                    <td>
                        <?= $infrastructure['description'] ?? '-' ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <h3>
        <?= lang('Research infrastructures with name, type and number of users by year', 'Forschungsinfrastrukturen mit Bezeichnung, Typ und Anzahl der Nutzer/-innen nach Jahr') ?>
    </h3>


    <!-- Filter by Year -->
    <div class="d-flex align-items-center mb-5">
        <i class="ph ph-funnel text-primary"></i>
        <span class="px-5"><?= lang('Year', 'Jahr') ?>:</span>
        <input type="number" class="form-control w-100" placeholder="2021" onchange="filterByYear(this.value, '#user-stats')" />
    </div>

    <table class="table" id="user-stats">
        <thead>
            <tr>
                <th><?= lang('Name', 'Name') ?></th>
                <th><?= lang('Type', 'Typ') ?></th>
                <th><?= lang('Year', 'Jahr') ?></th>
                <th class="text-right"><?= lang('Internal', 'Intern') ?></th>
                <th class="text-right"><?= lang('National', 'National') ?></th>
                <th class="text-right"><?= lang('International', 'International') ?></th>
                <th class="text-right"><?= lang('Total', 'Gesamt') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($infrastructures as $infrastructure) {
                $statistics = DB::doc2Arr($infrastructure['statistics'] ?? []);
                if (!empty($statistics)) {
                    usort($statistics, function ($a, $b) {
                        return $a['year'] <=> $b['year'];
                    });
                }
                foreach ($statistics as $yearstats) {
            ?>
                    <tr>
                        <td>
                            <a href="<?= ROOTPATH ?>/infrastructures/view/<?= $infrastructure['_id'] ?>">
                                <?= lang($infrastructure['name'], $infrastructure['name_de'] ?? null) ?>
                            </a>
                        </td>
                        <td>
                            <?= $infrastructure['type'] ?? '-' ?>
                        </td>
                        <td>
                            <?= $yearstats['year'] ?? $reportdate ?>
                        </td>
                        <td class="text-right">
                            <?= $yearstats['internal'] ?? 0 ?>
                        </td>
                        <td class="text-right">
                            <?= $yearstats['national'] ?? 0 ?>
                        </td>
                        <td class="text-right">
                            <?= $yearstats['international'] ?? 0 ?>
                        </td>
                        <td class="text-right">
                            <?= ($yearstats['internal'] ?? 0) + ($yearstats['national'] ?? 0) + ($yearstats['international'] ?? 0) ?>
                        </td>
                    </tr>
            <?php
                }
            } ?>
        </tbody>
    </table>


    <h3>
        Forschungsinfrastrukturen mit Bezeichnung, Typ und genutzten Stunden nach Jahr
    </h3>

    <!-- Filter by Year -->
    <div class="d-flex align-items-center mb-5">
        <i class="ph ph-funnel text-primary"></i>
        <span class="px-5"><?= lang('Year', 'Jahr') ?>:</span>
        <input type="number" class="form-control w-100" placeholder="2021" onchange="filterByYear(this.value, '#action-stats')" />
    </div>

    <table class="table" id="action-stats">
        <thead>
            <tr>
                <th>Name</th>
                <th>Typ</th>
                <th>Jahr</th>
                <th class="text-right">Genutzte Stunden</th>
                <th class="text-right">Zugriffe</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($infrastructures as $infrastructure) {
                $statistics = DB::doc2Arr($infrastructure['statistics'] ?? []);
                if (!empty($statistics)) {
                    usort($statistics, function ($a, $b) {
                        return $a['year'] <=> $b['year'];
                    });
                }
                foreach ($statistics as $yearstats) {
            ?>
                    <tr>
                        <td>
                            <a href="<?= ROOTPATH ?>/infrastructures/view/<?= $infrastructure['_id'] ?>">
                                <?= lang($infrastructure['name'], $infrastructure['name_de'] ?? null) ?>
                            </a>
                        </td>
                        <td>
                            <?= $infrastructure['type'] ?? '-' ?>
                        </td>
                        <td>
                            <?= $yearstats['year'] ?? $reportdate ?>
                        </td>
                        <td class="text-right">
                            <?= $yearstats['hours'] ?? 0 ?>
                        </td>
                        <td class="text-right">
                            <?= $yearstats['accesses'] ?? 0 ?>
                        </td>
                    </tr>
            <?php
                }
            } ?>
        </tbody>
    </table>
</div>





<script>
    function filterByYear(year, table) {
        var rows = document.querySelectorAll(table + ' tbody tr');
        if (year == '') {
            rows.forEach(function(row) {
                row.style.display = 'table-row';
            });
            return;
        }
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            if (cells[2].innerText == year) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
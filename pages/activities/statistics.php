<?php

/**
 * The statistics of all activities
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

// today is the default reportyear
if (!isset($_GET['reportyear']) || empty($_GET['reportyear'])) {
    $reportyear = CURRENTYEAR;
} else {
    $reportyear = intval($_GET['reportyear']);
}

$reportstart = $reportyear . '-01-01';
$reportend = $reportyear . '-12-31';

$filter = [
    'affiliated' => true,
    'start_date' => ['$gte' => $reportstart],
    '$or' => [
        ['end_date' => ['$lte' => $reportend]],
        ['end_date' => null]
    ]
];

$activities  = $osiris->activities->find($filter)->toArray();

$all = $osiris->activities->count(['affiliated' => true]);
?>

<style>
    tfoot th {
        font-weight: 400 !important;
        border-top: 1px solid var(--border-color);
        color: var(--muted-color);
        background-color: var(--gray-color-very-light);
    }

    tfoot th:first-child {
        border-bottom-left-radius: var(--border-radius);
    }

    tfoot th:last-child {
        border-bottom-right-radius: var(--border-radius);
    }
</style>

<h1>
    <i class="ph ph-chart-line-up" aria-hidden="true"></i>
    <?= lang('Statistics', 'Statistiken') ?>
</h1>

<div class="btn-toolbar">
    <a href="<?= ROOTPATH ?>/all-activities">
        <i class="ph ph-arrow-left"></i>
        <?= lang('Back to Activities', 'Zurück zu Aktivitäten') ?>
    </a>
</div>


<div class="alert signal">
    <i class="ph ph-warning text-signal"></i>
    <?= lang('All of the following statistics are based on the reporting year.', 'Alle unten aufgeführten Statistiken basieren auf dem angegebenen Reportjahr.') ?>

    <form action="<?= ROOTPATH ?>/activities/statistics" method="get" class="d-flex align-items-baseline mt-10" style="grid-gap: 1rem;">
        <h6 class="mb-0 mt-5"><?= lang('Change Reporting Year', 'Reportjahr ändern') ?>:</h6>
        <input type="number" name="reportyear" value="<?= $reportyear ?>" class="form-control w-auto d-inline-block" step="1" min="1900" max="<?= CURRENTYEAR + 2 ?>" />
        <button class="btn signal filled" type="submit"><?= lang('Update', 'Ändern') ?></button>
    </form>
</div>

<p class="text-muted">
    <?= lang('Only affiliated activities are counted (at least one author is affiliated with the institute).', 'Es werden nur affilierte Aktivitäten gezählt (mind. ein:e Autor:in ist mit dem Institut affiliert).') ?>
</p>

<br>
<div id="statistics">
    <p class="lead">
        <?= lang('Number of activities on the reporting date', 'Anzahl der Aktivitäten im Reportjahr') ?>:
        <b class="badge signal"><?= count($activities) ?></b>
        <span class="text-muted">(<?= $all ?> <?= lang('total', 'gesamt') ?>)</span>
    </p>


    <h2>
        <?= lang('Number of activities by category and type', 'Anzahl der Aktivitäten pro Typ') ?>
    </h2>

    <?php
    $activities_by_type = $osiris->activities->aggregate([
        [
            '$match' => $filter
        ],
        [
            '$group' => [
                '_id' => '$subtype',
                'type' => ['$first' => '$type'],
                'count' => ['$sum' => 1]
            ]
        ],
        [
            '$sort' => [
                'type' => 1
            ]
        ]
    ])->toArray();
    ?>

    <table class="table w-auto">
        <thead>
            <tr>
                <th><?= lang('Type', 'Typ') ?></th>
                <th><?= lang('Subtype', 'Untertyp') ?></th>
                <th><?= lang('Count', 'Anzahl') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activities_by_type as $activity): ?>
                <tr class="text-<?= $activity['type'] ?>">
                    <td><?= $Settings->title($activity['type']); ?></td>
                    <td><?= $Settings->title($activity['type'], $activity['_id']) ?></td>
                    <th><?= $activity['count'] ?></th>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2"><?= lang('Total', 'Gesamt') ?></th>
                <th><?= count($activities) ?></th>
            </tr>
        </tfoot>
    </table>

    <br>
    <hr>

    <h2>
        <?= lang('Statistics on publications', 'Statistiken zu Publikationen') ?>
    </h2>

    <p class="text-muted">
        <?= lang('Only publications with a publication date in the reporting year and at least one affiliated author are counted.', 'Es werden nur Publikationen mit einem Veröffentlichungsdatum im Reportjahr und mindestens einer/einem affilierten Autor/Autorin gezählt.') ?>
    </p>

    <?php
    $filter = [
        'type' => 'publication',
        'year' => $reportyear,
        'affiliated' => true
    ];
    $publications = $osiris->activities->aggregate([
        [
            '$match' => $filter
        ],
        [
            '$group' => [
                '_id' => '$subtype',
                'count' => ['$sum' => 1],
                // count epub = true
                'epub' => ['$sum' => ['$cond' => [['$eq' => ['$epub', true]], 1, 0]]]
            ]
        ],
        [
            '$sort' => [
                'count' => -1
            ]
        ]
    ])->toArray();
    $count_all = $osiris->activities->count($filter);
    $count_all_epub = $osiris->activities->count(array_merge($filter, ['epub' => true]));
    ?>

    <table class="table w-auto">
        <thead>
            <tr>
                <th><?= lang('Type of publication', 'Art der Publikation') ?></th>
                <th><?= lang('Count', 'Anzahl') ?></th>
                <th><?= lang('Count of Online*', 'davon Online*') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($publications as $publication): ?>
                <tr class="text-<?= $publication['_id'] ?>">
                    <td><?= $Settings->title(null, $publication['_id']) ?></td>
                    <th><?= $publication['count'] ?></th>
                    <td>
                        <?= $publication['epub'] ?? 0 ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="1"><?= lang('Total', 'Gesamt') ?></th>
                <th><?= $count_all ?></th>
                <th><?= $count_all_epub ?></th>
            </tr>
        </tfoot>
    </table>
    <p class="text-muted mt-0">
        *Online = Online ahead of print
    </p>


    <h3>
        <?= lang('Number of Open Access publications', 'Anzahl der Open Access-Pubikationen') ?>
    </h3>

    <?php
    $filter = ['oa_status' => ['$ne' => null], 'year' => $reportyear, 'affiliated' => true];

    $oa_publications = $osiris->activities->aggregate([
        ['$match' => $filter],
        [
            '$group' => [
                '_id' => '$oa_status',
                'count' => ['$sum' => 1],
            ]
        ],
        ['$project' => ['_id' => 0, 'status' => '$_id', 'count' => 1]],
        ['$sort' => ['count' => -1]],
    ])->toArray();

    $count_all = $osiris->activities->count($filter);
    ?>

    <table class="table w-auto">
        <thead>
            <tr>
                <th><?= lang('Status', 'Status') ?></th>
                <th><?= lang('Count', 'Anzahl') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($oa_publications as $oa): ?>
                <tr class="text-<?= $oa['status'] ?>">
                    <td>
                        <?php
                        switch ($oa['status']) {
                            case 'closed':
                                echo '<i class="icon-closed-access text-danger"></i> <span class="badge danger"> Closed Access</span>';
                                break;
                            case 'green':
                                echo '<i class="icon-open-access text-success"></i> 
                                        <span class="badge success">Green Open Access</span>';
                                break;
                            case 'gold':
                                echo '<i class="icon-open-access text-success"></i> 
                                        <span class="badge signal">Gold Open Access</span>';
                                break;
                            case 'hybrid':
                                echo '<i class="icon-open-access text-success"></i> 
                                        <span class="badge">Hybrid Open Access</span>';
                                break;
                            case 'bronze':
                                echo '<i class="icon-open-access text-success"></i> 
                                        <span class="badge secondary">Bronze Open Access</span>';
                                break;
                            case 'diamond':
                                echo '<i class="icon-open-access text-success"></i> 
                                        <span class="badge primary">Diamond Open Access</span>';
                                break;
                            default:
                                echo '<i class="icon-open-access text-success"></i> 
                                        <span class="badge muted">Open Access (Unknown Status)</span>';
                                break;
                        }
                        ?>

                    </td>
                    <th><?= $oa['count'] ?></th>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th><?= lang('Total', 'Gesamt') ?></th>
                <th><?= $count_all ?></th>
            </tr>
        </tfoot>
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
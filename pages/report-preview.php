<?php

/**
 * This is the preview for the report builder
 */
// markdown support
require_once BASEPATH . "/php/Report.php";

$Report = new Report($report);

$year = $_GET['year'] ?? CURRENTYEAR - 1;

?>

<div class="container w-800 mw-full">


    <h1>
        <?= lang('Report Preview', 'Berichtsvorschau') ?>
    </h1>

    <table class="table">
        <tbody>
            <tr>
                <td>
                    <span class="key"><?= lang('Title', 'Titel') ?></span>
                    <b><?= $report['title'] ?></b>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="key"><?= lang('Description', 'Beschreibung') ?></span>
                    <?= $report['description'] ?? '-' ?>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="key"><?= lang('Start year', 'Start-Jahr') ?></span>

                    <form action="" method="get">
                        <input type="number" class="form-control" name="year" id="year" value="<?= $year ?>" required>
                        <small class="text-muted">
                            <?= lang('Press enter to set a new start year', 'Drücke Enter, um ein anderes Startjahr zu wählen') ?>
                        </small>
                    </form>
                </td>
            </tr>
        </tbody>
    </table>


    <div class="box">
        <div class="content">
            <?php
            $Report->setYear($year);
            echo $Report->getReport();
            ?>
        </div>
    </div>

</div>
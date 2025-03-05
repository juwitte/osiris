<?php

/**
 * The overview of all infrastructures
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

$infrastructures  = $osiris->infrastructures->find();
?>


<h1>
    <i class="ph ph-shipping-container" aria-hidden="true"></i>
    <?= lang('Infrastructures', 'Infrastrukturen') ?>
</h1>

<?php if ($Settings->hasPermission('infrastructures.edit')) { ?>
    <a href="<?= ROOTPATH ?>/infrastructures/new">
        <i class="ph ph-plus"></i>
        <?= lang('Add new infrastructure', 'Neuen Bereich hinzufügen') ?>
    </a>
<?php } ?>

<div id="infrastructures">
    <?php foreach ($infrastructures as $infrastructure) { ?>
        <div class="box padded infrastructure">
            <h4 class="title">
                <a href="<?= ROOTPATH ?>/infrastructures/view/<?= $infrastructure['_id'] ?>" class="link">
                    <?= lang($infrastructure['name'], $infrastructure['name_de'] ?? null) ?>
                </a>
            </h4>
            <p class="text-muted">
                <?php if (!empty($infrastructure['subtitle'])) { ?>
                    <?= lang($infrastructure['subtitle'], $infrastructure['subtitle_de'] ?? null) ?>
                <?php } else { ?>
                    <?= get_preview(lang($infrastructure['description'], $infrastructure['description_de'] ?? null), 300) ?>
                <?php } ?>
            </p>
            <?php if ($Settings->hasPermission('infrastructures.edit')) { ?>
                <a class="btn" href="<?= ROOTPATH ?>/infrastructures/edit/<?= $infrastructure['_id'] ?>">
                    <i class="ph ph-edit"></i>
                    <?= lang('Edit', 'Bearbeiten') ?>
                </a>
            <?php } ?>
        </div>
    <?php } ?>
</div>
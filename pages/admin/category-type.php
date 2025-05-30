<?php

$color = $color ?? '#000000';
$formaction = ROOTPATH;
if (!empty($form) && isset($form['_id'])) {
    $id = $form['id'];
    $formaction .= "/crud/types/update/" . $form['_id'];
    $btntext = '<i class="ph ph-check"></i> ' . lang("Update", "Aktualisieren");
    $url = ROOTPATH . "/admin/types/" . $form['id'];
    $title = $name;
    $new = false;

    // render example
    include_once BASEPATH . "/php/Modules.php";
    $Modules = new Modules();
    $EXAMPLE = ['_id' => 1, 'type' => $form['parent'], 'subtype' => $form['id']];
    foreach ($form['modules'] ?? array() as $module) {
        $name = trim($module);
        if (str_ends_with($name, '*') || in_array($name, ['title', 'authors', 'date', 'date-range'])) {
            $name = str_replace('*', '', $name);
        }
        $f = $Modules->all_modules[$name] ?? array();
        $EXAMPLE = array_merge($f['fields'] ?? [], $EXAMPLE);
    }
    include_once BASEPATH . "/php/Document.php";
    $Document = new Document(false, 'print');
    $Document->setDocument($EXAMPLE);
    $type['example'] = $Document->format();
    $type['example_web'] = $Document->formatShort(false);

    $osiris->adminTypes->updateOne(
        ['_id' => $form['_id']],
        ['$set' => [
            'example' => $type['example'],
            'example_web' => $type['example_web'],
        ]]
    );
    $member = $osiris->activities->count(['subtype' => $id]);
} else {
    $new = true;
    $formaction .= "/crud/types/create";
    $btntext = '<i class="ph ph-check"></i> ' . lang("Save", "Speichern");
    $url = ROOTPATH . "/admin/types/*";
    $title = lang('New category', 'Neue Kategorie');
    $member = 0;

    // check if type is the first in the category
    if (isset($_GET['parent'])) {
        $p = $type['parent'];
        $first = $osiris->adminTypes->count(['parent' => $p]) == 0;

        if ($first) {
            $parent = $osiris->adminCategories->findOne(['id' => $p]);
            $type['icon'] = $parent['icon'];
            $type['name'] = $parent['name'];
            $type['name_de'] = $parent['name_de'];
            $type['id'] = $parent['id'];
        }
    }
    
}

?>

<div class="modal" id="unique" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <a href="#/" class="close" role="button" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </a>
            <h5 class="title"><?= lang('ID must be unique', 'Die ID muss einzigartig sein.') ?></h5>
            <p>
                <?= lang('Each category and each activity type must have a unique ID with which it is linked to an activity.', 'Jede Kategorie und jeder Aktivitätstyp muss eine einzigartige ID haben, mit der er zu einer Aktivität verknüpft wird.') ?>
            </p>
            <p>
                <?= lang('As the ID must be unique, the following previously used IDs and keywords (new) cannot be used as IDs:', 'Da die ID einzigartig sein muss, können folgende bereits verwendete IDs und Schlüsselwörter (new) nicht als ID verwendet werden:') ?>
            </p>
            <ul class="list" id="IDLIST">
                <?php foreach ($osiris->adminTypes->distinct('id') as $k) { ?>
                    <li><?= $k ?></li>
                <?php } ?>
                <li>new</li>
            </ul>
            <div class="text-right mt-20">
                <a href="#/" class="btn secondary" role="button"><?= lang('I understand', 'Ich verstehe') ?></a>
            </div>
        </div>
    </div>
</div>



<form action="<?= $formaction ?>" method="post" id="group-form">
    <input type="hidden" class="hidden" name="redirect" value="<?= $url ?>">

    <div class="box subtype" style="border-color:<?= $color ?>;">
        <h4 class="header" style="background-color:<?= $color ?>20; color:<?= $color ?>">
            <?php if (!isset($type['new'])) { ?>
                <i class="ph ph-<?= $type['icon'] ?? 'placeholder' ?> mr-10"></i>
                <?= lang($type['name'], $type['name_de'] ?? $type['name']) ?>
                <?php if ($type['disabled'] ?? false) { ?>
                    <span class="badge danger ml-20">DISABLED</span>
                <?php } ?>

            <?php } else { ?>
                <?= lang('New type of activity', 'Neuer Typ von Aktivität') ?>
            <?php } ?>
        </h4>

        <?php // if (isset($type['new'])) { 
        ?>

        <div class="content">

            <?php if (isset($type['parent'])) { ?>
                <input type="hidden" name="original_parent" value="<?= $type['parent'] ?>">
            <?php } ?>

            <label for="parent" class="required"><?= lang('Category', 'Übergeordnete Kategorie') ?></label>
            <select name="values[parent]" id="parent" class="form-control" required>
                <?php foreach ($osiris->adminCategories->find() as $cat) { ?>
                    <option value="<?= $cat['id'] ?>" <?= $type['parent'] == $cat['id'] ? 'selected' : '' ?>><?= lang($cat['name'], $cat['name_de']) ?></option>
                <?php } ?>
            </select>
        </div>
        <hr>
        <?php // } 
        ?>
        <div class="content">

            <div class="row row-eq-spacing">

                <?php if (isset($type['id'])) { ?>
                    <input type="hidden" name="original_id" value="<?= $type['id'] ?>">
                <?php } ?>

                <div class="col-sm-2">
                    <label for="id" class="required">ID</label>
                    <input type="text" class="form-control" name="values[id]" required value="<?= $type['id'] ?>" oninput="sanitizeID(this)">
                    <small><a href="#unique"><i class="ph ph-info"></i> <?= lang('Must be unqiue', 'Muss einzigartig sein') ?></a></small>
                </div>
                <div class="col-sm-2">
                    <label for="icon" class="required element-time"><a href="https://phosphoricons.com/" class="link" target="_blank" rel="noopener noreferrer">Icon</a> </label>

                    <div class="input-group">
                        <input type="text" class="form-control" name="values[icon]" required value="<?= $type['icon'] ?? 'placeholder' ?>" onchange="iconTest(this.value)">
                        <div class="input-group-append">
                            <span class="input-group-text">
                                <i class="ph ph-<?= $type['icon'] ?? 'placeholder' ?>" id="test-icon"></i>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-sm">
                    <label for="name" class="required ">Name (en)</label>
                    <input type="text" class="form-control" name="values[name]" required value="<?= $type['name'] ?? '' ?>">
                </div>
                <div class="col-sm">
                    <label for="name_de" class="">Name (de)</label>
                    <input type="text" class="form-control" name="values[name_de]" value="<?= $type['name_de'] ?? '' ?>">
                </div>
            </div>


            <div class="row row-eq-spacing">
                <div class="col-sm">
                    <label for="description"><?= lang('Description', 'Beschreibung') ?> (en)</label>
                    <input type="text" class="form-control" name="values[description]" value="<?= $type['description'] ?? '' ?>">
                </div>
                <div class="col-sm">
                    <label for="description_de" class=""><?= lang('Description', 'Beschreibung') ?> (de)</label>
                    <input type="text" class="form-control" name="values[description_de]" value="<?= $type['description_de'] ?? '' ?>">
                </div>
            </div>

            <div class="mt-20">
                <input type="hidden" name="values[guests]" value="">
                <div class="custom-checkbox">
                    <input type="checkbox" id="guest-question" value="1" name="values[guests]" <?= ($type['guests'] ?? false) ? 'checked' : '' ?>>
                    <label for="guest-question">
                        <?= lang('Guests should be registered for this activity', 'Gäste sollen zu dieser Aktivität angemeldet werden können?') ?>
                    </label>
                </div>
            </div>
            <?php if ($Settings->featureEnabled('portal')) { ?>
                <div class="mt-20">
                    <input type="hidden" name="values[portfolio]" value="">
                    <div class="custom-checkbox">
                        <input type="checkbox" id="portfolio-question" value="1" name="values[portfolio]" <?= ($type['portfolio'] ?? $type['parent'] == 'publication') ? 'checked' : '' ?>>
                        <label for="portfolio-question">
                            <?= lang('This type of activity should be visible in OSIRIS Portfolio.', 'Diese Art von Aktivität sollte in OSIRIS Portfolio sichtbar sein.') ?>
                        </label>
                    </div>
                </div>
            <?php } ?>
            <?php if ($Settings->featureEnabled('topics')) { ?>
                <div class="mt-20">
                    <input type="hidden" name="values[topics-required]" value="">
                    <div class="custom-checkbox">
                        <input type="checkbox" id="topics-question" value="1" name="values[topics-required]" <?= ($type['topics-required'] ?? false) ? 'checked' : '' ?>>
                        <label for="topics-question">
                            <?= lang('Research Topics are a required field for this activity', 'Forschungsbereiche sind für diese Aktivität ein Pflichtfeld') ?>
                        </label>
                    </div>
                </div>
            <?php } ?>

        </div>
        <hr>

        <div class="content">
            <label for="module" class="font-weight-bold"><?=lang('Data fields', 'Datenfelder')?>:</label>
            
            <a href="<?=ROOTPATH?>/admin/module-helper?type=<?=$st?>" target="_blank" rel="noopener noreferrer" class="ml-10">
                <?=lang('Field overview', 'Datenfelder-Übersicht')?> <i class="ph ph-arrow-square-out ml-5"></i>
            </a>
            <div class="author-widget">
                <div class="author-list p-10">
                    <?php
                    $module_lst = [];
                    foreach ($type['modules'] ?? array() as $module) {
                        $req = '';
                        $name = trim($module);
                        if (str_ends_with($name, '*') || in_array($name, ['title', 'authors', 'date', 'date-range'])) {
                            $name = str_replace('*', '', $name);
                            $module = $name . "*";
                            $req = 'required';
                        }
                        $module_lst[] = $name;
                    ?>
                        <div class='author <?= $req ?>' ondblclick="toggleRequired(this)">
                            <?= $name ?>
                            <input type='hidden' name='values[modules][]' value='<?= $module ?>'>
                            <a onclick='$(this).parent().remove()'>&times;</a>
                        </div>
                    <?php } ?>

                </div>
                <div class=" footer">
                    <div class="input-group sm d-inline-flex w-auto">
                        <select class="module-input form-control">
                            <option value="" disabled selected><?= lang('Add module ...', 'Füge Module hinzu ...') ?></option>
                            <?php
                            // read custom modules first
                            $custom_modules = $osiris->adminFields->distinct('id');
                            if (!empty($custom_modules)) {
                                foreach ($custom_modules as $m) {
                                    if (in_array($m, $module_lst)) continue;
                            ?>
                                    <option><?= $m ?></option>
                                <?php } ?>
                                <option disabled>---</option>
                            <?php
                            }
                            include_once BASEPATH . "/php/Modules.php";
                            $Modules = new Modules();
                            $all_modules = array_keys($Modules->all_modules);
                            sort($all_modules);
                            foreach ($all_modules as $m) {
                                if (in_array($m, $module_lst)) continue;
                            ?>
                                <option><?= $m ?></option>
                            <?php } ?>
                        </select>
                        <div class="input-group-append">
                            <button class="btn secondary h-full" type="button" onclick="addModule('<?= $t ?>', '<?= $st ?>');">
                                <i class="ph ph-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr>

        <div class="content">
            <label for="format" class="font-weight-bold">Templates:</label> 

            <a href="<?=ROOTPATH?>/admin/templates?type=<?=$st?>" target="_blank" rel="noopener noreferrer" class="ml-10">
                <?=lang('Template builder', 'Template-Baukasten')?> <i class="ph ph-arrow-square-out ml-5"></i>
            </a>

            <div class="input-group mb-10">
                <div class="input-group-prepend">
                    <span class="input-group-text w-100">Print</span>
                </div>
                <input type="text" class="form-control" name="values[template][print]" value="<?= $type['template']['print'] ?? '{title}' ?>">
            </div>

            <div class="input-group mb-10">
                <div class="input-group-prepend">
                    <span class="input-group-text w-100">Web Title</span>
                </div>
                <input type="text" class="form-control" name="values[template][title]" value="<?= $type['template']['title'] ?? '{title}' ?>">
            </div>

            <div class="input-group mb-10">
                <div class="input-group-prepend">
                    <span class="input-group-text w-100">Web Subtitle</span>
                </div>
                <input type="text" class="form-control" name="values[template][subtitle]" value="<?= $type['template']['subtitle'] ?? '{authors}' ?>">
            </div>


            <div class="alert primary ">
                <h3 class="title text-primary">
                    <?= lang('Example', 'Beispiel') ?>
                    <span data-toggle="tooltip" data-title="<?= lang('Will be updated as soon as you save the type.', 'Wird aktualisiert, sobald der Typ gespeichert wird.') ?>">
                        <i class="ph ph-question"></i>
                    </span>
                </h3>
                <b>Print</b> <br>
                <?= $type['example'] ?? '- save current form to generate an example -' ?>
                <hr>

                <b>Web</b> <br>
                <?= $type['example_web'] ?? '- save current form to generate an example -' ?>
            </div>
        </div>



        <hr>


        <div class="content">
            <label for="coins" class="font-weight-bold">Coins:</label>
            <input type="text" class="form-control" name="values[coins]" value="<?= $type['coins'] ?? '0' ?>">
            <span class="text-muted">
                <?= lang('Please note that <q>middle</q> authors will receive half the amount.', 'Bitte beachten Sie, dass <q>middle</q>-Autoren nur die Hälfte der Coins bekommen.') ?>
            </span>
        </div>

        <hr>


        <div class="content">
            <div class="custom-checkbox mb-10 danger">
                <input type="checkbox" id="disable-<?= $t ?>-<?= $st ?>" value="true" name="values[disabled]" <?= ($type['disabled'] ?? false) ? 'checked' : '' ?>>
                <label for="disable-<?= $t ?>-<?= $st ?>"><?= lang('Deactivate', 'Deaktivieren') ?></label>
            </div>
            <span class="text-muted">
                <?= lang('Deactivated types are retained for past activities, but no new ones can be added.', 'Deaktivierte Typen bleiben erhalten für vergangene Aktivitäten, es können aber keine neuen hinzugefügt werden.') ?>
            </span>
        </div>

    </div>
    <button class="btn success" id="submitBtn"><?= $btntext ?></button>
</form>


<?php if (!$new) { ?>


    <?php if ($member == 0) { ?>
        <div class="alert danger mt-20">
            <form action="<?= ROOTPATH ?>/crud/types/delete/<?= $id ?>" method="post">
                <input type="hidden" class="hidden" name="redirect" value="<?= ROOTPATH ?>/admin/categories/<?= $type['parent'] ?>">
                <button class="btn danger"><i class="ph ph-trash"></i> <?= lang('Delete', 'Löschen') ?></button>
                <span class="ml-20"><?= lang('Warning! Cannot be undone.', 'Warnung, kann nicht rückgängig gemacht werden!') ?></span>
            </form>
        </div>
    <?php } else { ?>

        <div class="alert danger mt-20">
            <?= lang("Can\'t delete type: $member activities associated.", "Kann Typ nicht löschen: $member Aktivitäten zugeordnet.") ?><br>
            <a href='<?= ROOTPATH ?>/activities/search#{"$and":[{"type":"<?= $id ?>"}]}' target="_blank" class="text-danger">
                <i class="ph ph-search"></i>
                <?= lang('View activities', 'Aktivitäten zeigen') ?>
            </a>

        </div>
    <?php } ?>


<?php } ?>


<script src="<?= ROOTPATH ?>/js/jquery-ui.min.js"></script>
<script src="<?= ROOTPATH ?>/js/admin-categories.js"></script>
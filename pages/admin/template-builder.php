<?php

include_once BASEPATH . "/php/Document.php";
include_once BASEPATH . "/php/example-document.php";
$Document = new Document();

if (isset($_GET['id']) && !empty($_GET['id'])) {
    if (!DB::is_ObjectID($_GET['id'])) {
        echo lang('The ID you entered is not valid. Please use a valid activity ID.');
    } else {
        $new = $osiris->activities->findOne(
            ['_id' => DB::to_ObjectID($_GET['id'])]
        );
        if (empty($new)) {
            echo lang("Sorry, the activity was not found in the database. We will use the default example.");
        } else {
            $form = $new;
        }
    }
}

$Document->setDocument($form);

$template = '';

if (isset($_GET['type'])){
    $t = $osiris->adminTypes->findOne(['id'=>$_GET['type']]);
    $templates = $t['template'];
    $template = $templates['print'];
}

?>

<div class="container">

    <h1>Template Builder</h1>
    <span class="badge danger"><i class="ph ph-warning"></i> BETA</span>

    <h2>
        <?= lang('All available templates', 'Alle verfügbaren Templates') ?>
    </h2>

    <form>
        <div class="form-group floating-form">
            <input type="text" name="id" placeholder="id" class="form-control" value="<?= $_GET['id'] ?? '' ?>">
            <label for="id">
                <?= lang('Enter activity ID and press enter for displaying a specific example', 'Gib eine Aktivitäts-ID an und drücke Enter, um ein spezifisches Beispiel zu zeigen.') ?>
            </label>
        </div>
    </form>


    <div class="row row-eq-spacing">
        <div class="col">
            <h3 class="mt-5">Template</h3>

            <style>
                .template-editor {
                    border: 1px solid var(--border-color);
                    box-sizing: border-box;
                    padding: 0;
                    border-radius: var(--border-radius);
                    background-color: white;
                }

                .template-editor .btn-group {
                    background-color: white;
                    margin: 3px;
                }

                .template-editor textarea {
                    border: none;
                    border-top: 1px solid var(--border-color);
                    border-bottom: 1px solid var(--border-color);
                    background-color: var(--primary-color-20);
                    border-radius: 0;
                    font-family: 'Source Code Pro', Courier, monospace;
                    height: 15rem;
                }

                .template-editor .example-area {
                    padding: 1rem;
                }
            </style>

            <div class="template-editor">
                <div class="btn-group">
                    <button class="btn link small" type="button" onclick="addElement('em')"><i class="ph ph-text-italic"></i></button>
                    <button class="btn link small" type="button" onclick="addElement('b')"><i class="ph ph-text-b"></i></button>
                    <button class="btn link small" type="button" onclick="addElement('u')"><i class="ph ph-text-underline"></i></button>

                    <button class="btn link small ml-10" type="button" onclick="addElement('br')"><i class="ph ph-key-return"></i></button>
                </div>
                <textarea name="template" id="template" class="form-control" placeholder="Start creating your template here"><?=$template?></textarea>
                <div class="example-area">
                    <b><?= lang('Example', 'Beispiel') ?>:</b>
                    <div id="example" style="min-height: 3rem;"></div>
                </div>
            </div>

        </div>
        <div class="col">
            <input type="search" id="search-templates" class="form-control mb-10" placeholder="Search in table ...">
            <div class="h-400 overflow-auto">
                <table class="table small" id="all-templates">

                    <tbody>
                        <?php

                        $fields = $Document->templates;
                        foreach ($Document->custom_fields as $name => $values) {
                            $fields[$name] = [];
                        }
                        foreach ($fields as $name => $fields) { ?>
                            <tr id="t-<?= $name ?>">
                                <td>
                                    <a onclick="addTemplate('<?= $name ?>')">
                                        <b><?= $name ?></b>
                                    </a>
                                    <?php
                                    $fields = array_filter($fields, function ($field) use ($name) {
                                        return $field != $name;
                                    });
                                    if (!empty($fields))
                                        echo ' <small>(' . implode(', ', $fields) . ')</small>';
                                    ?>

                                    <br>
                                    <small>
                                        <b><?= lang('Example', 'Beispiel') ?>:</b>
                                        <span class="example">
                                            <?php
                                            echo $Document->get_field($name, '-');
                                            ?>
                                        </span>
                                    </small>
                                </td>
                            </tr>
                        <?php   } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>


<script>
    $('#search-templates').on('input', function() {
        let table = $('#all-templates tbody')
        let input = $('#search-templates').val()
        table.find('tr').show()
        if (input == '') return;
        table.find('tr:not(:contains("' + input + '"))').hide()
    })

    function addTemplate(name) {
        let text = $('#template').val()
        // $('#template').val(`${text}{${name}}`)
        insertAtCursor('template', `{${name}}`)
        updateExample()
        // focus on the textarea
        $('#template').focus()
    }

    function addElement(element) {
        let text = $('#template').val()
        let field = document.getElementById('template')

        let startel = '<' + element + '>'
        let endel = '</' + element + '>'
        if (element == 'br') {
            endel = ''
        }

        let start = field.selectionStart
        let end = field.selectionEnd
        let selected = field.value.substring(start, end)
        let before = field.value.substring(0, start)
        let after = field.value.substring(end)

        field.value = before + startel + selected + endel + after
        field.selectionStart = start + startel.length
        field.selectionEnd = end + startel.length
        field.focus()

        updateExample()
    }


    function insertAtCursor(field, value, wrapAround = false) {

        field = document.getElementById(field)
        //IE support
        if (document.selection) {
            field.focus();
            const sel = document.selection.createRange();
            sel.text = value;
        }
        //MOZILLA and others
        else if (field.selectionStart || field.selectionStart == '0') {
            var startPos = field.selectionStart;
            var endPos = field.selectionEnd;
            field.value = field.value.substring(0, startPos) +
                value +
                field.value.substring(endPos, field.value.length);
        } else {
            field.value += value;
        }
    }

    function updateExample() {
        let content = $('#template').val()
        // replace {templates} with the respective examples
        let pattern = /{([^}]*)}/
        console.log(content);
        while (pattern.test(content)) {
            let match = pattern.exec(content)
            let name = match[1]
            let example = $(`#t-${name} .example`).html().trim()
            content = content.replace(`{${name}}`, example)
        }

        console.log(content);
        $('#example').html(content)
    }

    $('#template').on('change', updateExample)

    updateExample();
</script>
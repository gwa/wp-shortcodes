/*global  jQuery, gwa, tinyMCE*/
/*jslint browser: true continue: true*/
(function($) {
    'use strict';

    var gwa = window.gwa = window.gwa || {},
        el;

    function createFormObjectForShortcode(code) {
        var str = '<li><label><input type="radio" name="gwa-shortcode-select" value="' + code.name + '"><span>' + code.name + '</span></label></li>';
        return str;
    }

    function createAttributeFormObjectsForShortcode(code) {
        var str = '<div id="gwa-' + code.name + '-attr" class="gwa-attr"><ul>', a;

        if (code.params.length === 0) {
            str += '<li><label>No Attributes</label></li></ul></div>';
        } else {
            for (a in code.params) {
                str += '<li><label><input type="checkbox" name="' + code.name + '-' + code.params[a] + '" value="' + code.params[a] + '"><span>' + code.params[a] + '</span></label></li>';
            }
        }

        str += '</ul></div>';
        return str;
    }

    function insertPlainTextIntoTexteditor(text, editor) {
        var cursorPos = editor[0].selectionStart,
            val = editor.val();

        editor.val(val.substring(0, cursorPos) + text + val.substring(cursorPos));
    }

    function handleRadioClick() {
        // Hide all Attributes
        el.attr.find("div").removeClass('gwa-attr-show');
        // Show Attributes
        $("#gwa-" + $(this).val() + "-attr").addClass('gwa-attr-show');
        //Reset all checks
        el.attr.find(":checkbox").prop('checked', false);
        // Enable Button
        el.submit.removeAttr('disabled');
    }

    function handleSubmit(event) {
        var frmvals = $("#gwa-post-form").serializeArray(),
            shortcodeslug = frmvals[0].value,
            $cnt = $('#content'),
            str = '',
            attrstr = '',
            i;

        for (i = 1; i < frmvals.length; i++) {
            attrstr += ' ' + frmvals[i].value + '=""';
        }

        str = '[' + shortcodeslug + attrstr + '][/' + shortcodeslug + ']';

        //If visual editor is active
        if (typeof(tinyMCE) !== "undefined") {
            if (tinyMCE.activeEditor === null || tinyMCE.activeEditor.isHidden()) {
                insertPlainTextIntoTexteditor(str, $cnt);
            } else {
                tinyMCE.execCommand('mceInsertContent', false, str);
            }
        } else {
            insertPlainTextIntoTexteditor(str, $cnt);
        }

        //Close ThickBox
        $('#TB_closeWindowButton').click();
        event.preventDefault();

        return false;
    }

    gwa.initShortcodeForm = function(codes) {
        var str = '',
            astr = '',
            i,
            length;

        el = {
            select: $("#gwa-select-wrapper"),
            attr: $("#gwa-attr-wrapper"),
            submit: $("#gwa-submit"),
            ul: $("#gwa-select-wrapper ul")
        }

        for (i = 0, length = codes.length; i < length; i += 1) {
            str  += createFormObjectForShortcode(codes[i]);
            astr += createAttributeFormObjectsForShortcode(codes[i]);
        }

        el.ul.html(str);
        el.attr.append(astr);

        // Shortcode Selected
        el.select.off('change').on('change', ':radio', handleRadioClick);

        // init submit button
        el.submit.off('click').click(handleSubmit);
    };
}(jQuery));
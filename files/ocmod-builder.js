$('document')
    .ready(function (e) {
        $('#form_restore').submit(function (e) {
            if (!confirm(lang_confirm_restore))
                e.preventDefault();
        });

        let selected = null,
            selectedLink = null,
            changes = null,
            currentChange = null,
            fc = $('#file_content'),
            cw = $('#content_wrapper'),
            buttons = $('button[id^="btnGo"]');

        $('.get_diff,.get_ocmod,.get_orig,.get_upload').click(function (e) {
            e.preventDefault();

            let file = $(this).attr('href');
            if (!file)
                return;

            let data = {
                file: file,
                action: $(this).attr('class').replace('active', '').trim()
            };

            if (selected)
                selected.removeClass('active');
            if (selectedLink)
                selectedLink.removeClass('active');

            selected = $(this).parent().addClass('active');
            selectedLink = $(this).addClass('active');

            $.post('index.php', data, function (d) {
                if ('content' in d) {
                    let ext = file.substring(file.lastIndexOf('.')).toLowerCase();
                    let lang = ext == '.php' ? 'php' : (ext == '.twig' ? 'twig' : (ext == '.js' ? 'javascript' : ''));
                    let lines = d.content;
                    let tempHTML = $('<div>');
                    let numsHTML = $('<div>');
                    let lineNumber = 1;

                    for (let i = 0; i < lines.length; i++) {
                        let line = lines[i];

                        let actionRe = new RegExp("^\\[[+\\-]\\]");
                        let action = line.match(actionRe);
                        let actionClass = ' class="normal" tag="normal"';
                        if (action) {
                            line = line.substring(3);
                            actionClass = action[0] == '[+]' ? ' class="added" tag="added"' : ' class="removed" tag="removed"';
                        }

                        let spcRe = new RegExp("^[\t ]+");
                        let spc = line.match(spcRe);
                        let spaces = (spc ? spc[0] : '').replaceAll('\t', '&nbsp;&nbsp;&nbsp;&nbsp;').replaceAll(' ', '&nbsp;');
                        if (spc)
                            line = line.substring(spc[0].length);

                        let div = $('<div>').text(line);
                        let html = lang ? Prism.highlight(line, Prism.languages[lang], lang) : line;
                        if (!html)
                            html = '&nbsp;';

                        tempHTML.append('<code' + actionClass + '>' + spaces + html + '</code>');
                        numsHTML.append('<code>' + lineNumber + '&nbsp;</code>');

                        lineNumber++;
                    }

                    fc.html(tempHTML);
                    $('#line_numbers').html(numsHTML);

                    currentChange = null;
                    changes = $('code.normal + code:not(.normal),' +
                        'code.added + code:not(.added):not(.normal),' +
                        'code.removed + code:not(.removed):not(.normal)', fc)
                        .toArray();

                    enableNavButtons();
                } else {
                    if ('error' in d) {
                        $('#alertText').html(d.error);
                        $('#alert').fadeIn();
                    }
                }
            });
        });

        function enableNavButtons() {
            if (!changes || changes.length == 0) {
                buttons.attr('disabled', 'disabled');
                return;
            }

            if (!currentChange) {
                buttons.removeAttr('disabled');
                return;
            }

            let index = changes.indexOf(currentChange);

            // buttons[0].toggleAttribute('disabled', index == 0);
            // buttons[1].toggleAttribute('disabled', index == 0);
            // buttons[2].toggleAttribute('disabled', index >= changes.length - 1);
            // buttons[3].toggleAttribute('disabled', index >= changes.length - 1);
        }

        function highlightBlock(addClass) {
            let cc = $(currentChange);
            let tag = cc.attr('tag');
            let sel = cc.nextUntil(':not([tag="' + tag + '"]').add(cc);

            sel[addClass ? 'addClass' : 'removeClass']('selected');
        }

        buttons.click(function (e) {
            if (!changes || changes.length == 0)
                return;

            let id = $(this).attr('id');
            let index = currentChange ? changes.indexOf(currentChange) : 0;

            switch (id.substring(5)) {
                case 'First':
                    index = 0;
                    break;
                case 'Prev':
                    if (--index < 0)
                        index = changes.length - 1;
                    break;
                case 'Next':
                    if (!currentChange || ++index >= changes.length)
                        index = 0;
                    break;
                case 'Last':
                    index = changes.length - 1;
                    break;
            }

            if (currentChange)
                highlightBlock(false);

            currentChange = changes[index];

            let toGo = $(currentChange)[0].offsetTop - 60,
                scrTop = cw.scrollTop(),
                height = cw.height();

            if (toGo < scrTop || toGo > scrTop + height - 60)
                cw.scrollTop(toGo - height / 2);

            highlightBlock(true);
            enableNavButtons();
        });

        enableNavButtons();
    });

$(window).keydown(function (e) {
    let keys = ((e.ctrlKey ? 1 : 0) << 2) | ((e.shiftKey ? 1 : 0) << 1) | (e.alt ? 1 : 0);

    switch (e.keyCode) {
        case 35: //End
            (keys == 0) && $('#btnGoLast').click();
            break;
        case 36: //Home
            (keys == 0) && $('#btnGoFirst').click();
            break;
        case 38: //Up
            (keys == 4) && $('#btnGoPrev').click();
            break;
        case 40: //Down
            (keys == 4) && $('#btnGoNext').click();
            break;
    }
});
var Tplizer = new function () {
    var t = this, $t = {};

    t.add = function (tpls) {
        for (var i in tpls)
            $t[i] = tpls[i];
    };

    t.getTemplate = function (tpl) {
        if (tpl in $t)
            return $t[tpl].replace(/<tplizer id="([^"]+)"><\/tplizer>/ig,
                function (d, v) {
                    return t.getTemplate(v);
                });

        return '';
    };

    $(document).ready(function () {
        for (var id in $t) {
            if (id.charAt(0) === '$')
                $(document.createElement("script"))
                    .attr(
                        {
                            type: 'text/html',
                            id: id.substring(1)
                        })
                    .html($t[id])
                    .prependTo($('body'));
            else if (id.charAt(0) === '_')
                $($t[id]).prependTo($('body'));
        }

        $('tplizer').replaceWith(function () {
            return t.getTemplate($(this).attr('id'));
        });
    });
};
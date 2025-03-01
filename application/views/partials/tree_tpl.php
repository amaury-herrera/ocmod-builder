<script type="text/html" id="tree_tpl">
    <div data-bind="if: isRoot && typeof($root.showRoot) == 'function' && !$root.showRoot()">
        <div data-bind="template: {name: 'tree_tpl', data: {isRoot: false, leaf: leaf()[0].c || []}}"></div>
    </div>

    <div data-bind="if: !isRoot || typeof($root.showRoot) != 'function' || $root.showRoot()">
        <ul class="tree" data-bind="foreach: {data: leaf}, css: {rootLeaf: isRoot}">
            <li data-bind="if: !$data.parent || $data.parent.opened()">
                <a class="opener" data-bind="css: {plus: !opened(), minus: opened},
                    visible: !$parent.isRoot && $data.c().length > 0,
                    click: $root.toggleOpen, text: opened() ? '-':'+'"></a>
                <a href="#" data-bind="$element: 1,
                    css: {active: $root.activeLeaf() == $data,
                      isNew: $data.new,
                      hasOCMod: $data.o,
                      hasUpload: $data.u() || $data.f()},
                    click: function() { if ($root.activeLeaf() != $data && (!$root.canActivate || $root.canActivate($data))) $root.activeLeaf($data); }">
                    <span data-bind="text: n"></span>
                </a>

                <div data-bind="if: (typeof(c) == 'function' ? c() : []).length > 0">
                    <div data-bind="template: {name: 'tree_tpl', data: {isRoot: false, leaf: $data.c}}"></div>
                </div>
            </li>
        </ul>
    </div>
</script>
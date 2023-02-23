$(function () {
    $('#tree')
    .jstree({ 'core' : {
        'data' : [{id: "root", text: "Documents", state: { opened: true }, children: getTree()}]
    } }).on('ready.jstree', function() {
        $('#root_anchor').click(function(){
            console.log(this)
            $(this).jstree("open_all");
        })
    })
    .bind("select_node.jstree", function (e, data) {
        var href = data.node.a_attr.href;
        document.location.href = href;
    });
});

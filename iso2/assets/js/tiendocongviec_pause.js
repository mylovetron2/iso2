// JS cho chức năng tạm dừng (pause) tiến độ công việc
window.PauseAPI = {
    list: function (work_id, cb) {
        fetch('tiendocongviec_pause_api.php?action=list&work_id=' + work_id)
            .then(r => r.json()).then(cb);
    },
    add: function (data, cb) {
        fetch('tiendocongviec_pause_api.php?action=add', {
            method: 'POST', body: new URLSearchParams(data)
        }).then(r => r.json()).then(cb);
    },
    update: function (id, data, cb) {
        data.id = id;
        fetch('tiendocongviec_pause_api.php?action=update', {
            method: 'POST', body: new URLSearchParams(data)
        }).then(r => r.json()).then(cb);
    },
    delete: function (id, cb) {
        fetch('tiendocongviec_pause_api.php?action=delete', {
            method: 'POST', body: new URLSearchParams({ id })
        }).then(r => r.json()).then(cb);
    }
};

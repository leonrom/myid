var GetMyId = function(page, tag, xchg, key, clear) {
    /*
     * page - current blog's pag
     * tag - nme of tag in HTML-page, which may contain uesr's name
     * url - url of PHP-server
     * clear - additional parameter,- means to reinitialize user's name/cookie in PHP-server
     */
    'use strict'
    /* global Fingerprint2 */
    /* global lscook */

    this.p = {
        state: {
            server_id: undefined,
            id_browse: undefined,
            user_name: '?',
        },
        cnsts: {
            TAG: tag, // тег посля с именем усера
            KEY: key, // ключ сохранения в "cookies"
            XCHG: xchg, // класс  Exchange.js
        }
    }

    init(this.p, clear)

    function init(p, clear) {
        if (tag) {
            console.log('--> init()' +
                '\n    TAG="' + p.cnsts.TAG + '"' +
                '\n    URL="' + p.cnsts.URL + '"'
            )
        }
        else {
            console.log('--> init() newly attempt on error')
        }
        
        var data
        var str = lscook(p.cnsts.KEY)
        try {
            data = JSON.parse(str);
        }
        catch (e) {
            console.error('To JSON: for KEY="' + p.cnsts.KEY + '": str(read)="' + str + '": e=' + e.toGMTString)
        }
        if (!data || (data.server_id === undefined) || !tag || clear) {
            var s = 'Для сохранения истории посещений '
            if (!window.navigator.cookieEnabled) {
                var msg = s + 'включите cookie (и перезапустите браузер)'
                    //          alert(msg)
                console.log(msg)
            }
            else {
                var msg = s + 'используются cookie. Если Вы не согласны - покиньте сайт или отключите cookie в браузере '
                    //        alert(msg)
                console.log(msg)
            }
            testUserName(p)
            getUserFromServer(p)
        }
        else { // куки прочитаны, пользователь определен. Запрос на историю посещений
            p.state.server_id = data.server_id
            phpRequest(p, {
                type: "server_id",
                server_id: p.state.server_id
            })
        }
    }

    this.act = function() {
        console.log('--> this.act()')
        saveUserName(this.p)
    }

    function saveUserName(p) {
        console.log('--> saveUserName()')
        if (testUserName(p)) {
            if (p.state.server_id) {
                lscook(p.cnsts.KEY, JSON.stringify(p.state))
                phpRequest(p, {
                    type: "user_name",
                    server_id: p.state.server_id,
                    user_name: p.state.user_name
                })
                console.log('saveUserName(): saved')
            }
        }
    }

    function testUserName(p) {
        console.log('--> testUserName()')
        var rez = false
        if (p.state.user_name.length > 1) {
            rez = true
        }
        else {
            if (p.cnsts.TAG && !p.xmlhttp) {
                var tag = document.getElementById("this.p.cnsts.TAG");
                if (tag) {
                    var nam = tag.innerHTML.trim();
                    if (nam.length > 0) {
                        p.state.user_name = nam
                        rez = true
                        console.log('testUserName(): found')
                    }
                }
            }
        }
        return rez;
    }

    function getUserFromServer(p) { // get current browser's hash
        console.log('--> getNewUserFromServer()')
        var options = {
            swfPath: '/assets/FontList.swf',
            excludeUserAgent: true
        };
        var fingerprint2 = new Fingerprint2(options)
        fingerprint2.get(phpGetUserId);

        function phpGetUserId(hash) { // get user's Id from PHP-serverisFirstTime
            console.log('--> phpGetUserId()   hash="' + hash + '"')
            p.state.id_browse = hash
            phpRequest(p, {
                type: "id_browse",
                id_browse: p.state.id_browse,
                user_name: p.state.user_name
            })
        }
    }

    function phpRequest(p, dat) { // get user's Id from PHP-serverisFirstTime
        console.log('--> phpRequest():\n    dat.type=' + dat.type)

        var sdat = JSON.stringify(dat)
        p.cnsts.XCHG.act(sdat, retf)

        var retf = function(s) {
            console.log('--> retf(s) s= =\n' + s)
            var doc = document.getElementById("txtHint")
            if (doc) {
                doc.innerHTML = s
            }
            var ss = s.split("=>")
            if (ss.length > 1) {

                console.log('результат: ss[1]=' + ss[1])
                if (ss[1].length == 0) {
                    console.error('server return error on SQL at sdat=' + sdat)

                }
                else {
                    try {
                        var rdat = JSON.parse(ss[1]);
                        console.log('rdat.typ = ' + rdat.typ)
                        switch (rdat.typ) {
                            case 'history':
                                console.log('--> rdat:' +
                                    '  user_name=' + rdat.user_name +
                                    ', history=' + rdat.history)
                                var i = 0
                                rdat.history.split(';').forEach(function(act) {
                                    var ss = act.split(',')
                                    if (ss.length > 2) {
                                        var dts = ss[0].split('=')[1].trim()
                                        var typ = ss[1].split('=')[1].trim()
                                        var val = ss[2].split('=')[1].trim()
                                        var dat = new Date(dts + ' UTC')
                                        console.log('  ' + i++ + ') ' + dat.toLocaleString() + ' :: typ="' + typ + '", val="' + val + '"')
                                            // toLocaleString() - сжвтый формат, а функция toString() вернёт расширенный формат
                                    }
                                });
                                break

                            case 'addUser':
                                lscook(p.cnsts.KEY, JSON.stringify(rdat)) // store sate in my LS/cookie
                                console.log('--> rdat:' +
                                    '  server_id=' + rdat.server_id +
                                    ', id_browse=' + rdat.id_browse +
                                    ', user_name=' + rdat.user_name)
                                saveUserName(p)
                                break

                            default:
                                // nothing to do
                        }
                    }
                    catch (e) {
                        console.error('getFromPHP(): e=' + e.message)
                    }
                }
            }
        }
    }
}

// тестировние счетчиков страницы    
/*    
    if (!GetMyId.isStart3){       // счетчик вызовов скрипта после инициализации страницы
        GetMyId.isStart3 = 0;     // ведёт аналогично как  и window.isStart2
    }
    GetMyId.isStart3++    
    if (sessionStorage.isStart4){   // счетчик поселедовательных вызовов скрипта с этой закладни (без сброса при перерисовук)
        sessionStorage.isStart4 = Number(sessionStorage.isStart4) + 1;
    } else {
        sessionStorage.isStart4 = 1;
    }
    var isStart5 = window.localStorage.getItem('isStart5');   // общий для сех страниц счетчик вызовов скрипта
    if (!isStart5 || isNaN(isStart5)){      // наверное его изменять при первой инициализации предыдущего если больше нет закладок на сайт
        isStart5 = 0
    }
    window.localStorage.setItem('isStart5', ++isStart5);
    console.log(' isStart1= ' + this.isStart1 + ', isStart2= ' + window.isStart2 + ', isStart3= ' + GetMyId.isStart3 + 
        ', isStart4= ' + sessionStorage.isStart4 + ', isStart5= ' + isStart5 )
    return
 */

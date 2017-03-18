var Exchange = function(url, php, met) {
    /*
     * exchange with PHP server
     *
     *  url - remote server
     *  log - KLogger
     *  met - method: CORS, JSONP, etc
     */
    'use strict'

    this.x = {
        xmlh:  new XMLHttpRequest(),
        retf: undefined,
        MET: 'CORS', // пока только для CORS но потом м.б. и для JSONP
        URL: url,
        PHP: php,
        isReady: undefined
    }
    console.log('>>> Exchange  url= ' +  url + ', php=' + php);

    this.act = function(sdat, fun) {
        if (this.x.isReady){
            console.error('--- this.act on isReady=TRUE: sdat= ' +  sdat);
        }else{
            console.log('--- this.act: sdat= ' +  sdat);
            this.x.retf = fun
            if (this.x.MET == 'CORS') {
                getX(this.x, sdat, this.rezX)
            }
        }
    }

    this.rezX = function(e) { // event 'e' is not used here
        var xmlh = this.x.xmlh
        if (xmlh.readyState == 4) {
            if (xmlh.status == 200) {
                this.x.retf(xmlh.responseText)
                console.log('--> rezX(): xmlh.status=' + xmlh.status + ': ' + xmlh.statusText)
            } else {
                console.error('--> rezX(): xmlh.status=' + xmlh.status + ': ' + xmlh.statusText)
            }
            this.x.isReady = true
        }else{
            console.log('=== rezX(): receive: readyState=' + xmlh.readyState)
        }
    }

    function getX(x, sdat, fun) {
        console.log('--- getX() ask from: ' + x.URL);
        x.isReady = false

//        x.xmlh = new XMLHttpRequest()
        x.xmlh.onreadystatechange = fun

        x.xmlh.open("POST", x.URL + x.PHP + "?r=" + Math.random(), true)
        x.xmlh.setRequestHeader('Content-type', 'application/x-www-form-urlencoded')
        x.xmlh.responseType = 'text'

        x.xmlh.send("parms=" + sdat)
        x.xmlh.timeout = 2000;

        x.xmlh.ontimeout = function (e) {
            console.error('phpRequest(): TimeOut on XMLHttpRequest ')
            x.xmlh.abort();
            x.isReady = true
        };
        
        console.log('... finish')
    }

    this.x.isReady = true
}

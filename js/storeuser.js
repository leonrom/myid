var StoreUser = function() {
    /*
     * 
     */
    'use strict'
    /* global Exchange */
    /* global GetMyId */
    
    this.clear = false
    this.xchg = undefined
    this.getMyId = undefined
    
    this.ini = function(clear) {
        if (clear) {
            this.clear = clear
        }
//        var log = ? 
        this.xchg = new Exchange('.', '/php/getX.php', 'CORS')
    }

    this.enterPage = function(pgNam) {
        this.getMyId = new GetMyId(pgNam, 'tagUserName', this.xchg, 'myid', this.clear)            
    }
}

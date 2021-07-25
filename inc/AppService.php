<?php

/*
    AppService Class

    create the Server Instance and start socket
*/
class AppService {

    public $server_app;

    function __construct()
    {

        stream_set_blocking(STDIN, false);
        set_time_limit(0);
        ob_implicit_flush(TRUE);
        
    }



    public function start(){

        echo "Socket Server Up and running ...\n\r";
        echo "To stop The Server enter q ...\n\n";
        $this->server_app = new Server();
        $this->server_app->initServer();

    }

    

}
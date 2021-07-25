<?php

/* Class Server
   the proccess for open socket for users/clients 
 */
//require_once "./inc/ClientRequest.php";//static functions class


class Server{

    public $clients;//clients array

    public $_conn;//socket connection

    public function __construct()
    {
        $this->clients = []; // init the clients array
    }

    //start the socket listing and response for new connection
    public function initServer(){


        //create the socket and bind to ip and port
        $this->_conn = socket_create( AF_INET , SOCK_STREAM , SOL_TCP );
        $socket_bind = socket_bind($this->_conn ,IP_ADDRESS , PORT);
        $socket_listen = socket_listen($this->_conn);
        socket_set_nonblock($this->_conn );


        if( $this->_conn == false ){
            echo "Erorr in create socket \r" . socket_strerror(socket_last_error()) . "\r\n";
            die();
        }
        if( $socket_bind == false ){
            echo "Erorr in bind socket  \r" . socket_strerror(socket_last_error($this->_conn)) . "\r\n";
            die();
        }
        if( $socket_listen == false ){
            echo "Erorr in listen socket \r" . socket_strerror(socket_last_error($this->_conn)) . "\r\n";
            die();
        }
    
        
        //enter to loop  and start get new connection and response
        //to client option and server option
        while(true){
            //options for server :  'q' to exit
            // echo "server Command work\r\n";
            fscanf(STDIN, "%c", $action);
            switch ($action) {
                case 'q':
                    echo "Good Bye \n";
                    socket_close($this->_conn);
                    exit;
                    break;
            }
            unset($action);

            $read = array();
            $read[] = $this->_conn;
            $read = array_merge($read, $this->clients);
            $write = [];
            $except = [];
            
            // Set up a blocking call to socket_select and fill the arrays 
            // if any chars enter , if condetion under 1 need to continue in the loop 
            if ( socket_select($read, $write, $except, 0) < 1){
                continue;
            }
                        

            if(in_array($this->_conn,$read)){
            
                if( ($nClient = socket_accept($this->_conn)) === false){
                    echo "Error in socket accept.\r\n" . socket_strerror(socket_last_error($this->_conn)) . "\r\n";
                        continue;
                }

                socket_getpeername($nClient,$ip ,$port);
                echo "\n\r New Connection from : " .$ip.":".$port . "\n\n";
                
                // fill the clients array with ip and port for key ,
                // value is the socket resoruce

                $this->clients[$ip.":".$port] = $nClient;
                //echo "\n\r number of online clients : " . count($this->clients) . "\n\r";
        
                //send optins to the connected client
                $action_list = "\rActions List : \n 
                1. Get disk space (total on the server) \n 
                2. Get ping average to 8.8.8.8 \n
                3. Get top 5 search results from google 'enter number 3 and the text' \n
                4. Exit\n";
                

                //send msg with action list to the new cleint 
                $this->sendTextToClient($nClient,$action_list);

            }
            
                // loop over  all connected clients to read socket
                foreach ($this->clients as $key => $client) {


                    if (in_array($client, $read)) {

                        $socket_buffer = socket_read($client,2048, PHP_NORMAL_READ);

                        if (!$socket_buffer = trim($socket_buffer )) {
                            continue;
                        }

                        
                        if( !empty($socket_buffer[0]) ){

                            /*
                            $cmd = $socket_buffer[0];
                            echo "the action : " . $cmd. "\n\r";
                            $msgToClient = "action: ". $cmd ."\n\r" ;
                            $this->sendTextToClient($client,$msgToClient);
                            $this->clientRequest($client,$cmd);
                            */
                          
                            $this->client_action($client ,$socket_buffer);

                            
                        }
                    }
                }

        }
        
    }

    public function sendTextToClient($client , $msg){

        socket_write($client, $msg, strlen($msg));

    }


    //when user enter chars with the sends option
    // invoke function by action numbers
    public function client_action($client , $action){

        $cmd = $action[0];//get the action number first char

        switch ($cmd) {
            case 1:
                $response = $this->get_total_disk_space();
                $this->sendTextToClient($client,$response);
                break;
            case 2:
                $response = $this->get_avg_ping();
                $this->sendTextToClient($client,$response);
                break;
            case 3:
                echo $action;
                
                $stringToSearch =  substr($action , 2);
                echo $stringToSearch;

                if(empty($stringToSearch)){
                    $this->sendTextToClient($client,"search query is empty\n\r");
                    break;          
                }

                $response = $this->get_top_five_search(trim($stringToSearch));
                $this->sendTextToClient($client,$response);

                break; 
            case 4: 
                $this->sendTextToClient($client,"Good Bye.\n\r");
                $this->disconnect_client($client);
                break;
            default:
            $this->sendTextToClient(
                    $client,
                    "\r\nThe option you entered not avalible. \n\r"
            );
             break;           
        }
    }
    public function get_total_disk_space(){
        //Assuming the os linux
        $res = "Total Disk Space: "
       . number_format(disk_total_space("/")) . " bytes \r\n";
        return $res;
    }

    public function get_avg_ping(){
        //Assuming the operating system is windows or linux or Darwin(apple os)
       
        $osSystem = PHP_OS_FAMILY;
        $avg = 0 ;
        
        if($osSystem == "Windows"){
                // ping -n 3 8.8.8.8
                $cmd = "ping -n 3 8.8.8.8";
                exec($cmd, $output, $result);
                $res = $output[9];

                //$res = "Minimum = 0ms, Maximum = 0ms, Average = 0ms";
                $res = explode("," , $res);
                $res = explode("=" , $res[2]);

                if(isset($res[1])){
                    $avg = $res[1];
                }else{
                    $avg = "error";
                }


        }elseif($osSystem == "Darwin" || $osSystem == "Linux"){
                //ping -c 3 8.8.8.8
                $cmd = "ping -c 3 8.8.8.8";
                exec($cmd, $output, $result);
                $res = $output[7];
                $res = explode("= ",$res);
                
                $res = explode( "/" , $res[1] );

                if(isset($res[1])){
                    $avg = $res[1] . " ms ";
                }else{
                    $avg = "error";
                }


        }else{
            return "Unknown Os system";
        }

        return "Ping average to 8.8.8.8 : " .$avg . " \n\r";
    }

    public function get_top_five_search($toSearch){

        
        $url = BASE_API_URL . 
        "?key=" . KEY .
        "&cx=".CX . 
        "&num=5".
        "&q=".urlencode($toSearch);

        $res = "\n\n" ;
        $body = file_get_contents($url);
        $json = json_decode($body);
        
        if(
            !empty($json) && isset($json->items) && 
            $json->queries->request[0]->count >0
        ){
             
            $top5 = 0;
            foreach($json->items as $item){
                
                $res .= $top5+1 ." : " . $item->title . "\n";
               
                if($top5 == 4 || $top5 == $json->queries->request[0]->count){
                    break;
                }
                
                $top5++;
            }

        }else{
            $res = "no result\n";
        }
        //print_r($json);

        return "\r\nTop 5 Search Result : \r" .   $res .  "\n\r";


        
    }
    //
    public function disconnect_client($client){
       
        $arr = array_keys($this->clients,$client);
        
        if(isset($arr[0])){
            echo "\n\r Client : " . $arr[0] . " disconnected from Server...\n\r";
            socket_close($this->clients[$arr[0]]);
            unset($this->clients[$arr[0]]);
        }
     
    }
}

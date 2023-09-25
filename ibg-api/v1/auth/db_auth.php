<?php 

class authDatabase{


    private $hostname;
    private $dbname;
    private $username;
    private $password;
    
    private $conn;


    public function connect(){

        $this->hostname = "109.74.202.153";
        $this->username = "root";
        $this->dbname = "eticom";
        $this->password = "k12ght6";

        $this->conn = new mysqli($this->hostname, $this->username, $this->password, $this->dbname);
        if($this->conn->connect_errno){
            print_r($this->conn->connect_error);
            


        }else{
            return $this->conn;
        }

    }


    public function device_connect($building_id){

        $this->hostname = "52.211.218.106";
        $this->username = "root";
        if($building_id == 82){
            $this->dbname = "knx_48";
        }else{
            $this->dbname = "knx_".$building_id;
        }
        $this->password = "k12ght6k12ght6";

        $this->conn = new mysqli($this->hostname, $this->username, $this->password, $this->dbname);
        if($this->conn->connect_errno){
            print_r($this->conn->connect_error);
            


        }else{
            return $this->conn;
        }

    }


}

//TESTING
// $db = new authDatabase();

// $db->connect();

?>
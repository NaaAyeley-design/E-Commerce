<?php
require_once 'db_cred.php';

if (!class_exists('db_connection')) {
    class db_connection
    {
        public $db = null;
        public $results = null;

        function db_connect()
        {
            //connection
            $this->db = mysqli_connect(SERVER, USERNAME, PASSWD, DATABASE);

            //test the connection
            if (mysqli_connect_errno()) {
                return false;
            } else {
                return true;
            }
        }

        function db_conn()
        {
            //connection
            $this->db = mysqli_connect(SERVER, USERNAME, PASSWD, DATABASE);

            //test the connection
            if (mysqli_connect_errno()) {
                return false;
            } else {
                return $this->db;
            }
        }


        function db_query($sqlQuery)
        {
            if (!$this->db_connect()) {
                return false;
            } elseif ($this->db == null) {
                return false;
            }

            //run query 
            $this->results = mysqli_query($this->db, $sqlQuery);

            if ($this->results == false) {
                return false;
            } else {
                return true;
            }
        }

        function db_write_query($sqlQuery)
        {
            if (!$this->db_connect()) {
                return false;
            } elseif ($this->db == null) {
                return false;
            }

            //run query 
            $result = mysqli_query($this->db, $sqlQuery);

            if ($result == false) {
                return false;
            } else {
                return true;
            }
        }

  
        function db_fetch_one($sql)
        {
            if (!$this->db_query($sql)) {
                return false;
            }

            return mysqli_fetch_assoc($this->results);
        }

        function db_fetch_all($sql)
        {
            // if executing query returns false
            if (!$this->db_query($sql)) {
                return false;
            }
            //return all records
            return mysqli_fetch_all($this->results, MYSQLI_ASSOC);
        }

   
        function db_count()
        {
            //check if result was set
            if ($this->results == null) {
                return false;
            } elseif ($this->results == false) {
                return false;
            }

            //return count
            return mysqli_num_rows($this->results);
        }

        function last_insert_id()
        {
            return mysqli_insert_id($this->db);
        }
    }
}

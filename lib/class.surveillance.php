<?php 

class Surveillance {

    /**
     * Get the IP Address From Database and also Generate the Nonce Value
    */
    public static function nonce_value()
    {
        /**
         * The code below gets the IP Address from the database (Camera Table)
         * Using the id of the customer logged in
         */
        $id = $_SESSION[SESSION_NAME_USER_ID];
        $result = App::sql()->query_row("SELECT * FROM camera WHERE cust_id = '$id' LIMIT 1" );
        $getNonceUrl = "http://$result->server_addr:3389/api/getNonce";

        /**
         * The code below generates the nonce value using PHP FILE GET CONTENT
         * It also decodes it i.e to readable string
         */
        $getContect = file_get_contents($getNonceUrl);
        $jsonNoce = json_decode($getContect, true);
        return $finalNonce = $jsonNoce['reply']['nonce'];
        
    }

    /**
     * Update the Nonce Field in the camera_auth table
     * Update the Nonce Field using the Nonce value generated from `nonce_value` generated
     */
    public static function update_nonce()
    {
        $dbNonce = Surveillance::nonce_value();
        $id = $_SESSION[SESSION_NAME_USER_ID];

        $nonce_auth = App::sql()->insert("UPDATE camera_auth SET getNonce = '$dbNonce', updated_time = now() WHERE id = '$id'");

        return $nonce_auth;
    }

    /**
     * The Code below is an algorithem that generates the auth_digest value
     * This algorith uses username, password, method and nonce value to generate auth digest value
     */
    public static function auth_digest_algorithm()
    {
        $id = $_SESSION[SESSION_NAME_USER_ID];
        $camera_auth = App::sql()->query_row("SELECT * from camera_auth WHERE id = '$id'");
        $method = $camera_auth->method;
        $user_name = $camera_auth->username;
        $realm = $camera_auth->rem;
        $password = $camera_auth->password;
        $nonce = $camera_auth->getNonce;
        $digest = md5((string)$user_name . ":" .(string)$realm. ":" . (string)$password);
        $partial_h2 = md5((string)$method .  ":");
        $simplified_ha2 = md5((string)$digest . ":" . (string)$nonce . ":" . (string)$partial_h2);
        $auth_digest = base64_encode((string)$user_name . ":" . (string)$nonce . ":" . (string)$simplified_ha2);
        return $auth_digest;
    }
}
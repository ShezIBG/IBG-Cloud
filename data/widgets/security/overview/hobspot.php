<?php

if (isset($_POST['submit'])) {

    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $hs_lead_status = $_POST['hs_lead_status'];
    
    $contact_data = array(
        "firstname" => $firstname,
        "lastname" => $lastname,
        "email" => $email,
        "phone" => $phone,
        "hs_lead_status" => $hs_lead_status
    );

    $ans_hubspot = new ans_hubspot();
    $ans_hubspot->contact_create($contact_data);
    //$ans_hubspot->list_create("Recovery Lead Generation");
    $ans_hubspot->list_assign_contact("2", $contact_data["phone"]);

}

class ans_hubspot {
    //private $hapikey = "59573404-c104-47a6-8f69-c935ed724410";
    //private $hapikey = "305ba431-650b-499e-8b41-9f9e056ffa5b";
    private $hapikey = "eu1-6eb9-a1e8-4149-ac81-77b0298ec4e3";

    function list_assign_contact($lid, $phone) {
        (object)$arr = array(
            "phone" => array(
                $phone
            )
        );
        $post_json = json_encode($arr);
        $endpoint = 'https://api.hubapi.com/contacts/v1/lists/' . $lid . '/add?hapikey=' . $this->hapikey;
        $this->http($endpoint, $post_json);
    }

    function list_create($list_name)  {
        $arr = array(
            "name" => $list_name,
            "dynamic" => false,
            "filters" => array(
                array(
                    (object)array(
                        "operator" => "EQ",
                        "value" => "@hubspot",
                        "property" => "twitterhandle",
                        "type" => "string"
                    )
                )
            )
        );
        $post_json = json_encode($arr);
        $endpoint = 'https://api.hubapi.com/contacts/v1/lists?hapikey=' . $this->hapikey;
        $this->http($endpoint, $post_json);
    }

    function contact_create($contact_data) {
        $arr = array(
            'properties' => array(
                array(
                    'property' => 'firstname',
                    'value' => $contact_data["firstname"]
                ) ,
                array (
                    'property' => 'lastname',
                    'value' => $contact_data["lastname"]
                ) ,
                array(
                    'property' => 'email',
                    'value' => $contact_data["email"]
                ) ,
                array(
                    'property' => 'phone',
                    'value' => $contact_data["phone"]
                ),
                array(
                    'property' => 'hs_lead_status',
                    'value' => $contact_data["hs_lead_status"]
                )

            )
        );

        $post_json = json_encode($arr);
        $endpoint = 'https://api.hubapi.com/contacts/v1/contact?hapikey=' . $this->hapikey;
        $this->http($endpoint, $post_json);
    }

    function http($endpoint, $post_json) {

        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
        echo "curl Errors: " . $curl_errors;
        echo "Status code: " . $status_code;
        echo "Response: " . $response;
        return $response . "
";

    }
}

// header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
 ?>

 <?php
 echo $firstname;
 echo '<br>';
 echo $lastname;
 echo '<br>';
 echo $email;
 ?>
<?php

class XeroAPI {


    public static function update_token(){
        $current_refreshToken = App::sql()->query_row("SELECT refresh_token FROM xero WHERE id = '1';");
        $old_token = $current_refreshToken->refresh_token;
        //print_r($old_token);
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://identity.xero.com/connect/token',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => 'grant_type=refresh_token&refresh_token='.$old_token.'',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Basic QUExNUQ4RTk3QjAyNDVFRThGNEEzREZDNzQxRTg4MEU6TTVqYTc5S29US25pRWluWnhYemtPSnBtd1JsZ0hIN0ZXN1RSMHp5cnUyWVZoLTJl',
            'Content-Type: application/x-www-form-urlencoded',
            'Cookie: _abck=4ED1B0E4418568F85E581B99F3685691~-1~YAAQTnERAhAxyYiKAQAAhhdGuAo91bQx5MKaeDczmENbdAvZl0t4XD8nolnqd+uJnaMDUahV01sqF5gk2E/tamQ1FD6FiHJMFpcJHZSfRsDWALgrn/si8aG+LKlnbBNO4viM9FO3lvGJd+9GE0btY/K4WAWgZ+kco8WYWQRF49+rDcQuW9qTkeJnF+hd7ytKcAarXZWzcQ/Av3Caa69G+jvJtHlMkLvr616K2vrNbsWWTyldfG5wVipyCH1/c5rQrdZ0CgGeX8YHP3R30fUlmyvrcx0gHjZ1rprZseWtyzU1mZaqq5LQsWxVXJ+g+cWJ7s22kthKujurU5WQbMY5mjqd51rDQe915Tu1~-1~-1~-1; ak_bmsc=DD200C590BC2E2E03EA08B1808AF9425~000000000000000000000000000000~YAAQXXERAmrIuraKAQAAntAkuBUuiC1rP6FZ2DceudjDIX8s9WVjXl7gXCSJv4c+LHZ5EG5y3Ea+ah9gkM4Xv8TKMQ/bjjRKusKRN+eZLPOoUvrPwAA8aAFXJp4g66tzlWwaf7b0NNU2qf1acNLgoJjDsKbbl487dhMeqMA7YMCJJSebU3eFIiWvNLxJU0/YXteanZcSmH5ZZAN7+g2XzNpKhLIYXXlhAc47p9Xu/aQXKg/juITaGBM/kYLGX2PBwbgnKY8QvRM3gtW6DDhua0KkkDeW4CS65FQbvegQwJJjeHk9hIrrcJelOnlBqOtQrzlyiMYUuBQjuPHiqqU+wo8WqGrkj6kfESrs0Ng4L+hCgjVj8W7iEDvz2A==; bm_sv=36C6731CF18F5F8B1EBBF6B2DC5FCE5F~YAAQTnERAhExyYiKAQAAhhdGuBUG493jjlM7IPV0tfM9HFCIp0HI2D8MBeP19n3QCwMt3htrjN4FV6ZgV9hZ3cbZiwBM0i7vnEjROLuAUO1LcQDp81dgt2MYWTSCKMYGkH6uhpBq2SZvuirhnAafavoGEEQGpIOscEWkIUShKJSXAgqznpgr0utXDXrdWsvcsbVArW057gfS8t74sPIlRmeSZN+jH6ZvDNJNzZR3JyXxKIo8WpXXPuCDF6dUwA0=~1; bm_sz=CE54F42AC19EA4AF35B4B43EC00C86C9~YAAQXXERAi6du7aKAQAAowAsuBWoIt5ZTE0bUI6XH5V7mY0XrQAPZgDRkdh8z4clLrxt8AgMdE+UuVwYacRU4SIBfBSJ4a+zB5WpYeLaKqMMTq4Ck2FmSWO3KVQh4ZP5MeVo0cE7A2MaCeM0SQjm4C0nOnWHzoMDpcnfOKVBPpMTNxVU5nPYfe9TniXO5NB+2Z+nYfh6vbJjzuSjkRJGjy7WLSl9+7zlWsuENLvm5OXkrQRX3pOR95VS4fo1WEFLcrdkoR0SNRJ3PcsOlyzZB5Zo5PnBEuPS6gmcPPYcgyheMKbMoWpTOBaj3V+bGiDq9lybYHJxgxNS~3556660~3491393; Device=54c6e36577f74e8b88423b2afa084fb2'
          ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        //echo $response;
        



        // Decode the JSON response into a PHP array
        $responseArray = json_decode($response, true);

        if ($responseArray !== null) {
        // Initialize an array to store contact names
            $refresh_token = $responseArray["refresh_token"];
            $access_token = $responseArray["access_token"];
            
            // print_r("Refresh: ".$refresh_token);
            // print_r("Access: ".$access_token);


            App::sql()->update("UPDATE xero SET refresh_token = '$refresh_token', access_token = '$access_token' WHERE id = '1';");
            
        }
        else{

            return false;
        }

        return $access_token;
    }


    public static function getcontacts(){

        $access_token = XeroAPI::update_token();
        
        $result = App::sql()->query_row("SELECT tenant_id FROM xero WHERE id = '1';");
        $tenant_id =  $result->tenant_id;
       
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.xero.com/api.xro/2.0/contacts',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.$access_token.'',
            'xero-tenant-id: '.$tenant_id.'',
            'Accept: application/json',
            'Content-Type: application/json',
            'Cookie: _abck=4ED1B0E4418568F85E581B99F3685691~-1~YAAQXXERAm7CpbaKAQAAHT5jtwq7YjEz8RZ7KRgetYL/v9nkTfMbzRJX2zGMShtzuq0i02JwiTiYZBtJZtWTUHC3BvoxLhT4tB9Zb8ZUCis6KlwFQFkwQ247AuUREO3PkNni+G7YbMLd76GB86xE0xkzv4i8pAFKRGKHB3yX2Iz1DN+CWsWCnwiL4qJID9Hh5VnXaJaXN1q7yYDLyHBXhsgnBIHHxWpleugDbia3m1FeqzMO7MaA253RdjnGfKWCss5NabPU8FnM04BVXHyqhrJXOTRyFbWoMZMm+zbECarNsCPfSWurrhEPUNZqwBX7k4Jw9svfM0KBnz9mhLtWB4FW9oqtfisJlXXPZokcY+siEd469G563r+vkLmb5IIpUvvkN08=~-1~-1~-1; ak_bmsc=4FA8549AA5D7CE53C9140D761CAA569C~000000000000000000000000000000~YAAQXXERAtDApbaKAQAAiixjtxW0eMVHdRNNQilbjaA5NCGfj7q/FvYj4hZaL0qJdA7rdBYbQFtBBOQfcQ3cABGTTkJOgNqENwchNFCKrQ+ihobk6DkOMh/4d0tcUTxKnv4XPywGr/xLEWBwhc2GFbggp5gy6VOhOdI43rfctKjxXeM43R0mm4iGlBcOFa6l6ylGBO9UyONZJrAhUtOH6GWvsy/CxL44Cm8nKgux1UXKvFSDfFCWGvW1mGTUU4H4ZFslXASiFCWFqet1IfkZUpzCfsOZz66mJQwh5ejWR+lnK26BH95Ue9GVpE4rtJ3iiBomV6fFtkNopcc1UJ5QhtmR95gP6Gspjj/Iu5ftRqECucW9zjyUJNSwWQ==; bm_sv=C6CBCAA88791B7684D9981C62095D96D~YAAQXXERAt+tpraKAQAARJ5stxUcQPHIdJQo7H0iIonWJMGVl5kAoTkIkq4TcbbKMM1lZH0q/k2FuUC/d7AEt51rHMKu80yr4PLzIX/BOWOtTKpjStwN9ELOw7ASg7QlDM5ot7XmuU8AQDyWyfFQ12/oC9AVGR7aazPInnOlk2VQM+woTuBdfke5Vm/JScZNHJgyBT4NcUT5w6hVduyImy4fJLiUY4WKUTUigRCxkueu9ZvwhrGFeAfX6N7yJ0s=~1; bm_sz=1C551AE4AD302A41E989E281971B8A4F~YAAQXXERAnDCpbaKAQAAHT5jtxXEh4atePu4ozy1qaoR2EFBmFizFg6du/ekdJX/HHOrPms/FFXk1K3KTl4ur044L4/TcGLjf+IvYI9sNx2pwxHxoHObIlLKRaUF0fMIJuW/7zkFknKLRZS8ZjE0qDRTSWtkKt36s9HELgDuAr/ukgYH1oxrDL+1ae0av3vZBfxjly0Qg7VroeFlgarqSsPqFi8cy4TcaiZC075Qg6SOsnf0sKvMLMKoSf/twqUQ2K4IqNOx4UefBJ1EXpN172vU7AICd2EV/kzIJMoZAzoN~3617847~4534837'
          ),
        ));
        
        $response = curl_exec($curl);
        
        curl_close($curl);
        
        // Decode the JSON response into a PHP array
        $responseArray = json_decode($response, true);
        
        if ($responseArray !== null) {
            // Initialize an array to store contact names
            $contactNames = [];
        
            // Accessing the "Contacts" field, which is an array of contact objects
            $contacts = $responseArray["Contacts"];
        
            // Looping through the contacts array
            foreach ($contacts as $contact) {
                // Accessing the "Name" field within each contact object and adding it to the $contactNames array
                $name = $contact["Name"];
                $contactNames[] = $name;
            }
        
            // Now $contactNames contains an array of contact names
            // You can use this array as needed
        
            // Print out the extracted contact names TESTING ONLY
            // foreach ($contactNames as $name) {
            //     echo "Contact Name: " . $name . "\n";
            // }
        
            
        
        } else {
            echo "Error decoding JSON response.";
        }
        
        return $contactNames;


    }


    public static function Create_Contact($contact){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.xero.com/api.xro/2.0/contacts',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
        "Name": '.$contact.'
        }',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer eyJhbGciOiJSUzI1NiIsImtpZCI6IjFDQUY4RTY2NzcyRDZEQzAyOEQ2NzI2RkQwMjYxNTgxNTcwRUZDMTkiLCJ0eXAiOiJKV1QiLCJ4NXQiOiJISy1PWm5jdGJjQW8xbkp2MENZVmdWY09fQmsifQ.eyJuYmYiOjE2OTUyOTQ0NjEsImV4cCI6MTY5NTI5NjI2MSwiaXNzIjoiaHR0cHM6Ly9pZGVudGl0eS54ZXJvLmNvbSIsImF1ZCI6Imh0dHBzOi8vaWRlbnRpdHkueGVyby5jb20vcmVzb3VyY2VzIiwiY2xpZW50X2lkIjoiQUExNUQ4RTk3QjAyNDVFRThGNEEzREZDNzQxRTg4MEUiLCJzdWIiOiIyZmIwNjEwMGExZWI1YzIyYWQxZjYzNGE1MzgwNTZiYSIsImF1dGhfdGltZSI6MTY5NTI5NDM2NiwieGVyb191c2VyaWQiOiIwMDViZmZkMy0yNDA5LTRlZGItOGZhNS0xOWNiMjMxZWFiNDIiLCJnbG9iYWxfc2Vzc2lvbl9pZCI6IjM0YmY4M2VlZDc0ZjRmZDViMDhmYjliNGQwMzllMzllIiwic2lkIjoiMzRiZjgzZWVkNzRmNGZkNWIwOGZiOWI0ZDAzOWUzOWUiLCJqdGkiOiIyNkFGRkU4RTYxNzIxN0NFQ0VCOEM2QjVGMTdBOTE1MyIsImF1dGhlbnRpY2F0aW9uX2V2ZW50X2lkIjoiY2ViODYxMGMtMDVhYy00NmE0LWFiODUtNWU4OTY2MzhmODgxIiwic2NvcGUiOlsiZW1haWwiLCJwcm9maWxlIiwib3BlbmlkIiwiYWNjb3VudGluZy50cmFuc2FjdGlvbnMiLCJhY2NvdW50aW5nLmNvbnRhY3RzIiwiYWNjb3VudGluZy5jb250YWN0cy5yZWFkIiwib2ZmbGluZV9hY2Nlc3MiXSwiYW1yIjpbInB3ZCIsIm1mYSIsIm90cCJdfQ.OakezhcrGF9WhKsgA_JgGAyDLwmeqREZC7klyxzkLyPKL4X2wbqfk1x0IAODa3ZPTJcqU8fhMoiJBAYAXFlFW323bZ1_i0izxiPtfQJsh0KDvGR6zf1zkn4EG8qkQ1YkEEhuaeUNHR75tL1lVhFeQN2UnpASoaKwVn6tu2Rfux5q2Y5ylhbkRcV3KFuYM29Y1LtqE8xnANrRmp1qrVZi4ry18Rvvse0wlM5y4TZVayaBMS5C-hOFZtvn9f_JFZqNjsJ9Vt68dmMick-XN0mhgIgkJZzp47CwS8VRn7cpMMmvLB1QXelRFNQllT7XjhoN1hMgVOZWt4wtYY3jZqMt8A',
            'Xero-Tenant-Id: 8dc1ed63-3561-4b36-a72f-265b376ade9e',
            'Accept: application/json',
            'Content-Type: text/plain',
            'Cookie: _abck=4ED1B0E4418568F85E581B99F3685691~-1~YAAQXXERAm7CpbaKAQAAHT5jtwq7YjEz8RZ7KRgetYL/v9nkTfMbzRJX2zGMShtzuq0i02JwiTiYZBtJZtWTUHC3BvoxLhT4tB9Zb8ZUCis6KlwFQFkwQ247AuUREO3PkNni+G7YbMLd76GB86xE0xkzv4i8pAFKRGKHB3yX2Iz1DN+CWsWCnwiL4qJID9Hh5VnXaJaXN1q7yYDLyHBXhsgnBIHHxWpleugDbia3m1FeqzMO7MaA253RdjnGfKWCss5NabPU8FnM04BVXHyqhrJXOTRyFbWoMZMm+zbECarNsCPfSWurrhEPUNZqwBX7k4Jw9svfM0KBnz9mhLtWB4FW9oqtfisJlXXPZokcY+siEd469G563r+vkLmb5IIpUvvkN08=~-1~-1~-1; ak_bmsc=4FA8549AA5D7CE53C9140D761CAA569C~000000000000000000000000000000~YAAQXXERAtDApbaKAQAAiixjtxW0eMVHdRNNQilbjaA5NCGfj7q/FvYj4hZaL0qJdA7rdBYbQFtBBOQfcQ3cABGTTkJOgNqENwchNFCKrQ+ihobk6DkOMh/4d0tcUTxKnv4XPywGr/xLEWBwhc2GFbggp5gy6VOhOdI43rfctKjxXeM43R0mm4iGlBcOFa6l6ylGBO9UyONZJrAhUtOH6GWvsy/CxL44Cm8nKgux1UXKvFSDfFCWGvW1mGTUU4H4ZFslXASiFCWFqet1IfkZUpzCfsOZz66mJQwh5ejWR+lnK26BH95Ue9GVpE4rtJ3iiBomV6fFtkNopcc1UJ5QhtmR95gP6Gspjj/Iu5ftRqECucW9zjyUJNSwWQ==; bm_sv=C6CBCAA88791B7684D9981C62095D96D~YAAQXXERAmXapbaKAQAARF9ktxWrMi77Dc+qrGVJwKjwye++tR8rcq3DBea9cAugNR6ERMfEEFqX0UqCFvO/UpZreRmqJq6dXyZ1XqPkin2fYvRw1uJM5D71zX2iSptXmtyeuXFk3ViNJqgYdcVifFASOfWNaZRrgDIvyebwKC0uHMVV0vw/l/zrQzIvw+wgSEXK00X2+letH6Wc2I6gjVnfNg5gK6PknNLXpSX/Y+FmcYB9mvLfMWfElvjrqQ==~1; bm_sz=1C551AE4AD302A41E989E281971B8A4F~YAAQXXERAnDCpbaKAQAAHT5jtxXEh4atePu4ozy1qaoR2EFBmFizFg6du/ekdJX/HHOrPms/FFXk1K3KTl4ur044L4/TcGLjf+IvYI9sNx2pwxHxoHObIlLKRaUF0fMIJuW/7zkFknKLRZS8ZjE0qDRTSWtkKt36s9HELgDuAr/ukgYH1oxrDL+1ae0av3vZBfxjly0Qg7VroeFlgarqSsPqFi8cy4TcaiZC075Qg6SOsnf0sKvMLMKoSf/twqUQ2K4IqNOx4UefBJ1EXpN172vU7AICd2EV/kzIJMoZAzoN~3617847~4534837'
        ),

        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;


    }


}


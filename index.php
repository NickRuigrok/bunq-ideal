<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Document</title>
    </head>
    <body>
        <form id="form" method="post" action="processing.php">
            <input type="number" step="1" min="1" name="pid" placeholder="product_id"/>
            <input type="number" min="0.01" max="10000" step="0.01" name="amount" placeholder="price" required/>
            <select id="issuer" name="issuer" required>
                <option value="" disabled="" selected="">Select a bank</option>
            </select>
            <input type="submit" id="submit" name="submit" value="Pay"/>
        </form>
        <script>
            function guid() {
                function s4() {
                    return Math.floor((1 + Math.random()) * 0x10000)
                        .toString(16)
                        .substring(1);
                }
                return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
                    s4() + '-' + s4() + s4() + s4();
            }

            var selectIssuer = document.getElementById("issuer");

            function getIssuers() {
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'https://api.bunq.me/v1/bunqme-merchant-directory-ideal');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            var jsonResponse = JSON.parse(xhr.responseText);
                            jsonResponse['Response'][0]['IdealDirectory']['country'][0]['issuer'].forEach(function(value) {
                                var issuer = document.createElement("option");
                                issuer.text = value.name;
                                issuer.value = value.bic;
                                selectIssuer.add(issuer);
                            });
                        }
                        else {
                            console.log('Request failed.  Returned status of ' + xhr.status);
                        }
                    };
                    xhr.setRequestHeader('X-Bunq-Client-Request-Id', guid());
                    xhr.send();
                }
                getIssuers();
        </script>
    </body>
</html>
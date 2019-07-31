<?php
    // error_reporting(-1);

    // function definitons -------------------------------------------------------------
    function getDetailsJSON($place_id, $locName, $apiKEY) {

        $detailsQuery = "https://maps.googleapis.com/maps/api/place/details/json?placeid=" . $place_id . "&key=" . $apiKEY;
        $jsonStrDetails = file_get_contents($detailsQuery);
        $jsonObjDetails = json_decode($jsonStrDetails, true);

        $reviewsAry = [];
        $photosAry = [];

        $rCount = 0;
        if(isset($jsonObjDetails['result']['reviews'])) {
            while($rCount < count($jsonObjDetails['result']['reviews']) && $rCount < 5) {
                array_push($reviewsAry, [
                    'author_name' => $jsonObjDetails['result']['reviews'][$rCount]['author_name'],
                    'profile_photo_url' => $jsonObjDetails['result']['reviews'][$rCount]['profile_photo_url'],
                    'text' => $jsonObjDetails['result']['reviews'][$rCount]['text']
                ]);
                $rCount++;
            }    
        }
        

        $pCount = 0;
        if(isset($jsonObjDetails['result']['photos'])) {
            while($pCount < count($jsonObjDetails['result']['photos']) && $pCount < 5) {

                $picRef = $jsonObjDetails['result']['photos'][$pCount]['photo_reference'];
                $picQuery = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=2000&photoreference=" . $picRef . "&key=" . $apiKEY;
                $picData = file_get_contents($picQuery);
                $filePath = "img" . $pCount . ".png";
                file_put_contents($filePath, $picData);

                array_push($photosAry, [

                    'pic_href' => $filePath
                ]);
                $pCount++;
            }    
        }
        
        $jsonAry  = array(
            "REVIEWS" => $reviewsAry,
            "PHOTOS" => $photosAry,
            "NAME" => $locName
        );
        $json = json_encode($jsonAry);
        echo $json;
        exit();
    }

    // variables -------------------------------------------------------------------
    $keyword = isset($_POST['keyword']) ? $_POST['keyword'] : "";
    $category = isset($_POST['category']) ? $_POST['category'] : "";
    $distance = isset($_POST['distance']) ? $_POST['distance'] : "";
    $radio = isset($_POST['radio']) ? $_POST['radio'] : "";
    $locationFromForm = isset($_POST['location']) ? $_POST['location'] : "";
    $hereLoc = isset($_POST['hereLoc']) ? $_POST['hereLoc'] : "";
    $location = "";
    $locationAry = [];
    // $apiKEY = "Insert Your API KEY Here";

    // driver -----------------------------------------------------------------------
    if(isset($_POST['keyword'])) {

        if($radio == "location") {
            $geoQuery = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($locationFromForm) . "&key=" . $apiKEY;
            $jsonStrGeo = file_get_contents($geoQuery);
            $jsonObjGeo = json_decode($jsonStrGeo, true);
            $location = $jsonObjGeo['results'][0]['geometry']['location']['lat'] . "," . $jsonObjGeo['results'][0]['geometry']['location']['lng'];
            array_push($locationAry, [
                'lat' => $jsonObjGeo['results'][0]['geometry']['location']['lat'],
                'lng' => $jsonObjGeo['results'][0]['geometry']['location']['lng']
            ]);
        }
        else {
            $location = $hereLoc;
            $commaPosition = strpos($location, ",");
            $lat = substr($location, 0, $commaPosition);
            $lng = substr($location, $commaPosition+1);

            array_push($locationAry, [
                'lat' => $lat,
                'lng' => $lng
            ]);
        }

        // miles to meters conversion
        $radius = $distance * 1609.34;
        $nearbyQuery = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=" . $location . "&radius=" . $radius . "&type=" . urlencode($category) . "&keyword=" . urlencode($keyword) . "&key=" . $apiKEY;
        $jsonStrNearby = file_get_contents($nearbyQuery);
        $jsonObjNearby = json_decode($jsonStrNearby, true);
        $finalJSON = array(
            "SEARCH" => $jsonObjNearby,
            "START_LOC" => $locationAry
        );

        if(!array_key_exists('error_message', $jsonObjNearby)) {
            $nearbyJSON = json_encode($finalJSON);
        }
        else {
            $failedAry = array(
                "FAILED" => "Google Places Nearby Search Failed" 
            );
            $nearbyJSON = json_encode($failedAry);
        }
    }
    if(isset($_GET['place_id']) && isset($_GET['name'])) {

        $place_id = $_GET['place_id'];
        $locName = $_GET['name'];
        getDetailsJSON($place_id, $locName, $apiKEY);
    }
?>
<!doctype html>
<html class="" lang="">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Places Search</title>
        <base target="_blank">
    <style>
    /*CSS1 ============================================================================================================================*/
        a {
            color: #3745ec;
            text-decoration: none;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: 500;
        }
        input[type="submit"]:disabled {
            background-color: #f5f5f5;
        }
        button:active {
            background-color: #61adfa;
        }
        #reviews-arrow-img, #photos-arrow-img {
            display: block;
            margin-left: auto;
            margin-right: auto;
            margin-top: -1.75em;
            padding-bottom: 0.5em;
            width: 35px;
        }
        #reviews-button-text, #photos-button-text {
            color: #0a000a;
            font-family: Arial, Helvetica, sans-serif;
            text-align: center;
        }        
        .button-container {
            margin-top: 1em;
            padding-top: 2em;
            padding-bottom: 1em;
            position: relative;
            left: 3em;
        }
        fieldset {
            border-color: #FFFFFF;
        }
        .form-container {
            background-color: #f5f5f5;
            top: 0%;
            left: 50%;
            margin-top: 0;
            margin-left: auto;
            margin-right: auto;
            text-align: left;
            max-width: 700px;
            width: 65%;
        }
        #form-title {
            margin-top: -25px;
            font-style: italic;
            text-align: center;
        }
        #form-title h1 {
            border-bottom: 1px solid #d7d7d7;
            border-bottom-width: thin;
        }
        input[type=submit], input[type=reset] {
            width: 60px;
        }
        .empty-row {
            background-color: #f0f0f0;
            font-weight: 600;
            text-align: center;
        }
        #results-container {
            padding-top: 2em;
        }
        th, tr, td  {
            border-color: #dddddd;
            border-style: solid;
            border-width: 2px;
        }
        #results-tbl {
            border-collapse: collapse;
            border-color: #dddddd;
            border-style: solid;
            border-width: 3px;
            margin-left: auto;
            margin-right: auto;
            width: 70%;
        }

        .name-address-cell {
            padding-left: 1em;
        }

        .name-address-cell:hover {
            cursor: pointer;
        }

        .details-tbls {
            display: none;
            border-collapse: collapse;
            border-color: #dddddd;
            border-style: solid;
            border-width: 3px;
            margin-left: auto;
            margin-right: auto;
            max-width: 700px;
            width: 65%;
        }

        .photo-imgs {
            max-width: 670px;
            padding: 0.75em;
        }

        #place-name-title {
            
            font-size: 1.25em;
            font-weight: 600;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }

        .author-imgs {

            width: 2.25em;
        }

        .author-row {
            text-align: center;
        }

        .author-name {
            font-weight: 600;
        }

        .category-imgs {
            width: 3em;
        }

        .inline-divs {
            display: inline-block;
        }

        .gmap {
            background-color: grey;
            display: none;
            position: absolute;
            margin-top: .5em;
            height: 450px;
            width: 450px;
        }

        .gmap-mode-tbl {
            background-color: #efefef;
            border-collapse: collapse;
            border-style: none;
            display: none;
            position: absolute;
            margin-top: 0.5em;
            text-align: center;
            width: 100px;
        }

        .gmap-mode-tbl tr {
            border-collapse: collapse;
            border-style: none;
            width: 100%;
        }

        .gmap-mode-tbl-cell {
            border-style: none;
            padding: 0.5em;
            text-align: center;
            width: 100%;
        }

        .gmap-mode-tbl tr:hover {
            background-color: #d7d7d7;
            cursor: pointer;
        }

        span {
            cursor: pointer;
        }

        #radio-container {
            top: 1em;
            margin-left: 0.5em;
            position: relative;
        }

        .form-contents {
            margin-top: 0.5em;
            font-weight: 600;
        }

        /*CSS2 =================================================================================================================================*/
    </style>
    </head>
    <body>
        <!-- HTML1 -->
        <!--  HTML-Body --> 
        <div class="form-container">
            <form id="search-form" action="places.php" method="post" target="_self">
                <fieldset>
                    <div id="form-title">
                        <h1>Travel and Entertainment Search</h1>
                    </div>
                    <div class="form-contents">
                        Keyword <input id="keyword-field" type="text" form="search-form" name="keyword" required autofocus>
                    </div>
                    <div class="form-contents">
                        Category 
                        <select id="category-dropdown" name="category" form="search-form">
                            <option value="default" selected>default</option>
                            <option value="cafe">cafe</option>
                            <option value="bakery">bakery</option>
                            <option value="restaurant">restaurant</option>
                            <option value="beauty salon">beauty salon</option>
                            <option value="casino">casino</option>
                            <option value="movie theater">movie theater</option>
                            <option value="lodging">lodging</option>
                            <option value="airport">airport</option>
                            <option value="train station">train station</option>
                            <option value="subway station">subway station</option>
                            <option value="bus station">bus station</option>
                        </select>
                    </div>
                    <div id="distance-div" class="inline-divs form-contents">
                        Distance (miles) 
                        <input id="distance-field" type="text" form="search-form" name="distance" placeholder="10" value="">
                         from
                    </div> 
                    <div id="radio-container" class="inline-divs">
                        <input id="radio-here" type="radio" form="search-form" name="radio" value="here" checked>
                        <label for="radio-here">Here</label>
                        <input id="radio-here-hidden-field" type="hidden" name="hereLoc" value="">
                        <br>
                        <input id="radio-location" type="radio" form="search-form" name="radio" value="location">

                        <input id="radio-location-field" type="text" form="search-form" name="location" value="" placeholder="location" disabled>    
                    </div>
                         
                    <div class="button-container">
                        <span class="search-button">
                            <input id="search" type="submit" value="Search"/ disabled>
                        </span>
                        <span class="clear-button">
                            <input id="clear" type="reset" value="Clear" onclick="return clearResults();"/>
                        </span>
                    </div>
                </fieldset>
            </form>
        </div>
        <div id="results-container"></div>
        <div id="reviews-container" class="details-divs"></div>
        <br>
        <div id="photos-container" class="details-divs"></div>
        
        <script>
            // JS1
            // global variables -------------------------------------------------
            let userLocation = "";
            let providedLocation = "";

            // functions --------------------------------------------------------
            function buildPhotos(json) {
                let buttonCont = document.createElement("div");
                buttonCont.setAttribute("id", "photos-button-container");
                buttonCont.setAttribute("onclick", "togglePhotos()");
                document.querySelector('#photos-container').appendChild(buttonCont);
                
                let text = document.createElement("p");
                text.setAttribute("id", "photos-button-text");
                text.insertAdjacentHTML('beforeend', "click to show photos");

                let br = document.createElement("br");

                let img = document.createElement("img");
                img.setAttribute("id", "photos-arrow-img");
                img.setAttribute("src", "./imgs/gray_arrow_down.png");

                buttonCont.appendChild(text);
                buttonCont.appendChild(br);
                buttonCont.appendChild(img);

                let table = document.createElement("table");
                table.setAttribute("class", "details-tbls");
                table.setAttribute("id", "photos-tbl");
                document.querySelector('#photos-container').appendChild(table);

                let len = json.PHOTOS.length;
                if(len > 0) {
                    buildPhotosRows(table, json);
                }
                else {
                    let msg = 'No Photos Found';
                    showEmptyTable(table, msg);
                }
            }

            function buildPhotosRows(table, json) {
                let len = json.PHOTOS.length;

                for(i=0; i<len; i++) {
                    let tr = table.insertRow(-1);
                    let td = tr.insertCell(-1);
                    td.insertAdjacentHTML('beforeend', '<a href="' + json.PHOTOS[i].pic_href + '" target="_blank"><img class="photo-imgs" src="' + json.PHOTOS[i].pic_href + '"></a>');
                    tr.appendChild(td);    
                }
                
            }

            function buildReviews(json) {
                console.log(json.NAME);
                clearResultsTable();
                showPlaceName(json);

                let buttonCont = document.createElement("div");
                buttonCont.setAttribute("id", "reviews-button-container");
                buttonCont.setAttribute("onclick", "toggleReviews()");
                document.querySelector('#reviews-container').appendChild(buttonCont);
                
                let text = document.createElement("p");
                text.setAttribute("id", "reviews-button-text");
                text.insertAdjacentHTML('beforeend', "click to show reviews");

                let br = document.createElement("br");

                let img = document.createElement("img");
                img.setAttribute("id", "reviews-arrow-img");
                img.setAttribute("src", "./imgs/gray_arrow_down.png");

                buttonCont.appendChild(text);
                buttonCont.appendChild(br);
                buttonCont.appendChild(img);
                
                let table = document.createElement("table");
                table.setAttribute("class", "details-tbls");
                table.setAttribute("id", "reviews-tbl");
                document.querySelector('#reviews-container').appendChild(table);

                let len = json.REVIEWS.length;
                if(len > 0) {
                    buildReviewsRows(table, json);
                }
                else {
                    let msg = 'No Reviews Found';
                    showEmptyTable(table, msg);
                }
            }
            function buildReviewsRows(table, json) {
                console.log("in buildReviewsRows");

                let len = json.REVIEWS.length;
                for(i=0; i<len; i++) {

                    let tr1 = table.insertRow(-1);
                    let td1 = tr1.insertCell(-1);
                    td1.setAttribute("class", "author-row")
                    td1.insertAdjacentHTML('beforeend', '<img class="author-imgs" src="' + json.REVIEWS[i].profile_photo_url + '">' + '<span class="author-name">        ' + json.REVIEWS[i].author_name + '</span>');
                    tr1.appendChild(td1);

                    let tr2 = table.insertRow(-1);
                    let td2 = tr2.insertCell(-1);
                    td2.insertAdjacentHTML('beforeend', json.REVIEWS[i].text);
                    tr2.appendChild(td2);
                }
            }

            function buildTable(nearbyJSON) {

                let table = document.createElement("table");
                table.setAttribute("id", "results-tbl");
                document.querySelector('#results-container').appendChild(table);

                jsonAry = JSON.parse(nearbyJSON);
                console.log(jsonAry);
                if(!(jsonAry.hasOwnProperty('FAILED'))) {
                    
                    let len = jsonAry.SEARCH.results.length;
                    if(len > 0) {
                        buildTblHeader(table);
                        buildTblRows(table, jsonAry, len);
                    }
                    else {
                        let msg = 'No Records have been found';
                        showEmptyTable(table, msg);
                    }    
                }
                else {
                    let msg = "Error: Google Places Nearby Search Failed";
                    showEmptyTable(table, msg);
                } 
            }

            function buildTblHeader(table) {
                let tr = table.insertRow(-1);
                tr.setAttribute("id", "header-row");
                let headers = ['Category', 'Name', 'Address'];
                for(i=0; i<headers.length; i++) {
                    let th = document.createElement("th");
                    th.insertAdjacentHTML('beforeend', headers[i]);
                    tr.appendChild(th);
                }
            }

            function buildTblRows(table, jsonAry, len) {
                let icons = [];
                let names = [];
                let place_ids = [];
                let vicinities = [];
                let endLats = [];
                let endLngs = [];
                let headers = ['Category', 'Name', 'Address'];
                let results = jsonAry.SEARCH.results;
                let startLocLat = jsonAry.START_LOC[0].lat;
                let startLocLng = jsonAry.START_LOC[0].lng;

                for(i=0; i<len; i++) {

                    icons.push(results[i].icon);
                    names.push(results[i].name);
                    place_ids.push(results[i].place_id);
                    vicinities.push(results[i].vicinity);
                    endLats.push(results[i].geometry.location.lat);
                    endLngs.push(results[i].geometry.location.lng);
                }

                for(j=0; j<len; j++) {

                    let tr = table.insertRow(-1);
                    for(k=0; k<headers.length; k++) {
                        let td = tr.insertCell(-1);
                        if(headers[k] == "Category") {

                            td.insertAdjacentHTML('beforeend', '<img class="category-imgs" src="' + icons[j] + '" alt="category image">');
                        }
                        else if(headers[k] == "Name") {

                            td.setAttribute("class", "name-address-cell");
                            let name = names[j]; 
                            let place_id = place_ids[j];
                            td.insertAdjacentHTML('beforeend', name);
                            
                            // console.log("place id " + place_id);
                            
                            td.onclick = placeIdClosure(place_id, name);
                        }
                        else {

                            td.setAttribute("class", "name-address-cell");
                            let mapID = "map" + j;
                            let modeTblNum = 'mode-tbl-' + j;

                            let sp = document.createElement("span");
                            sp.insertAdjacentHTML('beforeend', vicinities[j]);
                            sp.onclick = toggleMapClosure(mapID, modeTblNum, endLats[j], endLngs[j]);
                            td.appendChild(sp);
                            
                            let br = document.createElement("br");
                            td.appendChild(br);

                            let mapContainer = document.createElement("div");
                            mapContainer.setAttribute("class", "gmap");
                            mapContainer.setAttribute("id", mapID);
                            mapContainer.setAttribute("style", "display:none;");
                            
                            let modeTbl = document.createElement("table");
                            modeTbl.setAttribute("class", "gmap-mode-tbl");
                            modeTbl.setAttribute("id", modeTblNum);
                            modeTbl.setAttribute("style", "display:none;");
                            let walkRow = modeTbl.insertRow(-1);
                            let walkCell = walkRow.insertCell(-1);
                            walkCell.setAttribute("class", "gmap-mode-tbl-cell");

                            let bikeRow = modeTbl.insertRow(-1);
                            let bikeCell = bikeRow.insertCell(-1);
                            bikeCell.setAttribute("class", "gmap-mode-tbl-cell");

                            let driveRow = modeTbl.insertRow(-1);
                            let driveCell = driveRow.insertCell(-1);
                            driveCell.setAttribute("class", "gmap-mode-tbl-cell");

                            walkCell.insertAdjacentHTML('beforeend', 'Walk there');
                            bikeCell.insertAdjacentHTML('beforeend', 'Bike there');
                            driveCell.insertAdjacentHTML('beforeend', 'Drive there');
                            walkCell.onclick = calcRouteClosure(mapID, "WALKING", startLocLat, startLocLng, endLats[j], endLngs[j]);
                            bikeCell.onclick = calcRouteClosure(mapID, "BICYCLING", startLocLat, startLocLng, endLats[j], endLngs[j]);
                            driveCell.onclick = calcRouteClosure(mapID, "DRIVING", startLocLat, startLocLng, endLats[j], endLngs[j]);

                            td.appendChild(mapContainer);
                            td.appendChild(modeTbl);
                        }
                        tr.appendChild(td);
                    }
                }
            }

            function buildMap(mapID, lat, lng) {
                // console.log("Called buildMap for " + mapID);
                directionsService = new google.maps.DirectionsService();
                directionsDisplay = new google.maps.DirectionsRenderer();
                let endLoc = {lat: lat, lng: lng};
                let map = new google.maps.Map(document.getElementById(mapID), {

                    center: endLoc,
                    zoom: 12
                });
                let marker = new google.maps.Marker({
                    position: endLoc,
                    map: map
                });
                directionsDisplay.setMap(map);
            }

            function calcRoute(mapID, selectedMode, startLat, startLng, endLat, endLng) {
                directionsService = new google.maps.DirectionsService();
                directionsDisplay = new google.maps.DirectionsRenderer();
                let endLoc = {lat: endLat, lng: endLng};
                let map = new google.maps.Map(document.getElementById(mapID), {

                    center: endLoc,
                    zoom: 12
                });
                directionsDisplay.setMap(map);

                let start = new google.maps.LatLng(startLat, startLng);
                let end = new google.maps.LatLng(endLat, endLng);
                let request = {
                    origin: start,
                    destination: end,
                    travelMode: google.maps.TravelMode[selectedMode]
                };
                directionsService.route(request, function(response, status) {
                    if(status == 'OK') {
                        directionsDisplay.setDirections(response);
                    }
                });
            }

            function calcRouteClosure(mapID, selectedMode, startLoc, startLng, endLoc, endLng) {
                return function() {
                    console.log("called calcRouteClosure");
                    calcRoute(mapID, selectedMode, startLoc, startLng, endLoc, endLng);
                }
            }

            function clearResults() {
                let keywordField = document.querySelector('#keyword-field');
                keywordField.value = "";

                let distanceField = document.querySelector('#distance-field');
                distanceField.value = "";

                let locationField = document.querySelector('#radio-location-field');
                locationField.value = "";
                disableLocationField();
                clearResultsTable();
                clearDetails();
                return true;
            }

            function clearResultsTable() {
                let tblData = document.querySelector('#results-tbl');
                if(tblData) {
                    tblData.parentNode.removeChild(tblData);
                }
            }

            function clearDetails() {
                let title = document.querySelector('#place-name-title');
                if(title) {
                    title.parentNode.removeChild(title);
                }
                let reviewsButton = document.querySelector('#reviews-button-container');
                if(reviewsButton) {
                    reviewsButton.parentNode.removeChild(reviewsButton);
                }
                let reviewsTbl = document.querySelector('#reviews-tbl');
                if(reviewsTbl) {
                    reviewsTbl.parentNode.removeChild(reviewsTbl);
                }
                let photosButton = document.querySelector('#photos-button-container');
                if(photosButton) {
                    photosButton.parentNode.removeChild(photosButton);
                }
                let photosTbl = document.querySelector('#photos-tbl');
                if(photosTbl) {
                    photosTbl.parentNode.removeChild(photosTbl);
                }
            }

            function disableLocationField () {
                let radioLocation = document.querySelector('#radio-location-field');
                radioLocation.disabled = true;
                radioLocation.required = false;
            }

            function enableLocationField () {
                console.log("enableLocationField called");
                let radioLocation = document.querySelector('#radio-location-field');
                radioLocation.disabled = false;
                radioLocation.required = true;
            }

            function getHereLocation() {
                let button = document.getElementById('search');
                let api = "http://ip-api.com/json";
                requestJSON(api, function(response) {

                    let locObj = JSON.parse(response);
                    userLocation = locObj["lat"] + "," + locObj["lon"];
                    button.disabled = false;
                    console.log("Location: " + userLocation);
                });
                console.log("getHerelocation called");
            }

            function requestDetails(response) {
                console.log("called requestDetails");
                console.log(response);
                // console.log("here: " + response);
                // let index = response.lastIndexOf('{"REVIEWS');
                // let editedResponse = response.slice(index);
                // console.log(editedResponse);
                let detailsJSON = JSON.parse(response);
                console.log(detailsJSON);
                buildReviews(detailsJSON);
                buildPhotos(detailsJSON);
            }

            function requestJSON(api, callback) {
                let request = new XMLHttpRequest();
                request.open("GET", api, true);
                request.onload = function(){
                    callback(request.responseText);
                }
                request.send();
            }

            function restoreData() {
                if(sessionStorage.keyword && sessionStorage.category 
                                          && sessionStorage.distance) {
                    document.querySelector('#keyword-field').value = sessionStorage.getItem('keyword');
                    document.querySelector('#category-dropdown').value = sessionStorage.getItem('category');
                    document.querySelector('#distance-field').value = sessionStorage.getItem('distance');

                    let hereRadioUsed = sessionStorage.getItem('hereRadio');
                    if(hereRadioUsed === 'true') {
                        document.querySelector('#radio-here').checked = true;
                    }
                    else {
                        enableLocationField();
                        document.querySelector('#radio-location').checked = true;
                        document.querySelector('#radio-location-field').value = sessionStorage.getItem('locText');
                    }
                    sessionStorage.clear();
                }
            }

            function storeData() {
                if(typeof(Storage) !== "undefined") {

                    let keyword = document.querySelector('#keyword-field').value;
                    let category = document.querySelector('#category-dropdown').value;
                    let distance = document.querySelector('#distance-field').value;
                    let hereRadio = document.querySelector('#radio-here').checked;
                    let locRadio = document.querySelector('#radio-location').checked;
                    let locText = document.querySelector('#radio-location-field').value;

                    sessionStorage.setItem('keyword', keyword);
                    sessionStorage.setItem('category', category);
                    sessionStorage.setItem('distance', distance);
                    sessionStorage.setItem('hereRadio', hereRadio);
                    sessionStorage.setItem('locRadio', locRadio);
                    sessionStorage.setItem('locText', locText);
                }
            }

            function showPlaceName(json) {
                let reviewsCont = document.querySelector('#reviews-container');
                let name = document.createElement("p");
                name.setAttribute("id", "place-name-title");
                name.insertAdjacentHTML('beforeend', json.NAME);
                reviewsCont.appendChild(name);
            }

            function placeIdClosure(place_id, name) {
                return function() {
                    let query = "?place_id=" + place_id + "&name=" + name;
                    requestJSON(query, function(response) {

                        requestDetails(response);
                    });
                }
            }

            function showEmptyTable(table, msg) {
                let tr = table.insertRow(-1);
                let td = tr.insertCell(-1);
                td.setAttribute("class", "empty-row");
                td.insertAdjacentHTML('beforeend', msg);
                tr.appendChild(td);
            }

            function submitForm() {
                let distanceField = document.querySelector('#distance-field');
                if(distanceField.value == "") {
                    distanceField.value = 10;
                }

                let radioButton = document.querySelector('#radio-here');
                if(radioButton.checked) {
                    // enableLocationField();
                    let radioLocation = document.querySelector('#radio-here-hidden-field');
                    radioLocation.value = userLocation;
                    console.log("Called from submitForm " + userLocation);
                }
                storeData();
            }

            function toggleMapClosure(mapID, modeTblNum, endLat, endLng) {
                return function() {
                    console.log("toggleMapClosure called");
                    toggleMap(mapID, modeTblNum, endLat, endLng);
                }
            }

            function toggleMap(mapID, modeTblNum, endLat, endLng) {
                let mapCont = document.querySelector('#' + mapID);
                let modeTbl = document.querySelector('#' + modeTblNum);
                if(mapCont.style.display == 'none') {
                    buildMap(mapID, endLat, endLng);
                    mapCont.style.display = 'block';
                    modeTbl.style.display = 'table';
                }                
                else {
                    mapCont.style.display = 'none';
                    modeTbl.style.display = 'none';
                }
            }

            function toggleReviews() {
                console.log("called toggleReviews");
                let photoText = document.querySelector('#photos-button-text');
                if(photoText.innerHTML == "click to hide photos") {
                    togglePhotos();
                }

                var text = document.querySelector('#reviews-button-text');
                var arrow = document.querySelector('#reviews-arrow-img');
                if(text.innerHTML == "click to show reviews") {
                    text.innerHTML = "click to hide reviews";
                    arrow.outerHTML = "<img id=\"reviews-arrow-img\" src=\"./imgs/gray_arrow_up.png\" />";
                    document.getElementById("reviews-tbl").style.display = "table";
                }
                else {
                    text.innerHTML = "click to show reviews";
                    arrow.outerHTML = "<img id=\"reviews-arrow-img\" src=\"./imgs/gray_arrow_down.png\" />";
                    document.getElementById("reviews-tbl").style.display = "none";
                }
            }

            function togglePhotos() {
                console.log("called togglePhotos");
                let reviewsText = document.querySelector('#reviews-button-text');
                if(reviewsText.innerHTML == "click to hide reviews") {
                    toggleReviews();
                }

                var text = document.querySelector('#photos-button-text');
                var arrow = document.querySelector('#photos-arrow-img');
                if(text.innerHTML == "click to show photos") {
                    text.innerHTML = "click to hide photos";
                    arrow.outerHTML = "<img id=\"photos-arrow-img\" src=\"./imgs/gray_arrow_up.png\" />";
                    document.getElementById("photos-tbl").style.display = "table";
                }
                else {
                    text.innerHTML = "click to show photos";
                    arrow.outerHTML = "<img id=\"photos-arrow-img\" src=\"./imgs/gray_arrow_down.png\" />";
                    document.getElementById("photos-tbl").style.display = "none";
                }
            }

            window.onload = restoreData();
            window.onload = getHereLocation();
            document.querySelector('#radio-here').addEventListener("click", disableLocationField);
            document.querySelector('#radio-location').addEventListener("click", enableLocationField);
            document.querySelector('#search-form').addEventListener("submit", submitForm);
            
            var nearbyJSON = <?php echo isset($nearbyJSON) ? json_encode($nearbyJSON) : '\'nil\'';?>;
            if(nearbyJSON !== 'nil') {
                buildTable(nearbyJSON);    
            }
            // JS2
        </script>
        <!-- <script async defer src="https://maps.googleapis.com/maps/api/js?key=Insert Your API Key Here"></script> -->
    </body>
</html>

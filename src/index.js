jQuery(document).ready(function($) {

    const CITIES = cityData.dests;
    const SOURCE = cityData.source; 

    const BATCH_SIZE = 30;
    const BATCHES = getBatches(CITIES, SOURCE, BATCH_SIZE);
    console.log("BATCHES", BATCHES[0]);

    const BATCH_RESULTS = getResults(BATCHES);
    //console.log(BATCH_RESULTS);

    //const RESULTS = parseResults(BATCH_RESULTS, CITIES);

    //console.log(RESULTS);

});




function getBatches(CITIES, SOURCE, BATCH_SIZE) {
    let batches = [];
    let batch = [SOURCE];

    for (const city in CITIES) {

        // if batch is full, push to BATCHES and start a new batch
        if (batch.length >= BATCH_SIZE) {
            batches.push(batch);
            batch = [SOURCE];
        }

        // push city to batch
        batch.push(CITIES[city]);
    }

    return batches;
}

function getKeyByValue(object, value) {
    return Object.keys(object).find(key => object[key] === value);
}

function parseResults(BATCH_RESULTS, CITIES) {
    const DISTS = {};

    for (let batch in BATCH_RESULTS) {
        let distances = batch.distances[0].splice(0, 1);
        let destinations = batch.destinations.splice(0, 1);
        
        for (let i = 0; i < destinations.length; i++) {
            let city = getKeyByValue(CITIES, destinations[i]);
            let distance = distances[i];

            DISTS[city] = distance;
        }
    }

    return DISTS;
}

function getResults(BATCHES) {
    const results = [];

    //let result = callApi(BATCHES);
    //console.log(result);
    
    for (let batch in BATCHES) {
        let request = new XMLHttpRequest();

        request.open('POST', "https://api.openrouteservice.org/v2/matrix/driving-car");

        request.setRequestHeader('Accept', 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8');
        request.setRequestHeader('Content-Type', 'application/json');
        request.setRequestHeader('Authorization', cityData.apiKey);

        request.onreadystatechange = function() {
            if (this.readyState === 4) {
                results.push(JSON.parse(this.responseText));
            }
        };

        const parsedBatch = batch.map(coord => [parseFloat(coord[0]), parseFloat(coord[1])]);

        let body = JSON.stringify({
            locations: parsedBatch,
            metrics: ["distance"],
            sources: [0],
            units: "mi"
        });
        console.log(body);
        //request.send(body);
        break;
    }
    
    //console.log("results", results);

    //return results;
}

function callApi(batch) {
    const parsedBatch = batch.map(coord => [parseFloat(coord[0]), parseFloat(coord[1])]);
    let request = new XMLHttpRequest();

    let results = 'placeholder';

    request.open('POST', "https://api.openrouteservice.org/v2/matrix/driving-car");

    request.setRequestHeader('Accept', 'application/json, application/geo+json, application/gpx+xml, img/png; charset=utf-8');
    request.setRequestHeader('Content-Type', 'application/json');
    request.setRequestHeader('Authorization', cityData.apiKey);

    request.onreadystatechange = function() {
        if (this.readyState === 4) {
            //console.log('Status:', this.status);
            //console.log('Headers:', this.getAllResponseHeaders());
            //console.log('Body:', this.responseText);
            results = JSON.parse(this.responseText);
            //results.push(JSON.parse(this.responseText));
        }
    };

    let body = JSON.stringify({
        locations: parsedBatch,
        metrics: ["distance"],
        sources: [0],
        units: "mi"
    });
    
    request.send(body);
    console.log("results: ", results);

    //return results;
}

function returnResponse(data) {
    return data;
}
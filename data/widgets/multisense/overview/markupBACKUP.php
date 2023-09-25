<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBG Mulisense</title>
</head>
<?php
    list($pressure, $temp, $tvoc, $co2, $humidity, $datetime) = MULTISENSE::dbConnect();

    $max_humidity = max($humidity);
    $min_humidity = min($humidity);

    $max_temp = max($temp);
    $min_temp = min($temp);


    $max_co2 = max($co2);
    $min_co2 = min($co2);

    $max_tvoc = max($tvoc);
    $min_tvoc = min($tvoc);

    $yesterday = date('Y-m-d',strtotime("-1 days"));
    
  ?>
<body>
<div class="multisense-container">
  <div class="multisense-calender">
  <div class="value-1">
    <p class="multisense-p">Calender:</p>
    <input id="multiCalender" type="date" style="color:black" onchange="updateDate()" value="<?php echo $yesterday; ?>"/>
  </div>
  </div>

  <div class="multisense-building">
  <div class="value-1">
  <p class="multisense-p">Choose building:</p>
  <select name="Building" id="Building"> select building
      <option value="building-1">building-1</option>
      <option value="building-2">building-2</option>
      <option value="building-3">building-3</option>
      <option value="building-4">building-4</option>
  </select>
  </div>
  </div>
  <div class="multisense-button-wrap">
  <button class="multi-download">Download</button>
    <button class="multi-download-2">Download</button>
  </div>
    <div class="multisense-container-elements">
        <div class="multisense-el-1">
            <div class="multisense-cont-info">
                <p class="high-water">Highest Recorded:</p>
                <p class="water-percentage-high" id="H_humidity"></p>
                <p class="low-water">Lowest Recorded:</p>
                <p class="water-percentage-low" id="L_humidity"></p>
                <div class="circle">
                   <div class="wave"></div>
                </div>
                <div class="multisense-border-line"></div>
                <p class="water-text">Humidity</p> 
            </div>     
        </div>
        <div class="multisense-el-2">
        <div class="multisense-cont-info">
                <p class="high-temp" >Highest recorded:</p>
                <p class="temp-percentage-high" id="H_temp">63%</p>
                <p class="low-temp" >Lowest recorded:</p>
                <p class="temp-percentage-low" id="L_temp">15%</p>
                <div class="fire">
                        <div class="fire-left">
                            <div class="main-fire"></div>
                            <div class="particle-fire"></div>
                        </div>
                        <div class="fire-center">
                            <div class="main-fire"></div>
                            <div class="particle-fire"></div>
                        </div>
                        <div class="fire-right">
                            <div class="main-fire"></div>
                            <div class="particle-fire"></div>
                        </div>
                        <div class="fire-bottom">
                            <div class="main-fire"></div>
                        </div>
                        </div>
                        <p class="temp-text">Temp</p> 
                </div>
        </div>
        <div class="multisense-el-3">
        <div class="multisense-cont-info">
                <p class="co2-high">highest recorded:</p>
                <p class="co2-percentage-high" id="H_CO2"></p>
                <p class="low-co2">lowest recorded:</p>
                <p class="co2-percentage-low" id="L_CO2"></p>
                <div class="center">
                     <div id="cloud"></div>
                </div> 
                <p class="co2">CO2</p> 
         </div>
        </div>
        <div class="multisense-el-4">
        <div class="multisense-cont-info">
                <p class="voc-high">highest recorded:</p>
                <p class="voc-percentage-high" id="H_tvoc" ></p>
                <p class="voc-co2">lowest recorded:</p>
                <p class="voc-percentage-low" id="L_tvoc"></p>
                <div id="light">
                    <div id="lineh1"></div>
                    <div id="lineh2"></div>
                    <div id="lineh3"></div>
	            </div>
                <p class="voc">Voc</p> 
         </div>

        </div>

    </div>
    <div class="multisesense-risk-bar">
        <div class="final-result">
            <p id="risky"></p>
        </div>
        <div class="final-result">
            <p id="result"></p>
        </div>
        <div class="temperature hidden">
            <label for="">Dew Point</label>
            <p id="dew-point" class="dew-p"></p>
        </div>
    </div>

    <div class="multisesense-button-container">
     <button class="multisesense-button-1 tablinks" onclick="openTab(event, 'temp-graph')">Temperature</button>
     <button class="multisesense-button-2 tablinks" onclick="openTab(event, 'humidity-graph')">Humidity</button>
     <button class="multisesense-button-3 tablinks" onclick="openTab(event, 'co2-graph')">CO2</button>
    </div>

    <div class="multisense-graph">
    <!-- Create tab content -->
    <div id="temp-graph" class="tabcontent">
      <canvas id="tempChart"></canvas>
    </div>

    <div id="humidity-graph" class="tabcontent">
      <canvas id="humidityChart"></canvas>
    </div>

    <div id="co2-graph" class="tabcontent">
      <canvas id="co2Chart"></canvas>
    </div>
    </div>

    <div class="google-maps-multisense">
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2381.70923296727!2d-2.2132593839784445!3d53.34846098225374!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x487a4d90b8e93199%3A0xbeb5a8c3e0559b06!2sIntelligent%20Building%20Group!5e0!3m2!1sen!2suk!4v1679480850432!5m2!1sen!2suk"  class="maps" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>


</div>
    
</body>
<!-- MAX AND MIN HUMIDITY VALUES -->

<script>
function calculateDewPoint(Temp_avg, Humidity_avg) {
  var temp =  Math.trunc(Temp_avg);
  var rh =  Math.trunc(Humidity_avg);
  var dewPoint = calculateDewPointC(temp, rh);
  var moldRisk = calculateMoldRisk(temp, rh);
  var risky = "";
  risky=moldRisk==0 ? 'No Risk' : 'Risk';
  document.getElementById('dew-point').innerHTML = Math.round(dewPoint) + '°C';
  document.getElementById('risky').innerHTML = 'Mold Risk : ' + risky;
  document.getElementById('result').innerHTML = 'Days to Mold : ' + moldRisk;
}
  
function calculateDewPointC(temp, rh) {
  var b = 17.625;
  var c = 243.05;
  var gamma = Math.log(rh/100) + b*temp/(c+temp);
  var dewPoint = c*gamma/(b-gamma);
  return dewPoint;
}
  
function calculateMoldRisk(temp, rh) {
  if(temp > 45 || temp < 2 || rh < 65) return 0;
  return pitable[8010 + (Math.round(temp) - 2) * 36 + Math.round(rh) - 65];
}

var pitable = new Array(9594);

const xhr = new XMLHttpRequest();
xhr.open('GET', '../../eticom/data/widgets/multisense/overview/ttmtable.txt');
xhr.onreadystatechange = function(){
  if (xhr.readyState === 4 && xhr.status === 200) {
    const pitableValues = xhr.responseText.trim().split(',');
    pitable = pitableValues.map(value => parseInt(value));
  }
}
xhr.send();
</script>

<script>
var yesterday="<?php echo $yesterday; ?>";
document.getElementById("multiCalender").setAttribute('max', yesterday);

</script>




<script>
var max_humidity="<?php echo $max_humidity; ?>";
var min_humidity="<?php echo $min_humidity; ?>";


const H_humidity = document.getElementById('H_humidity');
const L_humidity = document.getElementById('L_humidity');
H_humidity.innerHTML = max_humidity.toString()+"%";
L_humidity.innerHTML = min_humidity.toString()+"%";

</script>

<script>
    function updateDate(){
        var calender = document.getElementById('multiCalender').value;
        if($(calender).val() != 0){
            $.post("../../eticom/lib/class.multisenseUpdate.php", {
                variable:calender
            }, function(data){
                if (data != ""){
                    result = eval(data);
                    handleRequest(result);

                }
            } );
        }
    }

    function handleRequest(data){

        const arrofNum_humidity = [];
        const arrofNum_temp = [];
        const arrofNum_tvoc = [];
        const arrofNum_pressure = [];
        const arrofNum_co2 = [];

        //Convert and find max min for pressure START////////////////////////////////////////////////////////////////////////////////////////////

        data[0].forEach(str => {
            arrofNum_pressure.push(parseFloat(str));
        })
        var Maxpressure = Math.max(...arrofNum_pressure);
        var Minpressure = Math.min(...arrofNum_pressure);
        //Convert and find max min for Pressure END////////////////////////////////////////////////////////////////////////////////////////////
            
        //Convert and find max min for Temp START////////////////////////////////////////////////////////////////////////////////////////////
        data[1].forEach(str => {
            arrofNum_temp.push(parseFloat(str));
        })

        var MaxTemp = Math.max(...arrofNum_temp);
        var MinTemp = Math.min(...arrofNum_temp);
        updateHighestTemperature(MaxTemp);
        updateLowestTemperature(MinTemp);
        //Convert and find max min for Temp END////////////////////////////////////////////////////////////////////////////////////////////
        //Convert and find max min for TVOC START////////////////////////////////////////////////////////////////////////////////////////////
        data[2].forEach(str => {
            arrofNum_tvoc.push(parseFloat(str));
        })
        var MaxTvoc = Math.max(...arrofNum_tvoc);
        var MinTvoc = Math.min(...arrofNum_tvoc);
        mintvoc_ppm = MinTvoc/1000;
        const mintvoc = 0.0409*mintvoc_ppm*100;
        maxtvoc_ppm = MaxTvoc/1000;
        const maxtvoc = 0.0409*maxtvoc_ppm*100;
        displayTVOC(maxtvoc);
        displaylowTVOC(mintvoc);
        //Convert and find max min for TVOC END////////////////////////////////////////////////////////////////////////////////////////////
        //Convert and find max min for CO2 START////////////////////////////////////////////////////////////////////////////////////////////
        data[3].forEach(str => {
            arrofNum_co2.push(parseFloat(str));
        })
        var MaxCO2 = Math.max(...arrofNum_co2);
        var MinCO2 = Math.min(...arrofNum_co2);
        displaylowco2(MinCO2);
        displayco2(MaxCO2);
        
        //Convert and find max min for TVOC END////////////////////////////////////////////////////////////////////////////////////////////
        //Convert and find max min for humidity START////////////////////////////////////////////////////////////////////////////////////////////
        data[4].forEach(str => {
            arrofNum_humidity.push(parseFloat(str));
        })
        var Maxhumidity = Math.max(...arrofNum_humidity);
        var Minhumidity = Math.min(...arrofNum_humidity);
        const H_humidity = document.getElementById('H_humidity');
        const L_humidity = document.getElementById('L_humidity');
        H_humidity.innerHTML = Maxhumidity.toString()+"%";
        L_humidity.innerHTML = Minhumidity.toString()+"%";
        //Convert and find max min for humidity END////////////////////////////////////////////////////////////////////////////////////////////////
        //Update chart data
        ChartUpdate(arrofNum_temp, arrofNum_humidity, arrofNum_co2, true);

    }


</script>
<script>
    function updateHighestTemperature(temperature) {
        const mercury = document.getElementById('H_temp');
        mercury.innerHTML = temperature.toString()+'&#x2103';
    }


    function updateLowestTemperature(temperature) {
        const mercury = document.getElementById('L_temp');
        mercury.innerHTML = temperature.toString()+'&#x2103';
        
    }

</script>
<!-- MAX AND MIN TEMP VALUES -->
<script>
    var max_temp="<?php echo $max_temp; ?>";
    var min_temp="<?php echo $min_temp; ?>";
    
    updateHighestTemperature(Number(max_temp));
    updateLowestTemperature(Number(min_temp)); // This sets the thermometer to 60% and sets the mercury color to orange.
</script>

<script>
    function displayco2(co2) {
        
        const H_co2 = document.getElementById('H_CO2');
        H_co2.innerHTML = co2.toString()+" ppm";
        
    }

    var max_co2="<?php echo $max_co2; ?>";
    displayco2(Number(max_co2));


    function displaylowco2(co2) {
        const L_co2 = document.getElementById('L_CO2');
        L_co2.innerHTML = co2.toString()+" ppm";
    }

    var min_co2="<?php echo $min_co2; ?>";
    displaylowco2(Number(min_co2));



</script>


<script>
    function displayTVOC(tvoc) {
        const H_tvoc = document.getElementById('H_tvoc');
        H_tvoc.innerHTML = tvoc.toString()+" mg/&#13221";
    }

    var max_tvoc="<?php echo $max_tvoc; ?>";
    // Sensor returns ppb. ppb -> ppm (ppb/1000)
    tvoc_ppm = Number(max_tvoc)/1000;
    const tvoc = 0.0409*tvoc_ppm*100;
    
    // Display the temperature on the digital display
    // Conversion from ppb to ppm. Then ppm to mg/m3 (TVOC avg molecule weight 100g/molecule)
    //0.0409(constant) x 1(ppm) x 100(molecule weight) = 4.09 mg/m³.
    displayTVOC(tvoc);
</script>
    

<script>
    function displaylowTVOC(tvoc) {
            const L_tvoc = document.getElementById('L_tvoc');
            L_tvoc.innerHTML = tvoc.toString()+" mg/&#13221";
        }

        var min_tvoc="<?php echo $min_tvoc; ?>";
        // Sensor returns ppb. ppb -> ppm (ppb/1000)
        mintvoc_ppm = Number(min_tvoc)/1000;
        const mintvoc = 0.0409*mintvoc_ppm*100;
        

        // Display the temperature on the digital display
        // Conversion from ppb to ppm. Then ppm to mg/m3 (TVOC avg molecule weight 100g/molecule)
        //0.0409(constant) x 1(ppm) x 100(molecule weight) = 4.09 mg/m³.
        displaylowTVOC(mintvoc);

</script>

<script>

        const tempData = <?php echo json_encode($temp) ?>;
        const humidityData = <?php echo json_encode($humidity) ?>;
        const co2Data = <?php echo json_encode($co2) ?>;

        ChartUpdate(tempData, humidityData, co2Data, null);

        function ChartUpdate(temp, humidity, co2, updateData){
            
            var tempData = temp;
            var Tsum = 0;
            for (var number of tempData) {
                Tsum += Number(number);
            }
            Temp_avg = Tsum / tempData.length;
           
            
            var humidityData = humidity;
            var RHsum = 0;
            for (var rhnum of humidityData) {
                RHsum += Number(rhnum);
            }
            Humidity_avg = RHsum / humidityData.length;
            
            calculateDewPoint(Temp_avg, Humidity_avg);

            var co2Data = co2;
            var update_DATA = updateData;

            var x = 15; //minutes interval
            var times = []; // time array
            var tt = 0; // start time
            var ap = ['AM', 'PM']; // AM-PM

            //loop to increment the time and push results in array
            for (var i=0;tt<24*60; i++) {
            var hh = Math.floor(tt/60); // getting hours of day in 0-24 format
            var mm = (tt%60); // getting minutes of the hour in 0-55 format
            times[i] = ("0" + (hh % 12)).slice(-2) + ':' + ("0" + mm).slice(-2) + ap[Math.floor(hh/12)]; // pushing data in array in [00:00 - 12:00 AM/PM format]
            tt = tt + x;
            }

                // Initialize charts
                var tempChart = new Chart(document.getElementById('tempChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: times,
                    // labels: ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'],
                        datasets: [{
                            label: 'Temperature (C)',
                            data: tempData,
                            backgroundColor: function(data) {
                                var value = data.dataset.data[data.dataIndex];
                                if (value < 15) {
                                    return 'lightblue';
                                } else if (value >= 15 && value <= 19) {
                                    return '#e69b00';
                                } else if (value > 19 && value <= 23) {
                                    return '#e47200';
                                } else {
                                    return '#e47200';
                                }
                                },
                            
                        }]
                },
                options: {
                scales: {
                    y: {
                        grid: {
                            display: false,
                        },
                        title: {
                            display: true,
                            text: 'Temperature (C)',
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                        title: {
                            display: true,
                            text: 'Time',
                        },
                    },
                },
                plugins: {
                    legend: {display: false},
                    zoom: {
                        zoom: {
                        wheel: {
                            enabled: true,
                        },
                        pinch: {
                            enabled: true
                        },
                        mode: 'x',
                        }
                    }
                }
            },
            
            });
    

            var humidityChart = new Chart(document.getElementById('humidityChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: times,
                    //labels: ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'],
                            datasets: [{
                                label: 'Humidity',
                                data: humidityData,
                                backgroundColor: 'rgba(54, 162, 235, 1)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1
                                
                    }]
                },
                options: {
                    scales: {
                        y: {
                            grid: {
                                display: false,
                            },
                            title: {
                                display: true,
                                text: 'Humidity %RH',
                            },
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            title: {
                                display: true,
                                text: 'Time',
                            },
                        },
                    },
                    plugins: {
                        legend: {display: false},
                        zoom: {
                            zoom: {
                            wheel: {
                                enabled: true,
                            },
                            pinch: {
                                enabled: true
                            },
                            mode: 'x',
                            }
                        }
                    }
                }
            });

            var co2Chart = new Chart(document.getElementById('co2Chart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: times,
                    //labels: ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'],
                            datasets: [{
                                label: 'CO2',
                                data: co2Data,
                                backgroundColor: 'grey',
                                borderColor: 'grey',
                                borderWidth: 1
                                
                    }]
                },
                options: {
                    scales: {
                        y: {
                            grid: {
                                display: false,
                            },
                            title: {
                                display: true,
                                text: 'CO2 %',
                            },
                        },
                        x: {
                            grid: {
                                display: false,
                            },
                            title: {
                                display: true,
                                text: 'Time',
                            },
                        },
                    },
                    plugins: {
                        legend: {display: false},
                        zoom: {
                            zoom: {
                            wheel: {
                                enabled: true,
                            },
                            pinch: {
                                enabled: true
                            },
                            mode: 'x',
                            }
                        }
                    }
                }
            });

            // Set default tab to display
            document.getElementsByClassName("tablinks")[0].click();
            //tempChart.data.datasets[0].data[0] = 12;

            $("#multiCalender").on("change", function() {

                var calender = document.getElementById('multiCalender').value;
                

                if($(calender).val() != 0){
                    $.post("../../eticom/lib/class.multisenseUpdate.php", {
                        variable:calender
                    }, function(data){
                        if (data != ""){
                            result = eval(data);
                            const arrofNum_temp = [];
                            const arrofNum_CO2 = [];
                            const arrofNum_humidity = [];

                            result[1].forEach(str => {
                                arrofNum_temp.push(parseFloat(str));
                            })

                            result[3].forEach(str => {
                                arrofNum_CO2.push(parseFloat(str));
                            })

                            result[4].forEach(str => {
                                arrofNum_humidity.push(parseFloat(str));
                            })
                            
                            tempChart.data.datasets[0].data = arrofNum_temp;
                            co2Chart.data.datasets[0].data = arrofNum_CO2;
                            humidityChart.data.datasets[0].data = arrofNum_humidity;

                            tempChart.update();
                            co2Chart.update();
                            humidityChart.update();

                        }
                    } );
                }
            });

        }
    
    
    // Function to open a tab
    function openTab(evt, tabName) {
        // Declare variables
        var i, tabcontent, tablinks;

        // Get all elements with class="tabcontent" and hide them
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }

        // Get all elements with class="tablinks" and remove the class "active"
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }

        // Show the current tab, and add an "active" class to the button that opened the tab
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className += " active";
    }

        

        
</script>

</html>
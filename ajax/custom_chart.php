<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://kit.fontawesome.com/f2a2eacd59.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" type="text/css" media="screen" href="<?php ASSETS_URL ?>/assets/css/newstyle.css">
  <link rel="stylesheet" type="text/css" media="screen" href="<?php ASSETS_URL ?>/assets/css/mobilemedia.css">
  <title>Custom Data Report</title>
</head>
<body>

<?php 
$nameErr="";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (empty($_POST["mm_datefrom"])) {
    $nameErr = "Error! Please return to previous page and enter both dates into the field!";
    print_r($nameErr);exit;
  } else if (empty($_POST["mm_dateto"])) {
    $nameErr = "Error! Please return to previous page and enter both dates into the field!";
    print_r($nameErr);exit;
  }
}
//  Build the Sql query with values from the FORM

  $Date_from = $_POST['mm_datefrom'];
  $Date_to = $_POST['mm_dateto'];
  $Selected_Meters = $_POST['meter_list'];
  //$Selected_Meters = [117,118,119,120,121,122];
//  print_r($Date_from);
//  print_r($Date_to);exit;
//print_r($Selected_Meters[0]);exit;
  $con = new mysqli("109.74.202.153", "root", "k12ght6", "eticom");
  $i = 0;
  foreach($Selected_Meters as $meters){
    $query = $con->query("
    SELECT meter_id, reading_day, total_imported_total, total_cost_total FROM automated_meter_reading_history 
    WHERE (reading_day BETWEEN '".$Date_from."' AND '".$Date_to."') AND meter_id IN ($meters);
    ");

    foreach($query as $data)
    {
      $reading_day[$i][] = $data['reading_day'];
      $imported_total[$i][] = $data['total_imported_total'];
      
    }
    $i = $i + 1;
  }

  $x = 0;

  foreach($Selected_Meters as $meters){
    $query_meter_desc = $con->query("
    SELECT id, description FROM meter WHERE id IN ($meters);
    ");
   
    foreach($query_meter_desc as $name)
    {
      $meter_name[$x][] = $name['description'];
      $_name_post[] = $name['description'];

    }
    $x = $x + 1;
  }
//print_r($_name_post);exit;
?>
<div class="return_el">
  <a href ="<?php APP_URL ?>/eticom/dashboard#view/dashboard/meters" class="icon-block">
  <i class="fa-solid fa-1x fa-arrow-rotate-left"></i>
  <span>Return</span>
  </a>
  <p class="Tool-Title">Analysis Tool</p>
</div>
<div class="grid_chart">
  <div class="grid_chart_el_1" >
     <canvas id="myChart" class="chartbox"></canvas>
  </div>
  <div class="grid_chart_el_2">
     <p id="Date">Date: </p>
     <p id="Value">Top Consumer: </p>
  </div>
  <form action="newtest.php" method="post" class="grid_chart_el_3">
  <input type="submit" value="Export Data to CSV" class="csv_button">
  <input type="hidden" name="mm_datefrom" value=<?php echo $Date_from?>><br>
  <input type="hidden" name="mm_dateto" value=<?php echo $Date_to?>><br>
   <?php 
    foreach($Selected_Meters as $value)
      {
          echo '<input type="hidden" name="meter_list[]" value="'. $value. '">';
      }

    foreach($_name_post as $meter_desc)
      {
          echo '<input type="hidden" name="meter_description[]" value="'. $meter_desc. '">';
      }
  ?>
  
   </form class='grid_chart_el_4'>
    <button id="graph" onclick="Export_graph()">Download Graph as</button>
</div>

<script>
  // === include 'setup' then 'config' above ===
  const meters = <?php echo json_encode($meter_name) ?>;
  const Requested_Meters = <?php echo json_encode($imported_total)?>;
  const Requsted_Dates = <?php echo json_encode($reading_day)?>;
  

  LineColor = ['#465262',
        '#0097ce',
        '#2ea8a1',
        '#6cbf65',
        '#bede18',
        '#9399a3',
        '#696969',
        '#FF0000',
        '#800000',
        '#FF7F50',
        '#00FFFF',
        '#00CED1',
        '#DAA520',
        '#FFFF00',
        '#F08080',
        '#7CFC00',
        '#00008B',
        '#FF1493',
        '#800080',
        '#FFE4B5',
        '#B0C4DE',]

  var lineChartData = { labels: Requsted_Dates[0], datasets: [] },
  array = Requested_Meters;


  array.forEach(function (a, i){
    lineChartData.datasets.push({
      label: meters[i],
      backgroundColor:[LineColor[i]],
      borderColor: [LineColor[i]],
      fillColor: 'rgba(220,220,220,0.2)',
      strokeColor: 'rgba(220,220,220,1)',
      pointColor: 'rgba(220,220,220,1)',
      pointStrokeColor: '#fff',
      pointHighlightFill: '#fff',
      pointHighlightStroke:
      'rgba(220,220,220,1)',
      data: array[i]
    });
  });

// Note: changes to the plugin code is not reflected to the chart, because the plugin is loaded at chart construction time and editor changes only trigger an chart.update().
const plugin = {
  id: 'customCanvasBackgroundColor',
  beforeDraw: (chart, args, options) => {
    const {ctx} = chart;
    ctx.save();
    ctx.globalCompositeOperation = 'destination-over';
    ctx.fillStyle = options.color || '#99ffff';
    ctx.fillRect(0, 0, chart.width, chart.height);
    ctx.restore();
  }
};







  console.log(lineChartData);
  const data = lineChartData;
  var yAxesticks = [];
  var highestVal;
  const config = {
    type: 'line',
    data: data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          callback : function(value,index,values){
                                    yAxesticks = values;
                                    return value;}
        },
        x:{
            ticks:{
                maxTicksLimit: Requsted_Dates[0].length
            }
        },
        x2:{
          display: false,
          labels: Requsted_Dates[1]
        }
      },
      plugins:{
        customCanvasBackgroundColor: {
        color: 'Transparent',
      },
        zoom:{
            pan:{
                enabled:true
            }
        }
      }
    },
    plugins: [plugin],

  };
  highestVal = yAxesticks[0];
  //console.log(highestVal);
  var myChart = new Chart(
    document.getElementById('myChart'),
    config
  );
  //console.log(data);
  TopConsumer();

  function TopConsumer(){
    const DataSet = myChart.data.datasets[0].data;
    DataSetLength = (myChart.data.datasets).length;
    
    for (let i = 0; i < DataSetLength; i++) {
       let textlol = "The number is " + i;
        console.log(textlol)
      }

    
    //console.log(DataSetLength)
    
    const maxValue = Math.max(...DataSet).toFixed(4);
    let str_maxValue = maxValue.toString();
    console.log(maxValue);
    
    
    const index = myChart.data.datasets[0].data.indexOf(str_maxValue)
    console.log(index);
    const Date = document.getElementById('Date').innerText = 'Date (Y/M/D): '+myChart.data.labels[index];
    const Value = document.getElementById('Value').innerText= 'Top Consumer: '+maxValue+' kWh';

  }


  function Export_graph(){
    const imageLink = document.createElement('a');
    const canvas = document.getElementById('myChart');
    imageLink.download = 'Meter Graph';

    imageLink.href = canvas.toDataURL('image/jpg', 1);
    //document.write('<img src=" '+imgref+' "/>')
   
    imageLink.click()

  }
</script>

</body>
</html>


<style>
  
  .Tool-Title{
    color: white;
    font-family: 'Poppins', sans-serif;
    margin-left:50%;
    margin-top: 1%;
}

@media only screen and (min-width: 320px) and (max-width: 479px) {


  .return_el {
    margin-left: -8px;
  }

 a.icon-block {
    margin-top: 14px;
    margin-left: 18px;
 }
 #Date {
  margin-top:54px
 }

#graph {
    height: 48px;
    font-size: 1em;
    grid-column: 1/5;
    grid-row: 3/4;
    margin-top: 35px;
    margin-left: 5px;
    background: #2C3742;
    color: white;
    width: 97%;
}

.grid_chart > .grid_chart_el_1 {
    grid-column: 1/5;
    grid-row: 2/3;
    display: block;
    box-sizing: border-box;
    background: white;
    border-radius: 12px;
    margin-top: 1px;
  }
  .grid_chart_el_2 {
    width: 100%;
    height: 21vh;
    background: white;
    box-shadow: lightgrey 1%;
    grid-column: 1/5;
    margin-top: 55px;
    grid-row: 1/2;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgb(0 0 0 / 20%);
}
.grid_chart_el_3 {
    grid-column: 1/5;
    margin-top: 89px;
    margin-left: 11px;
    grid-row: 3/4;
}

.Tool-Title{
    color: white;
    font-family: 'Poppins', sans-serif;
    margin-left:40%;
    margin-top: 4%;
}


.csv_button{
  width: 100%;
  margin-left: -6px;
  height: 48px;
  font-size:1em;
  font-weight: 100;
}
}


</style>
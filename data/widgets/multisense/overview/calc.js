function calculateDewPoint() {
  var temp = parseFloat(document.getElementById('temp').value);
  var rh = parseFloat(document.getElementById('rh').value);
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